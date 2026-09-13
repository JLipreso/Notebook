<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * PSGC address reference data (D-030: latest PSA snapshot)
 * — 2026-09-13-005 Phase 002.
 *
 * ============================== SOURCE FILE ==================================
 * Reads ONE CSV from database/seeders/data/psgc-<YYYYQN>.csv (newest wins).
 * Currently committed: psgc-2026Q2.csv, from the PSA's 2Q 2026 publication.
 *
 * To refresh on a new PSA release:
 *   node scripts/psgc-xlsx-to-csv.mjs <PSGC-<N>Q-<YYYY>-Publication-Datafile.xlsx>
 * then delete the previous quarter's CSV. See that folder's README.
 *
 * Columns are matched by the PSA's own header names (normalised, since case and
 * spacing drift between quarters): a 10-digit code, a name, a geographic level.
 *
 * Parentage comes from the CODE STRUCTURE (RR PP MM BBB), not row order, so a
 * re-export in a different sort order still seeds correctly. That same structure
 * is the fallback when the PSA leaves Geographic Level blank — which it does on
 * ~50 rows per quarter, including, in 2Q-2026, Negros Island Region itself.
 * =============================================================================
 *
 * Idempotent: upserts on the natural `code` PK. Inserts are chunked because the
 * barangay level alone is ~42k rows.
 */
class PsgcSeeder extends Seeder
{
    private const CHUNK = 1000;

    public function run(): void
    {
        $path = $this->locateCsv();

        if ($path === null) {
            $this->command?->warn('PSGC: no database/seeders/data/psgc-*.csv found — SKIPPING.');
            $this->command?->warn('       Download the PSA quarterly XLSX, save as CSV there, re-run. (Phase-002 §3)');

            return;
        }

        $rows = $this->readCsv($path);

        $buckets = ['region' => [], 'province' => [], 'city' => [], 'barangay' => []];

        // Province codes are collected in a first pass: a city's province_code
        // can only be resolved once every province in the file is known, and
        // nothing is in the DB yet at this point.
        $provinceCodes = [];

        foreach ($rows as [$code, $name, $level]) {
            if ($this->classify($code, $level) === 'province') {
                $provinceCodes[$code] = true;
            }
        }

        foreach ($rows as [$code, $name, $level]) {
            $bucket = $this->classify($code, $level);

            if ($bucket === null) {
                continue;
            }

            // Codes are CHAR(10) STRINGS, never numbers: a bare integer bound
            // into a CHAR column makes sqlite compare int-to-text, the FK finds
            // no parent row, and the insert dies with "FOREIGN KEY constraint
            // failed" even though the parent is right there.
            $buckets[$bucket][] = match ($bucket) {
                'region' => ['code' => $code, 'name' => $name],
                'province' => ['code' => $code, 'region_code' => $this->regionPrefix($code), 'name' => $name],
                'city' => [
                    'code' => $code,
                    // NULL for NCR cities, which have no province level.
                    'province_code' => $this->provincePrefix($code, $provinceCodes),
                    'region_code' => $this->regionPrefix($code),
                    'name' => $name,
                    'class' => $this->cityClass($level),
                ],
                'barangay' => ['code' => $code, 'city_muni_code' => $this->cityPrefix($code), 'name' => $name],
            };
        }

        // FK order: regions -> provinces -> cities -> barangays.
        $this->upsert('regions', $buckets['region'], ['name']);
        $this->upsert('provinces', $buckets['province'], ['region_code', 'name']);
        $this->upsert('cities_municipalities', $buckets['city'], ['province_code', 'region_code', 'name', 'class']);

        // Drop barangays whose parent city is missing (defensive: a truncated
        // export would otherwise fail the FK mid-chunk).
        $cityCodes = array_flip(array_column($buckets['city'], 'code'));
        $barangays = array_values(array_filter(
            $buckets['barangay'],
            fn (array $b) => isset($cityCodes[$b['city_muni_code']]),
        ));

        if ($orphans = count($buckets['barangay']) - count($barangays)) {
            $this->command?->warn("PSGC: skipped {$orphans} barangay row(s) with no matching city/municipality.");
        }

        $this->upsert('barangays', $barangays, ['city_muni_code', 'name']);

        $this->command?->info(sprintf(
            'PSGC seeded from %s — %d regions, %d provinces, %d cities/municipalities, %d barangays.',
            basename($path),
            count($buckets['region']),
            count($buckets['province']),
            count($buckets['city']),
            count($barangays),
        ));
    }

