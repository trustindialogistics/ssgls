<template>
  <BasePage>
    <SendInvoiceModal />
    <UploadPodModal />
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="pageTitle" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="invoiceStore.invoiceTotalCount"
          variant="primary-outline"
          @click="toggleFilter"
        >
          {{ $t('general.filter') }}
          <template #right="slotProps">
            <BaseIcon
              v-if="!showFilters"
              name="FunnelIcon"
              :class="slotProps.class"
            />
            <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
          </template>
        </BaseButton>

        <router-link
          v-if="userStore.hasAbilities(abilities.CREATE_INVOICE)"
          :to="`${basePath}/create`"
        >
          <BaseButton variant="primary" class="ml-4">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ newButtonLabel }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper
      v-show="showFilters"
      :row-on-xl="true"
      @clear="clearFilter"
    >
      <BaseInputGroup :label="isLorryReceiptRoute ? 'Party' : $t('customers.customer', 1)">
        <BaseCustomerSelectInput
          v-model="filters.customer_id"
          :placeholder="$t('customers.type_or_click')"
          value-prop="id"
          label="name"
          :type="isLorryReceiptRoute ? 'OWNER,DRIVER,BROKER' : 'CUSTOMER'"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('invoices.status')">
        <BaseMultiselect
          v-model="filters.status"
          :groups="true"
          :options="status"
          searchable
          :placeholder="$t('general.select_a_status')"
          @update:modelValue="setActiveTab"
          @remove="clearStatusSearch()"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.from')">
        <BaseDatePicker
          v-model="filters.from_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <div
        class="hidden w-8 h-0 mx-4 border border-gray-400 border-solid xl:block"
        style="margin-top: 1.5rem"
      />

      <BaseInputGroup :label="$t('general.to')" class="mt-2">
        <BaseDatePicker
          v-model="filters.to_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="receiptNumberLabel">
        <BaseInput v-model="filters.invoice_number">
          <template #left="slotProps">
            <BaseIcon name="HashtagIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="emptyTitle"
      :description="emptyDescription"
    >
      <MoonwalkerIcon class="mt-5 mb-4" />
      <template
        v-if="userStore.hasAbilities(abilities.CREATE_INVOICE)"
        #actions
      >
        <BaseButton
          variant="primary-outline"
          @click="$router.push(`${basePath}/create`)"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ createButtonLabel }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <div
        class="
          relative
          flex
          items-center
          justify-between
          h-10
          mt-5
          list-none
          border-b-2 border-gray-200 border-solid
        "
      >
        <BaseTabGroup class="-mb-5" @change="setStatusFilter">
          <BaseTab :title="$t('general.all')" filter="" />
          <BaseTab :title="$t('general.draft')" filter="DRAFT" />
          <BaseTab :title="$t('general.sent')" filter="SENT" />
          <BaseTab :title="$t('general.due')" filter="DUE" />
        </BaseTabGroup>

        <BaseDropdown
          v-if="
            invoiceStore.selectedInvoices.length &&
            userStore.hasAbilities(abilities.DELETE_INVOICE)
          "
          class="absolute float-right"
        >
          <template #activator>
            <span
              class="
                flex
                text-sm
                font-medium
                cursor-pointer
                select-none
                text-primary-400
              "
            >
              {{ $t('general.actions') }}
              <BaseIcon name="ChevronDownIcon" />
            </span>
          </template>

          <BaseDropdownItem @click="removeMultipleInvoices">
            <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>

          <template v-if="!isLorryReceiptRoute">
            <BaseDropdownItem @click="downloadBulkLrReceipts">
              <BaseIcon name="ArrowDownTrayIcon" class="mr-3 text-gray-600" />
              {{ $t('general.download_lr') }}
            </BaseDropdownItem>

            <BaseDropdownItem @click="downloadBulkLrReceiptsMultiCopy">
              <BaseIcon name="DocumentDuplicateIcon" class="mr-3 text-gray-600" />
              {{ $t('general.download_multi_lr') }}
            </BaseDropdownItem>
          </template>

          <BaseDropdownItem v-if="isLorryReceiptRoute" @click="downloadBulkLorryReceipts">
            <BaseIcon name="ArrowDownTrayIcon" class="mr-3 text-gray-600" />
            {{ $t('general.download_lorry_receipt') }}
          </BaseDropdownItem>

          <BaseDropdownItem v-if="isLorryReceiptRoute" @click="downloadLorryReceiptWithDocuments">
            <BaseIcon name="DocumentArrowDownIcon" class="mr-3 text-gray-600" />
            Download Lorry Receipt Document
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <BaseTable
        ref="table"
        :data="fetchData"
        :columns="invoiceColumns"
        :placeholder-count="invoiceStore.invoiceTotalCount >= 20 ? 10 : 5"
        :key="tableInstanceKey"
        class="mt-10"
      >
        <template #header>
          <div class="absolute items-center left-6 top-2.5 select-none">
            <BaseCheckbox
              v-model="invoiceStore.selectAllField"
              variant="primary"
              @change="invoiceStore.selectAllInvoices"
            />
          </div>
        </template>

        <template #cell-checkbox="{ row }">
          <div class="relative block">
            <BaseCheckbox
              :id="row.id"
              v-model="selectField"
              :value="row.data.id"
            />
          </div>
        </template>

        <template #cell-name="{ row }">
          <BaseText :text="partyName(row.data)" />
        </template>

        <template #cell-invoice_number="{ row }">
          <router-link
            :to="{ path: `${basePath}/${row.data.id}/view` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.invoice_number }}
          </router-link>
        </template>

        <template #cell-invoice_date="{ row }">
          {{ row.data.formatted_invoice_date }}
        </template>

        <template #cell-status="{ row }">
          <BaseInvoiceStatusBadge :status="row.data.status" class="px-3 py-1">
            <BaseInvoiceStatusLabel :status="row.data.status" />
          </BaseInvoiceStatusBadge>
        </template>

        <template v-if="hasAtleastOneAbility()" #cell-actions="{ row }">
          <InvoiceDropdown
            :row="row.data"
            :table="table"
            :resource-base-path="basePath"
            :after-delete-path="basePath"
            :show-payment-action="false"
          />
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { computed, ref, reactive, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { debouncedWatch } from '@vueuse/core'

import abilities from '@/scripts/admin/stub/abilities'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useDialogStore } from '@/scripts/stores/dialog'
import http from '@/scripts/http'

