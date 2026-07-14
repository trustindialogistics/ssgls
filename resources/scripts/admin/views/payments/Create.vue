<template>
  <PaymentModeModal />

  <BasePage class="relative payment-create">
    <form action="" @submit.prevent="submitPaymentData">
      <BasePageHeader :title="pageTitle" class="mb-5">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem
            :title="$t('general.home')"
            to="/admin/dashboard"
          />
          <BaseBreadcrumbItem
            :title="$t('payments.payment', 2)"
            to="/admin/payments"
          />
          <BaseBreadcrumbItem :title="pageTitle" to="#" active />
        </BaseBreadcrumb>

        <template #actions>
          <BaseButton
            :loading="isSaving"
            :disabled="isSaving"
            variant="primary"
            type="submit"
            class="hidden sm:flex"
          >
            <template #left="slotProps">
              <BaseIcon
                v-if="!isSaving"
                name="ArrowDownOnSquareIcon"
                :class="slotProps.class"
              />
            </template>
            {{
              isEdit
                ? $t('payments.update_payment')
                : $t('payments.save_payment')
            }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <BaseCard>
        <BaseInputGrid>
          <BaseInputGroup
            :label="$t('payments.date')"
            :content-loading="isLoadingContent"
            required
            :error="
              v$.currentPayment.payment_date.$error &&
              v$.currentPayment.payment_date.$errors[0].$message
            "
          >
            <BaseDatePicker
              v-model="paymentStore.currentPayment.payment_date"
              :content-loading="isLoadingContent"
              :calendar-button="true"
              calendar-button-icon="calendar"
              :invalid="v$.currentPayment.payment_date.$error"
              @update:modelValue="v$.currentPayment.payment_date.$touch()"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('payments.payment_number')"
            :content-loading="isLoadingContent"
            required
          >
            <BaseInput
              v-model="paymentStore.currentPayment.payment_number"
              :content-loading="isLoadingContent"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('payments.customer')"
            :error="
              v$.currentPayment.customer_id.$error &&
              v$.currentPayment.customer_id.$errors[0].$message
            "
            :content-loading="isLoadingContent"
            required
          >
            <BaseCustomerSelectInput
              :key="paymentStore.currentPayment.customer_id"
              v-model="paymentStore.currentPayment.customer_id"
              :content-loading="isLoadingContent"
              v-if="!isLoadingContent"
              :invalid="v$.currentPayment.customer_id.$error"
              :placeholder="$t('customers.select_a_customer')"
              show-action
              @update:modelValue="
                selectNewCustomer(paymentStore.currentPayment.customer_id)
              "
            />
          </BaseInputGroup>

          <BaseInputGroup
            v-if="isEdit || invoiceList.length === 0"
            :content-loading="isLoadingContent"
            :label="$t('payments.invoice')"
            :help-text="
              selectedInvoice
                ? `${t('payments.amount_due')}: ${
                    paymentStore.currentPayment.maxPayableAmount / 100
                  }`
                : ''
            "
          >
            <BaseMultiselect
              v-model="paymentStore.currentPayment.invoice_id"
              :content-loading="isLoadingContent"
              value-prop="id"
              track-by="invoice_number"
              label="invoice_number"
              :options="invoiceList"
              :loading="isLoadingInvoices"
              :placeholder="$t('invoices.select_invoice')"
              @select="onSelectInvoice"
            >
              <template #singlelabel="{ value }">
                <div class="absolute left-3.5">
                  {{ value.invoice_number }} ({{
                    utils.formatMoney(value.total, value.customer.currency)
                  }})
                </div>
              </template>

              <template #option="{ option }">
                {{ option.invoice_number }} ({{
                  utils.formatMoney(option.total, option.customer.currency)
                }})
              </template>
            </BaseMultiselect>
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('payments.amount')"
            :content-loading="isLoadingContent"
            :error="
              v$.currentPayment.amount.$error &&
              v$.currentPayment.amount.$errors[0].$message
            "
            required
          >
            <div class="relative w-full">
              <BaseMoney
                :key="paymentStore.currentPayment.currency"
                v-model="amount"
                :currency="paymentStore.currentPayment.currency"
                :content-loading="isLoadingContent"
                :invalid="v$.currentPayment.amount.$error"
                :disabled="!isEdit && invoiceList.length > 0"
                @update:modelValue="v$.currentPayment.amount.$touch()"
              />
            </div>
          </BaseInputGroup>

          <BaseInputGroup
            v-if="isEdit || invoiceList.length === 0"
            label="TDS Amount"
            :content-loading="isLoadingContent"
          >
            <BaseMoney
              v-model="tdsAmount"
              :currency="paymentStore.currentPayment.currency"
              :content-loading="isLoadingContent"
            />
          </BaseInputGroup>

          <BaseInputGroup
            v-if="isEdit || invoiceList.length === 0"
            label="Deduction Amount"
            :content-loading="isLoadingContent"
          >
            <BaseMoney
              v-model="deductionAmount"
              :currency="paymentStore.currentPayment.currency"
              :content-loading="isLoadingContent"
            />
          </BaseInputGroup>

          <BaseInputGroup
            v-if="isEdit || invoiceList.length === 0"
            label="Invoice Paid Status"
            :content-loading="isLoadingContent"
          >
            <BaseMultiselect
              v-model="paymentStore.currentPayment.invoice_paid_status"
              :content-loading="isLoadingContent"
              :options="invoicePaidStatusOptions"
              label="label"
              value-prop="value"
              track-by="value"
              :disabled="!hasInvoiceDeduction"
              placeholder="Select status"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :content-loading="isLoadingContent"
            :label="$t('payments.payment_mode')"
          >
            <BaseMultiselect
              v-model="paymentStore.currentPayment.payment_method_id"
              :content-loading="isLoadingContent"
              label="name"
              value-prop="id"
              track-by="name"
              :options="paymentStore.paymentModes"
              :placeholder="$t('payments.select_payment_mode')"
              searchable
            >
              <template #action>
                <BaseSelectAction @click="addPaymentMode">
                  <BaseIcon
                    name="PlusIcon"
                    class="h-4 mr-2 -ml-2 text-center text-primary-400"
                  />
                  {{ $t('settings.payment_modes.add_payment_mode') }}
                </BaseSelectAction>
              </template>
            </BaseMultiselect>
          </BaseInputGroup>

          <ExchangeRateConverter
            :store="paymentStore"
            store-prop="currentPayment"
            :v="v$.currentPayment"
            :is-loading="isLoadingContent"
            :is-edit="isEdit"
            :customer-currency="paymentStore.currentPayment.currency_id"
          />
        </BaseInputGrid>

        <!-- Payment Custom Fields -->
        <PaymentCustomFields
          type="Payment"
          :is-edit="isEdit"
          :is-loading="isLoadingContent"
          :store="paymentStore"
          store-prop="currentPayment"
          :custom-field-scope="paymentValidationScope"
          class="mt-6"
        />

        <!-- Bulk Invoice Allocation Table -->
        <div v-if="!isEdit && invoiceList.length > 0" class="mt-8">
          <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-4">
            <h3 class="text-lg font-semibold text-gray-900">
              Invoice Allocations
            </h3>
            <div class="flex items-center gap-2">
              <label class="text-sm font-medium text-gray-750">Total Amount Received:</label>
              <div class="w-44">
                <BaseMoney
                  v-model="totalAmountReceived"
                  :currency="paymentStore.currentPayment.currency"
                  placeholder="Enter total amount"
                  @update:modelValue="onTotalAmountReceivedChange"
                />
              </div>
            </div>
          </div>

          <div class="overflow-x-auto border border-gray-200 rounded-lg shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="w-12 px-6 py-3 text-left">
                    <input
                      type="checkbox"
                      :checked="isAllSelected"
                      @change="toggleSelectAllInvoices"
                      class="rounded text-primary-600 focus:ring-primary-500 h-4 w-4 border-gray-300 cursor-pointer"
                    />
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Invoice
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    Due Amount
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-40">
                    Amount to Pay
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-36">
                    TDS Amount
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-36">
                    Deductions
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider w-40">
                    Paid Status
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="invoice in invoiceList" :key="invoice.id" :class="{'bg-primary-50/10': invoice.selected}">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <input
                      type="checkbox"
                      v-model="invoice.selected"
                      @change="toggleInvoiceSelection(invoice)"
                      class="rounded text-primary-600 focus:ring-primary-500 h-4 w-4 border-gray-300 cursor-pointer"
                    />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-semibold text-gray-900">
                      {{ invoice.invoice_number }}
                    </div>
                    <div class="text-xs text-gray-500">
                      {{ invoice.formattedInvoiceDate }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ utils.formatMoney(invoice.due_amount, paymentStore.currentPayment.currency) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <BaseMoney
                      v-model="invoice.amount_to_pay"
                      :currency="paymentStore.currentPayment.currency"
                      @update:modelValue="onAmountToPayChange(invoice)"
                    />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <BaseMoney
                      v-model="invoice.tds_amount"
                      :currency="paymentStore.currentPayment.currency"
                      @update:modelValue="onAllocationChange"
                    />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <BaseMoney
                      v-model="invoice.deduction_amount"
                      :currency="paymentStore.currentPayment.currency"
                      @update:modelValue="onAllocationChange"
                    />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <BaseMultiselect
                      v-model="invoice.invoice_paid_status"
                      :options="invoicePaidStatusOptions"
                      label="label"
                      value-prop="value"
                      track-by="value"
                      :disabled="!((invoice.tds_amount || 0) > 0 || (invoice.deduction_amount || 0) > 0)"
                      placeholder="Status"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Payment Note field -->
        <div class="relative mt-6">
          <div
            class="
              z-20
              float-right
              text-sm
              font-semibold
              leading-5
              text-primary-400
            "
          >
            <SelectNotePopup type="Payment" @select="onSelectNote" />
          </div>

          <label class="mb-4 text-sm font-medium text-gray-800">
            {{ $t('estimates.notes') }}
          </label>

          <BaseCustomInput
            v-model="paymentStore.currentPayment.notes"
            :content-loading="isLoadingContent"
            :fields="PaymentFields"
            class="mt-1"
          />
        </div>

        <BaseButton
          :loading="isSaving"
          :content-loading="isLoadingContent"
          variant="primary"
          type="submit"
          class="flex justify-center w-full mt-4 sm:hidden md:hidden"
        >
          <template #left="slotProps">
            <BaseIcon
              v-if="!isSaving"
              name="ArrowDownOnSquareIcon"
              :class="slotProps.class"
            />
          </template>
          {{
            isEdit ? $t('payments.update_payment') : $t('payments.save_payment')
          }}
        </BaseButton>
      </BaseCard>
    </form>
  </BasePage>
</template>

<script setup>
import ExchangeRateConverter from '@/scripts/admin/components/estimate-invoice-common/ExchangeRateConverter.vue'

import {
  ref,
  reactive,
  computed,
  inject,
  watch,
  onBeforeUnmount,
} from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  required,
  numeric,
  helpers,
  between,
  requiredIf,
  decimal,
} from '@vuelidate/validators'

import useVuelidate from '@vuelidate/core'
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import { usePaymentStore } from '@/scripts/admin/stores/payment'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useCustomFieldStore } from '@/scripts/admin/stores/custom-field'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useModalStore } from '@/scripts/stores/modal'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useGlobalStore } from '@/scripts/admin/stores/global'

