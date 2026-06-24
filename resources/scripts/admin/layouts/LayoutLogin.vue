<template>
  <div class="login-page">
    <NotificationRoot />

    <div
      class="login-card"
    >
      <div class="w-full">
        <div class="login-logo-wrap">
          <MainLogo
            v-if="!loginPageLogo"
            class="login-logo"
          />

          <img
            v-else
            :src="loginPageLogo"
            class="login-logo"
            alt="Company Logo"
          />
        </div>

        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
import NotificationRoot from '@/scripts/components/notifications/NotificationRoot.vue'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'
import { computed } from 'vue'

const loginPageLogo = computed(() => {
  if (window.login_page_logo) {
    return window.login_page_logo
  }

  return false
})
</script>

<style scoped>
.login-page {
  align-items: center;
  background: #f3f4f6;
  display: flex;
  justify-content: center;
  min-height: 100vh;
  padding: 40px 16px;
}

.login-card {
  background: #ffffff;
  border-radius: 8px;
  box-shadow: 0 10px 30px rgba(17, 24, 39, 0.08);
  color: #111827;
  max-width: 448px;
  padding: 40px;
  width: 100%;
}

.login-logo-wrap {
  align-items: center;
  display: flex;
  justify-content: center;
  margin-bottom: 32px;
  width: 100%;
}

.login-logo {
  display: block;
  height: auto;
  max-height: 160px;
  max-width: 320px;
  object-fit: contain;
  width: auto;
}

.login-card :deep(form) {
  margin-top: 24px;
}

.login-card :deep(input) {
  min-height: 42px;
  width: 100%;
}

.login-card :deep(svg:not(.login-logo)) {
  height: 20px;
  width: 20px;
}

.login-card :deep(button[type='submit']) {
  align-items: center;
  background: #5851d8;
  border-radius: 6px;
  color: #ffffff;
  display: inline-flex;
  font-size: 14px;
  font-weight: 600;
  justify-content: center;
  min-height: 42px;
  padding: 8px 18px;
}

@media (max-width: 480px) {
  .login-card {
    padding: 32px 24px;
  }

  .login-logo {
    max-width: 200px;
  }
}
</style>
