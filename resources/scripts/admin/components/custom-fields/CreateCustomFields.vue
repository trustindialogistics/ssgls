<template>
  <div
    v-if="
      store[storeProp] && store[storeProp].customFields.length > 0 && !isLoading
    "
  >
    <!-- Lorry Receipt template sections -->
    <div v-if="isLorryReceiptTemplate" class="space-y-6">
      <section
        v-if="docketNoFieldEntry"
        class="overflow-hidden bg-white border border-gray-200 border-solid rounded-lg"
      >
        <BaseInputGrid :layout="gridLayout" class="p-4">
          <SingleField
            :custom-field-scope="customFieldScope"
            :store="store"
            :store-prop="storeProp"
            :index="docketNoFieldEntry.index"
            :field="docketNoFieldEntry.field"
          />
        </BaseInputGrid>
      </section>

      <section
        v-for="section in lorryFieldSections"
        :key="section.key"
        class="overflow-hidden bg-white border border-gray-200 border-solid rounded-lg"
      >
        <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 border-b border-gray-200">
          <span class="inline-flex items-center justify-center w-8 h-8 text-sm font-semibold text-gray-900 bg-white border border-gray-300 rounded-full">
            {{ section.key }}
          </span>
          <div class="text-left">
            <h6 class="m-0 text-sm font-semibold tracking-wide text-gray-900 uppercase">
              {{ section.title }}
            </h6>
            <p class="m-0 mt-0.5 text-xs text-gray-500">
              {{ section.description }}
            </p>
          </div>
        </div>

        <BaseInputGrid :layout="gridLayout" class="p-4">
          <SingleField
            v-for="entry in section.fields"
            :key="entry.field.id"
            :custom-field-scope="customFieldScope"
            :store="store"
            :store-prop="storeProp"
            :index="entry.index"
            :field="entry.field"
          />
        </BaseInputGrid>
      </section>
    </div>

    <BaseInputGrid v-else :layout="gridLayout">
      <SingleField
        v-for="(field, index) in store[storeProp].customFields"
        :key="field.id"
        :custom-field-scope="customFieldScope"
        :store="store"
        :store-prop="storeProp"
        :index="index"
        :field="field"
      />
    </BaseInputGrid>
  </div>
</template>

<script setup>
import moment from 'moment'
import lodash from 'lodash'
import http from '@/scripts/http'
import { useCustomFieldStore } from '@/scripts/admin/stores/custom-field'
import { computed, nextTick, ref, watch } from 'vue'
import SingleField from './CreateCustomFieldsSingle.vue'

const customFieldStore = useCustomFieldStore()

