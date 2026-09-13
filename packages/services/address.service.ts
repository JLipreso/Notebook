import type {
  AddressChain,
  ApiResponse,
  Barangay,
  CityMunicipality,
  Province,
  Region,
} from '@notebook/types'

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
 * The INDEPENDENT-CITY branch: cities that hang off a region with no province.
 *
 * Not an NCR special case, despite what Phase 005 assumed — the real PSGC data
 * has 48 such cities: all 31 in NCR plus 17 Highly Urbanized Cities (Cebu,
 * Davao, Baguio, Iloilo, Bacolod...) that are independent of any province by
 * law. AddressSelect therefore asks EVERY region for these and shows them
 * alongside its provinces.
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

/**
 * Resolve a saved barangay_code back to its full chain, so the profile screen
 * can re-display an address without walking four endpoints on load.
 */
export function chain(barangayCode: string): Promise<ApiResponse<AddressChain>> {
  return datasource(
    () => {
      const barangay = mockBarangays.find((b) => b.code === barangayCode) ?? null
      const city = barangay
        ? (mockCitiesMunicipalities.find((c) => c.code === barangay.city_muni_code) ?? null)
        : null
      const province = city?.province_code
        ? (mockProvinces.find((p) => p.code === city.province_code) ?? null)
        : null
      const region = city ? (mockRegions.find((r) => r.code === city.region_code) ?? null) : null

      return mockOk({ region, province, city_municipality: city, barangay })
    },
    async () => {
      const { data } = await http.get<ApiResponse<AddressChain>>(
        `/address/barangays/${barangayCode}/chain`,
      )
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
