<template>
  <BasePage>
    <form @submit.prevent="submit">
      <BasePageHeader :title="pageTitle">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/dashboard" />
          <BaseBreadcrumbItem :title="title" :to="basePath" />
          <BaseBreadcrumbItem :title="pageTitle" to="#" active />
        </BaseBreadcrumb>

        <template #actions>
          <div class="flex items-center justify-end">
            <BaseButton type="submit" :loading="isSaving" :disabled="isSaving">
              <template #left="slotProps">
                <BaseIcon name="ArrowDownOnSquareIcon" :class="slotProps.class" />
              </template>
              {{ isEdit ? `Update ${singularTitle}` : `Save ${singularTitle}` }}
            </BaseButton>
          </div>
        </template>
      </BasePageHeader>

      <BaseCard class="mt-5">
        <div class="grid grid-cols-5 gap-4 mb-8">
          <h6 class="col-span-5 text-lg font-semibold text-left lg:col-span-1">
            Basic Info
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <ProfileField
              v-model="form.name"
              :label="`${singularTitle} Name`"
              required
              :content-loading="isLoadingExisting"
              :error="v$.name.$error && v$.name.$errors[0].$message"
              @input="v$.name.$touch()"
            />

            <ProfileField
              v-model="form.phone"
              label="Phone No."
              :content-loading="isLoadingExisting"
            />

            <ProfileField
              v-model="form.address"
              label="Full Address"
              textarea
              class="md:col-span-2"
              :content-loading="isLoadingExisting"
            />
          </BaseInputGrid>
        </div>

        <BaseDivider class="mb-5 md:mb-8" />

        <div class="grid grid-cols-5 gap-4 mb-8">
          <h6 class="col-span-5 text-lg font-semibold text-left lg:col-span-1">
            {{ detailsSectionTitle }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <template v-if="type === 'OWNER'">
              <ProfileField
                v-model="form.bank_account_no"
                label="Owner Bank Account No."
                :content-loading="isLoadingExisting"
              />
              <ProfileField
                v-model="form.financer_name"
                label="Owner PAN No."
                :content-loading="isLoadingExisting"
              />
              <ProfileField
                v-model="form.financer_address"
                label="Financer Address"
                textarea
                class="md:col-span-2"
                :content-loading="isLoadingExisting"
              />
            </template>

            <template v-else-if="type === 'DRIVER'">
              <ProfileField
                v-model="form.bank_account_no"
                label="Driver Bank Account No."
                :content-loading="isLoadingExisting"
              />
              <ProfileField
                v-model="form.place"
                label="Name of Place"
                :content-loading="isLoadingExisting"
              />
              <ProfileField
                v-model="form.licence_no"
                label="Licence No."
                :content-loading="isLoadingExisting"
              />
              <ProfileField
                v-model="form.licence_date"
                label="Dt."
                type="date"
                :content-loading="isLoadingExisting"
              />
              <ProfileField
                v-model="form.licence_issued_by"
                label="Issued"
                :content-loading="isLoadingExisting"
              />
              <ProfileField
                v-model="form.rto_address"
                label="RTO"
                textarea
                class="md:col-span-2"
                :content-loading="isLoadingExisting"
              />
              <ProfileField
                v-model="form.valid_up_to"
                label="Valid up Dt."
                type="date"
                :content-loading="isLoadingExisting"
              />
            </template>

            <template v-else-if="type === 'BROKER'">
              <ProfileField
                v-model="form.bank_account_no"
                label="Broker Bank Account No."
                :content-loading="isLoadingExisting"
              />
              <ProfileField
                v-model="form.advice_no"
                label="Broker Pan No."
                :content-loading="isLoadingExisting"
              />
              <ProfileField
                v-model="form.destination_broker_name"
                label="Desti. Broker Name"
                :content-loading="isLoadingExisting"
              />
              <ProfileField
                v-model="form.destination_broker_address"
                label="Add"
                textarea
                class="md:col-span-2"
                :content-loading="isLoadingExisting"
              />
            </template>
          </BaseInputGrid>
        </div>
      </BaseCard>
    </form>
  </BasePage>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, reactive, ref, resolveComponent, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { required, minLength, helpers } from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { useLorryPartyProfileStore } from '@/scripts/admin/stores/lorry-party-profile'
import { useNotificationStore } from '@/scripts/stores/notification'

defineOptions({ name: 'LorryPartyProfileCreate' })

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
              : h(resolveComponent('BaseInput'), {
                  modelValue: props.modelValue,
                  'onUpdate:modelValue': (value) => emit('update:modelValue', value),
                  onInput: () => emit('input'),
                  contentLoading: props.contentLoading,
                  type: props.type,
                }),
          ]
        ),
      ])
  },
})

const route = useRoute()
const router = useRouter()
const store = useLorryPartyProfileStore()
const notificationStore = useNotificationStore()
const { t } = useI18n()

const isSaving = ref(false)
const isLoadingExisting = ref(false)

const emptyForm = {
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

const type = computed(() => route.meta.profileType)
const isEdit = computed(() => !!route.params.id)
const portalTitle = {
  OWNER: 'Owner List',
  DRIVER: 'Driver List',
  BROKER: 'Broker List',
}
const singularByType = {
  OWNER: 'Owner',
  DRIVER: 'Driver',
  BROKER: 'Broker',
}
const basePathByType = {
  OWNER: '/admin/owner-portal',
  DRIVER: '/admin/driver-portal',
  BROKER: '/admin/broker-portal',
}

const title = computed(() => portalTitle[type.value] || 'Party List')
const singularTitle = computed(() => singularByType[type.value] || 'Party')
const pageTitle = computed(() =>
  isEdit.value ? `Edit ${singularTitle.value}` : `New ${singularTitle.value}`
)
const basePath = computed(() => basePathByType[type.value] || '/admin/owner-portal')
const detailsSectionTitle = computed(() => {
  if (type.value === 'OWNER') return 'Owner Details'
  if (type.value === 'DRIVER') return 'Driver Licence Details'
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

async function load() {
  Object.assign(form, emptyForm, { type: type.value })

  if (!isEdit.value) {
    return
  }

  isLoadingExisting.value = true

  try {
    const response = await store.fetchProfile(route.params.id)
    Object.assign(form, emptyForm, response.data.data, { type: type.value })
  } finally {
    isLoadingExisting.value = false
  }
}

async function submit() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return
  }

  isSaving.value = true

  try {
    await store.saveProfile({ ...form, type: type.value }, route.params.id)
    notificationStore.showNotification({
      type: 'success',
      message: `${singularTitle.value} profile saved successfully.`,
    })
    router.push(basePath.value)
  } finally {
    isSaving.value = false
  }
}

onMounted(load)

watch(
  () => [route.meta.profileType, route.params.id],
  () => {
    load()
  }
)
</script>
