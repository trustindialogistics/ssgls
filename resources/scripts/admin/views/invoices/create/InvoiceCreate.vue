<template>
  <SelectTemplateModal v-if="false" />
  <ItemModal v-if="!isTransportEntryTemplate" />
  <TaxTypeModal v-if="!isTransportEntryTemplate" />
  <SalesTax
    v-if="!isTransportEntryTemplate && salesTaxEnabled && (!isLoadingContent || route.query.customer)"
    :store="invoiceStore"
    :is-edit="isEdit"
    store-prop="newInvoice"
    :customer="invoiceStore.newInvoice.customer"
  />

  <BasePage class="relative invoice-create-page">
    <form @submit.prevent="submitForm">
      <BasePageHeader :title="pageTitle">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem
            :title="$t('general.home')"
            to="/admin/dashboard"
          />
          <BaseBreadcrumbItem
            :title="indexTitle"
            :to="indexPath"
          />
          <BaseBreadcrumbItem
            v-if="isEdit"
            :title="editTitle"
            to="#"
            active
          />
          <BaseBreadcrumbItem
            v-else
            :title="createTitle"
            to="#"
            active
          />
        </BaseBreadcrumb>

        <template #actions>
          <router-link
            v-if="isEdit"
            :to="`/invoices/pdf/${invoiceStore.newInvoice.unique_hash}`"
            target="_blank"
          >
            <BaseButton class="mr-3" variant="primary-outline" type="button">
              <span class="flex">
                {{ $t('general.view_pdf') }}
              </span>
            </BaseButton>
          </router-link>

          <BaseButton
            :loading="isSaving"
            :disabled="isSaving"
            variant="primary"
            type="submit"
          >
            <template #left="slotProps">
              <BaseIcon
                v-if="!isSaving"
                name="ArrowDownOnSquareIcon"
                :class="slotProps.class"
              />
            </template>
            {{ isLrReceipt ? 'Save LR' : $t('invoices.save_invoice') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <!-- Select Customer & Basic Fields  -->
      <InvoiceBasicFields
        :v="v$"
        :is-loading="isLoadingContent"
        :is-edit="isEdit"
      />

      <div v-if="isLrReceipt" class="mb-8">
        <InvoiceCustomFields
          type="Invoice"
          :is-edit="isEdit"
          :is-loading="isLoadingContent"
          :store="invoiceStore"
          store-prop="newInvoice"
          :template-name="invoiceStore.newInvoice.template_name"
          :custom-field-scope="invoiceValidationScope"
        />
      </div>

      <BaseScrollPane>
        <!-- Invoice Items -->
        <InvoiceItems
          :currency="invoiceStore.newInvoice.selectedCurrency"
          :is-loading="isLoadingContent"
          :item-validation-scope="invoiceValidationScope"
          :store="invoiceStore"
          store-prop="newInvoice"
        />

        <!-- Invoice Footer Section -->
        <div
          class="
            block
            mt-10
            invoice-foot
            lg:flex lg:justify-between lg:items-start
          "
        >
          <div class="relative w-full lg:w-1/2 lg:mr-4">
            <!-- Invoice Custom Notes -->
            <NoteFields
              v-if="!isTransportEntryTemplate"
              :store="invoiceStore"
              store-prop="newInvoice"
              :fields="invoiceNoteFieldList"
              type="Invoice"
            />

            <!-- Invoice Custom Fields -->
            <InvoiceCustomFields
              v-if="!isLrReceipt"
              type="Invoice"
              :is-edit="isEdit"
              :is-loading="isLoadingContent"
              :store="invoiceStore"
              store-prop="newInvoice"
              :template-name="invoiceStore.newInvoice.template_name"
              :custom-field-scope="invoiceValidationScope"
              class="mb-6"
            />

            <!-- Invoice Template Button-->
            <SelectTemplate
              v-if="false"
              :store="invoiceStore"
              store-prop="newInvoice"
              component-name="InvoiceTemplate"
              :is-mark-as-default="isMarkAsDefault"
            />
          </div>

          <InvoiceTotal
            v-if="!isTransportEntryTemplate"
            :currency="invoiceStore.newInvoice.selectedCurrency"
            :is-loading="isLoadingContent"
            :store="invoiceStore"
            store-prop="newInvoice"
            tax-popup-type="invoice"
          />
        </div>
      </BaseScrollPane>
    </form>
  </BasePage>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import moment from 'moment'
import {
  required,
  maxLength,
  helpers,
  requiredIf,
  decimal,
} from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { cloneDeep } from 'lodash'

import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useModuleStore } from '@/scripts/admin/stores/module'
import { useNotesStore } from '@/scripts/admin/stores/note'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useCustomFieldStore } from '@/scripts/admin/stores/custom-field'

