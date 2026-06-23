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

        <!-- Basic Info -->
        <div class="grid grid-cols-5 gap-4 mt-4 mb-2">
          <h6 class="col-span-5 text-base font-semibold text-left lg:col-span-1 text-gray-900">
            Basic Info
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <ProfileField
              v-model="form.name"
              :label="`${singularTitle} Name`"
              required
              :error="v$.name.$error && v$.name.$errors[0].$message"
              @input="v$.name.$touch()"
            />

            <ProfileField
              v-model="form.phone"
              label="Phone No."
            />

            <ProfileField
              v-model="form.address"
              label="Full Address"
              textarea
              class="md:col-span-2"
            />
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
              <ProfileField
                v-model="form.bank_account_no"
                label="Owner Bank Account No."
              />
              <ProfileField
                v-model="form.financer_name"
                label="Owner PAN No."
              />

              <!-- Document Upload Section for Owner -->
              <div class="col-span-5 mt-4 mb-2">
                <h6 class="text-base font-semibold text-gray-700 text-left">Mandatory Required Documents</h6>
              </div>

              <ProfileField
                v-model="form.rc_front_path"
                label="Attach RC Front"
                type="file"
              />
              <ProfileField
                v-model="form.rc_back_path"
                label="Attach RC Back"
                type="file"
              />
              <ProfileField
                v-model="form.insurance_path"
                label="Attach Insurance Copy"
                type="file"
              />
            </template>

            <template v-else-if="form.type === 'DRIVER'">
              <ProfileField
                v-model="form.bank_account_no"
                label="Driver Bank Account No."
              />
              <ProfileField
                v-model="form.licence_no"
                label="Licence No."
              />
              <ProfileField
                v-model="form.licence_date"
                label="Issued Date"
                type="date"
              />
              <ProfileField
                v-model="form.rto_address"
                label="RTO"
                textarea
                class="md:col-span-2"
              />
              <ProfileField
                v-model="form.valid_up_to"
                label="Valid up Dt."
                type="date"
              />

              <!-- Document Upload Section for Driver -->
              <div class="col-span-5 mt-4 mb-2">
                <h6 class="text-base font-semibold text-gray-700 text-left">Mandatory Required Documents</h6>
              </div>

              <ProfileField
                v-model="form.license_front_path"
                label="Attach Driving License Front"
                type="file"
              />
              <ProfileField
                v-model="form.license_back_path"
                label="Attach Driving License Back"
                type="file"
              />
            </template>

            <template v-else-if="form.type === 'BROKER'">
              <ProfileField
                v-model="form.bank_account_no"
                label="Broker Bank Account No."
              />
              <ProfileField
                v-model="form.advice_no"
                label="Broker Pan No."
              />
              <ProfileField
                v-model="form.destination_broker_name"
                label="Desti. Broker Name"
              />
              <ProfileField
                v-model="form.destination_broker_address"
                label="Add"
                textarea
                class="md:col-span-2"
              />

              <!-- Document Upload Section for Broker -->
              <div class="col-span-5 mt-4 mb-2">
                <h6 class="text-base font-semibold text-gray-700 text-left">Mandatory Required Documents</h6>
              </div>

              <ProfileField
                v-model="form.pan_front_path_broker"
                label="Attach PAN Front"
                type="file"
              />
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
import { computed, defineComponent, h, reactive, ref, resolveComponent, watch } from 'vue'
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

const ProfileField = defineComponent({
  props: {
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, required: true },
    textarea: { type: Boolean, default: false },
    type: { type: String, default: 'text' },
    error: { type: [String, Boolean], default: '' },
    required: { type: Boolean, default: false },
    contentLoading: { type: Boolean, default: false },
  },
  emits: ['update:modelValue', 'input'],
  setup(props, { emit, attrs }) {
    const handleFileChange = (e) => {
      const file = e.target.files[0]
      if (!file) return
      
      const reader = new FileReader()
      reader.onload = () => {
        emit('update:modelValue', reader.result)
        emit('input')
      }
      reader.readAsDataURL(file)
    }

    const removeFile = () => {
      emit('update:modelValue', '')
      emit('input')
    }

    const getFileName = (path) => {
      if (!path) return ''
      if (path.startsWith('data:')) {
        return 'New File Selected'
      }
      return path.substring(path.lastIndexOf('/') + 1)
    }

    return () =>
      h('div', { class: ['col-span-5 md:col-span-1', attrs.class] }, [
        h(
          resolveComponent('BaseInputGroup'),
          {
            label: props.label,
            error: props.error,
            required: props.required,
            contentLoading: props.contentLoading,
          },
          () => [
            props.textarea
              ? h(resolveComponent('BaseTextarea'), {
                  modelValue: props.modelValue,
                  'onUpdate:modelValue': (value) => emit('update:modelValue', value),
                  contentLoading: props.contentLoading,
                  rows: 3,
                })
              : props.type === 'file'
                ? props.modelValue
                  ? h('div', { class: 'flex flex-col p-3 border border-gray-200 rounded-md bg-gray-50' }, [
                      h('div', { class: 'flex items-center justify-between' }, [
                        h('span', { class: 'text-sm font-medium text-gray-700 truncate max-w-[200px]', title: getFileName(props.modelValue) }, getFileName(props.modelValue)),
                        h('div', { class: 'flex items-center space-x-2' }, [
                          !props.modelValue.startsWith('data:')
                            ? h('a', { href: props.modelValue, target: '_blank', class: 'text-xs text-primary-600 hover:text-primary-700 hover:underline' }, 'View')
                            : null,
                          h('button', { type: 'button', class: 'text-xs text-red-600 hover:text-red-700 hover:underline', onClick: removeFile }, 'Remove')
                        ])
                      ])
                    ])
                  : h('div', { class: 'relative border-2 border-dashed border-gray-300 rounded-md bg-gray-50 hover:bg-gray-100 transition p-4 text-center cursor-pointer' }, [
                      h('input', {
                        type: 'file',
                        accept: 'image/*,application/pdf',
                        class: 'absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10',
                        onChange: handleFileChange
                      }),
                      h('div', { class: 'flex flex-col items-center justify-center space-y-1' }, [
                        h(resolveComponent('BaseIcon'), { name: 'CloudArrowUpIcon', class: 'h-6 w-6 text-gray-400' }),
                        h('span', { class: 'text-xs text-gray-500' }, 'Click to upload document')
                      ])
                    ])
                : h(resolveComponent('BaseInput'), {
                    modelValue: props.modelValue,
                    'onUpdate:modelValue': (value) => emit('update:modelValue', value),
                    onInput: () => emit('input'),
                    contentLoading: props.contentLoading,
                    type: props.type
                  })
          ]
        ),
      ])
  },
})

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
  rc_front_path: '',
  rc_back_path: '',
  pan_front_path: '',
  insurance_path: '',
  license_front_path: '',
  license_back_path: '',
  pan_front_path_broker: '',
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


</script>
