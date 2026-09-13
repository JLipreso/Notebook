import type { ApiResponse, Barangay, CityMunicipality, Province, Region } from '@notebook/types'

import { datasource } from './datasource'
import { http } from './http'
import { mockBarangays, mockCitiesMunicipalities, mockOk, mockProvinces, mockRegions } from './mock'

// Public PSGC reference data (Phase 005). Cached hard server-side — these
// change quarterly at most.

export function regions(): Promise<ApiResponse<Region[]>> {
  return datasource(
    () => mockOk(mockRegions),
    async () => {
      const { data } = await http.get<ApiResponse<Region[]>>('/address/regions')
      return data
    },
  )
}

export function provinces(regionCode: string): Promise<ApiResponse<Province[]>> {
  return datasource(
    () => mockOk(mockProvinces.filter((p) => p.region_code === regionCode)),
    async () => {
      const { data } = await http.get<ApiResponse<Province[]>>(`/address/regions/${regionCode}/provinces`)
      return data
    },
  )
}

/** Cities of a province. */
export function citiesMunicipalities(provinceCode: string): Promise<ApiResponse<CityMunicipality[]>> {
  return datasource(
    () => mockOk(mockCitiesMunicipalities.filter((c) => c.province_code === provinceCode)),
    async () => {
      const { data } = await http.get<ApiResponse<CityMunicipality[]>>(`/address/provinces/${provinceCode}/cities`)
      return data
    },
  )
}

/**
 * The NCR path: cities that hang directly off a region with no province.
 * AddressSelect calls this instead of citiesMunicipalities() when the chosen
 * region has no provinces.
 */
export function citiesMunicipalitiesByRegion(regionCode: string): Promise<ApiResponse<CityMunicipality[]>> {
  return datasource(
    () => mockOk(mockCitiesMunicipalities.filter((c) => c.region_code === regionCode && c.province_code === null)),
    async () => {
      const { data } = await http.get<ApiResponse<CityMunicipality[]>>(`/address/regions/${regionCode}/cities`)
      return data
    },
  )
}

export function barangays(cityMuniCode: string): Promise<ApiResponse<Barangay[]>> {
  return datasource(
    () => mockOk(mockBarangays.filter((b) => b.city_muni_code === cityMuniCode)),
    async () => {
      const { data } = await http.get<ApiResponse<Barangay[]>>(`/address/cities/${cityMuniCode}/barangays`)
      return data
    },
  )
}
