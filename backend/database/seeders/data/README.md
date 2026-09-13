# PSGC seed data

`PsgcSeeder` reads ONE CSV from this directory, named `psgc-<YYYYQN>.csv`.
The newest quarter wins if several are present.

**Current file:** `psgc-2026Q2.csv` — converted from the PSA's
*PSGC 2Q 2026 Publication Datafile* (D-030: latest snapshot at implementation
time). 43,768 source rows → 18 regions, 80 provinces, 1,660 cities/municipalities,
42,010 barangays.

This file IS committed: public government reference data, not a credential.

## Refreshing on a new PSA release

1. Download the latest quarterly PSGC publication (XLSX) from
   [psa.gov.ph](https://psa.gov.ph) → Philippine Standard Geographic Code.
2. Convert it — no extra tooling needed, the script is dependency-free:

   ```bash
   node scripts/psgc-xlsx-to-csv.mjs ~/Downloads/PSGC-<N>Q-<YYYY>-Publication-Datafile.xlsx
   ```

   It finds the `PSGC` sheet **by name** (the PSA reorders and hides sheets
   between quarters), resolves the shared-string table, and writes
   `psgc-<YYYYQN>.csv` here, taking the quarter from the source filename.
3. Delete the previous quarter's CSV so only one remains.
4. `php artisan migrate:fresh --seed` and check the printed counts.

## Two things the parser handles that the raw file gets wrong

**Blank `Geographic Level` cells.** The PSA leaves that column empty on a
handful of rows every quarter. In 2Q-2026 that included **Negros Island Region
itself** — whose three provinces *do* carry a level, so skipping the blank row
orphaned them and failed the FK. The seeder falls back to inferring the tier
from the documented code structure (`RR PP MM BBB`: the first zero-run gives the
level), so a blank cell at any tier still lands correctly.

**Leading zeros.** Excel stores the code cell as a number, so `0100000000`
becomes `100000000`. The seeder pads back to the official 10 digits.

Parentage is derived from the code structure rather than row order, so a
re-export in a different sort order still seeds correctly. Upserts are on `code`,
so re-running is safe.

If the columns ever stop matching, the seeder throws and **prints the headers it
actually saw** rather than silently seeding nothing.
