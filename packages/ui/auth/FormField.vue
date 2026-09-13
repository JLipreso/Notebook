<script setup lang="ts">
// Shared labelled input + error slot. Both form factors compose this; neither
// re-implements field markup (D-027 guardrail).
withDefaults(
  defineProps<{
    label: string
    type?: string
    modelValue: string
    error?: string | null
    required?: boolean
    autocomplete?: string
    placeholder?: string
  }>(),
  { type: 'text', error: null, required: false, autocomplete: undefined, placeholder: undefined },
)

defineEmits<{ 'update:modelValue': [value: string] }>()

const id = `field-${Math.random().toString(36).slice(2, 9)}`
</script>

<template>
  <div class="flex flex-col gap-1">
    <label :for="id" class="text-sm font-medium text-ink">
      {{ label }}
      <span v-if="required" class="text-margin" aria-hidden="true">*</span>
    </label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :required="required"
      :autocomplete="autocomplete"
      :placeholder="placeholder"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="error ? `${id}-error` : undefined"
      class="rounded border border-paper-shade bg-paper px-3 py-2 text-ink outline-none focus:border-ink"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <p v-if="error" :id="`${id}-error`" class="text-sm text-margin">{{ error }}</p>
  </div>
</template>
