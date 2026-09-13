// PSGC address reference data — mirrors 0001_01_01_000004_create_psgc_tables
// (database-schema.md §4, tables 3-6).
//
// These are the ONE exception to the UUID rule: the official PSGC 10-digit code
// is the primary key, so every id here is `code`, not `id`.

export interface Region {
  code: string
  name: string
}

export interface Province {
  code: string
  region_code: string
  name: string
}

export type CityMunicipalityClass = 'city' | 'municipality' | 'sub_municipality'

export interface CityMunicipality {
  code: string
  /** NULL for NCR cities, which sit directly under the region. */
  province_code: string | null
  region_code: string
  name: string
  class: CityMunicipalityClass | null
}

export interface Barangay {
  code: string
  city_muni_code: string
  name: string
}

/** The resolved four-level chain, for displaying a saved address. */
export interface AddressChain {
  region: Region | null
  province: Province | null
  city_municipality: CityMunicipality | null
  barangay: Barangay | null
}
