# Q — How should the address chain handle cities with no province?

_Raised 2026-09-13 during Phase 005, by the implementer. Answered same day by the Lead Developer._

## The problem

[Phase-005](../Phase-005.md) specifies an **"NCR path"**: one endpoint, `GET /api/address/regions/{code}/cities`, described as "NCR path — cities with `province_code IS NULL`". The implication is that NCR is the only region where the province level is skipped.

The real PSGC data disagrees. After seeding the PSA 2Q-2026 publication:

| | count |
|---|---|
| Cities with **no province** | **48** |
| …of which in NCR | 31 |
| …**Highly Urbanized Cities elsewhere** | **17** |

The 17 are City of Cebu, Davao, Baguio, Iloilo, Bacolod, Angeles, Olongapo, Lucena, Lapu-Lapu, Mandaue, Tacloban, Zamboanga, Cagayan de Oro, Iligan, General Santos, Butuan, Puerto Princesa — spread across **14 regions**. They are independent of any province by law, and the PSA encodes that with a `3xx` province segment.

Treating this as an NCR special case would make all 17 **unselectable**: a user in Cebu City could not enter their address at all.

## Options put to the Lead Developer

**A — Province optional everywhere.** After picking a region, show both its provinces and its independent cities. Picking a province filters the city list; picking an independent city skips the province. One code path, no NCR branch.

**B — NCR-only, as written.** Follow the phase file literally; the 17 HUCs stay unreachable until a later fix.

**C — Pause and decide later.** Build the rest of Phase 005, defer the address chain.

## **Decision: A — province optional everywhere.**

_Lead Developer, 2026-09-13._

Implemented as described. `AddressSelect.vue` asks every region for both its provinces and its independent cities; the Province select is disabled (with an explanatory placeholder) when a region has none.

This is a **behaviour change from the phase file**, not a deviation to be undone — Phase 005's "NCR path" wording is narrower than the data it has to serve. The endpoint name (`/regions/{code}/cities`) is unchanged; only its meaning generalises from "NCR's cities" to "this region's province-less cities".

No D-ID assigned: this is an implementation consequence of D-020's address requirement, not a new product rule. If it ever needs to be re-litigated, this file is the record.
