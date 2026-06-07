<template>
  <div class="graph-container h-[320px] w-full">
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

function toChartAmounts(values) {
  return values.map((value) => Number(value || 0) / 100)
}

function lineDataset(label, data, color, isDashed = false) {
  return {
    label,
    fill: false,
    tension: 0.4,
    backgroundColor: color,
    borderColor: color,
    borderWidth: 3,
    borderDash: isDashed ? [6, 4] : [],
    pointBorderColor: color,
    pointBackgroundColor: '#fff',
    pointBorderWidth: 2,
    pointHoverRadius: 6,
    pointHoverBackgroundColor: color,
    pointHoverBorderColor: '#fff',
    pointHoverBorderWidth: 2,
    pointRadius: 4,
    pointHitRadius: 12,
    type: 'line',
    order: 1,
    data,
  }
}

function barDataset(label, data, bgColor, borderColor) {
  return {
    label,
    backgroundColor: bgColor,
    borderColor: borderColor,
    borderWidth: 1.5,
    borderRadius: 6,
    borderSkipped: false,
    barPercentage: 0.8,
    categoryPercentage: 0.6,
    type: 'bar',
    order: 2,
    data,
  }
}

function buildDatasets() {
  return [
    lineDataset(
      'Consignment Profit/Loss',
      toChartAmounts(props.receipts),
      'rgba(16, 185, 129, 1)' // Emerald Green line
    ),
  ]
}

function buildOptions() {
  return {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      mode: 'index',
      intersect: false,
    },
    plugins: {
      tooltip: {
        enabled: true,
        backgroundColor: '#1f2937', // Dark charcoal tooltip
        titleColor: '#fff',
        titleFont: {
          family: 'Inter, system-ui, sans-serif',
          weight: 'bold',
          size: 13,
        },
        bodyColor: '#e5e7eb',
        bodyFont: {
          family: 'Inter, system-ui, sans-serif',
          size: 12,
        },
        padding: 12,
        cornerRadius: 8,
        borderColor: '#374151',
        borderWidth: 1,
        callbacks: {
          label(context) {
            const formatted = utils.formatMoney(
              Math.round(context.parsed.y * 100),
              defaultCurrency.value
            )
            return `  ${context.dataset.label}: ${formatted}`
          },
        },
      },
      legend: {
        display: true,
        position: 'top',
        labels: {
          usePointStyle: true,
          boxWidth: 8,
          boxHeight: 8,
          padding: 20,
          font: {
            family: 'Inter, system-ui, sans-serif',
            size: 12,
            weight: '500',
          },
        },
      },
    },
    scales: {
      x: {
        grid: {
          display: false, // Clean look: no vertical lines
        },
        ticks: {
          color: '#6b7280',
          font: {
            family: 'Inter, system-ui, sans-serif',
            size: 11,
          },
        },
      },
      y: {
        grid: {
          color: '#f3f4f6', // Light gray dashed horizontal lines
          borderDash: [5, 5],
          drawBorder: false,
        },
        ticks: {
          color: '#6b7280',
          font: {
            family: 'Inter, system-ui, sans-serif',
            size: 11,
          },
          callback(value) {
            if (value >= 1000 || value <= -1000) {
              return (value / 1000).toLocaleString() + 'k'
            }
            return value.toLocaleString()
          },
        },
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
    type: 'bar', // Mixed chart uses 'bar' base type so we can mix lines and bars
    data: data,
    options: buildOptions(),
  })
})

function update() {
  if (!myLineChart) {
    return
  }

  myLineChart.destroy()

  const context = graph.value.getContext('2d')
  myLineChart = new Chart(context, {
    type: 'bar',
    data: {
      labels: props.labels,
      datasets: buildDatasets(),
    },
    options: buildOptions(),
  })
}
</script>
