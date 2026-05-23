<template>
  <img :src="logoUrl" v-bind="$attrs" />
</template>

<script setup>
const logoUrl = new URL('$images/logo.png', import.meta.url)
</script>

