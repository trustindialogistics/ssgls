<template>
  <BaseContentPlaceholders v-if="contentLoading">
    <BaseContentPlaceholdersBox
      :rounded="true"
      class="w-full"
      :style="`height: ${loadingPlaceholderSize}px`"
    />
  </BaseContentPlaceholders>

  <textarea
    v-else
    v-bind="$attrs"
    ref="textarea"
    :value="modelValue"
    :class="[defaultInputClass, inputBorderClass, shouldUppercase ? 'uppercase' : '']"
    :disabled="disabled"
    @input="onInput"
  />
</template>

<script setup>
import { computed, onMounted, ref, useAttrs } from 'vue'
import { useRoute } from 'vue-router'

const props = defineProps({
  contentLoading: {
    type: Boolean,
    default: false,
  },
  row: {
    type: Number,
    default: null,
  },
  invalid: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  modelValue: {
    type: [String, Number],
    default: '',
  },
  defaultInputClass: {
    type: String,
    default:
      'box-border w-full px-3 py-2 text-sm not-italic font-normal leading-snug text-left text-black placeholder-gray-400 bg-white border border-gray-200 border-solid rounded outline-hidden',
  },
  autosize: {
    type: Boolean,
    default: false,
  },
  borderless: {
    type: Boolean,
    default: false,
  },
  autoUppercase: {
    type: Boolean,
    default: null,
  },
})

const route = useRoute()
const attrs = useAttrs()

const shouldUppercase = computed(() => {
  // 1. Never uppercase email or password fields
  const nameAttr = String(attrs.name || attrs.id || '').toLowerCase()

  if (nameAttr.includes('email') || nameAttr.includes('password')) {
    return false
  }

  // 2. If props.autoUppercase is explicitly set to true or false, respect it
  if (props.autoUppercase !== null && props.autoUppercase !== undefined) {
    return props.autoUppercase
  }

  // 3. Otherwise, determine based on route path
  if (route && route.path) {
    const pathLower = route.path.toLowerCase()
    if (
      pathLower.includes('invoices') ||
      pathLower.includes('lr-receipts') ||
      pathLower.includes('lorry-receipts') ||
      pathLower.includes('owner-portal') ||
      pathLower.includes('driver-portal') ||
      pathLower.includes('broker-portal') ||
      pathLower.includes('/customer')
    ) {
      return true
    }
  }

  return false
})

const textarea = ref(null)

const inputBorderClass = computed(() => {
  if (props.invalid && !props.borderless) {
    return 'border-red-400 ring-red-400 focus:ring-red-400 focus:border-red-400'
  } else if (!props.borderless) {
    return 'focus:ring-primary-400 focus:border-primary-400'
  }

  return 'border-none outline-hidden focus:ring-primary-400 focus:border focus:border-primary-400'
})

const loadingPlaceholderSize = computed(() => {
  switch (props.row) {
    case 2:
      return '56'
    case 4:
      return '94'
    default:
      return '56'
  }
})

const emit = defineEmits(['update:modelValue'])

function onInput(e) {
  let val = e.target.value
  if (shouldUppercase.value) {
    const start = e.target.selectionStart
    const end = e.target.selectionEnd
    val = val.toUpperCase()
    e.target.value = val
    e.target.setSelectionRange(start, end)
  }

  emit('update:modelValue', val)

  if (props.autosize) {
    e.target.style.height = 'auto'
    e.target.style.height = `${e.target.scrollHeight}px`
  }
}

onMounted(() => {
  if (textarea.value && props.autosize) {
    textarea.value.style.height = textarea.value.scrollHeight + 'px'

    if (textarea.value.style.overflow && textarea.value.style.overflow.y) {
      textarea.value.style.overflow.y = 'hidden'
    }

    textarea.value.style.resize = 'none'
  }
})
</script>