import SelectNotePopup from '@/scripts/admin/components/SelectNotePopup.vue'
import PaymentCustomFields from '@/scripts/admin/components/custom-fields/CreateCustomFields.vue'
import PaymentModeModal from '@/scripts/admin/components/modal-components/PaymentModeModal.vue'

const route = useRoute()
const router = useRouter()

const paymentStore = usePaymentStore()
const notificationStore = useNotificationStore()
const customerStore = useCustomerStore()
const customFieldStore = useCustomFieldStore()
const companyStore = useCompanyStore()
const modalStore = useModalStore()
const invoiceStore = useInvoiceStore()
const globalStore = useGlobalStore()

const utils = inject('utils')
const { t } = useI18n()

let isSaving = ref(false)
let isLoadingInvoices = ref(false)
let invoiceList = ref([])
const selectedInvoice = ref(null)
const urlInvoice = ref(null)
const lastLoadedCustomerId = ref(null)
const totalAmountReceived = ref(0)
const isFetchingByNumber = ref(false)

const isAllSelected = computed(() => {
  return invoiceList.value.length > 0 && invoiceList.value.every((inv) => inv.selected)
})

const computedMaxPayableAmount = computed(() => {
  if (!isEdit.value && invoiceList.value.length > 0) {
    const selectedInvoices = invoiceList.value.filter(inv => inv.selected)
    if (selectedInvoices.length > 0) {
      return selectedInvoices.reduce((sum, inv) => sum + inv.due_amount, 0)
    }
  }
  return paymentStore.currentPayment.maxPayableAmount
})