import MoonwalkerIcon from '@/scripts/components/icons/empty/MoonwalkerIcon.vue'
import InvoiceDropdown from '@/scripts/admin/components/dropdowns/InvoiceIndexDropdown.vue'
import SendInvoiceModal from '@/scripts/admin/components/modal-components/SendInvoiceModal.vue'
import UploadPodModal from '@/scripts/admin/components/modal-components/UploadPodModal.vue'

const { t } = useI18n()
const invoiceStore = useInvoiceStore()
const userStore = useUserStore()
const dialogStore = useDialogStore()
const route = useRoute()

const table = ref(null)
const tableKey = ref(0)
const showFilters = ref(false)
const isRequestOngoing = ref(true)
const activeTab = ref('general.draft')
const isLorryReceiptRoute = computed(() => route.name?.startsWith('lorry-receipts'))
const templateName = computed(() => isLorryReceiptRoute.value ? 'lorry_receipt' : 'lr_receipt')
const tableInstanceKey = computed(() => `${templateName.value}-${tableKey.value}`)
const basePath = computed(() => isLorryReceiptRoute.value ? '/admin/lorry-receipts' : '/admin/lr-receipts')
const pageTitle = computed(() => isLorryReceiptRoute.value ? 'Lorry Receipts' : 'LR Receipts')
const singularTitle = computed(() => isLorryReceiptRoute.value ? 'Lorry Receipt' : 'LR Receipt')
const receiptNumberLabel = computed(() => isLorryReceiptRoute.value ? 'Challan No.' : 'Docket No.')
const newButtonLabel = computed(() => `New ${singularTitle.value}`)
const createButtonLabel = computed(() => `Create ${singularTitle.value}`)
const emptyTitle = computed(() => `No ${pageTitle.value}`)
const emptyDescription = computed(() => `Create your first ${singularTitle.value.toLowerCase()}`)

const status = computed(() => [
  {
    label: t('invoices.status'),
    options: [
      { label: t('general.draft'), value: 'DRAFT' },
      { label: t('general.sent'), value: 'SENT' },
      { label: t('invoices.viewed'), value: 'VIEWED' },
      { label: t('invoices.completed'), value: 'COMPLETED' },
    ],
  },
  {
    label: t('invoices.paid_status'),
    options: [
      { label: t('invoices.unpaid'), value: 'UNPAID' },
      { label: t('invoices.paid'), value: 'PAID' },
      { label: t('invoices.partially_paid'), value: 'PARTIALLY_PAID' },
    ],
  },
])

const filters = reactive({
  customer_id: '',
  status: '',
  from_date: '',
  to_date: '',
  invoice_number: '',
})

const showEmptyScreen = computed(
  () => !invoiceStore.invoiceTotalCount && !isRequestOngoing.value
)

const selectField = computed({
  get: () => invoiceStore.selectedInvoices,
  set: (value) => {
    return invoiceStore.selectInvoice(value)
  },
})

