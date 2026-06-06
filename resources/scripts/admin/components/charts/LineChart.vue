<template>
  <div class="graph-container h-[300px]">
    <canvas id="graph" ref="graph" />
  </div>
</template>

<script setup>
import { Chart } from 'chart.js/auto'
import { ref, computed, onMounted, watch, inject } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const utils = inject('utils')

const props = defineProps({
  labels: {
    type: Array,
    required: true,
    default: () => [],
  },
  values: {
    type: Array,
    required: true,
    default: () => [],
  },
  debits: {
    type: Array,
    default: () => [],
  },
  credits: {
    type: Array,
    default: () => [],
  },
  invoices: {
    type: Array,
    required: true,
    default: () => [],
  },
  expenses: {
    type: Array,
    required: true,
    default: () => [],
  },
  receipts: {
    type: Array,
    required: true,
    default: () => [],
  },
  income: {
    type: Array,
    required: true,
    default: () => [],
  },
})

let myLineChart = null
const graph = ref(null)
const companyStore = useCompanyStore()
const defaultCurrency = computed(() => {
  return companyStore.selectedCompanyCurrency
})

const hasDebitCreditData = computed(() => {
  return props.debits.length > 0 || props.credits.length > 0
})

const chartType = computed(() => (hasDebitCreditData.value ? 'bar' : 'line'))

function toChartAmounts(values) {
  return values.map((value) => Number(value || 0) / 100)
}

function lineDataset(label, data, color, backgroundColor) {
  return {
    label,
    fill: false,
    tension: 0.3,
    backgroundColor,
    borderColor: color,
    borderCapStyle: 'butt',
    borderDash: [],
    borderDashOffset: 0.0,
    borderJoinStyle: 'miter',
    pointBorderColor: color,
    pointBackgroundColor: '#fff',
    pointBorderWidth: 1,
    pointHoverRadius: 5,
    pointHoverBackgroundColor: color,
    pointHoverBorderColor: 'rgba(220,220,220,1)',
    pointHoverBorderWidth: 2,
    pointRadius: 4,
    pointHitRadius: 10,
    data,
  }
}

function buildMixedDatasets() {
  return [
    {
      label: 'Amount Debited',
      backgroundColor: 'rgba(239, 68, 68, 0.65)',
      borderColor: 'rgb(220, 38, 38)',
      borderWidth: 1,
      borderRadius: 4,
      data: toChartAmounts(props.debits),
      order: 2,
    },
    {
      label: 'Amount Credited',
      backgroundColor: 'rgba(34, 197, 94, 0.65)',
      borderColor: 'rgb(22, 163, 74)',
      borderWidth: 1,
      borderRadius: 4,
      data: toChartAmounts(props.credits),
      order: 2,
    },
    {
      ...lineDataset(
        'Profit/Loss',
        toChartAmounts(props.income),
        'rgba(88, 81, 216, 1)',
        'rgba(236, 235, 249)'
      ),
      type: 'line',
      order: 1,
    },
  ]
}

function buildDefaultDatasets() {
  return [
    lineDataset(
      'Sales',
      toChartAmounts(props.invoices),
      '#040405',
      'rgba(230, 254, 249)'
    ),
    lineDataset(
      'Receipts',
      toChartAmounts(props.receipts),
      'rgb(2, 201, 156)',
      'rgba(230, 254, 249)'
    ),
    lineDataset(
      'Expenses',
      toChartAmounts(props.expenses),
      'rgb(255,0,0)',
      'rgba(245, 235, 242)'
    ),
    lineDataset(
      'Net Income',
      toChartAmounts(props.income),
      'rgba(88, 81, 216, 1)',
      'rgba(236, 235, 249)'
    ),
  ]
}

function buildDatasets() {
  return hasDebitCreditData.value ? buildMixedDatasets() : buildDefaultDatasets()
}

function buildOptions() {
  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      tooltip: {
        enabled: true,
        callbacks: {
          label(context) {
            return utils.formatMoney(
              Math.round(context.parsed.y * 100),
              defaultCurrency.value
            )
          },
        },
      },
      legend: {
        display: hasDebitCreditData.value,
      },
    },
  }
}

watch(
  () => [
    props.labels,
    props.debits,
    props.credits,
    props.invoices,
    props.expenses,
    props.receipts,
    props.income,
  ],
  () => update(),
  { deep: true }
)

onMounted(() => {
  const context = graph.value.getContext('2d')
  const data = {
    labels: props.labels,
    datasets: buildDatasets(),
  }

  myLineChart = new Chart(context, {
    type: chartType.value,
    data: data,
    options: buildOptions(),
  })
})

function update() {
  if (!myLineChart) {
    return
  }

  myLineChart.config.type = chartType.value
  myLineChart.options.plugins.legend.display = hasDebitCreditData.value
  myLineChart.data.labels = props.labels
  myLineChart.data.datasets = buildDatasets()
  myLineChart.update('none')
}
</script>