import InvoiceItems from '@/scripts/admin/components/estimate-invoice-common/CreateItems.vue'
import InvoiceTotal from '@/scripts/admin/components/estimate-invoice-common/CreateTotal.vue'
import SelectTemplate from '@/scripts/admin/components/estimate-invoice-common/SelectTemplateButton.vue'
import InvoiceBasicFields from './InvoiceCreateBasicFields.vue'
import InvoiceCustomFields from '@/scripts/admin/components/custom-fields/CreateCustomFields.vue'
import NoteFields from '@/scripts/admin/components/estimate-invoice-common/CreateNotesField.vue'
import SelectTemplateModal from '@/scripts/admin/components/modal-components/SelectTemplateModal.vue'
import TaxTypeModal from '@/scripts/admin/components/modal-components/TaxTypeModal.vue'
import ItemModal from '@/scripts/admin/components/modal-components/ItemModal.vue'
import SalesTax from '@/scripts/admin/components/estimate-invoice-common/SalesTax.vue'

const invoiceStore = useInvoiceStore()
const companyStore = useCompanyStore()
const customFieldStore = useCustomFieldStore()
const moduleStore = useModuleStore()
const notesStore = useNotesStore()

const { t } = useI18n()
let route = useRoute()
let router = useRouter()

const invoiceValidationScope = 'newInvoice'
let isSaving = ref(false)
const isMarkAsDefault = ref(false)
const dueDateManuallyChanged = ref(false)
let isAutoUpdatingDueDate = false
let expectedAutoDueDate = ref(null)

const invoiceNoteFieldList = ref([
  'customer',
  'company',
  'customerCustom',
  'invoice',
  'invoiceCustom',
])

let isLoadingContent = computed(
  () => invoiceStore.isFetchingInvoice || invoiceStore.isFetchingInitialSettings
)

const isLrReceipt = computed(() => route.path.includes('/admin/lr-receipts'))
const indexTitle = computed(() =>
  isLrReceipt.value ? 'LR Receipts' : t('invoices.invoice', 2)
)
const indexPath = computed(() =>
  isLrReceipt.value ? '/admin/lr-receipts' : '/admin/invoices'
)
const createTitle = computed(() =>
  isLrReceipt.value ? 'New LR Receipt' : t('invoices.new_invoice')
)
const editTitle = computed(() =>
  isLrReceipt.value ? 'Edit LR Receipt' : t('invoices.edit_invoice')
)
let pageTitle = computed(() => (isEdit.value ? editTitle.value : createTitle.value))

const salesTaxEnabled = computed(() => {
  return (
    companyStore.selectedCompanySettings.sales_tax_us_enabled === 'YES' &&
    moduleStore.salesTaxUSEnabled
  )
})

const isOfficeInvoiceTemplate = computed(() => {
  return invoiceStore.newInvoice.template_name === 'office_invoice'
})

const isTransportEntryTemplate = computed(() => {
  return isOfficeInvoiceTemplate.value || invoiceStore.newInvoice.template_name === 'lr_receipt'
})

let isEdit = computed(
  () => route.name === 'invoices.edit' || route.name === 'lr-receipts.edit'
)
watch(
  () => route.name,
  () => {
    // Keep original behavior: the create/edit component is shared across routes.
    isMarkAsDefault.value = false
  }
)

const rules = {
  invoice_date: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  reference_number: {
    maxLength: helpers.withMessage(
      t('validation.price_maxlength'),
      maxLength(255)
    ),
  },
  customer_id: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  invoice_number: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  exchange_rate: {
    required: requiredIf(function () {
      helpers.withMessage(t('validation.required'), required)
      return invoiceStore.showExchangeRate
    }),
    decimal: helpers.withMessage(t('validation.valid_exchange_rate'), decimal),
  },
}

