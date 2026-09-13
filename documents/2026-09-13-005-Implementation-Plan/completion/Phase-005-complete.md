# Phase 005 complete — Profile & PSGC address

_2026-09-13 · branch `Workstation-Laptop` · task 2026-09-13-005._

The signed-in user edits their profile with the official Region → Province → City/Municipality → Barangay chain, in both apps, mock and live. No geolocation anywhere (D-020).

## What shipped

**Backend**

| Endpoint | Notes |
|---|---|
| `GET /api/address/regions` | all regions, sorted |
| `GET /api/address/regions/{code}/provinces` | provinces of a region |
| `GET /api/address/provinces/{code}/cities` | cities under a province |
| `GET /api/address/regions/{code}/cities` | the region's **province-less** cities |
| `GET /api/address/cities/{code}/barangays` | barangays of a city |
| `GET /api/address/barangays/{code}/chain` | **added** — resolves a saved code back to its full chain |
| `GET /api/profile` · `PUT /api/profile` | auth; email and role not editable |

All address routes are public, `throttle:public`, cached a day, and guard `{code}` as `[0-9]{10}`.

**Frontend**

- `packages/ui/address/AddressSelect.vue` — the shared four-level cascade
- `packages/ui/address/SelectField.vue` — labelled select matching `FormField`
- `packages/ui/address/useProfile.ts` — load/save/format, shared by both apps
- Profile views per app: browser settings page, mobile portrait stack screen

## ⚠ A design change the phase file did not anticipate

**The "NCR path" is really an independent-city path, and it affects 48 cities, not 31.**

Phase 005 describes `GET /api/address/regions/{code}/cities` as the "NCR path". The real PSGC data has **48 province-less cities**: NCR's 31, plus **17 Highly Urbanized Cities** — Cebu, Davao, Baguio, Iloilo, Bacolod, Lapu-Lapu, Mandaue, Zamboanga, Cagayan de Oro and eight more — spread across 14 regions and independent of any province by law.

Building the NCR special case as written would have made all 17 **unselectable**: a user in Cebu City could not have entered their address.

Raised with the Lead Developer, who chose **province optional everywhere** (full options and rationale: [questions/001-independent-cities.md](../questions/001-independent-cities.md)). Every region is asked for both its provinces and its independent cities; the Province select disables itself with an explanatory placeholder when a region has none. One code path, no NCR branch.

The endpoint name is unchanged — only its meaning generalises.

## 🔴 A Phase-002 bug this surfaced, now fixed

Investigating the above exposed a **real bug in `PsgcSeeder`**: `provincePrefix()` sliced **four** digits, but PSGC province codes use **five**. The structure is `RR PPP MM BBB`, not `RR PP MM BBB` — my Phase-002 comment had it wrong.

Effect: **~1,350 of 1,660 cities were silently given `province_code = NULL`.** Every region looked like NCR. The seeder reported plausible totals and no error, so nothing failed — it would have shipped as "the dropdown is broken outside Metro Manila".

Proof, counted against the source file: of 1,648 cities, **1,562 match a 5-digit province prefix vs 178 on 4**.

Fixed in `provincePrefix()` and `classifyByCode()` (which shared the wrong width). After reseeding:

| | before | after |
|---|---|---|
| provinces | 80 | **84** |
| cities with a province | ~307 | **1,608 / 1,656** |
| province-less cities | ~1,353 | **48** (31 NCR + 17 HUC) |

The four "new" provinces were previously misclassified as cities by the same bad width.

**Anyone who seeded before this commit must re-run `php artisan migrate:fresh --seed`.**

## Verified

- **42 backend tests pass** (131 assertions); 13 are new: region/province/city/barangay filtering, the independent-city branch in a non-NCR region, chain resolution both ways, unknown barangay → 404 envelope, non-numeric codes not matching the route, profile auth, update + rehydrate, bad `barangay_code` → 422, **email and role unchanged when posted**, address clearable.
- **Live against real data:** Region VII returns provinces (Bohol, Cebu) *and* independent cities (Cebu, Lapu-Lapu, Mandaue); a Cebu City barangay resolves to `Adlaon, City of Cebu, Region VII` with no province.
- **Mock mode with the backend stopped:** all three shapes work — province path, independent city outside NCR, NCR. The fixture gained Region VII with City of Cebu precisely so mock exercises the same branch as live.
- Root `pnpm typecheck` (7 projects) + `pnpm build:all` green; `refresh-docs` run (13 routes).

## Notes

- **Sign-up still does not collect an address** — the phase file's default, and it keeps registration short. The profile screen is the only place address is entered.
- `AddressSelect` loads in `onMounted`, not with a top-level `await`: a top-level await would make it an async component and force every consumer into `<Suspense>`.
- `useProfile`'s form type is `Required<Omit<UpdateProfilePayload, 'avatar_path'>>` — the payload is `Partial`, which makes `barangay_code` `string | null | undefined`, and no `v-model` target accepts that.
- Avatar upload is deliberately not built: `avatar_path` is a column, but uploads are Phase 008's machinery. Wire it there.

## Notes for Phase 006

The library is the next screen, and the first to use `mintId()` from `@notebook/sync` for client-minted UUIDs. `notebookService` already has the full surface (`list`/`create`/`update`/`archive`/`remove`) with working mock paths — Phase 006 builds `NotebookCard`/`NotebookGrid`/`NewNotebookDialog` in `packages/ui/notebook/` and the backend `NotebookController` behind them.
