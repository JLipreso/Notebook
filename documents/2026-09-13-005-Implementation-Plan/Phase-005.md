# Phase 005 — Profile & PSGC address

**Goal:** the signed-in user completes/edits their profile with the official Region → Province → City/Municipality → Barangay dropdown chain. No geolocation anywhere (D-020).

**Prereq:** Phase-004. **Decisions:** D-014, D-020, D-030.

## 1. Backend

### `AddressController` — public reference data, cached hard
Banner `// ==== ADDRESS (2026-09-13-005 Phase 005) ====`; all routes public with `throttle:public`:

| Endpoint | Returns |
|---|---|
| `GET /api/address/regions` | all regions (`code`, `name`), sorted |
| `GET /api/address/regions/{code}/provinces` | provinces of a region |
| `GET /api/address/provinces/{code}/cities` | cities/municipalities of a province |
| `GET /api/address/regions/{code}/cities` | NCR path — cities with `province_code IS NULL` |
| `GET /api/address/cities/{code}/barangays` | barangays of a city/municipality |

`{code}` guarded `->where('code', '[0-9]{10}')` (natural keys, NOT whereUuid). Wrap each in `Cache::remember` (a day — PSGC changes quarterly). Select only `code, name(, class)`.

### `ProfileController`
| Endpoint | Behavior |
|---|---|
| `GET /api/profile` (auth) | own `User` row, contract shape |
| `PUT /api/profile` (auth) | update `first_name, last_name, middle_name, birthday, mobile_number, barangay_code, address_line, avatar_path`; validate `barangay_code` `exists:barangays,code`. Email/role NOT editable here |

## 2. Frontend

- **`packages/ui/address/AddressSelect.vue`** — the four-level cascading select, one shared component: loads each level from `address.service` on parent change, resets children on change, handles the NCR no-province branch, disabled-until-parent-picked, loading states. Props: `modelValue` (barangay_code), emits full chain for display.
- Profile screen per app (browser: settings page; mobile: profile tab/stack screen), composing shared field components + `AddressSelect`. Per-view state in composables (`useProfile()` in each app or shared in `packages/ui` if identical) — NOT in Pinia (auth-only rule).
- Sign-up flow (Phase-004) gains the address step if the Lead Developer wants address-at-signup — default: address editable post-signup in profile only (brief requires address fields, not their moment; keeping sign-up short is the default).
- Mock fixtures already carry the PSGC sample — dropdowns must work in mock mode.

## 3. Verification

- Feature tests: dropdown chain endpoints (counts, FK filtering, NCR branch), profile update validation (bad barangay_code rejected).
- Manual walk: pick Region NCR → skip province → city → barangay; save; reload; persisted chain re-displays.

## Acceptance checklist

- [ ] Full dropdown chain works in both apps, mock AND live, including the NCR path
- [ ] Profile edit persists and re-hydrates; email/role untouchable
- [ ] Address endpoints cached + rate-limited; tests green
- [ ] typecheck + build:all green · refresh-docs ran (routes changed) · PR (`M1 Phase 005 — profile & address`) · completion note
