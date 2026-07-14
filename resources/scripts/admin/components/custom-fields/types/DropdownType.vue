<template>
  <BaseMultiselect
    v-model="inputValue"
    :options="computedOptions"
    :label="label"
    :value-prop="valueProp"
    :object="object"
  />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Object, Number],
    default: null,
  },
  options: {
    type: Array,
    default: () => [],
  },
  valueProp: {
    type: String,
    default: 'name',
  },
  label: {
    type: String,
    default: 'name',
  },
  object: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])

const inputValue = computed({
  get: () => props.modelValue,
  set: (value) => {
    emit('update:modelValue', value)
  },
})

const computedOptions = computed(() => {
  return (props.options || []).map((option) => {
    if (typeof option === 'string') {
      return { name: option }
    }
    return option
  })
})
</script>