const paymentValidationScope = 'newEstimate'

const PaymentFields = reactive([
  'customer',
  'company',
  'customerCustom',
  'payment',
  'paymentCustom',
])

const amount = computed({
  get: () => paymentStore.currentPayment.amount / 100,
  set: (value) => {
    paymentStore.currentPayment.amount = Math.round(value * 100)
  },
})

const tdsAmount = computed({
  get: () => (paymentStore.currentPayment.tds_amount || 0) / 100,
  set: (value) => {
    paymentStore.currentPayment.tds_amount = Math.round((value || 0) * 100)
  },
})

const deductionAmount = computed({
  get: () => (paymentStore.currentPayment.deduction_amount || 0) / 100,
  set: (value) => {
    paymentStore.currentPayment.deduction_amount = Math.round((value || 0) * 100)
  },
})

const hasInvoiceDeduction = computed(() => {
  return (
    (paymentStore.currentPayment.tds_amount || 0) > 0 ||
    (paymentStore.currentPayment.deduction_amount || 0) > 0
  )
})

const invoicePaidStatusOptions = computed(() => [
  { label: t('invoices.unpaid'), value: 'UNPAID' },
  { label: t('invoices.partially_paid'), value: 'PARTIALLY_PAID' },
  { label: t('invoices.paid'), value: 'PAID' },
])

