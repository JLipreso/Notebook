<script setup lang="ts">
// Labelled <select> matching FormField's shape, so a profile form can mix
// inputs and dropdowns without styling drift.
withDefaults(
  defineProps<{
    label: string
    modelValue: string | null
    options: { code: string; name: string }[]
    placeholder?: string
    disabled?: boolean
    loading?: boolean
    error?: string | null
    required?: boolean
  }>(),
  { placeholder: 'Select…', disabled: false, loading: false, error: null, required: false },
)

defineEmits<{ 'update:modelValue': [value: string | null] }>()

const id = `select-${Math.random().toString(36).slice(2, 9)}`
</script>

<template>
  <div class="flex flex-col gap-1">
    <label :for="id" class="text-sm font-medium text-ink">
      {{ label }}
      <span v-if="required" class="text-margin" aria-hidden="true">*</span>
    </label>
    <select
      :id="id"
      :value="modelValue ?? ''"
      :disabled="disabled || loading"
      :aria-invalid="error ? 'true' : undefined"
      :aria-busy="loading ? 'true' : undefined"
      class="rounded border border-paper-shade bg-paper px-3 py-2 text-ink outline-none focus:border-ink disabled:opacity-50"
      @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value || null)"
    >
      <option value="">{{ loading ? 'Loading…' : placeholder }}</option>
      <option v-for="option in options" :key="option.code" :value="option.code">
        {{ option.name }}
      </option>
    </select>
    <p v-if="error" class="text-sm text-margin">{{ error }}</p>
  </div>
</template>
