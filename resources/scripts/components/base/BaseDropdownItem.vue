<template>
  <MenuItem v-slot="{ active }">
    <div
      v-bind="$attrs"
      role="menuitem"
      tabindex="0"
      :class="[
        active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
        'group flex items-center px-4 py-2 text-sm font-normal whitespace-normal cursor-pointer',
      ]"
      @click="onClick"
      @keydown.enter="onClick"
      @keydown.space.prevent="onClick"
    >
      <slot :active="active" />
    </div>
  </MenuItem>
</template>

<script setup>
import { MenuItem } from '@headlessui/vue'

defineOptions({
  inheritAttrs: false,
})

const emit = defineEmits(['click'])

function onClick(event) {
  emit('click', event)
}
</script>