const isLoadingContent = computed(() => paymentStore.isFetchingInitialData)

const isEdit = computed(() => route.name === 'payments.edit')

const pageTitle = computed(() => {
  if (isEdit.value) {
    return t('payments.edit_payment')
  }
  return t('payments.new_payment')
})

const rules = computed(() => {
  return {
    currentPayment: {
      customer_id: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      payment_date: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      amount: {
        required: helpers.withMessage(t('validation.required'), required),
        between: helpers.withMessage(
          t('validation.payment_greater_than_due_amount'),
          between(0, computedMaxPayableAmount.value)
        ),
      },
      exchange_rate: {
        required: requiredIf(function () {
          helpers.withMessage(t('validation.required'), required)
          return paymentStore.showExchangeRate
        }),
        decimal: helpers.withMessage(
          t('validation.valid_exchange_rate'),
          decimal
        ),
      },
    },
  }
})

const v$ = useVuelidate(rules, paymentStore, {
  $scope: paymentValidationScope,
})

// Reset State on Create
paymentStore.resetCurrentPayment()

if (route.query.customer) {
  paymentStore.currentPayment.customer_id = route.query.customer
}

async function initPaymentData() {
  await paymentStore.fetchPaymentInitialData(isEdit.value)

  if (route.params.id && !isEdit.value) {
    await setInvoiceFromUrl()
  }
}
initPaymentData()

