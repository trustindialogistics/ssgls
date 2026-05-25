<template>
  <BaseModal
    :show="modalActive"
    :initial-focus="initialFocusRef"
    @close="closeModal"
    @open="onModalOpen"
  >
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalStore.title }}
        <BaseIcon
          name="XMarkIcon"
          class="w-6 h-6 text-gray-500 cursor-pointer"
          @click="closeModal"
        />
      </div>
    </template>

    <form @submit.prevent="submitPod">
      <div
        ref="initialFocusRef"
        class="sr-only outline-none focus:outline-none"
        tabindex="-1"
        aria-hidden="true"
      />

      <div class="px-8 py-6 sm:p-6">
        <BaseInputGroup label="POD File" variant="vertical" required>
          <BaseFileUploader
            v-model="selectedFiles"
            accept="image/*,application/pdf"
            base64
            input-field-name="pod"
            @change="onFileInputChange"
            @remove="onFileInputRemove"
          />
        </BaseInputGroup>
      </div>

      <div class="z-0 flex justify-end px-4 py-4 border-t border-gray-200 border-solid">
        <BaseButton
          class="mr-2"
          variant="primary-outline"
          type="button"
          @click="closeModal"
        >
          {{ $t('general.cancel') }}
        </BaseButton>

        <BaseButton
          :loading="isUploading"
          :disabled="isUploading || !podImage"
          variant="primary"
          type="submit"
        >
          <template #left="slotProps">
            <BaseIcon
              v-if="!isUploading"
              name="ArrowUpTrayIcon"
              :class="slotProps.class"
            />
          </template>
          Upload POD
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useModalStore } from '@/scripts/stores/modal'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'

const modalStore = useModalStore()
const invoiceStore = useInvoiceStore()

const selectedFiles = ref([])
const podImage = ref(null)
const isUploading = ref(false)
const initialFocusRef = ref(null)

const modalActive = computed(
  () => modalStore.active && modalStore.componentName === 'UploadPodModal'
)

function onModalOpen() {
  selectedFiles.value = []
  podImage.value = null
}

function onFileInputChange(fieldName, image, fileCount, file) {
  podImage.value = JSON.stringify({
    name: file.name,
    data: image,
  })
}

function onFileInputRemove() {
  podImage.value = null
}

async function submitPod() {
  if (!modalStore.id || !podImage.value) {
    return
  }

  isUploading.value = true

  try {
    await invoiceStore.uploadPod({
      id: modalStore.id,
      pod: podImage.value,
    })

    modalStore.refreshData && modalStore.refreshData()
    closeModal()
  } finally {
    isUploading.value = false
  }
}

function closeModal() {
  modalStore.closeModal()
  selectedFiles.value = []
  podImage.value = null
}
</script>
