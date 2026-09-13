<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { addressService } from '@notebook/services'
import type { AddressChain, Barangay, CityMunicipality, Province, Region } from '@notebook/types'
import SelectField from './SelectField.vue'

/**
 * The four-level PSGC address chain (Phase 005, D-020: no geolocation).
 *
 * ONE shared component — both form factors render this; neither reimplements
 * the cascade (D-027 guardrail).
 *
 * ============================ THE PROVINCE IS OPTIONAL ======================
 * Phase 005 described an "NCR path" where only NCR skips the province. The real
 * PSGC data disagrees: 48 cities have no province — all 31 in NCR, PLUS 17
 * Highly Urbanized Cities (Cebu, Davao, Baguio, Iloilo, Bacolod...) that are
 * independent of any province by law, spread across 14 regions.
 *
 * So there is no NCR special case. For EVERY region we load both its provinces
 * and its independent cities; picking a province narrows the city list, and
 * picking an independent city leaves the province empty. One code path, and
 * Cebu City is reachable.
 * ===========================================================================
 *
 * v-model is the barangay_code (what `users.barangay_code` stores). The full
 * resolved chain is emitted separately for display.
 */
const props = defineProps<{
  /** barangay_code, or null when unset. */
  modelValue: string | null
  disabled?: boolean
  required?: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string | null]
  'update:chain': [chain: AddressChain]
}>()

const regions = ref<Region[]>([])
const provinces = ref<Province[]>([])
const cities = ref<CityMunicipality[]>([])
const barangays = ref<Barangay[]>([])

const regionCode = ref<string | null>(null)
const provinceCode = ref<string | null>(null)
const cityCode = ref<string | null>(null)

const loading = ref({ regions: false, provinces: false, cities: false, barangays: false })
const error = ref<string | null>(null)

/** Suppresses the cascade's reset while we hydrate from a saved value. */
let hydrating = false

async function loadRegions(): Promise<void> {
  loading.value.regions = true
  try {
    const { data } = await addressService.regions()
    regions.value = data ?? []
  } catch {
    error.value = 'Could not load regions.'
  } finally {
    loading.value.regions = false
  }
}

/** Provinces AND independent cities load together — both are region children. */
async function loadRegionChildren(code: string): Promise<void> {
  loading.value.provinces = true
  loading.value.cities = true
  try {
    const [provinceResult, cityResult] = await Promise.all([
      addressService.provinces(code),
      addressService.citiesMunicipalitiesByRegion(code),
    ])
    provinces.value = provinceResult.data ?? []
    cities.value = cityResult.data ?? []
  } catch {
    error.value = 'Could not load provinces.'
  } finally {
    loading.value.provinces = false
    loading.value.cities = false
  }
}

async function loadProvinceCities(code: string): Promise<void> {
  loading.value.cities = true
  try {
    const { data } = await addressService.citiesMunicipalities(code)
    cities.value = data ?? []
  } catch {
    error.value = 'Could not load cities.'
  } finally {
    loading.value.cities = false
  }
}

async function loadBarangays(code: string): Promise<void> {
  loading.value.barangays = true
  try {
    const { data } = await addressService.barangays(code)
    barangays.value = data ?? []
  } catch {
    error.value = 'Could not load barangays.'
  } finally {
    loading.value.barangays = false
  }
}

function emitChain(): void {
  emit('update:chain', {
    region: regions.value.find((r) => r.code === regionCode.value) ?? null,
    province: provinces.value.find((p) => p.code === provinceCode.value) ?? null,
    city_municipality: cities.value.find((c) => c.code === cityCode.value) ?? null,
    barangay: barangays.value.find((b) => b.code === props.modelValue) ?? null,
  })
}

watch(regionCode, async (code) => {
  if (hydrating) return

  provinceCode.value = null
  cityCode.value = null
  provinces.value = []
  cities.value = []
  barangays.value = []
  emit('update:modelValue', null)

  if (code) await loadRegionChildren(code)
  emitChain()
})

watch(provinceCode, async (code) => {
  if (hydrating) return

  cityCode.value = null
  barangays.value = []
  emit('update:modelValue', null)

  // Clearing the province returns to the region's independent cities.
  if (code) await loadProvinceCities(code)
  else if (regionCode.value) await loadRegionChildren(regionCode.value)

  emitChain()
})

watch(cityCode, async (code) => {
  if (hydrating) return

  barangays.value = []
  emit('update:modelValue', null)

  if (code) await loadBarangays(code)
  emitChain()
})

/**
 * Hydrate from a saved barangay_code: resolve the chain in ONE call, then load
 * each level's options so the user can still change any of them.
 */
async function hydrate(barangayCode: string): Promise<void> {
  const { data } = await addressService.chain(barangayCode)
  if (!data?.barangay) return

  hydrating = true
  try {
    regionCode.value = data.region?.code ?? null
    provinceCode.value = data.province?.code ?? null
    cityCode.value = data.city_municipality?.code ?? null

    if (data.region) await loadRegionChildren(data.region.code)
    if (data.province) await loadProvinceCities(data.province.code)
    if (data.city_municipality) await loadBarangays(data.city_municipality.code)
  } finally {
    hydrating = false
  }

  emitChain()
}

// Loaded in onMounted rather than with a top-level await: a top-level await
// makes this an async component, which would force every consuming app to wrap
// it in <Suspense>. The cascade handles empty option lists fine meanwhile.
onMounted(async () => {
  await loadRegions()
  if (props.modelValue) await hydrate(props.modelValue)
})
</script>

<template>
  <div class="flex flex-col gap-4">
    <p v-if="error" role="alert" class="text-sm text-margin">{{ error }}</p>

    <SelectField
      v-model="regionCode"
      label="Region"
      placeholder="Select region"
      :options="regions"
      :loading="loading.regions"
      :disabled="disabled"
      :required="required"
    />

    <SelectField
      v-model="provinceCode"
      label="Province"
      :placeholder="provinces.length ? 'Select province (or pick a city below)' : 'No provinces — pick a city below'"
      :options="provinces"
      :loading="loading.provinces"
      :disabled="disabled || !regionCode || provinces.length === 0"
    />

    <SelectField
      v-model="cityCode"
      label="City / Municipality"
      placeholder="Select city or municipality"
      :options="cities"
      :loading="loading.cities"
      :disabled="disabled || !regionCode"
      :required="required"
    />

    <SelectField
      :model-value="modelValue"
      label="Barangay"
      placeholder="Select barangay"
      :options="barangays"
      :loading="loading.barangays"
      :disabled="disabled || !cityCode"
      :required="required"
      @update:model-value="
        (value) => {
          $emit('update:modelValue', value)
          emitChain()
        }
      "
    />
  </div>
</template>
