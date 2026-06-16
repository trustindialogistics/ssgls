import http from '@/scripts/http'
import { defineStore } from 'pinia'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { handleError } from '@/scripts/helpers/error-handling'

export const useDashboardStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc('dashboard', {
    state: () => ({
      stats: {
        totalAmountDue: 0,
        totalCustomerCount: 0,
        totalInvoiceCount: 0,
        totalLrReceiptCount: 0,
      },

      chartData: {
        months: [],
        debitTotals: [],
        creditTotals: [],
        invoiceTotals: [],
        expenseTotals: [],
        receiptTotals: [],
        netIncomeTotals: [],
      },

      totalSales: null,
      totalReceipts: null,
      totalExpenses: null,
      totalNetIncome: null,

      recentDueInvoices: [],
      recentLrReceipts: [],

      isDashboardDataLoaded: false,
    }),

    actions: {
      loadData(params) {
        return new Promise((resolve, reject) => {
          http
            .get(`/api/v1/dashboard`, { params })
            .then((response) => {
              // Stats
              this.stats.totalAmountDue = response.data.total_amount_due
              this.stats.totalCustomerCount = response.data.total_customer_count
              this.stats.totalInvoiceCount = response.data.total_invoice_count
              this.stats.totalLrReceiptCount = response.data.total_lr_receipt_count

              // Dashboard Chart
              if (this.chartData && response.data.chart_data) {
                this.chartData.months = response.data.chart_data.months || []
                this.chartData.debitTotals =
                  response.data.chart_data.debit_totals || []
                this.chartData.creditTotals =
                  response.data.chart_data.credit_totals || []
                this.chartData.invoiceTotals =
                  response.data.chart_data.invoice_totals || []
                this.chartData.expenseTotals =
                  response.data.chart_data.expense_totals || []
                this.chartData.receiptTotals =
                  response.data.chart_data.receipt_totals || []
                this.chartData.netIncomeTotals =
                  response.data.chart_data.net_income_totals || []
              }

              // Dashboard Chart Labels
              this.totalSales = response.data.total_sales
              this.totalReceipts = response.data.total_receipts
              this.totalExpenses = response.data.total_expenses
              this.totalNetIncome = response.data.total_net_income

              // Dashboard Table Data
              this.recentDueInvoices = response.data.recent_due_invoices
              this.recentLrReceipts = response.data.recent_lr_receipts

              this.isDashboardDataLoaded = true

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },
    },
  })()
}