watch(
  () => paymentStore.currentPayment.customer_id,
  (customerId) => {
    if (customerId) {
      onCustomerChange(customerId)
    }
  },
  { immediate: true }
)

watch(
  hasInvoiceDeduction,
  (hasDeduction) => {
    if (!hasDeduction) {
      paymentStore.currentPayment.invoice_paid_status = ''
    } else if (!paymentStore.currentPayment.invoice_paid_status) {
      paymentStore.currentPayment.invoice_paid_status = 'PAID'
    }
  }
)

watch(
  () => paymentStore.currentPayment.payment_number,
  async (val) => {
    if (route.params.id) return

    const cleanVal = String(val || '').trim()
    if (cleanVal.length < 3 || isFetchingByNumber.value) {
      return
    }

    if (urlInvoice.value && urlInvoice.value.invoice_number === cleanVal) {
      return
    }

    try {
      isFetchingByNumber.value = true
      const res = await invoiceStore.fetchInvoices({
        invoice_number: cleanVal,
        limit: 1,
      })
      const match = res.data.data?.[0]
      if (match && match.invoice_number === cleanVal) {
        const fullRes = await invoiceStore.fetchInvoice(match.id)
        await applyInvoiceToPayment(fullRes.data.data)
      }
    } catch (err) {
      console.error('Failed to fetch invoice by number:', err)
    } finally {
      isFetchingByNumber.value = false
    }
  }
)

async function addPaymentMode() {
  modalStore.openModal({
    title: t('settings.payment_modes.add_payment_mode'),
    componentName: 'PaymentModeModal',
  })
}

function onSelectNote(data) {
  paymentStore.currentPayment.notes = '' + data.notes
}

async function applyInvoiceToPayment(invoice) {
  urlInvoice.value = invoice

  let customerId = invoice.customer.id

  const fields = invoice.fields || []
  const gstTaxThroughField = fields.find(
    (f) => f.custom_field?.label === 'GST Tax Through' || f.custom_field?.name === 'GST Tax Through'
  )
  const gstTaxThroughValue = String(gstTaxThroughField?.default_answer || gstTaxThroughField?.string_answer || '').trim()

  if (gstTaxThroughValue) {
    const upperVal = gstTaxThroughValue.toUpperCase()
    if (upperVal === 'CONSIGNEE') {
      if (invoice.consignee_customer_id) {
        customerId = invoice.consignee_customer_id
      }
    } else if (upperVal === 'CONSIGNOR') {
      customerId = invoice.customer.id
    } else {
      try {
        const customersRes = await customerStore.fetchCustomers({
          search: gstTaxThroughValue,
          limit: 10,
        })
        const foundCustomer = customersRes.data.data.find(
          (c) => String(c.name || c.display_name).trim().toLowerCase() === gstTaxThroughValue.toLowerCase()
        )
        if (foundCustomer) {
          customerId = foundCustomer.id
        }
      } catch (err) {
        console.error('Failed to fetch customer for GST Tax Through:', err)
      }
    }
  }

  if (customerId) {
    try {
      let resCustomer = await customerStore.fetchCustomer(customerId)
      customerStore.editCustomer = resCustomer.data.data
    } catch (err) {
      console.error('Failed to pre-fetch customer details:', err)
    }
  }

  paymentStore.currentPayment.customer_id = customerId
  paymentStore.currentPayment.invoice_id = invoice.id
  paymentStore.currentPayment.payment_number = invoice.invoice_number
}