const v$ = useVuelidate(
  rules,
  computed(() => invoiceStore.newInvoice),
  { $scope: invoiceValidationScope }
)

customFieldStore.resetCustomFields()
v$.value.$reset
invoiceStore.resetCurrentInvoice()
if (isLrReceipt.value) {
  invoiceStore.newInvoice.template_name = 'lr_receipt'
}
invoiceStore.fetchInvoiceInitialSettings(isEdit.value)

watch(
  () => invoiceStore.newInvoice.customer,
  (newVal) => {
    if (newVal && newVal.currency) {
      invoiceStore.newInvoice.selectedCurrency = newVal.currency
    } else {
      invoiceStore.newInvoice.selectedCurrency =
        companyStore.selectedCompanyCurrency
    }
  }
)

watch(
  () => companyStore.selectedCompanySettings?.tax_included_by_default,
  (newVal) => {
    invoiceStore.newInvoice.tax_included = newVal === 'YES'
  },
  {immediate: true}
)

// Watch for manual changes to due_date
watch(() => invoiceStore.newInvoice.due_date, (newDueDate, oldDueDate) => {
  if (!isAutoUpdatingDueDate && newDueDate !== oldDueDate && oldDueDate !== undefined && newDueDate !== expectedAutoDueDate.value) {
    dueDateManuallyChanged.value = true
  }
});

// Watch invoice_date and automatically update due_date when it changes
watch(() => invoiceStore.newInvoice.invoice_date, (newInvoiceDate, oldInvoiceDate) => {
  if (
    companyStore.selectedCompanySettings?.invoice_set_due_date_automatically === 'YES' &&
    newInvoiceDate &&
    newInvoiceDate !== oldInvoiceDate &&
    oldInvoiceDate !== undefined
  ) {

    const dueDateDays = parseInt(companyStore.selectedCompanySettings.invoice_due_date_days || 0);
    const invoiceDate = moment(newInvoiceDate)
    
    if (invoiceDate.isValid()) {
      const calculatedDueDate = invoiceDate.clone().add(dueDateDays, 'days').format('YYYY-MM-DD')
      expectedAutoDueDate.value = calculatedDueDate
      
      
      if (dueDateManuallyChanged.value) {
        const currentDueDate = invoiceStore.newInvoice.due_date
        if (currentDueDate) {
          const dueDateMoment = moment(currentDueDate)
          if (dueDateMoment.isValid() && dueDateMoment.isSameOrAfter(invoiceDate, 'day')) {
            return // Manual due date still valid/in the future
          }
        }

        // Manual due date is in the past/invalid
        dueDateManuallyChanged.value = false
      }
      
      // Set the calculated due date
      isAutoUpdatingDueDate = true
      invoiceStore.newInvoice.due_date = calculatedDueDate
      isAutoUpdatingDueDate = false
    }
    
  }
})

async function submitForm() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    console.log('Form is invalid:', v$.value.$errors)
    return false
  }

  isSaving.value = true

  let data = cloneDeep({
    ...invoiceStore.newInvoice,
    sub_total: invoiceStore.getSubTotal,
    total: invoiceStore.getTotal,
    tax: invoiceStore.getTotalTax,
  })

  data.items = data.items.map((item) => ({
    ...item,
    custom_fields: item.customFields || [],
  }))

  if (data.discount_per_item === 'YES') {
    data.items.forEach((item, index) => {
      if (item.discount_type === 'fixed'){
        data.items[index].discount = item.discount * 100
      }
    })
  }
  else {
    if (data.discount_type === 'fixed'){
      data.discount = data.discount * 100
    }
  }
    if (
    !invoiceStore.newInvoice.tax_per_item === 'YES'
    && data.taxes.length
  ){
    data.tax_type_ids = data.taxes.map(_t => _t.tax_type_id)
  }

  try {
    const action = isEdit.value
      ? invoiceStore.updateInvoice
      : invoiceStore.addInvoice

    const response = await action(data)

    router.push(isLrReceipt.value ? `/admin/lr-receipts/${response.data.data.id}/view` : `/admin/invoices/${response.data.data.id}/view`)
  } catch (err) {
    console.error(err)
  }

  isSaving.value = false
}
</script>
