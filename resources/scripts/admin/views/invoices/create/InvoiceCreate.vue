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

  <!-- Hidden File Input for Auto Fill -->
  <input
    type="file"
    ref="fileInput"
    accept="image/*,application/pdf"
    class="hidden"
    @change="onAutoFillFileSelected"
  />

  <!-- Pause/Freezing Screen Loader Overlay -->
  <div
    v-if="isAutoFilling"
    class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-gray-900/60 backdrop-blur-xs"
  >
    <div class="p-8 bg-white rounded-xl shadow-2xl flex flex-col items-center max-w-sm w-full mx-4 border border-gray-100">
      <div class="animate-spin rounded-full h-14 w-14 border-t-4 border-b-4 border-primary-500 mb-6"></div>
      <h3 class="text-xl font-bold text-gray-900 mb-2">LR Receipt is being Auto Filled</h3>
      <p class="text-sm text-gray-500 text-center leading-relaxed">
        Please wait a moment while we process your document. To ensure all fields are populated correctly, please do not close or navigate away from this screen.
      </p>
    </div>
  </div>

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
            v-if="isLrReceipt"
            class="mr-3"
            variant="primary-outline"
            type="button"
            :loading="isAutoFilling"
            :disabled="isAutoFilling"
            @click="triggerAutoFill"
          >
            <template #left="slotProps">
              <BaseIcon
                v-if="!isAutoFilling"
                name="SparklesIcon"
                :class="slotProps.class"
              />
            </template>
            Auto Fill LR
          </BaseButton>

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
            {{ isTransportReceipt ? saveButtonLabel : $t('invoices.save_invoice') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <!-- Select Customer & Basic Fields  -->
      <InvoiceBasicFields
        :v="v$"
        :is-loading="isEdit ? isLoadingContent : false"
        :is-edit="isEdit"
      />

      <div v-if="isTransportReceipt" class="mb-8">
        <LorryReceiptPartyFields v-if="isLorryReceipt" />

        <InvoiceCustomFields
          type="Invoice"
          :is-edit="isEdit"
          :is-loading="isLoadingContent"
          :store="invoiceStore"
          store-prop="newInvoice"
          :template-name="invoiceStore.newInvoice.template_name"
          :custom-field-scope="invoiceValidationScope"
        />

        <div
          v-if="isLorryReceipt"
          class="grid grid-cols-1 gap-4 mt-8 md:grid-cols-2 xl:grid-cols-3"
        >
          <BaseInputGroup
            v-for="document in lorryDocumentFields"
            :key="document.key"
            :label="document.label"
            variant="vertical"
          >
            <BaseFileUploader
              accept="image/*,application/pdf"
              base64
              :input-field-name="document.key"
              :recommended-text="getLorryDocumentHint(document.key)"
              @change="onLorryDocumentChange"
              @remove="onLorryDocumentRemove(document.key)"
            />
          </BaseInputGroup>
        </div>
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
              v-if="!isTransportReceipt"
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
import http from '@/scripts/http'
import { useNotificationStore } from '@/scripts/stores/notification'

import InvoiceItems from '@/scripts/admin/components/estimate-invoice-common/CreateItems.vue'
import InvoiceTotal from '@/scripts/admin/components/estimate-invoice-common/CreateTotal.vue'
import SelectTemplate from '@/scripts/admin/components/estimate-invoice-common/SelectTemplateButton.vue'
import InvoiceBasicFields from './InvoiceCreateBasicFields.vue'
import LorryReceiptPartyFields from './LorryReceiptPartyFields.vue'
import InvoiceCustomFields from '@/scripts/admin/components/custom-fields/CreateCustomFields.vue'
import NoteFields from '@/scripts/admin/components/estimate-invoice-common/CreateNotesField.vue'
import SelectTemplateModal from '@/scripts/admin/components/modal-components/SelectTemplateModal.vue'
import TaxTypeModal from '@/scripts/admin/components/modal-components/TaxTypeModal.vue'
import ItemModal from '@/scripts/admin/components/modal-components/ItemModal.vue'
import SalesTax from '@/scripts/admin/components/estimate-invoice-common/SalesTax.vue'
import BaseFileUploader from '@/scripts/components/base/BaseFileUploader.vue'

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

const isAutoFilling = ref(false)
const fileInput = ref(null)

const triggerAutoFill = () => {
  fileInput.value?.click()
}

const onAutoFillFileSelected = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  isAutoFilling.value = true

  const formData = new FormData()
  formData.append('file', file)

  try {
    const response = await http.post('/api/v1/invoices/auto-fill', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })

    const data = response.data

    // 1. Populate Consignor and Consignee (customer)
    if (data.consignor) {
      invoiceStore.newInvoice.consignor = data.consignor
    }
    if (data.consignee) {
      invoiceStore.newInvoice.customer = data.consignee
      invoiceStore.newInvoice.customer_id = data.consignee.id
    }

    // 2. Populate basic fields
    if (data.date) {
      invoiceStore.newInvoice.invoice_date = data.date
    }
    if (data.due_date) {
      invoiceStore.newInvoice.due_date = data.due_date
    }
    // 3. Populate custom fields (From, To, Truck No, Mode of Payment, GST Tax Payable By, etc.)
    if (data.fields) {
      Object.entries(data.fields).forEach(([label, value]) => {
        setInvoiceCustomField(label, value)
      })
    }

    // 4. Populate item custom fields on the first item row
    if (data.item_fields && invoiceStore.newInvoice.items?.[0]) {
      Object.entries(data.item_fields).forEach(([label, value]) => {
        setItemCustomField(0, label, value)
      })
    }

  } catch (error) {
    console.error('Failed to auto-fill LR receipt:', error)
    const notificationStore = useNotificationStore()
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || 'Failed to auto-fill LR receipt. Please try again.',
    })
  } finally {
    isAutoFilling.value = false
    if (fileInput.value) {
      fileInput.value.value = ''
    }
  }
}

