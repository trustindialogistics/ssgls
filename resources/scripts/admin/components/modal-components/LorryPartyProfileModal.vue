<template>
  <BaseModal
    :show="modalActive"
    @close="closeModal"
  >
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalStore.title }}

        <BaseIcon
          name="XMarkIcon"
          class="h-6 w-6 text-gray-500 cursor-pointer"
          @click="closeModal"
        />
      </div>
    </template>
    
    <form @submit.prevent="submit">
      <div class="px-6 pb-6 max-h-[70vh] overflow-y-auto space-y-6">
        <!-- Auto-fill with Document -->
        <div class="bg-primary-50 border border-primary-200 rounded-md p-4 flex items-center justify-between gap-4 mt-4">
          <div class="flex-1 flex items-start gap-3">
            <BaseIcon name="DocumentTextIcon" class="h-6 w-6 text-primary-500 mt-0.5" />
            <div class="text-left">
              <label class="text-sm font-semibold text-gray-900 block">Auto-fill with Document</label>
              <span class="text-xs text-gray-500">Upload Aadhaar, DL, or PAN to automatically extract name, address, and ID. Upload front and back images sequentially to merge details.</span>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <input
              type="file"
              ref="autofillFile"
              class="hidden"
              accept="image/*,application/pdf"
              @change="handleAutofillUpload"
            />
            <BaseButton
              type="button"
              variant="primary-outline"
              size="sm"
              :loading="isAutofilling"
              @click="$refs.autofillFile.click()"
            >
              Upload Document
            </BaseButton>
          </div>
        </div>

        <!-- Basic Info -->
        <div class="grid grid-cols-5 gap-4 mt-4 mb-2">
          <h6 class="col-span-5 text-base font-semibold text-left lg:col-span-1 text-gray-900">
            Basic Info
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <BaseInputGroup
              :label="`${singularTitle} Name`"
              required
              :error="v$.name.$error && v$.name.$errors[0].$message"
            >
              <BaseInput
                v-model="form.name"
                type="text"
                :invalid="v$.name.$error"
                @input="v$.name.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup label="Phone No.">
              <BaseInput
                v-model="form.phone"
                type="text"
              />
            </BaseInputGroup>

            <BaseInputGroup label="Full Address" class="md:col-span-2">
              <BaseTextarea
                v-model="form.address"
                rows="2"
              />
            </BaseInputGroup>
          </BaseInputGrid>
        </div>

        <BaseDivider />

        <!-- Details Section -->
        <div class="grid grid-cols-5 gap-4 mb-2">
          <h6 class="col-span-5 text-base font-semibold text-left lg:col-span-1 text-gray-900">
            {{ detailsSectionTitle }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <template v-if="form.type === 'OWNER'">
              <BaseInputGroup label="Owner Bank Account No.">
                <BaseInput
                  v-model="form.bank_account_no"
                  type="text"
                />
              </BaseInputGroup>
              <BaseInputGroup label="Owner PAN No.">
                <BaseInput
                  v-model="form.financer_name"
                  type="text"
                />
              </BaseInputGroup>
              <BaseInputGroup label="Financer Address" class="md:col-span-2">
                <BaseTextarea
                  v-model="form.financer_address"
                  rows="2"
                />
              </BaseInputGroup>
            </template>

            <template v-else-if="form.type === 'DRIVER'">
              <BaseInputGroup label="Driver Bank Account No.">
                <BaseInput
                  v-model="form.bank_account_no"
                  type="text"
                />
              </BaseInputGroup>
              <BaseInputGroup label="Name of Place">
                <BaseInput
                  v-model="form.place"
                  type="text"
                />
              </BaseInputGroup>
              <BaseInputGroup label="Licence No.">
                <BaseInput
                  v-model="form.licence_no"
                  type="text"
                />
              </BaseInputGroup>
              <BaseInputGroup label="Issued DT.">
                <BaseInput
                  v-model="form.licence_date"
                  type="date"
                />
              </BaseInputGroup>
              <BaseInputGroup label="RTO" class="md:col-span-2">
                <BaseTextarea
                  v-model="form.rto_address"
                  rows="2"
                />
              </BaseInputGroup>
              <BaseInputGroup label="Valid up Dt.">
                <BaseInput
                  v-model="form.valid_up_to"
                  type="date"
                />
              </BaseInputGroup>
            </template>

            <template v-else-if="form.type === 'BROKER'">
              <BaseInputGroup label="Broker Bank Account No.">
                <BaseInput
                  v-model="form.bank_account_no"
                  type="text"
                />
              </BaseInputGroup>
              <BaseInputGroup label="Broker Pan No.">
                <BaseInput
                  v-model="form.advice_no"
                  type="text"
                />
              </BaseInputGroup>
              <BaseInputGroup label="Dt.">
                <BaseInput
                  v-model="form.advice_date"
                  type="date"
                />
              </BaseInputGroup>
              <BaseInputGroup label="Desti. Broker Name">
                <BaseInput
                  v-model="form.destination_broker_name"
                  type="text"
                />
              </BaseInputGroup>
              <BaseInputGroup label="Add" class="md:col-span-2">
                <BaseTextarea
                  v-model="form.destination_broker_address"
                  rows="2"
                />
              </BaseInputGroup>
            </template>
          </BaseInputGrid>
        </div>
      </div>

      <div class="z-0 flex justify-end p-4 border-t border-gray-200 border-solid">
        <BaseButton
          class="mr-3 text-sm"
          type="button"
          variant="primary-outline"
          @click="closeModal"
        >
          Cancel
        </BaseButton>

        <BaseButton :loading="isSaving" variant="primary" type="submit">
          <template #left="slotProps">
            <BaseIcon
              v-if="!isSaving"
              name="ArrowDownOnSquareIcon"
              :class="slotProps.class"
            />
          </template>
          Save
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import http from '@/scripts/http'
import { useModalStore } from '@/scripts/stores/modal'
import { useLorryPartyProfileStore } from '@/scripts/admin/stores/lorry-party-profile'
import { useNotificationStore } from '@/scripts/stores/notification'
import { required, minLength, helpers } from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { useI18n } from 'vue-i18n'

const emit = defineEmits(['saved'])

const { t } = useI18n()
const modalStore = useModalStore()
const profileStore = useLorryPartyProfileStore()
const notificationStore = useNotificationStore()

const isSaving = ref(false)

const emptyForm = {
  id: null,
  customer_id: null,
  type: '',
  code: '',
  name: '',
  address: '',
  phone: '',
  bank_account_no: '',
  financer_name: '',
  financer_address: '',
  place: '',
  licence_no: '',
  licence_date: '',
  licence_issued_by: '',
  rto_address: '',
  valid_up_to: '',
  advice_no: '',
  advice_date: '',
  destination_broker_name: '',
  destination_broker_address: '',
}

const form = reactive({ ...emptyForm })

const modalActive = computed(
  () => modalStore.active && modalStore.componentName === 'LorryPartyProfileModal'
)

const singularTitle = computed(() => {
  if (form.type === 'OWNER') return 'Owner'
  if (form.type === 'DRIVER') return 'Driver'
  if (form.type === 'BROKER') return 'Broker'
  return 'Party'
})

const detailsSectionTitle = computed(() => {
  if (form.type === 'OWNER') return 'Owner Details'
  if (form.type === 'DRIVER') return 'Driver Licence Details'
  return 'Broker Details'
})

const rules = computed(() => ({
  name: {
    required: helpers.withMessage(t('validation.required'), required),
    minLength: helpers.withMessage(
      t('validation.name_min_length', { count: 3 }),
      minLength(3)
    ),
  },
}))

const v$ = useVuelidate(rules, form)

watch(
  modalActive,
  (active) => {
    if (active) {
      Object.assign(form, emptyForm, profileStore.current)
    }
  }
)

function closeModal() {
  modalStore.closeModal()
  setTimeout(() => {
    Object.assign(form, emptyForm)
    profileStore.$patch({ current: null })
    v$.value.$reset()
  }, 300)
}

async function submit() {
  v$.value.$touch()
  if (v$.value.$invalid) {
    return
  }

  isSaving.value = true
  try {
    const response = await profileStore.saveProfile({ ...form }, form.id)
    notificationStore.showNotification({
      type: 'success',
      message: `${singularTitle.value} profile saved successfully.`,
    })
    emit('saved', { type: form.type, profile: response.data.data })
    closeModal()
  } catch (err) {
    console.error(err)
  } finally {
    isSaving.value = false
  }
}

const autofillFile = ref(null)
const isAutofilling = ref(false)

async function handleAutofillUpload(event) {
  const file = event.target.files[0]
  if (!file) return

  isAutofilling.value = true
  const formData = new FormData()
  formData.append('file', file)

  try {
    const response = await http.post('/api/v1/lorry-party-profiles/auto-fill', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    if (response.data && response.data.data) {
      const data = response.data.data

      // Incremental merging: only overwrite if response field is present and non-empty
      if (data.name && data.name.trim() !== '') {
        form.name = data.name.trim()
      }
      if (data.phone && data.phone.trim() !== '') {
        form.phone = data.phone.trim()
      }
      if (data.address && data.address.trim() !== '') {
        form.address = data.address.trim()
      }

      if (form.type === 'OWNER') {
        if (data.id_type === 'PAN' && data.id_number) {
          form.financer_name = data.id_number
        }
      } else if (form.type === 'DRIVER') {
        if (data.id_type === 'DRIVING_LICENSE' && data.id_number) {
          form.licence_no = data.id_number
        }
        if (data.licence_date) {
          form.licence_date = data.licence_date
        }
        if (data.valid_up_to) {
          form.valid_up_to = data.valid_up_to
        }
        if (data.rto_address) {
          form.rto_address = data.rto_address
        }
        if (data.place) {
          form.place = data.place
        }
      } else if (form.type === 'BROKER') {
        if (data.id_type === 'PAN' && data.id_number) {
          form.advice_no = data.id_number
        }
      }

      notificationStore.showNotification({
        type: 'success',
        message: 'Document parsed and details auto-filled successfully!',
      })
    }
  } catch (err) {
    console.error(err)
    const errorMsg = err.response?.data?.error || 'Failed to parse document.'
    notificationStore.showNotification({
      type: 'error',
      message: errorMsg,
    })
  } finally {
    isAutofilling.value = false
    if (event.target) {
      event.target.value = ''
    }
  }
}
</script>
