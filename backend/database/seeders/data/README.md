# PSGC seed data

`PsgcSeeder` reads ONE CSV from this directory, named `psgc-<YYYYQN>.csv`
(e.g. `psgc-2026Q2.csv`). The newest quarter wins if several are present.

**The file is not committed yet** — it must be produced once, by hand:

1. Download the latest quarterly PSGC publication (XLSX) from
   [psa.gov.ph](https://psa.gov.ph) → Philippine Standard Geographic Code.
   D-030 locks this to the *latest snapshot* at implementation time.
2. Save the main sheet as CSV into this folder with the quarter in the name.
3. Commit it. This IS committed data — public government reference material,
   not a credential (Phase-002 §3).

The seeder finds its columns by matching the PSA's own headers (code / name /
geographic level), pads codes back to 10 digits if Excel dropped a leading zero,
and derives parentage from the code structure rather than row order — so a
re-export in a different order still seeds correctly.

Until the file exists, `db:seed` prints a SKIP warning and seeds no address
rows. Everything else (notebook types) seeds normally.