const props = defineProps({
  store: {
    type: Object,
    required: true,
  },
  storeProp: {
    type: String,
    required: true,
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
  type: {
    type: String,
    default: null,
  },
  gridLayout: {
    type: String,
    default: 'two-column',
  },
  isLoading: {
    type: Boolean,
    default: null,
  },
  customFieldScope: {
    type: String,
    required: true,
  },
  templateName: {
    type: String,
    default: null,
  },
})

const isVehicleLookupReady = ref(false)
const latestVehicleLookupToken = ref(0)
const lastVehicleLookupLorryNo = ref('')

const hiddenLorryFieldLabels = [
  'Owner Code',
  'Owner Name',
  'Driver Name',
  'Broker Name',
  'Broker Code',
  'No Of Pages',
  'Driver Place',
  'Paid To',
  'Lorry Hire Amount',
  'Other Charges Amount',
  'Gross Hire Rupees',
  'Advance Cash Cheque No',
  'Advance Bank',
  'Advance Amount',
  'Balance Payable At',
  'Balance Amount',
  'Balance Rupees Only',
  'Gross Hire Amount',
  'Balance Rupees',
  'Advance Received By',
  'Final Paid To',
  'Detention Amount',
  'Extra Hire Amount',
  'Final Other Amount',
  'Final Total Extra Amount',
  'Grand Total',
  'Less Advance Other Branch Amount',
  'Less Deduction Claims Amount',
  'Total Less Amount',
  'Final Balance Code',
  'Net Amount Payable',
  'Final Cash Cheque No',
  'Final Rupees Only',
]

const lorryVehicleAutofillLabels = [
  'Regd at',
  'Body Type',
  'Make',
  'Model',
  'Colour',
  'Chasis No',
  'Engine No',
  'Owner Name',
  'Owner Address',
  'Owner Phone No',
  'Owner PAN No',
  'Paid To',
  'Final Paid To',
]

const lorryFieldSectionDefinitions = [
  {
    key: 'A',
    title: 'Vehicle & Trip',
    description: 'Route, package, weight, and vehicle particulars.',
    labels: [
      'From',
      'To',
      'No Of Packages',
      'Actual Weight',
      'Charge Weight',
      'Lorry No',
      'Regd at',
      'Body Type',
      'Make',
      'Model',
      'Colour',
      'Chasis No',
      'Engine No',
    ],
  },
  {
    key: 'C',
    title: 'Hire Particulars',
    description: 'Hire amount, advance details, and balance payable place.',
    labels: [
      'Lorry Hire',
      'Add Other Charges',
      'Advance Paid by Cash/Cheque No',
      'Advance On',
      'Balance Payable at',
      'Advance Paid Rs',
      'Bank',
    ],
  },
  {
    key: 'D',
    title: 'Loading Remarks',
    description: 'Loading responsibility details.',
    labels: ['Loaded By'],
  },
  {
    key: 'E',
    title: 'Final Payment',
    description: 'Final additions, deductions, payment details, and bilties.',
    labels: [
      'Add Detention Rs.',
      'Extra Hire Rs',
      'Other Rs',
      'Less Adv. at other branch',
      'Less Deduction for Claims',
      'Final Balance Amount Paid at',
      'Final Balance Date',
      'Cash/Cheque No.',
      'Final Bank',
    ],
  },
  {
    key: 'B',
    title: 'Owner, Driver & Broker',
    description: 'Profile-filled party details and broker references.',
    labels: [
      'Owner Address',
      'Owner Phone No',
      'Owner Bank Account No',
      'Owner PAN No',
      'Driver Address',
      'Driver Licence No',
      'Issued Dt.',
      'Driver Licence Issued By',
      'Driver RTO',
      'Driver Valid Up To',
      'Driver Bank Account No',
      'Broker Address',
      'Broker Pan No',
      'Broker Phone No',
      'Broker Bank Account No',
    ],
  },
]

const isLorryReceiptTemplate = computed(() => {
  return props.store[props.storeProp]?.template_name === 'lorry_receipt'
})

const isOfficeInvoiceTemplate = computed(() => {
  return props.store[props.storeProp]?.template_name === 'office_invoice'
})

const isLrReceiptTemplate = computed(() => {
  return props.store[props.storeProp]?.template_name === 'lr_receipt'
})

const customFieldsWithIndex = computed(() => {
  return (props.store[props.storeProp]?.customFields || []).map((field, index) => ({
    field,
    index,
  }))
})

const docketNoFieldEntry = computed(() => {
  return customFieldsWithIndex.value.find((entry) => {
    return entry.field.label === 'Docket No' || entry.field.label === 'Received No Of Bilties' || entry.field.label === 'Received No. of Bilties'
  })
})

const lorryFieldSections = computed(() => {
  const assignedLabels = new Set()

  const sections = lorryFieldSectionDefinitions
    .map((section) => {
      const fields = section.labels
        .map((label) => {
          const field = customFieldsWithIndex.value.find((entry) => {
            if (label === 'Balance Payable at') {
              return entry.field.label === 'Balance Payable at' || entry.field.label === 'Balance Payable At'
            }
            return entry.field.label === label
          })

          if (field) {
            assignedLabels.add(field.field.label)
          }

          return field
        })
        .filter(Boolean)

      return {
        ...section,
        fields,
      }
    })
    .filter((section) => section.fields.length)

  const otherFields = customFieldsWithIndex.value.filter((field) => {
    return !assignedLabels.has(field.field.label) && !hiddenLorryFieldLabels.includes(field.field.label)
  })

  return sections
})

function mergeExistingValues() {
  if (props.isEdit) {
    const customFields = props.store[props.storeProp].customFields || []
    const fields = props.store[props.storeProp].fields || []

    if (customFields.length === 0 || fields.length === 0) {
      return
    }

    const updatedFields = [...customFields]
    let changed = false

    fields.forEach((field) => {
      const existingIndex = updatedFields.findIndex(
        (f) => f.id === field.custom_field_id
      )

      if (existingIndex > -1) {
        let value = field.default_answer

        if (value && field.custom_field?.type === 'DateTime') {
          value = moment(field.default_answer, 'YYYY-MM-DD HH:mm:ss').format(
            'YYYY-MM-DD HH:mm'
          )
        }

        if (updatedFields[existingIndex].value !== value || !updatedFields[existingIndex].custom_field_id) {
          updatedFields[existingIndex] = {
            ...updatedFields[existingIndex],
            ...field,
            id: field.custom_field_id,
            value: value,
            label: field.custom_field?.label || updatedFields[existingIndex].label,
            options: field.custom_field?.options || updatedFields[existingIndex].options,
            is_required: field.custom_field?.is_required || updatedFields[existingIndex].is_required,
            placeholder: field.custom_field?.placeholder || updatedFields[existingIndex].placeholder,
            order: field.custom_field?.order || updatedFields[existingIndex].order,
          }
          changed = true
        }
      }
    })

    if (changed) {
      props.store[props.storeProp].customFields = updatedFields
    }
  }
}

async function getInitialCustomFields() {
  isVehicleLookupReady.value = false

  const res = await customFieldStore.fetchCustomFields({
    type: props.type,
    limit: 'all',
    template_name: props.templateName,
  })

  let data = res.data.data

  data.map((d) => (d.value = d.default_answer))

  props.store[props.storeProp].customFields = lodash.sortBy(
    data,
    (_cf) => _cf.order
  )

  mergeExistingValues()
  syncLorryReceiptDerivedFields()

  await nextTick()

  lastVehicleLookupLorryNo.value = compactValue(findField('Lorry No')?.value)
  isVehicleLookupReady.value = true
}

function findField(label) {
  return props.store[props.storeProp].customFields?.find((field) => field.label === label)
}

function setFieldValue(label, value) {
  const field = findField(label)
  const normalizedValue = value ?? ''

  if (field && field.value !== normalizedValue) {
    field.value = normalizedValue
  }
}

function compactValue(value) {
  return String(value || '').trim()
}

function syncLorryReceiptDerivedFields() {
  if (!isLorryReceiptTemplate.value) {
    return
  }

  setFieldValue('No Of Pages', '1')
  setFieldValue('Driver Place', findField('Driver Address')?.value)

  const brokerPanNoField = findField('Broker Pan No')
  const brokerPanNo = String(brokerPanNoField?.value || '').toUpperCase()

  if (brokerPanNoField && brokerPanNoField.value !== brokerPanNo) {
    brokerPanNoField.value = brokerPanNo
  }
}

function applyLorryVehicleDefaults(fields) {
  lorryVehicleAutofillLabels.forEach((label) => {
    if (Object.prototype.hasOwnProperty.call(fields, label)) {
      setFieldValue(label, fields[label])
    }
  })
}

async function fetchLorryVehicleDefaults(lorryNo) {
  const lookupToken = latestVehicleLookupToken.value + 1
  latestVehicleLookupToken.value = lookupToken

  try {
    const response = await http.get('/api/v1/invoices/lorry-receipt-vehicle-lookup', {
      params: { lorry_no: lorryNo },
    })

    if (
      lookupToken !== latestVehicleLookupToken.value ||
      compactValue(findField('Lorry No')?.value) !== lorryNo ||
      !response.data?.found
    ) {
      return
    }

    applyLorryVehicleDefaults(response.data.fields || {})
  } catch {
    // Vehicle lookup is convenience-only; users can still fill the fields manually.
  }
}

const debouncedFetchLorryVehicleDefaults = lodash.debounce(fetchLorryVehicleDefaults, 450)

getInitialCustomFields()

watch(
  () => props.store[props.storeProp].fields,
  () => {
    mergeExistingValues()
    syncLorryReceiptDerivedFields()
  },
  { deep: true }
)

watch(
  () => props.store[props.storeProp].customFields,
  () => {
    syncLorryReceiptDerivedFields()
  },
  { deep: true }
)

watch(
  () => findField('Lorry No')?.value,
  (value) => {
    if (!isLorryReceiptTemplate.value || !isVehicleLookupReady.value) {
      return
    }

    const lorryNo = compactValue(value)

    if (lorryNo === lastVehicleLookupLorryNo.value) {
      return
    }

    lastVehicleLookupLorryNo.value = lorryNo

    if (!lorryNo) {
      return
    }

    debouncedFetchLorryVehicleDefaults(lorryNo)
  }
)

watch(
  () => props.templateName,
  () => {
    getInitialCustomFields()
  }
)
</script>
