import { reactive, ref } from 'vue'
import { profileService } from '@notebook/services'
import type { UpdateProfilePayload } from '@notebook/services'
import type { AddressChain, User } from '@notebook/types'
import { extractErrors, type AuthFormErrors } from '../auth/useAuthForm'

/**
 * Profile load + save (Phase 005).
 *
 * Per-view state lives in a composable, NOT Pinia — the auth store stays
 * auth-only (CLAUDE.md §3). Shared here rather than duplicated per app because
 * the logic is identical; only the layout differs (D-027).
 */
export function useProfile() {
  // Every field is non-optional here even though the payload type is Partial:
  // a form always HAS a value for each control, and leaving them optional makes
  // `barangay_code` string|null|undefined, which no v-model target accepts.
  const form = reactive<Required<Omit<UpdateProfilePayload, 'avatar_path'>>>({
    first_name: '',
    last_name: '',
    middle_name: '',
    birthday: '',
    mobile_number: '',
    barangay_code: null,
    address_line: '',
  })

  const chain = ref<AddressChain | null>(null)
  const loading = ref(false)
  const saving = ref(false)
  const saved = ref(false)
  const errors = ref<AuthFormErrors>({ message: null, fields: null })

  function apply(user: User): void {
    form.first_name = user.first_name
    form.last_name = user.last_name
    form.middle_name = user.middle_name ?? ''
    form.birthday = user.birthday
    form.mobile_number = user.mobile_number
    form.barangay_code = user.barangay_code
    form.address_line = user.address_line ?? ''
  }

  async function load(): Promise<void> {
    loading.value = true
    errors.value = { message: null, fields: null }

    try {
      const { data } = await profileService.get()
      if (data) apply(data)
    } catch (error) {
      errors.value = extractErrors(error)
    } finally {
      loading.value = false
    }
  }

  async function save(): Promise<boolean> {
    saving.value = true
    saved.value = false
    errors.value = { message: null, fields: null }

    try {
      // Empty optional text means "cleared", which the API expects as null.
      const { data } = await profileService.update({
        ...form,
        middle_name: form.middle_name || null,
        address_line: form.address_line || null,
      })

      if (data) apply(data)
      saved.value = true
      return true
    } catch (error) {
      errors.value = extractErrors(error)
      return false
    } finally {
      saving.value = false
    }
  }

  /** "Barangay, City, Province, Region" — omits the province when there is none. */
  function formatChain(value: AddressChain | null = chain.value): string {
    if (!value) return ''

    return [
      value.barangay?.name,
      value.city_municipality?.name,
      value.province?.name,
      value.region?.name,
    ]
      .filter(Boolean)
      .join(', ')
  }

  return { form, chain, loading, saving, saved, errors, load, save, formatChain }
}