function setInvoiceCustomField(label, value) {
  const normalizedLabel = label.toLowerCase().replace(/[^a-z0-9]/g, '')
  const field = invoiceStore.newInvoice.customFields?.find(
    (f) => f.label.toLowerCase().replace(/[^a-z0-9]/g, '') === normalizedLabel
  )
  if (field) {
    field.value = value || ''
  }
}

function setItemCustomField(itemIndex, label, value) {
  const item = invoiceStore.newInvoice.items[itemIndex]
  if (!item) return

  const normalizedLabel = label.toLowerCase().replace(/[^a-z0-9]/g, '')
  const field = item.customFields?.find(
    (f) => f.label.toLowerCase().replace(/[^a-z0-9]/g, '') === normalizedLabel
  )
  if (field) {
    field.value = value || ''
  }
}

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
const isLorryReceipt = computed(() => route.path.includes('/admin/lorry-receipts'))
const isTransportReceipt = computed(() => isLrReceipt.value || isLorryReceipt.value)
const transportTemplateName = computed(() => isLorryReceipt.value ? 'lorry_receipt' : 'lr_receipt')
const indexTitle = computed(() =>
  isLorryReceipt.value ? 'Lorry Receipts' : isLrReceipt.value ? 'LR Receipts' : t('invoices.invoice', 2)
)
const indexPath = computed(() =>
  isLorryReceipt.value ? '/admin/lorry-receipts' : isLrReceipt.value ? '/admin/lr-receipts' : '/admin/invoices'
)
const createTitle = computed(() =>
  isLorryReceipt.value ? 'New Lorry Receipt' : isLrReceipt.value ? 'New LR Receipt' : t('invoices.new_invoice')
)
const editTitle = computed(() =>
  isLorryReceipt.value ? 'Edit Lorry Receipt' : isLrReceipt.value ? 'Edit LR Receipt' : t('invoices.edit_invoice')
)
let pageTitle = computed(() => (isEdit.value ? editTitle.value : createTitle.value))
const saveButtonLabel = computed(() => isLorryReceipt.value ? 'Save Lorry Receipt' : 'Save LR')
const lorryDocumentFields = [
  { key: 'aadhar_front_copy', label: 'Aadhar Front Copy' },
  { key: 'aadhar_back_copy', label: 'Aadhar Back Copy' },
  { key: 'pan_card_front_copy', label: 'Pan Card Copy Front' },
  { key: 'pan_card_back_copy', label: 'Pan Card Copy Back' },
  { key: 'rc_copy_front', label: 'RC Copy Front' },
  { key: 'rc_copy_back', label: 'RC Copy Back' },
]

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
  return ['office_invoice', 'lr_receipt', 'lorry_receipt'].includes(invoiceStore.newInvoice.template_name)
})