    private function locateCsv(): ?string
    {
        $matches = glob(database_path('seeders/data/psgc-*.csv')) ?: [];

        // Newest quarter wins if several snapshots are present.
        rsort($matches);

        return $matches[0] ?? null;
    }

    /**
     * @return list<array{0:string,1:string,2:string}>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("PSGC: cannot open {$path}");
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            throw new RuntimeException("PSGC: {$path} is empty");
        }

        // Strip a UTF-8 BOM off the first header cell if Excel added one.
        $header[0] = preg_replace('/^\x{FEFF}/u', '', (string) $header[0]);

        $codeIdx = $this->findColumn($header, ['psgc', 'code']);
        $nameIdx = $this->findColumn($header, ['name']);
        $levelIdx = $this->findColumn($header, ['level', 'geographic']);

        if ($codeIdx === null || $nameIdx === null || $levelIdx === null) {
            fclose($handle);
            throw new RuntimeException(
                'PSGC: could not find code/name/level columns in '.basename($path).
                ' — headers seen: '.implode(' | ', $header)
            );
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $code = preg_replace('/\D/', '', (string) ($row[$codeIdx] ?? ''));
            $name = trim((string) ($row[$nameIdx] ?? ''));
            $level = trim((string) ($row[$levelIdx] ?? ''));

            if ($code === '' || $name === '') {
                continue;
            }

            // PSA sometimes drops the leading zero when the sheet is saved as
            // CSV (the cell is numeric) — restore it to the official 10 digits.
            $code = str_pad($code, 10, '0', STR_PAD_LEFT);

            $rows[] = [$code, $name, $level];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $needles
     */
    private function findColumn(array $header, array $needles): ?int
    {
        foreach ($header as $i => $label) {
            $normalised = strtolower(preg_replace('/[^a-z]/i', '', (string) $label));

            foreach ($needles as $needle) {
                if (str_contains($normalised, $needle)) {
                    return $i;
                }
            }
        }

        return null;
    }

    private function classify(string $code, string $level): ?string
    {
        $bucket = match (strtolower(preg_replace('/[^a-z]/i', '', $level))) {
            'reg', 'region' => 'region',
            'prov', 'province' => 'province',
            'city', 'mun', 'municipality', 'submun', 'submunicipality' => 'city',
            'bgy', 'brgy', 'barangay' => 'barangay',
            default => null,
        };

        // The PSA leaves Geographic Level BLANK on a handful of rows every
        // quarter — in 2Q-2026 that included Negros Island Region itself, whose
        // provinces DO carry a level, so skipping the blank row orphaned three
        // provinces and failed the FK. Fall back to the documented code
        // structure (RR PP MM BBB): the first zero-run tells you the tier.
        return $bucket ?? $this->classifyByCode($code);
    }

    /** Infer the tier from the PSGC code structure when the level cell is blank. */
    private function classifyByCode(string $code): ?string
    {
        if (strlen($code) !== 10) {
            return null;
        }

        if (substr($code, 2) === '00000000') {
            return 'region';
        }

        if (substr($code, 4) === '000000') {
            return 'province';
        }

        if (substr($code, 7) === '000') {
            return 'city';
        }

        return 'barangay';
    }

    private function cityClass(string $level): string
    {
        return match (strtolower(preg_replace('/[^a-z]/i', '', $level))) {
            'city' => 'city',
            'submun', 'submunicipality' => 'sub_municipality',
            default => 'municipality',
        };
    }

    /** Codes nest as RRPPMMBBB — the region is the first two digits. */
    private function regionPrefix(string $code): string
    {
        return substr($code, 0, 2).'00000000';
    }

    /**
     * NULL for NCR cities, which have no province level.
     *
     * @param  array<string, true>  $provinceCodes  every province code in the file
     */
    private function provincePrefix(string $code, array $provinceCodes): ?string
    {
        $province = substr($code, 0, 4).'000000';

        return isset($provinceCodes[$province]) ? $province : null;
    }

    private function cityPrefix(string $code): string
    {
        return substr($code, 0, 7).'000';
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $update
     */
    private function upsert(string $table, array $rows, array $update): void
    {
        $now = now();

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            $chunk = array_map(
                fn (array $row) => [...$row, 'created_at' => $now, 'updated_at' => $now],
                $chunk,
            );

            DB::table($table)->upsert($chunk, ['code'], $update);
        }
    }
}
