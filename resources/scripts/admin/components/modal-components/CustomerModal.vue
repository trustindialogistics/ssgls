<template>
  <BaseModal
    :show="modalActive"
    @close="closeCustomerModal"
    @open="setInitialData"
  >
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalStore.title }}

        <BaseIcon
          name="XMarkIcon"
          class="h-6 w-6 text-gray-500 cursor-pointer"
          @click="closeCustomerModal"
        />
      </div>
    </template>
    <form action="" @submit.prevent="submitCustomerData">
      <div class="px-6 pb-6 max-h-[70vh] overflow-y-auto space-y-6">
        <!-- Basic Info -->
        <div class="grid grid-cols-5 gap-4 mt-4 mb-2">
          <h6 class="col-span-5 text-base font-semibold text-left lg:col-span-1 text-gray-900">
            {{ $t('customers.basic_info') }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <BaseInputGroup
              :label="$t('customers.display_name')"
              required
              :error="v$.name.$error && v$.name.$errors[0].$message"
            >
              <BaseInput
                v-model.trim="customerStore.currentCustomer.name"
                type="text"
                name="name"
                class="mt-1 md:mt-0"
                :invalid="v$.name.$error"
                @input="v$.name.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup
              :label="$t('login.email')"
              :error="v$.email.$error && v$.email.$errors[0].$message"
            >
              <BaseInput
                v-model.trim="customerStore.currentCustomer.email"
                type="text"
                name="email"
                class="mt-1 md:mt-0"
                :invalid="v$.email.$error"
                @input="v$.email.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('customers.phone')">
              <BaseInput
                v-model.trim="customerStore.currentCustomer.phone"
                type="text"
                name="phone"
                class="mt-1 md:mt-0"
              />
            </BaseInputGroup>

            <BaseInputGroup label="GSTIN No">
              <BaseInput
                v-model="customerStore.currentCustomer.tax_id"
                type="text"
                class="mt-1 md:mt-0"
              />
            </BaseInputGroup>
          </BaseInputGrid>
        </div>

        <BaseDivider />

        <!-- Portal Access -->
        <div class="grid grid-cols-5 gap-4 mb-2">
          <h6 class="col-span-5 text-base font-semibold text-left lg:col-span-1 text-gray-900">
            {{ $t('customers.portal_access') }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <div class="md:col-span-2">
              <p class="text-sm text-gray-500">
                Would you like to allow this customer to login to the Customer Portal?
              </p>

              <BaseSwitch
                v-model="customerStore.currentCustomer.enable_portal"
                class="mt-2 flex"
              />
            </div>

            <BaseInputGroup
              v-if="customerStore.currentCustomer.enable_portal"
              :content-loading="isFetchingInitialData"
              :label="$t('customers.portal_access_url')"
              class="md:col-span-2"
              :help-text="$t('customers.portal_access_url_help')"
            >
              <CopyInputField :token="getCustomerPortalUrl" />
            </BaseInputGroup>

            <BaseInputGroup
              v-if="customerStore.currentCustomer.enable_portal"
              :content-loading="isFetchingInitialData"
              :error="v$.password.$error && v$.password.$errors[0].$message"
              :label="$t('customers.password')"
            >
              <BaseInput
                v-model.trim="customerStore.currentCustomer.password"
                :content-loading="isFetchingInitialData"
                :type="isShowPassword ? 'text' : 'password'"
                name="password"
                :invalid="v$.password.$error"
                @input="v$.password.$touch()"
              >
                <template #right>
                  <BaseIcon
                    :name="isShowPassword ? 'EyeIcon' : 'EyeSlashIcon'"
                    class="mr-1 text-gray-500 cursor-pointer"
                    @click="isShowPassword = !isShowPassword"
                  />
                </template>
              </BaseInput>
            </BaseInputGroup>

            <BaseInputGroup
              v-if="customerStore.currentCustomer.enable_portal"
              :error="
                v$.confirm_password.$error &&
                v$.confirm_password.$errors[0].$message
              "
              :content-loading="isFetchingInitialData"
              :label="$t('customers.confirm_password')"
            >
              <BaseInput
                v-model.trim="customerStore.currentCustomer.confirm_password"
                :content-loading="isFetchingInitialData"
                :type="isShowConfirmPassword ? 'text' : 'password'"
                name="confirm_password"
                :invalid="v$.confirm_password.$error"
                @input="v$.confirm_password.$touch()"
              >
                <template #right>
                  <BaseIcon
                    :name="isShowConfirmPassword ? 'EyeIcon' : 'EyeSlashIcon'"
                    class="mr-1 text-gray-500 cursor-pointer"
                    @click="isShowConfirmPassword = !isShowConfirmPassword"
                  />
                </template>
              </BaseInput>
            </BaseInputGroup>
          </BaseInputGrid>
        </div>

        <BaseDivider />

        <!-- Billing Address -->
        <div class="grid grid-cols-5 gap-4 mb-2">
          <h6 class="col-span-5 text-base font-semibold text-left lg:col-span-1 text-gray-900">
            {{ $t('customers.billing_address') }}
          </h6>

          <BaseInputGrid class="col-span-5 lg:col-span-4">
            <BaseInputGroup :label="$t('customers.name')">
              <template #labelRight>
                <button
                  type="button"
                  class="text-xs text-primary-500 hover:text-primary-600 focus:outline-hidden"
                  @click="copyDisplayNameToBillingName"
                >
                  Copy Display Name
                </button>
              </template>
              <BaseInput
                v-model="customerStore.currentCustomer.billing.name"
                type="text"
                class="mt-1 md:mt-0"
              />
            </BaseInputGroup>

            <BaseInputGroup
              :label="$t('customers.address')"
              :error="
                v$.billing.address_street_1.$error &&
                v$.billing.address_street_1.$errors[0].$message
              "
              class="md:col-span-2"
            >
              <BaseTextarea
                v-model="customerStore.currentCustomer.billing.address_street_1"
                :placeholder="$t('general.street_1')"
                rows="2"
                class="mt-1 md:mt-0"
                :invalid="v$.billing.address_street_1.$error"
                @input="v$.billing.address_street_1.$touch()"
              />

              <BaseTextarea
                v-model="customerStore.currentCustomer.billing.address_street_2"
                :placeholder="$t('general.street_2')"
                rows="2"
                class="mt-3"
                :invalid="v$.billing.address_street_2.$error"
                @input="v$.billing.address_street_2.$touch()"
              />
            </BaseInputGroup>
          </BaseInputGrid>
        </div>
      </div>

      <div
        class="z-0 flex justify-end p-4 border-t border-gray-200 border-solid"
      >
        <BaseButton
          class="mr-3 text-sm"
          type="button"
          variant="primary-outline"
          @click="closeCustomerModal"
        >
          {{ $t('general.cancel') }}
        </BaseButton>

        <BaseButton :loading="isLoading" variant="primary" type="submit">
          <template #left="slotProps">
            <BaseIcon
              v-if="!isLoading"
              name="ArrowDownOnSquareIcon"
              :class="slotProps.class"
            />
          </template>
          {{ $t('general.save') }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'

import {
  required,
  minLength,
  maxLength,
  email,
  alpha,
  url,
  helpers,
  requiredIf,
  sameAs,
} from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'

import { useModalStore } from '@/scripts/stores/modal'
import { useEstimateStore } from '@/scripts/admin/stores/estimate'
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import CopyInputField from '@/scripts/admin/components/CopyInputField.vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useRecurringInvoiceStore } from '@/scripts/admin/stores/recurring-invoice'

const recurringInvoiceStore = useRecurringInvoiceStore()
const modalStore = useModalStore()
const estimateStore = useEstimateStore()
const customerStore = useCustomerStore()
const companyStore = useCompanyStore()
const globalStore = useGlobalStore()
const invoiceStore = useInvoiceStore()
const notificationStore = useNotificationStore()

let isFetchingInitialData = ref(false)

const { t } = useI18n()
const route = useRoute()
const isEdit = ref(false)
const isLoading = ref(false)
let isShowPassword = ref(false)
let isShowConfirmPassword = ref(false)

const modalActive = computed(
  () => modalStore.active && modalStore.componentName === 'CustomerModal'
)

const rules = computed(() => {
  return {
    name: {
      required: helpers.withMessage(t('validation.required'), required),
      minLength: helpers.withMessage(
        t('validation.name_min_length', { count: 3 }),
        minLength(3)
      ),
    },
    currency_id: {
      required: helpers.withMessage(t('validation.required'), required),
    },
    password: {
      required: helpers.withMessage(
        t('validation.required'),
        requiredIf(
          customerStore.currentCustomer.enable_portal == true &&
            !customerStore.currentCustomer.password_added
        )
      ),
      minLength: helpers.withMessage(
        t('validation.password_min_length', { count: 8 }),
        minLength(8)
      ),
    },
    confirm_password: {
      sameAsPassword: helpers.withMessage(
        t('validation.password_incorrect'),
        sameAs(customerStore.currentCustomer.password)
      ),
    },
    email: {
      required: helpers.withMessage(
        t('validation.required'),
        requiredIf(customerStore.currentCustomer.enable_portal == true)
      ),
      email: helpers.withMessage(t('validation.email_incorrect'), email),
    },
    prefix: {
      minLength: helpers.withMessage(
        t('validation.name_min_length', { count: 3 }),
        minLength(3)
      ),
    },
    website: {
      url: helpers.withMessage(t('validation.invalid_url'), url),
    },

    billing: {
      address_street_1: {
        maxLength: helpers.withMessage(
          t('validation.address_maxlength', { count: 255 }),
          maxLength(255)
        ),
      },
      address_street_2: {
        maxLength: helpers.withMessage(
          t('validation.address_maxlength', { count: 255 }),
          maxLength(255)
        ),
      },
    },

    shipping: {
      address_street_1: {
        maxLength: helpers.withMessage(
          t('validation.address_maxlength', { count: 255 }),
          maxLength(255)
        ),
      },
      address_street_2: {
        maxLength: helpers.withMessage(
          t('validation.address_maxlength', { count: 255 }),
          maxLength(255)
        ),
      },
    },
  }
})

const v$ = useVuelidate(
  rules,
  computed(() => customerStore.currentCustomer)
)

const getCustomerPortalUrl = computed(() => {
  return `${window.location.origin}/${companyStore.selectedCompany.slug}/customer/login`
})

function copyAddress() {
  customerStore.copyAddress()
}

function copyDisplayNameToBillingName() {
  if (customerStore.currentCustomer.billing) {
    customerStore.currentCustomer.billing.name = customerStore.currentCustomer.name
  }
}

async function setInitialData() {
  if (modalStore.title?.includes('Add')) {
    customerStore.resetCurrentCustomer()
  }

  if (!customerStore.isEdit) {
    customerStore.currentCustomer.currency_id =
      companyStore.selectedCompanyCurrency.id
  }
}

async function submitCustomerData() {
  v$.value.$touch()

  if (v$.value.$invalid && customerStore.currentCustomer.email === '') {
    notificationStore.showNotification({
      type: 'error',
      message: t('settings.notification.please_enter_email'),
    })
  }

  if (v$.value.$invalid) {
    return true
  }

  isLoading.value = true

  let data = {
    ...customerStore.currentCustomer,
  }

  try {
    let response = null
    if (customerStore.isEdit) {
      response = await customerStore.updateCustomer(data)
    } else {
      response = await customerStore.addCustomer(data)
    }

    if (response.data) {
      isLoading.value = false
      // Automatically create newly created customer
      if (route.name === 'invoices.create' || route.name === 'invoices.edit') {
        invoiceStore.selectCustomer(response.data.data.id)
      }
      if (
        route.name === 'estimates.create' ||
        route.name === 'estimates.edit'
      ) {
        estimateStore.selectCustomer(response.data.data.id)
      }
      if (
        route.name === 'recurring-invoices.create' ||
        route.name === 'recurring-invoices.edit'
      ) {
        recurringInvoiceStore.selectCustomer(response.data.data.id)
      }
      closeCustomerModal()
    }
  } catch (err) {
    console.error(err)
    isLoading.value = false
  }
}

function closeCustomerModal() {
  modalStore.closeModal()
  setTimeout(() => {
    customerStore.resetCurrentCustomer()
    v$.value.$reset()
  }, 300)
}
</script>