async function setInvoiceFromUrl() {
  let res = await invoiceStore.fetchInvoice(route?.params?.id)
  const invoice = res.data.data

  if (invoice.template_name !== 'office_invoice') {
    router.push('/admin/payments/create')
    return
  }

  await applyInvoiceToPayment(invoice)
}

async function onSelectInvoice(id) {
  if (id) {
    selectedInvoice.value = invoiceList.value.find((inv) => inv.id === id)

    amount.value = selectedInvoice.value.due_amount / 100
    paymentStore.currentPayment.maxPayableAmount =
      selectedInvoice.value.due_amount
  }
}

function onCustomerChange(customer_id) {
  if (customer_id) {
    if (lastLoadedCustomerId.value === customer_id && invoiceList.value.length) {
      return
    }

    lastLoadedCustomerId.value = customer_id

    let data = {
      customer_id: customer_id,
      status: 'DUE',
      template_name: 'office_invoice',
      limit: 'all',
    }

    if (isEdit.value) {
      data.status = ''
    }

    isLoadingInvoices.value = true

    const isRecordPaymentFromInvoice =
      route.params.id &&
      !isEdit.value &&
      urlInvoice.value &&
      paymentStore.currentPayment.invoice_id === urlInvoice.value.id

    const promises = [customerStore.fetchCustomer(customer_id)]
    if (!isRecordPaymentFromInvoice) {
      promises.push(invoiceStore.fetchInvoices(data))
    }

    Promise.all(promises)
      .then(async (results) => {
        const res2 = results[0]
        const res1 = isRecordPaymentFromInvoice ? null : results[1]

        if (res1) {
          invoiceList.value = res1.data.data.map((inv) => ({
            ...inv,
            selected: false,
            amount_to_pay: 0,
            tds_amount: 0,
            deduction_amount: 0,
            invoice_paid_status: 'PAID',
          }))
        } else if (isRecordPaymentFromInvoice && urlInvoice.value) {
          const mappedInv = {
            ...urlInvoice.value,
            selected: true,
            amount_to_pay: urlInvoice.value.due_amount / 100,
            tds_amount: 0,
            deduction_amount: 0,
            invoice_paid_status: 'PAID',
          }
          invoiceList.value = [mappedInv]
        }

        if (res2 && res2.data) {
          paymentStore.currentPayment.selectedCustomer = res2.data.data
          paymentStore.currentPayment.customer = res2.data.data
          paymentStore.currentPayment.currency = res2.data.data.currency
          customerStore.editCustomer = res2.data.data
        }

        if (paymentStore.currentPayment.invoice_id) {
          selectedInvoice.value = invoiceList.value.find(
            (inv) => inv.id === paymentStore.currentPayment.invoice_id
          )

          if (!selectedInvoice.value && urlInvoice.value && urlInvoice.value.id === paymentStore.currentPayment.invoice_id) {
            const mappedInv = {
              ...urlInvoice.value,
              selected: true,
              amount_to_pay: urlInvoice.value.due_amount / 100,
              tds_amount: 0,
              deduction_amount: 0,
              invoice_paid_status: 'PAID',
            }
            invoiceList.value.push(mappedInv)
            selectedInvoice.value = mappedInv
          }

          if (selectedInvoice.value) {
            selectedInvoice.value.selected = true
            selectedInvoice.value.amount_to_pay = selectedInvoice.value.due_amount / 100
          }

          if (selectedInvoice.value) {
            paymentStore.currentPayment.maxPayableAmount =
              selectedInvoice.value.due_amount +
              paymentStore.currentPayment.amount +
              (paymentStore.currentPayment.tds_amount || 0) +
              (paymentStore.currentPayment.deduction_amount || 0)

            if (amount.value === 0) {
              amount.value = selectedInvoice.value.due_amount / 100
            }
          }
        }

        if (isEdit.value) {
          // remove all invoices that are paid except currently selected invoice
          invoiceList.value = invoiceList.value.filter((v) => {
            return (
              v.due_amount > 0 || v.id == paymentStore.currentPayment.invoice_id
            )
          })
        }

        isLoadingInvoices.value = false
      })
      .catch((error) => {
        lastLoadedCustomerId.value = null
        isLoadingInvoices.value = false
        console.error(error, 'error')
      })
  }
}
onBeforeUnmount(() => {
  paymentStore.resetCurrentPayment()
  invoiceList.value = []
  customerStore.editCustomer = null
})

