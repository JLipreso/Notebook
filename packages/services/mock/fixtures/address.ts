import type { Barangay, CityMunicipality, Province, Region } from '@notebook/types'

// A trimmed PSGC sample. It deliberately covers BOTH shapes the real data has:
// cities under a province, and province-less "independent" cities — which are
// not only NCR. Region VII carries City of Cebu, a Highly Urbanized City that
// sits directly under its region, so mock mode exercises the same branch as
// live (Phase 005).

export const mockRegions: Region[] = [
  { code: '1300000000', name: 'National Capital Region (NCR)' },
  { code: '0400000000', name: 'Region IV-A (CALABARZON)' },
  { code: '0700000000', name: 'Region VII (Central Visayas)' },
]

export const mockProvinces: Province[] = [
  { code: '0421000000', region_code: '0400000000', name: 'Cavite' },
  { code: '0434000000', region_code: '0400000000', name: 'Laguna' },
  { code: '0702200000', region_code: '0700000000', name: 'Cebu' },
]

export const mockCitiesMunicipalities: CityMunicipality[] = [
  // NCR: no province.
  { code: '1380600000', province_code: null, region_code: '1300000000', name: 'City of Manila', class: 'city' },
  { code: '1380700000', province_code: null, region_code: '1300000000', name: 'Quezon City', class: 'city' },
  { code: '0421005000', province_code: '0421000000', region_code: '0400000000', name: 'Bacoor', class: 'city' },
  { code: '0421006000', province_code: '0421000000', region_code: '0400000000', name: 'Imus', class: 'municipality' },
  { code: '0434010000', province_code: '0434000000', region_code: '0400000000', name: 'Calamba', class: 'city' },
  // Under a province.
  { code: '0702201000', province_code: '0702200000', region_code: '0700000000', name: 'Alcantara', class: 'municipality' },
  // Highly urbanized: province-less, but NOT in NCR.
  { code: '0730600000', province_code: null, region_code: '0700000000', name: 'City of Cebu', class: 'city' },
]

export const mockBarangays: Barangay[] = [
  { code: '1380600001', city_muni_code: '1380600000', name: 'Barangay 1' },
  { code: '1380600002', city_muni_code: '1380600000', name: 'Barangay 2' },
  { code: '1380700001', city_muni_code: '1380700000', name: 'Bagong Pag-asa' },
  { code: '1380700002', city_muni_code: '1380700000', name: 'Diliman' },
  { code: '0421005001', city_muni_code: '0421005000', name: 'Alima' },
  { code: '0421005002', city_muni_code: '0421005000', name: 'Bayanan' },
  { code: '0421005003', city_muni_code: '0421005000', name: 'Molino I' },
  { code: '0421006001', city_muni_code: '0421006000', name: 'Anabu I-A' },
  { code: '0421006002', city_muni_code: '0421006000', name: 'Bucandala I' },
  { code: '0434010001', city_muni_code: '0434010000', name: 'Barandal' },
  { code: '0434010002', city_muni_code: '0434010000', name: 'Canlubang' },
  { code: '0702201001', city_muni_code: '0702201000', name: 'Cabadiangan' },
  { code: '0730600001', city_muni_code: '0730600000', name: 'Apas' },
  { code: '0730600002', city_muni_code: '0730600000', name: 'Lahug' },
]
