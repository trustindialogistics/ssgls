<template>
  <BaseCard class="flex flex-col mt-6">
    <ChartPlaceholder v-if="customerStore.isFetchingViewData" />

    <div v-else class="grid grid-cols-12">
      <div class="col-span-12 xl:col-span-9 xxl:col-span-10">
        <div class="flex flex-col gap-4 mt-1 mb-6 lg:flex-row lg:items-start lg:justify-between">
          <h6 class="flex items-center">
            <BaseIcon name="ChartBarSquareIcon" class="h-5 text-primary-400" />
            {{ $t('dashboard.monthly_chart.title') }}
          </h6>

          <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
          <div class="w-full h-10 sm:w-40">
            <BaseMultiselect
              v-model="selectedYear"
              :options="years"
              :allow-empty="false"
              :show-labels="false"
              :placeholder="$t('dashboard.select_year')"
              :can-deselect="false"
              @select="onChangeYear"
            />
          </div>
          <template v-if="selectedYear === 'Custom'">
            <BaseInputGroup label="From Date">
              <BaseDatePicker
                v-model="customRange.from_date"
                :calendar-button="true"
                calendar-button-icon="calendar"
              />
            </BaseInputGroup>
            <BaseInputGroup label="To Date">
              <BaseDatePicker
                v-model="customRange.to_date"
                :calendar-button="true"
                calendar-button-icon="calendar"
              />
            </BaseInputGroup>
            <BaseButton
              variant="primary"
              type="button"
              @click="loadCustomRange"
            >
              Apply
            </BaseButton>
          </template>
          </div>
        </div>

        <LineChart
          v-if="isLoading"
          :invoices="getChartInvoices"
          :expenses="getChartExpenses"
          :receipts="getReceiptTotals"
          :income="getNetProfits"
          :labels="getChartMonths"
          class="sm:w-full"
        />
      </div>

      <div
        class="
          grid
          col-span-12
          mt-6
          text-center
          xl:mt-0
          sm:grid-cols-4
          xl:text-right xl:col-span-3 xl:grid-cols-1
          xxl:col-span-2
        "
      >
        <div class="px-6 py-2">
          <span class="text-xs leading-5 lg:text-sm">
            {{ $t('dashboard.chart_info.total_sales') }}
          </span>
          <br />
          <span
            v-if="isLoading"
            class="block mt-1 text-xl font-semibold leading-8"
          >
            <BaseFormatMoney
              :amount="chartData.salesTotal"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </span>
        </div>

        <div class="px-6 py-2">
          <span class="text-xs leading-5 lg:text-sm">
            {{ $t('dashboard.chart_info.total_receipts') }}
          </span>
          <br />

          <span
            v-if="isLoading"
            class="block mt-1 text-xl font-semibold leading-8"
            style="color: #00c99c"
          >
            <BaseFormatMoney
              :amount="chartData.totalReceipts"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </span>
        </div>

        <div class="px-6 py-2">
          <span class="text-xs leading-5 lg:text-sm">
            {{ $t('dashboard.chart_info.total_expense') }}
          </span>
          <br />
          <span
            v-if="isLoading"
            class="block mt-1 text-xl font-semibold leading-8"
            style="color: #fb7178"
          >
            <BaseFormatMoney
              :amount="chartData.totalExpenses"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </span>
        </div>

        <div class="px-6 py-2">
          <span class="text-xs leading-5 lg:text-sm">
            {{ $t('dashboard.chart_info.net_income') }}
          </span>
          <br />
          <span
            v-if="isLoading"
            class="block mt-1 text-xl font-semibold leading-8"
            style="color: #5851d8"
          >
            <BaseFormatMoney
              :amount="chartData.netProfit"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </span>
        </div>
      </div>
    </div>

    <CustomerInfo />

    <div class="pt-6 mt-6 border-t border-gray-200 border-solid">
      <div class="flex flex-col gap-4 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <BaseHeading>Customer Transactions</BaseHeading>
        <div class="flex flex-wrap gap-2">
          <BaseButton
            v-for="tab in activityTabs"
            :key="tab.value"
            size="sm"
            :variant="selectedActivityTab === tab.value ? 'primary' : 'primary-outline'"
            type="button"
            @click="selectedActivityTab = tab.value"
          >
            {{ tab.label }}
          </BaseButton>
        </div>
      </div>

      <div class="overflow-x-auto border border-gray-200 border-solid rounded-md">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th
                v-for="column in activeColumns"
                :key="column"
                class="px-4 py-3 text-xs font-semibold tracking-wide text-left text-gray-500 uppercase whitespace-nowrap"
              >
                {{ column }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="record in activeRecords"
              :key="record.id"
              class="border-t border-gray-200 border-solid"
            >
              <td class="px-4 py-3 font-medium text-primary-500 whitespace-nowrap">
                <router-link :to="recordRoute(record)">
                  {{ recordNumber(record) }}
                </router-link>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                {{ recordDate(record) }}
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                {{ recordStatus(record) }}
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <BaseFormatMoney
                  :amount="recordAmount(record)"
                  :currency="recordCurrency(record)"
                />
              </td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <BaseFormatMoney
                  :amount="recordBalance(record)"
                  :currency="recordCurrency(record)"
                />
              </td>
            </tr>
            <tr v-if="activeRecords.length === 0">
              <td
                class="px-4 py-6 text-sm text-center text-gray-500"
                :colspan="activeColumns.length"
              >
                No records found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import CustomerInfo from './CustomerInfo.vue'
import LineChart from '@/scripts/admin/components/charts/LineChart.vue'
import { ref, computed, watch, reactive, inject } from 'vue'
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import { useRoute } from 'vue-router'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import ChartPlaceholder from './CustomerChartPlaceholder.vue'
import { useI18n } from 'vue-i18n'
import moment from 'moment'

const companyStore = useCompanyStore()
const customerStore = useCustomerStore()
const utils = inject('utils')
const { t } = useI18n()

const route = useRoute()

let isLoading = ref(false)
let chartData = reactive({})
let data = reactive({})
let activity = reactive({
  invoices: [],
  lrReceipts: [],
  payments: [],
})
let years = reactive([
  {label: t('dateRange.this_year'), value: 'This year'},
  {label: t('dateRange.previous_year'), value: 'Previous year'},
  {label: 'Custom Date', value: 'Custom'},
])
let selectedYear = ref('This year')
const customRange = reactive({
  from_date: moment().startOf('year').format('YYYY-MM-DD'),
  to_date: moment().format('YYYY-MM-DD'),
})
const selectedActivityTab = ref('invoices')
const activityTabs = [
  { label: 'Invoices', value: 'invoices' },
  { label: 'LR Receipts', value: 'lrReceipts' },
  { label: 'Payments', value: 'payments' },
]
const activeColumns = ['Number', 'Date', 'Status', 'Amount', 'Balance']

const getChartExpenses = computed(() => {
  if (chartData.expenseTotals) {
    return chartData.expenseTotals
  }
  return []
})

const getNetProfits = computed(() => {
  if (chartData.netProfits) {
    return chartData.netProfits
  }
  return []
})

const getChartMonths = computed(() => {
  if (chartData && chartData.months) {
    return chartData.months
  }
  return []
})

const getReceiptTotals = computed(() => {
  if (chartData.receiptTotals) {
    return chartData.receiptTotals
  }
  return []
})

const getChartInvoices = computed(() => {
  if (chartData.invoiceTotals) {
    return chartData.invoiceTotals
  }

  return []
})

watch(
  route,
  () => {
    if (route.params.id) {
      loadCustomer()
    }
    selectedYear.value = 'This year'
  },
  { immediate: true }
)

async function loadCustomer() {
  isLoading.value = false
  let response = await customerStore.fetchViewCustomer({
    id: route.params.id,
  })

  if (response.data) {
    Object.assign(chartData, response.data.meta.chartData)
    setActivity(response.data.meta.activity)
    Object.assign(data, response.data.data)
  }

  isLoading.value = true
}

async function onChangeYear(data) {
  const selectedValue = data?.value || data

  if (selectedValue === 'Custom') {
    return loadCustomRange()
  }

  let params = {
    id: route.params.id,
  }

  selectedValue === 'Previous year'
    ? (params.previous_year = true)
    : (params.this_year = true)

  let response = await customerStore.fetchViewCustomer(params)

  if (response.data.meta.chartData) {
    Object.assign(chartData, response.data.meta.chartData)
    setActivity(response.data.meta.activity)
  }

  return true
}

async function loadCustomRange() {
  let response = await customerStore.fetchViewCustomer({
    id: route.params.id,
    from_date: moment(customRange.from_date).format('YYYY-MM-DD'),
    to_date: moment(customRange.to_date).format('YYYY-MM-DD'),
  })

  if (response.data.meta.chartData) {
    Object.assign(chartData, response.data.meta.chartData)
    setActivity(response.data.meta.activity)
  }
}

function setActivity(nextActivity = {}) {
  activity.invoices = nextActivity.invoices?.data || nextActivity.invoices || []
  activity.lrReceipts = nextActivity.lrReceipts?.data || nextActivity.lrReceipts || []
  activity.payments = nextActivity.payments?.data || nextActivity.payments || []
}

const activeRecords = computed(() => {
  return activity[selectedActivityTab.value] || []
})

function recordRoute(record) {
  if (selectedActivityTab.value === 'payments') {
    return `/admin/payments/${record.id}/view`
  }

  if (selectedActivityTab.value === 'lrReceipts') {
    return `/admin/lr-receipts/${record.id}/view`
  }

  return `/admin/invoices/${record.id}/view`
}

function recordNumber(record) {
  return record.payment_number || record.invoice_number
}

function recordDate(record) {
  return record.formatted_payment_date || record.formatted_invoice_date
}

function recordStatus(record) {
  if (selectedActivityTab.value === 'payments') {
    return record.payment_method?.name || 'Received'
  }

  return String(record.paid_status || record.status || '').replaceAll('_', ' ')
}

function recordAmount(record) {
  return record.amount ?? record.total ?? 0
}

function recordBalance(record) {
  if (selectedActivityTab.value === 'payments') {
    return 0
  }

  return record.due_amount ?? 0
}

function recordCurrency(record) {
  return record.customer?.currency || companyStore.selectedCompanyCurrency
}
</script>
