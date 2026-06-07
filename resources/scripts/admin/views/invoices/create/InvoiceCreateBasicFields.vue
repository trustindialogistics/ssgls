<template>
  <div class="grid grid-cols-12 gap-8 mt-6 mb-8">
    <BaseCustomerSelectPopup
      v-model="invoiceStore.newInvoice.customer"
      :valid="v.customer_id"
      :content-loading="isLoading"
      type="invoice"
      :label="isTransportReceiptTemplate ? customerLabel : ''"
      :class="isTransportReceiptTemplate ? 'order-2 col-span-12 lg:col-span-4 pr-0' : 'col-span-12 lg:col-span-5 pr-0'"
    />

    <div
      v-if="isLrReceiptTemplate"
      class="order-1 col-span-12 lg:col-span-4 pr-0"
    >
      <BaseContentPlaceholders v-if="isLoading">
        <BaseContentPlaceholdersBox
          :rounded="true"
          class="w-full"
          style="min-height: 170px"
        />
      </BaseContentPlaceholders>
      <div v-else>
        <div
          v-if="selectedConsignor"
          class="flex flex-col p-4 bg-white border border-gray-200 border-solid min-h-[170px] rounded-md"
        >
          <div class="flex relative justify-between gap-3 mb-2">
            <BaseText
              :text="selectedConsignor.name || selectedConsignor.display_name"
              class="flex-1 text-base font-medium text-left text-gray-900"
            />
            <div class="flex flex-wrap justify-end gap-x-4 gap-y-2">
              <a
                class="relative my-0 text-sm flex items-center font-medium cursor-pointer text-primary-500"
                @click.stop="editConsignor"
              >
                <BaseIcon name="PencilIcon" class="text-gray-500 h-4 w-4 mr-1" />
                {{ $t('general.edit') }}
              </a>
              <a
                class="relative my-0 text-sm flex items-center font-medium cursor-pointer text-primary-500"
                @click="resetConsignor"
              >
                <BaseIcon name="XCircleIcon" class="text-gray-500 h-4 w-4 mr-1" />
                {{ $t('general.deselect') }}
              </a>
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-8 mt-2">
            <div v-if="selectedConsignor.billing" class="flex flex-col">
              <label class="mb-1 text-sm font-medium text-left text-gray-400 uppercase whitespace-nowrap">
                Bill To
              </label>
              <div class="flex flex-col flex-1 p-0 text-left">
                <label
                  v-for="(line, index) in formatAddressLines(selectedConsignor.billing)"
                  :key="`billing-${index}-${line}`"
                  class="relative w-11/12 text-sm truncate"
                >
                  {{ line }}
                </label>
              </div>
            </div>
            <div v-if="selectedConsignor.shipping" class="flex flex-col">
              <label class="mb-1 text-sm font-medium text-left text-gray-400 uppercase whitespace-nowrap">
                Ship To
              </label>
              <div class="flex flex-col flex-1 p-0 text-left">
                <label
                  v-for="(line, index) in formatAddressLines(selectedConsignor.shipping)"
                  :key="`shipping-${index}-${line}`"
                  class="relative w-11/12 text-sm truncate"
                >
                  {{ line }}
                </label>
              </div>
            </div>
          </div>
        </div>

        <Popover v-else v-slot="{ open }" class="relative flex flex-col rounded-md">
          <PopoverButton
            :class="{
              'focus:ring-2 focus:ring-primary-400': !open,
            }"
            class="w-full outline-hidden rounded-md"
            @click="ensureConsignorsLoaded"
          >
            <div class="relative flex justify-center px-0 p-0 py-16 bg-white border border-gray-200 border-solid rounded-md min-h-[170px]">
              <BaseIcon
                name="UserIcon"
                class="flex justify-center !w-10 !h-10 p-2 mr-5 text-sm text-white bg-gray-200 rounded-full font-base"
              />
              <div class="mt-1">
                <label class="text-lg font-medium text-gray-900">Consignor</label>
              </div>
            </div>
          </PopoverButton>

          <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-1 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-1 opacity-0"
          >
            <div v-if="open" class="absolute min-w-full z-10">
              <PopoverPanel
                v-slot="{ close }"
                focus
                static
                class="overflow-hidden rounded-md shadow-lg ring-1 ring-black/5 bg-white"
              >
                <div class="relative">
                  <BaseInput
                    v-model="consignorSearch"
                    container-class="m-4"
                    :placeholder="$t('general.search')"
                    type="text"
                    icon="search"
                    @update:modelValue="debounceSearchConsignors"
                  />

                  <ul class="max-h-80 flex flex-col overflow-auto list border-t border-gray-200">
                    <li
                      v-for="customer in customerStore.customers"
                      :key="customer.id"
                      class="flex px-6 py-2 border-b border-gray-200 border-solid cursor-pointer hover:cursor-pointer hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100"
                      @click="selectConsignor(customer, close)"
                    >
                      <div class="flex items-center justify-center h-10 w-10 mr-4 rounded-full bg-gray-100 uppercase text-primary-500">
                        {{ (customer.name || customer.display_name || 'C').charAt(0) }}
                      </div>
                      <div class="flex-1 flex flex-col text-left">
                        <span class="text-sm font-medium text-gray-900">
                          {{ customer.name || customer.display_name }}
                        </span>
                        <span class="text-xs text-gray-500">
                          {{ customer.phone || customer.tax_id }}
                        </span>
                      </div>
                    </li>
                  </ul>

                  <button
                    type="button"
                    class="flex items-center justify-center w-full px-6 py-3 bg-gray-100 cursor-pointer"
                    @click="openCustomerModal(close)"
                  >
                    <BaseIcon name="PlusIcon" class="h-5 text-primary-400" />
                    <label class="m-0 ml-3 text-sm leading-none cursor-pointer font-base text-primary-400">
                      {{ $t('customers.add_new_customer') }}
                    </label>
                  </button>
                </div>
              </PopoverPanel>
            </div>
          </transition>
        </Popover>
      </div>
    </div>

    <BaseInputGrid :class="isTransportReceiptTemplate ? 'order-3 col-span-12 lg:col-span-4' : 'col-span-12 lg:col-span-7'">
      <BaseInputGroup
        :label="isTransportReceiptTemplate ? 'Date' : $t('invoices.invoice_date')"
        :content-loading="isLoading"
        required
        :error="v.invoice_date.$error && v.invoice_date.$errors[0].$message"
      >
        <BaseDatePicker
          v-model="invoiceStore.newInvoice.invoice_date"
          :content-loading="isLoading"
          :calendar-button="true"
          calendar-button-icon="calendar"
          :enableTime="enableTime"
          :time24hr="time24h"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('invoices.due_date')"
        :content-loading="isLoading"
      >
        <BaseDatePicker
          v-model="invoiceStore.newInvoice.due_date"
          :content-loading="isLoading"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="isTransportReceiptTemplate ? numberLabel : $t('invoices.invoice_number')"
        :content-loading="isLoading"
        :error="v.invoice_number.$error && v.invoice_number.$errors[0].$message"
        required
      >
        <BaseInput
          v-model="invoiceStore.newInvoice.invoice_number"
          :content-loading="isLoading"
          @input="v.invoice_number.$touch()"
        />
      </BaseInputGroup>

      <BaseInputGroup
        v-if="gstTaxThroughField"
        :label="gstTaxThroughField.label"
        :required="gstTaxThroughField.is_required ? true : false"
      >
        <BaseMultiselect
          v-model="gstTaxThroughField.value"
          :options="gstTaxThroughField.options"
          label="name"
          value-prop="name"
        />
      </BaseInputGroup>

      <ExchangeRateConverter
        v-if="!isTransportReceiptTemplate"
        :store="invoiceStore"
        store-prop="newInvoice"
        :v="v"
        :is-loading="isLoading"
        :is-edit="isEdit"
        :customer-currency="invoiceStore.newInvoice.currency_id"
      />
    </BaseInputGrid>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import { useDebounceFn } from '@vueuse/core'