const invoiceColumns = computed(() => {
  return [
    {
      key: 'checkbox',
      thClass: 'extra w-10',
      tdClass: 'font-medium text-gray-900',
      placeholderClass: 'w-10',
      sortable: false,
    },
    { key: 'invoice_date', label: t('invoices.date'), thClass: 'extra', tdClass: 'font-medium' },
    { key: 'invoice_number', label: receiptNumberLabel.value },
    { key: 'name', label: t('invoices.customer') },
    { key: 'status', label: t('invoices.status') },
    {
      key: 'actions',
      label: t('invoices.action'),
      tdClass: 'text-right text-sm font-medium',
      thClass: 'text-right',
      sortable: false,
    },
  ]
})

function partyName(invoice) {
  return invoice.customer?.name || invoice.customer?.display_name || '-'
}

debouncedWatch(
  filters,
  () => {
    setFilters()
  },
  { debounce: 500 }
)

watch(templateName, () => {
  resetFilters()
  resetSelection()
  tableKey.value += 1
  isRequestOngoing.value = true
})

onUnmounted(() => {
  if (invoiceStore.selectAllField) {
    invoiceStore.selectAllInvoices()
  }
})

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_INVOICE,
    abilities.EDIT_INVOICE,
    abilities.VIEW_INVOICE,
    abilities.SEND_INVOICE,
  ])
}

function refreshTable() {
  table.value && table.value.refresh()
}

async function clearStatusSearch() {
  filters.status = ''
  refreshTable()
}

async function fetchData({ page, sort }) {
  const data = {
    customer_id: filters.customer_id,
    status: filters.status,
    from_date: filters.from_date,
    to_date: filters.to_date,
    invoice_number: filters.invoice_number,
    template_name: templateName.value,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isRequestOngoing.value = true
  const response = await invoiceStore.fetchInvoices(data)
  isRequestOngoing.value = false

  return {
    data: response.data.data,
    pagination: {
      totalPages: response.data.meta.last_page,
      currentPage: page,
      totalCount: response.data.meta.total,
      limit: 10,
    },
  }
}

function setStatusFilter(val) {
  if (activeTab.value == val.title) {
    return true
  }

  activeTab.value = val.title

  switch (val.title) {
    case t('general.draft'):
      filters.status = 'DRAFT'
      break
    case t('general.sent'):
      filters.status = 'SENT'
      break
    case t('general.due'):
      filters.status = 'DUE'
      break
    default:
      filters.status = ''
      break
  }
}

function setActiveTab() {
  // compatibility with BaseMultiselect on status
}

function setFilters() {
  resetSelection()

  tableKey.value += 1
  refreshTable()
}

function clearFilter() {
  resetFilters()
}

function resetFilters() {
  filters.customer_id = ''
  filters.status = ''
  filters.from_date = ''
  filters.to_date = ''
  filters.invoice_number = ''
  activeTab.value = t('general.all')
}

function resetSelection() {
  invoiceStore.$patch((state) => {
    state.selectedInvoices = []
    state.selectAllField = false
  })
}

async function removeMultipleInvoices() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: `Are you sure you want to delete these ${pageTitle.value}?`,
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        await invoiceStore.deleteMultipleInvoices({ template_name: templateName.value }).then((res) => {
          if (res.data.success) {
            refreshTable()

            invoiceStore.$patch((state) => {
              state.selectedInvoices = []
              state.selectAllField = false
            })
          }
        })
      }
    })
}

async function downloadBulkLrReceipts() {
  const ids = invoiceStore.selectedInvoices
  try {
    const response = await http.post('/api/v1/invoices/bulk-pdf', {
      ids,
      copy_type: 'consignee',
    }, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'lr-receipts.pdf')
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Failed to download LR receipts', error)
  }
}

async function downloadBulkLrReceiptsMultiCopy() {
  const ids = invoiceStore.selectedInvoices
  try {
    const response = await http.post('/api/v1/invoices/bulk-pdf', {
      ids,
      multi_copy: true,
    }, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'lr-receipts-multi-copy.pdf')
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Failed to download multi-copy LR receipts', error)
  }
}

async function downloadBulkLorryReceipts() {
  const ids = invoiceStore.selectedInvoices
  try {
    const response = await http.post('/api/v1/invoices/bulk-pdf', { ids }, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'lorry-receipts.pdf')
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Failed to download lorry receipts', error)
  }
}

async function downloadLorryReceiptWithDocuments() {
  if (invoiceStore.selectedInvoices.length !== 1) {
    dialogStore.openDialog({
      title: 'Select One Receipt',
      message: 'Please select exactly one lorry receipt to download with documents.',
      yesLabel: t('general.ok'),
      hideNoButton: true,
      size: 'lg',
    })
    return
  }

  const id = invoiceStore.selectedInvoices[0]
  try {
    const response = await http.get(`/api/v1/invoices/${id}/download-with-documents`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `lorry-receipt-${id}-with-documents.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Failed to download lorry receipt with documents', error)
  }
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }

  showFilters.value = !showFilters.value
}
</script>