async function submitPaymentData() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return false
  }

  isSaving.value = true

  let data = {
    ...paymentStore.currentPayment,
  }

  if (!isEdit.value && invoiceList.value.length > 0) {
    const selectedInvoices = invoiceList.value.filter((inv) => inv.selected)
    if (selectedInvoices.length > 0) {
      data.allocations = selectedInvoices.map((inv) => ({
        invoice_id: inv.id,
        amount: Math.round((inv.amount_to_pay || 0) * 100),
        tds_amount: Math.round((inv.tds_amount || 0) * 100),
        deduction_amount: Math.round((inv.deduction_amount || 0) * 100),
        invoice_paid_status: inv.invoice_paid_status || 'PAID',
      }))
    }
  }

  let response = null

  try {
    const action = isEdit.value
      ? paymentStore.updatePayment
      : paymentStore.addPayment

    response = await action(data)

    if (data.allocations && data.allocations.length > 0) {
      router.push('/admin/payments')
    } else {
      router.push(`/admin/payments/${response.data.data.id}/view`)
    }
  } catch (err) {
    isSaving.value = false
  }
}

function onTotalAmountReceivedChange(value) {
  let remainingCents = Math.round((value || 0) * 100)
  
  invoiceList.value.forEach((inv) => {
    if (remainingCents <= 0) {
      inv.selected = false
      inv.amount_to_pay = 0
      inv.tds_amount = 0
      inv.deduction_amount = 0
      return
    }
    
    inv.selected = true
    if (remainingCents >= inv.due_amount) {
      inv.amount_to_pay = inv.due_amount / 100
      remainingCents -= inv.due_amount
    } else {
      inv.amount_to_pay = remainingCents / 100
      remainingCents = 0
    }
    inv.tds_amount = 0
    inv.deduction_amount = 0
  })
  
  onAllocationChange()
}

function toggleInvoiceSelection(invoice) {
  if (invoice.selected) {
    invoice.amount_to_pay = invoice.due_amount / 100
  } else {
    invoice.amount_to_pay = 0
    invoice.tds_amount = 0
    invoice.deduction_amount = 0
  }
  onAllocationChange()
}

function toggleSelectAllInvoices() {
  const selectAll = !isAllSelected.value
  invoiceList.value.forEach((inv) => {
    inv.selected = selectAll
    if (selectAll) {
      inv.amount_to_pay = inv.due_amount / 100
    } else {
      inv.amount_to_pay = 0
      inv.tds_amount = 0
      inv.deduction_amount = 0
    }
  })
  onAllocationChange()
}

function onAmountToPayChange(invoice) {
  if ((invoice.amount_to_pay || 0) > 0) {
    invoice.selected = true
  } else {
    invoice.selected = false
    invoice.tds_amount = 0
    invoice.deduction_amount = 0
  }
  onAllocationChange()
}

function onAllocationChange() {
  let total = 0
  invoiceList.value.forEach((inv) => {
    if (inv.selected) {
      total += Math.round((inv.amount_to_pay || 0) * 100)
    }
  })
  paymentStore.currentPayment.amount = total
}

function selectNewCustomer(id) {
  let params = {
    userId: id,
  }

  if (route.params.id) params.model_id = route.params.id

  paymentStore.currentPayment.invoice_id = selectedInvoice.value = null
  paymentStore.currentPayment.amount = 0
  totalAmountReceived.value = 0
  invoiceList.value = []
  lastLoadedCustomerId.value = null
  paymentStore.getNextNumber(params, true)
}
</script>