import ExchangeRateConverter from '@/scripts/admin/components/estimate-invoice-common/ExchangeRateConverter.vue'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import { useModalStore } from '@/scripts/stores/modal'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useRouter } from 'vue-router'
import { useLorryPartyProfileStore } from '@/scripts/admin/stores/lorry-party-profile'

const props = defineProps({
  v: {
    type: Object,
    default: null,
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
})

const invoiceStore = useInvoiceStore()
const companyStore = useCompanyStore()
const customerStore = useCustomerStore()
const modalStore = useModalStore()
const globalStore = useGlobalStore()
const router = useRouter()
const selectedConsignor = ref(null)
const consignorSearch = ref('')
const isInitialized = ref(false)

const enableTime = computed(() => {
  return (
    companyStore.selectedCompanySettings.invoice_use_time === 'YES'
  );
})
const time24h = computed(() => {
  return (
    companyStore.selectedCompanySettings.carbon_time_format.indexOf('H') > -1
  );
})

const isLrReceiptTemplate = computed(() => {
  return invoiceStore.newInvoice.template_name === 'lr_receipt'
})
const isLorryReceiptTemplate = computed(() => {
  return invoiceStore.newInvoice.template_name === 'lorry_receipt'
})
const isTransportReceiptTemplate = computed(() => {
  return isLrReceiptTemplate.value || isLorryReceiptTemplate.value
})
const customerLabel = computed(() => {
  if (isLorryReceiptTemplate.value) {
    return 'Party'
  }
  if (isLrReceiptTemplate.value) {
    return 'Consignee'
  }
  return 'Consignor'
})
const numberLabel = computed(() => isLorryReceiptTemplate.value ? 'Challan No.' : 'Docket No.')
const gstTaxThroughField = computed(() => {
  return getInvoiceField('GST Tax Through')
})

const debounceSearchConsignors = useDebounceFn(() => {
  fetchConsignors(consignorSearch.value)
}, 500)

function getInvoiceField(label) {
  return invoiceStore.newInvoice.customFields?.find((_field) => _field.label === label)
}

function setInvoiceField(label, value) {
  const field = getInvoiceField(label)

  if (field) {
    field.value = value || ''
  }
}

function compact(value) {
  return value ? String(value).trim() : ''
}

function formatAddressLines(address) {
  if (!address) {
    return []
  }

  const cityState = [compact(address.city), compact(address.state)]
    .filter(Boolean)
    .join(', ')
  const cityStateZip = [cityState, compact(address.zip)].filter(Boolean).join(' ')

  return [
    compact(address.name),
    compact(address.address_street_1),
    compact(address.address_street_2),
    cityStateZip,
  ].filter(Boolean)
}

function formatPartyDetails(customer) {
  if (!customer) {
    return ''
  }

  const address = customer.billing || customer.shipping
  const lines = [
    compact(customer.name || customer.display_name),
    ...formatAddressLines(address).filter(
      (line) => line !== compact(customer.name || customer.display_name)
    ),
  ]

  return lines.filter(Boolean).join('\n')
}

function syncConsigneeFields(customer) {
  setInvoiceField('Consignee', formatPartyDetails(customer))
  setInvoiceField('Consignee Phone No', customer?.phone)
  setInvoiceField('Consignee GST No', customer?.tax_id)
}

function syncConsignorFields(customer) {
  setInvoiceField('Consignor', formatPartyDetails(customer))
  setInvoiceField('Consignor Phone No', customer?.phone)
  setInvoiceField('Consignor GST No', customer?.tax_id)
}

function partyName(customer) {
  return compact(customer?.name || customer?.display_name)
}

async function syncLorryPartyPaymentFields(customer) {
  setInvoiceField('Paid To', partyName(customer))
  setInvoiceField('Final Paid To', partyName(customer))

  if (customer && customer.id) {
    try {
      const lorryPartyProfileStore = useLorryPartyProfileStore()
      const response = await lorryPartyProfileStore.fetchProfiles({
        customer_id: customer.id,
        limit: 'all',
      })
      const profiles = response.data?.data || []
      const ownerProfile = profiles.find((p) => p.type === 'OWNER')
      if (ownerProfile) {
        setInvoiceField('Owner Bank Account No', ownerProfile.bank_account_no)
      }
    } catch (e) {
      console.error('Failed to sync lorry party bank account no', e)
    }
  }
}

async function selectConsignor(customer, close) {
  const response = await customerStore.fetchCustomer(customer.id)
  selectedConsignor.value = response.data.data
  syncConsignorFields(selectedConsignor.value)
  close()
  consignorSearch.value = ''
}

function resetConsignor() {
  selectedConsignor.value = null
  syncConsignorFields(null)
}

function fetchConsignors(search = '') {
  customerStore.fetchCustomers({
    display_name: search,
    page: 1,
  })
}

function ensureConsignorsLoaded() {
  if (!customerStore.customers.length || customerStore.loadedType !== 'CUSTOMER') {
    customerStore.customers = []
    fetchConsignors()
  }
}

function openCustomerModal(close) {
  close?.()
  globalStore.fetchCurrencies()
  globalStore.fetchCountries()

  modalStore.openModal({
    title: 'Add Customer',
    componentName: 'CustomerModal',
    size: 'lg',
  })
}

async function editConsignor() {
  if (!selectedConsignor.value?.id) {
    return
  }

  await customerStore.fetchCustomer(selectedConsignor.value.id)

  modalStore.openModal({
    title: 'Edit Customer',
    componentName: 'CustomerModal',
    size: 'lg',
  })
}

watch(
  () => invoiceStore.newInvoice.customer,
  (customer) => {
    if (props.isEdit && !isInitialized.value) {
      return
    }

    if (isLrReceiptTemplate.value) {
      syncConsigneeFields(customer)
    }

    if (isLorryReceiptTemplate.value && customer) {
      syncLorryPartyPaymentFields(customer)
    }
  },
  { deep: true }
)

watch(
  () => invoiceStore.newInvoice.customFields,
  () => {
    if (props.isEdit && !isInitialized.value) {
      return
    }

    if (isLrReceiptTemplate.value) {
      syncConsigneeFields(invoiceStore.newInvoice.customer)
      syncConsignorFields(selectedConsignor.value)
    }

    if (isLorryReceiptTemplate.value && invoiceStore.newInvoice.customer) {
      syncLorryPartyPaymentFields(invoiceStore.newInvoice.customer)
    }
  },
  { deep: false }
)

watch(
  isLrReceiptTemplate,
  (isLr) => {
    if (!isLr) {
      selectedConsignor.value = null
    }
  },
  { immediate: true }
)

async function initializeConsignorFromCustomFields() {
  if (!isLrReceiptTemplate.value) {
    isInitialized.value = true
    return
  }

  if (invoiceStore.newInvoice.consignor) {
    selectedConsignor.value = invoiceStore.newInvoice.consignor
    isInitialized.value = true
    return
  }

  const consignorField = getInvoiceField('Consignor')
  if (!consignorField || !consignorField.value) {
    isInitialized.value = true
    return
  }

  // Extract the first line of the custom field value (which is the customer name)
  const lines = consignorField.value.split('\n')
  const name = lines[0]?.trim()

  if (!name) {
    isInitialized.value = true
    return
  }

  try {
    const response = await customerStore.fetchCustomers({
      display_name: name,
      page: 1,
    })

    const customer = response.data?.data?.find(
      (c) => (c.name || c.display_name) === name
    )

    if (customer) {
      const fullCustomerRes = await customerStore.fetchCustomer(customer.id)
      selectedConsignor.value = fullCustomerRes.data.data
    }
  } catch (error) {
    console.error('Failed to initialize consignor customer from custom fields', error)
  } finally {
    isInitialized.value = true
  }
}

watch(
  () => props.isLoading,
  async (loading) => {
    if (!loading && isLrReceiptTemplate.value) {
      if (props.isEdit) {
        await initializeConsignorFromCustomFields()
      } else {
        if (invoiceStore.newInvoice.consignor) {
          selectedConsignor.value = invoiceStore.newInvoice.consignor
          syncConsignorFields(selectedConsignor.value)
        }
        isInitialized.value = true
      }
    } else if (!props.isEdit) {
      isInitialized.value = true
    }
  },
  { immediate: true }
)

</script>