let isEdit = computed(
  () => ['invoices.edit', 'lr-receipts.edit', 'lorry-receipts.edit'].includes(route.name)
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
    required: helpers.withMessage(
      t('validation.required'),
      requiredIf(() => !isLrReceipt.value)
    ),
  },
  invoice_number: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  exchange_rate: {
    required: helpers.withMessage(
      t('validation.required'),
      requiredIf(() => invoiceStore.showExchangeRate && !isTransportReceipt.value)
    ),
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
if (isTransportReceipt.value) {
  invoiceStore.newInvoice.template_name = transportTemplateName.value
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

async function submitForm() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return false
  }

  isSaving.value = true

  let data = cloneDeep({
    ...invoiceStore.newInvoice,
    sub_total: invoiceStore.getSubTotal,
    total: invoiceStore.getTotal,
    tax: invoiceStore.getTotalTax,
  })

  if (data.customFields) {
    data.customFields.forEach((field) => {
      if (field.label === 'Received No Of Bilties' || field.label === 'Received No. of Bilties') {
        let val = String(field.value || '')
        val = val.replace(/[^0-9,]/g, '')
        val = val.replace(/,+/g, ',')
        val = val.replace(/^,|,$/g, '')
        field.value = val
      }
    })
  }

  if (data.template_name === 'lorry_receipt') {
    data.items = data.items.map((item) => ({
      ...item,
      name: item.name || 'Lorry Receipt',
      title: item.title || 'Lorry Receipt',
    }))
  }

  data.items = data.items.map((item) => {
    let customFields = item.customFields || []
    customFields.forEach((field) => {
      if (field.label === 'Received No Of Bilties' || field.label === 'Received No. of Bilties') {
        let val = String(field.value || '')
        val = val.replace(/[^0-9,]/g, '')
        val = val.replace(/,+/g, ',')
        val = val.replace(/^,|,$/g, '')
        field.value = val
      } else if (field.label === 'Consignment Number') {
        let val = String(field.value || '')
        val = val.replace(/[^0-9]/g, '')
        field.value = val
      }
    })
    return {
      ...item,
      custom_fields: customFields,
    }
  })

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

    router.push(isLorryReceipt.value ? `/admin/lorry-receipts/${response.data.data.id}/view` : isLrReceipt.value ? `/admin/lr-receipts/${response.data.data.id}/view` : `/admin/invoices/${response.data.data.id}/view`)
  } catch (err) {
    console.error(err)
  }

  isSaving.value = false
}

function onLorryDocumentChange(fieldName, data, fileCount, file) {
  invoiceStore.newInvoice.lorry_documents = {
    ...(invoiceStore.newInvoice.lorry_documents || {}),
    [fieldName]: {
      name: file.name,
      data,
    },
  }
}

function onLorryDocumentRemove(fieldName) {
  if (!invoiceStore.newInvoice.lorry_documents) {
    return
  }

  delete invoiceStore.newInvoice.lorry_documents[fieldName]
}

function getLorryDocumentHint(fieldName) {
  const document = invoiceStore.newInvoice.lorry_documents?.[fieldName]

  if (document?.file_name) {
    return `Uploaded: ${document.file_name}`
  }

  return 'PNG, JPEG, or PDF'
}
</script>
