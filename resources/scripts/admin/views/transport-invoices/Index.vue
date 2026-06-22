<template>
  <BasePage>
    <BasePageHeader title="Transport Invoices">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem title="Transport Invoices" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <router-link to="/admin/transport-invoices/create">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            New Transport Invoice
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <BaseTable
      ref="table"
      :data="fetchData"
      :columns="columns"
      :placeholder-count="transportInvoiceStore.transportInvoiceTotalCount >= 20 ? 10 : 5"
      :key="tableKey"
      class="mt-6"
    >
      <template #cell-sl_no="{ index }">
        {{ index + 1 }}
      </template>

      <template #cell-lr_number="{ row }">
        <router-link
          :to="{ path: `/admin/transport-invoices/${row.data.id}/view` }"
          class="font-medium text-primary-500"
        >
          {{ row.data.lr_number }}
        </router-link>
      </template>

      <template #cell-customer="{ row }">
        <BaseText :text="row.data.customer?.name || ''" />
      </template>

      <template #cell-invoice_date="{ row }">
        {{ row.data.formatted_invoice_date }}
      </template>

      <template #cell-due_date="{ row }">
        {{ row.data.formatted_due_date }}
      </template>
    </BaseTable>
  </BasePage>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useTransportInvoiceStore } from '@/scripts/admin/stores/transport-invoice'

const transportInvoiceStore = useTransportInvoiceStore()
const table = ref(null)
const tableKey = ref(0)

const columns = computed(() => [
  { key: 'sl_no', label: 'SL No', thClass: 'extra w-12', tdClass: 'font-medium text-gray-500', sortable: false },
  { key: 'invoice_date', label: 'Bill Date', thClass: 'extra', tdClass: 'font-medium' },
  { key: 'lr_number', label: 'Bill No. (LR No.)' },
  { key: 'customer', label: 'Party' },
  { key: 'due_date', label: 'Due Date' },
])

async function fetchData({ page, sort }) {
  const params = {
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  const response = await transportInvoiceStore.fetchTransportInvoices(params)

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
</script>

