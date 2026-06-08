<template>
  <div>
    <div
      v-if="dashboardStore.isDashboardDataLoaded"
      class="grid grid-cols-10 mt-8 bg-white rounded shadow"
    >
      <!-- Chart -->
      <div
        class="
          grid grid-cols-1
          col-span-10
          px-4
          py-5
          lg:col-span-7
          xl:col-span-8
          sm:p-6
        "
      >
        <div class="flex justify-between mt-1 mb-4 flex-col md:flex-row">
          <h6 class="flex items-center sw-section-title h-10">
            <BaseIcon name="ChartBarSquareIcon" class="text-primary-400 mr-1" />
            {{ $t('dashboard.monthly_chart.title') }}
          </h6>

          <div class="flex flex-col gap-3 my-2 md:m-0 sm:flex-row sm:items-end">
            <div class="w-full h-10 sm:w-40">
              <BaseMultiselect
                v-model="selectedYear"
                :options="years"
                value-prop="value"
                :allow-empty="false"
                :show-labels="false"
                :placeholder="$t('dashboard.select_year')"
                :can-deselect="false"
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
          :debits="dashboardStore.chartData.debitTotals || []"
          :credits="dashboardStore.chartData.creditTotals || []"
          :invoices="dashboardStore.chartData.invoiceTotals"
          :expenses="dashboardStore.chartData.expenseTotals"
          :receipts="dashboardStore.chartData.receiptTotals"
          :income="dashboardStore.chartData.netIncomeTotals"
          :labels="dashboardStore.chartData.months"
          class="sm:w-full"
        />
      </div>

      <!-- Chart Labels -->
      <div
        class="
          grid grid-cols-3
          col-span-10
          text-center
          border-t border-l border-gray-200 border-solid
          lg:border-t-0 lg:text-right lg:col-span-3
          xl:col-span-2
          lg:grid-cols-1
        "
      >
        <div class="p-6">
          <span class="text-xs leading-5 lg:text-sm">
            {{ $t('dashboard.chart_info.total_sales') }}
          </span>
          <br />
          <span class="block mt-1 text-xl font-semibold leading-8 lg:text-2xl">
            <BaseFormatMoney
              :amount="dashboardStore.totalSales"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </span>
        </div>
        <div class="p-6">
          <span class="text-xs leading-5 lg:text-sm">
            Consignment Profit/Loss
          </span>
          <br />
          <span
            :class="[
              'block mt-1 text-xl font-semibold leading-8 lg:text-2xl',
              dashboardStore.totalReceipts >= 0 ? 'text-green-500' : 'text-red-500'
            ]"
          >
            <BaseFormatMoney
              :amount="dashboardStore.totalReceipts"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </span>
        </div>
        <div class="p-6">
          <span class="text-xs leading-5 lg:text-sm">
            {{ $t('dashboard.chart_info.total_expense') }}
          </span>
          <br />
          <span
            class="
              block
              mt-1
              text-xl
              font-semibold
              leading-8
              lg:text-2xl
              text-red-400
            "
          >
            <BaseFormatMoney
              :amount="dashboardStore.totalExpenses"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </span>
        </div>
        <div
          class="
            col-span-3
            p-6
            border-t border-gray-200 border-solid
            lg:col-span-1
          "
        >
          <span class="text-xs leading-5 lg:text-sm">
            {{ $t('dashboard.chart_info.net_income') }}
          </span>
          <br />
          <span
            class="
              block
              mt-1
              text-xl
              font-semibold
              leading-8
              lg:text-2xl
              text-primary-500
            "
          >
            <BaseFormatMoney
              :amount="dashboardStore.totalNetIncome"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </span>
        </div>
      </div>
    </div>

    <ChartPlaceholder v-else />
  </div>
</template>

<script setup>
import { defineAsyncComponent, ref, watch, inject, reactive } from 'vue'
import { useDashboardStore } from '@/scripts/admin/stores/dashboard'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import ChartPlaceholder from './DashboardChartPlaceholder.vue'
import abilities from '@/scripts/admin/stub/abilities'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useI18n } from 'vue-i18n'
import moment from 'moment'

const dashboardStore = useDashboardStore()
const companyStore = useCompanyStore()
const LineChart = defineAsyncComponent(() =>
  import('@/scripts/admin/components/charts/LineChart.vue')
)

const { t } = useI18n()
const utils = inject('utils')
const userStore = useUserStore()
const years = ref([
  { label: t('dateRange.this_month'), value: 'This month' },
  { label: t('dateRange.this_year'), value: 'This year' },
  { label: t('dateRange.previous_year'), value: 'Previous year' },
  { label: 'Custom Date', value: 'Custom' },
])
const selectedYear = ref('This month')
const customRange = reactive({
  from_date: moment().startOf('year').format('YYYY-MM-DD'),
  to_date: moment().format('YYYY-MM-DD'),
})

watch(
  selectedYear,
  (val) => {
    onChangeYear(val)
  },
  { immediate: true }
)

function selectedYearValue(data) {
  return data?.value || data
}

function onChangeYear(data) {
  const value = selectedYearValue(data)

  if (value === 'Custom') {
    loadCustomRange()
    return
  }

  if (value === 'Previous year') {
    loadData({ previous_year: true })
    return
  }

  if (value === 'This month') {
    loadData({ view_type: 'day' })
    return
  }

  loadData()
}

async function loadData(params) {
  if (userStore.hasAbilities(abilities.DASHBOARD)) {
    await dashboardStore.loadData(params)
  }
}

async function loadCustomRange() {
  await loadData({
    from_date: moment(customRange.from_date).format('YYYY-MM-DD'),
    to_date: moment(customRange.to_date).format('YYYY-MM-DD'),
  })
}
</script>
