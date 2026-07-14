<template>
  <tr
    :class="[
      'box-border',
      isTransportEntryTemplate ? '' : 'bg-white border border-gray-200 border-solid rounded-b'
    ]"
  >
    <td :colspan="itemTableColumnCount" class="p-0 text-left align-top">
      <table class="w-full">
        <colgroup>
          <col style="width: 34%; min-width: 260px" />
          <col v-if="isEstimateEntry" style="width: 12%; min-width: 120px" />
          <col v-if="isEstimateEntry" style="width: 12%; min-width: 120px" />
          <col style="width: 10%; min-width: 120px" />
          <col style="width: 15%; min-width: 120px" />
          <col
            v-if="store[storeProp].discount_per_item === 'YES'"
            style="width: 15%; min-width: 160px"
          />
          <col style="width: 15%; min-width: 120px" />
        </colgroup>
        <tbody>
          <tr v-if="!isTransportEntryTemplate">
            <td class="px-5 py-4 text-left align-top">
              <div class="flex justify-start">
                <div
                  class="flex items-center justify-center w-5 h-5 mt-2 mr-2 text-gray-300 cursor-move  handle"
                >
                  <DragIcon />
                </div>
                <div v-if="isTransportEntryTemplate" class="w-full">
                  <BaseInput
                    v-model="manualItemName"
                    :invalid="v$.name.$error"
                    :content-loading="loading"
                    small
                    placeholder="Manual consignment row"
                    @input="v$.name.$touch()"
                  />
                </div>
                <BaseItemSelect
                  v-else
                  type="Invoice"
                  :item="itemData"
                  :invalid="v$.name.$error"
                  :invalid-description="v$.description.$error"
                  :taxes="itemData.taxes"
                  :index="index"
                  :store-prop="storeProp"
                  :store="store"
                  @search="searchVal"
                  @select="onSelectItem"
                />
              </div>
            </td>
            <td v-if="isEstimateEntry" class="px-5 py-4 text-left align-top">
              <BaseInput
                v-model="truckType"
                :content-loading="loading"
                small
                placeholder="Truck Type"
                @change="syncItemToStore()"
              />
            </td>
            <td v-if="isEstimateEntry" class="px-5 py-4 text-left align-top">
              <BaseInput
                v-model="weight"
                :content-loading="loading"
                small
                placeholder="Weight"
                @change="syncItemToStore()"
              />
            </td>
            <td class="px-5 py-4 text-right align-top">
              <BaseInput
                v-model="quantity"
                :invalid="v$.quantity.$error"
                :content-loading="loading"
                type="number"
                small
                step="any"
                @change="syncItemToStore()"
                @input="v$.quantity.$touch()"
              />
            </td>
            <td class="px-5 py-4 text-left align-top">
              <div class="flex flex-col">
                <div class="flex-auto flex-fill bd-highlight">
                  <div class="relative w-full">
                    <BaseMoney
                      :key="selectedCurrency"
                      v-model="price"
                      :invalid="v$.price.$error"
                      :content-loading="loading"
                      :currency="selectedCurrency"
                    />
                  </div>
                </div>
              </div>
            </td>
            <td
              v-if="store[storeProp].discount_per_item === 'YES'"
              class="px-5 py-4 text-left align-top"
            >
              <div class="flex flex-col">
                <div class="flex" style="width: 120px" role="group">
                  <BaseInput
                    v-model="discount"
                    :invalid="v$.discount_val.$error"
                    :content-loading="loading"
                    class="
                      border-r-0
                      focus:border-r-2
                      rounded-tr-sm rounded-br-sm
                      h-[38px]
                    "
                  />
                  <BaseDropdown position="bottom-end">
                    <template #activator>
                      <BaseButton
                        :content-loading="loading"
                        class="rounded-tr-md rounded-br-md !p-2 rounded-none"
                        type="button"
                        variant="white"
                      >
                        <span class="flex items-center">
                          {{
                            itemData.discount_type == 'fixed'
                              ? currency.symbol
                              : '%'
                          }}

                          <BaseIcon
                            name="ChevronDownIcon"
                            class="w-4 h-4 ml-1 text-gray-500"
                          />
                        </span>
                      </BaseButton>
                    </template>

                    <BaseDropdownItem @click="selectFixed">
                      {{ $t('general.fixed') }}
                    </BaseDropdownItem>

                    <BaseDropdownItem @click="selectPercentage">
                      {{ $t('general.percentage') }}
                    </BaseDropdownItem>
                  </BaseDropdown>
                </div>
              </div>
            </td>
            <td class="px-5 py-4 text-right align-top">
              <div class="flex items-center justify-end text-sm">
                <span>
                  <BaseContentPlaceholders v-if="loading">
                    <BaseContentPlaceholdersText :lines="1" class="w-16 h-5" />
                  </BaseContentPlaceholders>

                  <BaseFormatMoney
                    v-else
                    :amount="total"
                    :currency="selectedCurrency"
                  />
                </span>
                <div class="flex items-center justify-center w-6 h-10 mx-2">
                  <BaseIcon
                    v-if="showRemoveButton"
                    class="h-5 text-gray-700 cursor-pointer"
                    name="TrashIcon"
                    @click="store.removeItem(index)"
                  />
                </div>
              </div>
            </td>
          </tr>
          <tr v-if="!isTransportEntryTemplate && store[storeProp].tax_per_item === 'YES'">
            <td class="px-5 py-4 text-left align-top" />
            <td colspan="4" class="px-5 py-4 text-left align-top">
              <BaseContentPlaceholders v-if="loading">
                <BaseContentPlaceholdersText
                  :lines="1"
                  class="w-24 h-8 border border-gray-200 rounded-md"
                />
              </BaseContentPlaceholders>

              <ItemTax
                v-for="(tax, index1) in itemData.taxes"
                v-else
                :key="tax.id"
                :index="index1"
                :item-index="index"
                :tax-data="tax"
                :taxes="itemData.taxes"
                :discounted-total="total"
                :total-tax="totalSimpleTax"
                :total="subtotal"
                :currency="currency"
                :update-items="syncItemToStore"
                :ability="abilities.CREATE_INVOICE"
                :store="store"
                :store-prop="storeProp"
                :discount="discount"
                @update="updateTax"
              />
            </td>
          </tr>
          <tr v-if="itemCustomFields.length > 0">
            <td
              v-if="!isTransportEntryTemplate"
              class="px-5 pb-4 text-left align-top"
            />
            <td
              :colspan="itemDetailColspan"
              class="text-left align-top"
              :class="isTransportEntryTemplate ? 'p-0 py-2' : 'px-5 pb-4'"
            >
              <!-- Invoice Template -->
              <div
                v-if="isOfficeInvoiceTemplate"
                class="overflow-hidden bg-white border border-gray-200 border-solid rounded-lg space-y-4 mb-6"
              >
                <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 border-b border-gray-200 justify-between">
                  <div class="text-left">
                    <h6 class="m-0 text-sm font-semibold tracking-wide text-gray-900 uppercase">
                      Consignment Details
                    </h6>
                  </div>
                  <div class="office-row-actions">
                    <BaseIcon
                      v-if="showRemoveButton"
                      class="h-5 text-red-500 hover:text-red-700 cursor-pointer"
                      name="TrashIcon"
                      @click="store.removeItem(index)"
                    />
                  </div>
                </div>
                <div class="office-consignment-grid p-4">
                  <CustomFieldSingle
                    v-for="(field, fieldIndex) in itemCustomFields"
                    :key="field.id"
                    :custom-field-scope="`${itemValidationScope}.items.${index}.customFields`"
                    :store="store"
                    :store-prop="storeProp"
                    :index="fieldIndex"
                    :field="field"
                    :class="{
                      'office-invoice-field': isTransportEntryTemplate,
                    }"
                  />
                  <BaseInputGroup label="Amount">
                    <BaseFormatMoney
                      :amount="transportAmount"
                      :currency="selectedCurrency"
                    />
                  </BaseInputGroup>
                </div>
              </div>

              <!-- LR Receipt Template -->
              <div
                v-else-if="isLrReceiptTemplate"
                class="overflow-hidden bg-white border border-gray-200 border-solid rounded-lg space-y-4"
              >
                <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 border-b border-gray-200">
                  <div class="text-left">
                    <h6 class="m-0 text-sm font-semibold tracking-wide text-gray-900 uppercase">
                      LR Details
                    </h6>
                  </div>
                </div>
                <div class="office-consignment-grid p-4">
                  <CustomFieldSingle
                    v-for="(field, fieldIndex) in itemCustomFields"
                    :key="field.id"
                    :custom-field-scope="`${itemValidationScope}.items.${index}.customFields`"
                    :store="store"
                    :store-prop="storeProp"
                    :index="fieldIndex"
                    :field="field"
                    :class="{
                      'office-invoice-field': isTransportEntryTemplate,
                    }"
                  />
                  <BaseInputGroup label="Net Amount">
                    <BaseFormatMoney
                      :amount="transportAmount"
                      :currency="selectedCurrency"
                    />
                  </BaseInputGroup>
                </div>
              </div>

              <!-- Generic Transport Template fallback -->
              <div
                v-else-if="isTransportEntryTemplate"
                class="office-consignment-grid"
              >
                <CustomFieldSingle
                  v-for="(field, fieldIndex) in itemCustomFields"
                  :key="field.id"
                  :custom-field-scope="`${itemValidationScope}.items.${index}.customFields`"
                  :store="store"
                  :store-prop="storeProp"
                  :index="fieldIndex"
                  :field="field"
                  :class="{
                    'office-invoice-field': isTransportEntryTemplate,
                  }"
                />
                <BaseInputGroup :label="isLrReceiptTemplate ? 'Net Amount' : 'Amount'">
                  <BaseFormatMoney
                    :amount="transportAmount"
                    :currency="selectedCurrency"
                  />
                </BaseInputGroup>
                <div class="office-row-actions">
                  <BaseIcon
                    v-if="showRemoveButton"
                    class="h-5 text-gray-700 cursor-pointer"
                    name="TrashIcon"
                    @click="store.removeItem(index)"
                  />
                </div>
              </div>
              <BaseInputGrid v-else layout="three-column">
                <CustomFieldSingle
                  v-for="(field, fieldIndex) in itemCustomFields"
                  :key="field.id"
                  :custom-field-scope="`${itemValidationScope}.items.${index}.customFields`"
                  :store="store"
                  :store-prop="storeProp"
                  :index="fieldIndex"
                  :field="field"
                />
              </BaseInputGrid>
            </td>
          </tr>
        </tbody>
      </table>
    </td>
  </tr>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Guid from 'guid'
import TaxStub from '@/scripts/admin/stub/tax'
import ItemTax from './CreateItemRowTax.vue'
import { debounce, sumBy } from 'lodash'
import abilities from '@/scripts/admin/stub/abilities'
import {
  required,
  between,
  maxLength,
  helpers,
  minValue,
} from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useItemStore } from '@/scripts/admin/stores/item'
import { useCustomFieldStore } from '@/scripts/admin/stores/custom-field'
import DragIcon from '@/scripts/components/icons/DragIcon.vue'
import CustomFieldSingle from '@/scripts/admin/components/custom-fields/CreateCustomFieldsSingle.vue'

const props = defineProps({
  store: {
    type: Object,
    default: null,
  },
  storeProp: {
    type: String,
    default: '',
  },
  itemData: {
    type: Object,
    default: null,
  },
  index: {
    type: Number,
    default: null,
  },
  type: {
    type: String,
    default: '',
  },
  loading: {
    type: Boolean,
    default: false,
  },
  currency: {
    type: [Object, String],
    required: true,
  },
  invoiceItems: {
    type: Array,
    required: true,
  },
  itemValidationScope: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update', 'remove', 'itemValidate'])

const companyStore = useCompanyStore()
const itemStore = useItemStore()
const customFieldStore = useCustomFieldStore()
const itemCustomFields = computed(() => props.itemData.customFields || [])
const lastMatchedLrDetails = ref(null)
const isOfficeInvoiceTemplate = computed(
  () => props.storeProp !== 'newRecurringInvoice' && props.store[props.storeProp].template_name === 'office_invoice'
)
const isLrReceiptTemplate = computed(
  () => props.storeProp !== 'newRecurringInvoice' && props.store[props.storeProp].template_name === 'lr_receipt'
)
const isLorryReceiptTemplate = computed(
  () => props.storeProp !== 'newRecurringInvoice' && props.store[props.storeProp].template_name === 'lorry_receipt'
)
const isTransportEntryTemplate = computed(
  () => isOfficeInvoiceTemplate.value || isLrReceiptTemplate.value || isLorryReceiptTemplate.value
)
const isEstimateEntry = computed(
  () => props.storeProp === 'newEstimate' && !isTransportEntryTemplate.value
)
const itemTableColumnCount = computed(() => {
  let count = isEstimateEntry.value ? 7 : 5

  if (props.store[props.storeProp].discount_per_item === 'YES') {
    count += 1
  }

  return count
})
const itemDetailColspan = computed(() => {
  return isTransportEntryTemplate.value ? 5 : itemTableColumnCount.value - 1
})

let route = useRoute()
const { t } = useI18n()

const quantity = computed({
  get: () => {
    return props.itemData.quantity
  },
  set: (newValue) => {
    updateItemAttribute('quantity', parseFloat(newValue))
  },
})

const manualItemName = computed({
  get: () => {
    return props.itemData.name
  },
  set: (newValue) => {
    updateItemAttribute('name', newValue)
  },
})

const truckType = computed({
  get: () => {
    return props.itemData.truck_type
  },
  set: (newValue) => {
    updateItemAttribute('truck_type', newValue)
  },
})

const weight = computed({
  get: () => {
    return props.itemData.weight
  },
  set: (newValue) => {
    updateItemAttribute('weight', newValue)
  },
})

const price = computed({
  get: () => {
    const price = props.itemData.price
    return price / 100
  },

  set: (newValue) => {
    let price = Math.round(newValue * 100)
    updateItemAttribute('price', price)
    setDiscount()
  },
})

const subtotal = computed(() => Math.round(props.itemData.price * props.itemData.quantity))

const discount = computed({
  get: () => {
    return props.itemData.discount
  },
  set: (newValue) => {
    updateItemAttribute('discount', newValue)
    setDiscount()
  },
})

const total = computed(() => {
  return subtotal.value - props.itemData.discount_val
})

const officeAmount = computed(() => {
  return Math.round(
    (getOfficeFieldNumber('Rate') +
      getOfficeFieldNumber('Other Charge') +
      getOfficeFieldNumber('LR Charge') +
      getOfficeFieldNumber('DD Charge')) *
      100
  )
})

const lrReceiptAmount = computed(() => {
  return Math.round(
    (getOfficeFieldNumber('Basic Freight') +
      getOfficeFieldNumber('Local Collection') +
      getOfficeFieldNumber('Door Delivery') +
      getOfficeFieldNumber('Hamali') +
      getOfficeFieldNumber('Docket Charge') +
      getOfficeFieldNumber('Other Charge') +
      getOfficeFieldNumber('FOV')) *
      100
  )
})

const transportAmount = computed(() => {
  return isLrReceiptTemplate.value ? lrReceiptAmount.value : officeAmount.value
})
const consignmentNumber = computed(() => {
  if (!isOfficeInvoiceTemplate.value) {
    return ''
  }

  return getOfficeField('Consignment Number')?.value || ''
})

const selectedCurrency = computed(() => {
  if (props.currency) {
    return props.currency
  } else {
    return companyStore.selectedCompanyCurrency
  }
})

const showRemoveButton = computed(() => {
  if (isLrReceiptTemplate.value) {
    return false
  }

  if (props.store[props.storeProp].items.length == 1) {
    return false
  }
  return true
})

const totalSimpleTax = computed(() => {
  return Math.round(
    sumBy(props.itemData.taxes, function (tax) {
      if (tax.amount) {
        return tax.amount
      }
      return 0
    })
  )
})

const totalTax = computed(() => totalSimpleTax.value)

const rules = {
  name: {
    required: helpers.withMessage(t('validation.required'), required),
  },
  quantity: {
    required: helpers.withMessage(t('validation.required'), required),
    maxLength: helpers.withMessage(
      t('validation.amount_maxlength'),
      maxLength(20)
    ),
  },
  price: {
    required: helpers.withMessage(t('validation.required'), required),
    maxLength: helpers.withMessage(
      t('validation.price_maxlength'),
      maxLength(20)
    ),
  },
  discount_val: {
    between: helpers.withMessage(
      t('validation.discount_maxlength'),
      between(
        0,
        computed(() => Math.abs(subtotal.value))
      )
    ),
  },
  description: {
    maxLength: helpers.withMessage(
      t('validation.notes_maxlength'),
      maxLength(65000)
    ),
  },
}

const v$ = useVuelidate(
  rules,
  computed(() => props.store[props.storeProp].items[props.index]),
  { $scope: props.itemValidationScope }
)

onMounted(() => {
  ensureTransportItemName()
  loadItemCustomFields()
})

watch(
  () => props.itemData.fields,
  () => {
    loadItemCustomFields()
  }
)

watch(
  () => props.store[props.storeProp].template_name,
  () => {
    ensureTransportItemName()
    updateItemAttribute('customFields', [])
    loadItemCustomFields()
  }
)

watch(
  () => itemCustomFields.value.map((field) => field.value),
  () => {
    syncTransportAmountToStore()
  },
  { deep: true }
)

watch(
  consignmentNumber,
  (newValue, oldValue) => {
    if (newValue === oldValue) {
      return
    }

    fetchAndApplyLrReceiptDetails(newValue)
  }
)

watch(
  () => props.store[props.storeProp].customFields?.length,
  () => {
    if (lastMatchedLrDetails.value) {
      applyLrInvoiceFields(lastMatchedLrDetails.value)
    }
  }
)

//
// if (
//   route.params.id &&
//   (props.store[props.storeProp].tax_per_item === 'YES' || 'NO')
// ) {
//   if (props.store[props.storeProp].items[props.index].taxes === undefined) {
//     props.store.$patch((state) => {
//       state[props.storeProp].items[props.index].taxes = [
//         { ...TaxStub, id: Guid.raw() },
//       ]
//     })
//   }
// }

function updateTax(data) {
  props.store.$patch((state) => {
     state[props.storeProp].items[props.index]['taxes'][data.index] = data.item
  })

  let lastTax = props.itemData.taxes[props.itemData.taxes.length - 1]

  if (lastTax?.tax_type_id !== 0) {
    props.store.$patch((state) => {
      state[props.storeProp].items[props.index].taxes.push({
        ...TaxStub,
        id: Guid.raw(),
      })
    })
  }

  syncItemToStore()
}

function setDiscount() {
  const newValue = props.store[props.storeProp].items[props.index].discount
  const absoluteSubtotal = Math.abs(subtotal.value)

  if (props.itemData.discount_type === 'percentage'){
    updateItemAttribute('discount_val', Math.round((absoluteSubtotal * newValue) / 100))
  } else {
    updateItemAttribute('discount_val', Math.min(Math.round(newValue * 100), absoluteSubtotal))
  }
}

function searchVal(val) {
  updateItemAttribute('name', val)
}

function ensureTransportItemName() {
  if (!isTransportEntryTemplate.value || props.itemData.name) {
    return
  }

  updateItemAttribute(
    'name',
    isLrReceiptTemplate.value ? `LR Receipt ${props.index + 1}` : `Consignment ${props.index + 1}`
  )
}

function getOfficeFieldNumber(label) {
  const field = getOfficeField(label)
  const value = field?.value

  if (value === null || value === undefined || value === '') {
    return 0
  }

  const numericValue = parseFloat(String(value).replace(/[^0-9.-]/g, ''))

  return Number.isNaN(numericValue) ? 0 : numericValue
}

function getOfficeField(label) {
  return itemCustomFields.value.find((_field) => _field.label === label)
}

const fetchAndApplyLrReceiptDetails = debounce(async (docketNumber) => {
  const normalizedDocketNumber = String(docketNumber || '').trim()

  if (!isOfficeInvoiceTemplate.value || !normalizedDocketNumber) {
    lastMatchedLrDetails.value = null
    return
  }

  try {
    const response = await props.store.fetchLrReceiptDetailsByDocket(normalizedDocketNumber)

    if (normalizedDocketNumber !== String(consignmentNumber.value || '').trim()) {
      return
    }

    if (!response.data?.found) {
      lastMatchedLrDetails.value = null
      return
    }

    applyLrReceiptDetails(response.data)
  } catch (error) {
    lastMatchedLrDetails.value = null
  }
}, 400)

function applyLrReceiptDetails(details) {
  lastMatchedLrDetails.value = details

  const itemFields = details.item_fields || {}
  let itemUpdated = false

  itemUpdated = setCustomFieldValue(itemCustomFields.value, 'From', itemFields.From) || itemUpdated
  itemUpdated = setCustomFieldValue(itemCustomFields.value, 'Destination', itemFields.Destination) || itemUpdated
  itemUpdated = setCustomFieldValue(itemCustomFields.value, 'Vehicle No', itemFields['Vehicle No']) || itemUpdated
  itemUpdated = setCustomFieldValue(itemCustomFields.value, 'Invoice No', itemFields['Invoice No']) || itemUpdated
  itemUpdated = setCustomFieldValue(itemCustomFields.value, 'Consignment Date', details.invoice_date) || itemUpdated
  itemUpdated = setCustomFieldValue(itemCustomFields.value, 'Pkg', itemFields.Pkg) || itemUpdated
  itemUpdated = setCustomFieldValue(itemCustomFields.value, 'Charged Weight Kgs', itemFields['Charged Weight Kgs']) || itemUpdated
  itemUpdated = setCustomFieldValue(itemCustomFields.value, 'Rate', itemFields.Rate) || itemUpdated
  itemUpdated = setCustomFieldValue(itemCustomFields.value, 'Other Charge', itemFields['Other Charge']) || itemUpdated
  itemUpdated = setCustomFieldValue(itemCustomFields.value, 'LR Charge', itemFields['LR Charge']) || itemUpdated
  itemUpdated = setCustomFieldValue(itemCustomFields.value, 'DD Charge', itemFields['DD Charge']) || itemUpdated

  if (itemUpdated) {
    props.store.$patch((state) => {
      state[props.storeProp].items[props.index].customFields = [...itemCustomFields.value]
    })

    syncItemToStore()
  }

  applyLrInvoiceFields(details)
}

function applyLrInvoiceFields(details) {
  const invoiceFields = props.store[props.storeProp].customFields || []
  let updated = false

  updated = setCustomFieldValue(
    invoiceFields,
    'GST Tax Through',
    details.invoice_fields?.['GST Tax Through']
  ) || updated

  if (!updated) {
    selectLrCustomer(details)
    return
  }

  props.store.$patch((state) => {
    state[props.storeProp].customFields = [...invoiceFields]
  })

  selectLrCustomer(details)
}

function selectLrCustomer(details) {
  if (!details.customer_id || props.store[props.storeProp].customer_id === details.customer_id) {
    return
  }

  props.store.selectCustomer(details.customer_id)
}

function setCustomFieldValue(fields, label, value) {
  if (value === null || value === undefined || value === '') {
    return false
  }

  const field = fields.find((_field) => normalizeCustomFieldLabel(_field.label) === normalizeCustomFieldLabel(label))

  if (!field || field.value === value) {
    return false
  }

  field.value = value
  return true
}

function normalizeCustomFieldLabel(label) {
  return String(label || '').replace(/[^a-z0-9]+/gi, '').toLowerCase()
}

function syncTransportAmountToStore() {
  if (!isTransportEntryTemplate.value) {
    return
  }

  const amount = transportAmount.value

  props.store.$patch((state) => {
    state[props.storeProp].items[props.index].quantity = 1
    state[props.storeProp].items[props.index].price = amount
    state[props.storeProp].items[props.index].discount = 0
    state[props.storeProp].items[props.index].discount_val = 0
    state[props.storeProp].items[props.index].tax = 0
    state[props.storeProp].items[props.index].total = amount
  })

  syncItemToStore()
}

function onSelectItem(itm) {
  props.store.$patch((state) => {
    state[props.storeProp].items[props.index].name = itm.name
    state[props.storeProp].items[props.index].price = itm.price
    state[props.storeProp].items[props.index].item_id = itm.id
    state[props.storeProp].items[props.index].description = itm.description

    if (itm.unit) {
      state[props.storeProp].items[props.index].unit_name = itm.unit.name
    }

    if (props.store[props.storeProp].tax_per_item === 'YES' && itm.taxes) {
      let index = 0

      itm.taxes.forEach((tax) => {
        updateTax({ index, item: { ...tax } })
        index++
      })
    }

    if (state[props.storeProp].exchange_rate) {
      state[props.storeProp].items[props.index].price /=
        state[props.storeProp].exchange_rate
    }
  })

  itemStore.fetchItems()
  syncItemToStore()
}

function selectFixed() {
  if (props.itemData.discount_type === 'fixed') {
    return
  }

  updateItemAttribute('discount_val', Math.round(props.itemData.discount * 100))
  updateItemAttribute('discount_type', 'fixed')
}

function selectPercentage() {
  if (props.itemData.discount_type === 'percentage') {
    return
  }

  updateItemAttribute(
    'discount_val',
    (subtotal.value * props.itemData.discount) / 100
  )

  updateItemAttribute('discount_type', 'percentage')
}

function syncItemToStore() {
  let itemTaxes = props.store[props.storeProp]?.items[props.index]?.taxes

  if (!itemTaxes) {
    itemTaxes = []
  }

  let data = {
    ...props.store[props.storeProp].items[props.index],
    index: props.index,
    total: total.value,
    sub_total: subtotal.value,
    totalSimpleTax: totalSimpleTax.value,
    totalTax: totalTax.value,
    tax: totalTax.value,
    taxes: [...itemTaxes],
    tax_type_ids: itemTaxes.flatMap(_t =>
      _t.tax_type_id ? _t.tax_type_id : [],
    ),
  }

  props.store.updateItem(data)
}

function updateItemAttribute(attribute, value) {
  props.store.$patch((state) => {
    state[props.storeProp].items[props.index][attribute] = value
  })

  syncItemToStore()
}

async function loadItemCustomFields() {
  if (props.itemData.customFields?.length) {
    return
  }

  const templateName = props.storeProp === 'newRecurringInvoice' ? null : props.store[props.storeProp].template_name

  const response = await customFieldStore.fetchCustomFields({
    type: 'Item',
    limit: 'all',
    template_name: templateName,
  })

  let fields = response.data.data.map((field) => ({
    ...field,
    value: field.default_answer,
  }))

  if (props.itemData.fields?.length) {
    fields = fields.map((field) => {
      const existingField = props.itemData.fields.find(
        (_field) => _field.custom_field_id === field.id
      )

      if (!existingField) {
        return field
      }

      return {
        ...field,
        ...existingField,
        id: existingField.custom_field_id,
        value: existingField.default_answer,
        label: existingField.custom_field.label,
        options: existingField.custom_field.options,
        is_required: existingField.custom_field.is_required,
        placeholder: existingField.custom_field.placeholder,
        order: existingField.custom_field.order,
      }
    })
  }

  updateItemAttribute(
    'customFields',
    fields.sort((firstField, secondField) => firstField.order - secondField.order)
  )

  syncTransportAmountToStore()
}
</script>

<style scoped>
.office-consignment-grid {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(1, minmax(0, 1fr));
}

@media (min-width: 640px) {
  .office-consignment-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 768px) {
  .office-consignment-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .office-consignment-grid {
    grid-template-columns: repeat(6, minmax(120px, 1fr));
  }
}

.office-row-actions {
  align-items: end;
  display: flex;
  min-height: 48px;
}

.office-invoice-field :deep(label) {
  font-size: 11px;
  line-height: 14px;
  margin-bottom: 3px;
}

.office-invoice-field :deep(input),
.office-invoice-field :deep(textarea),
.office-invoice-field :deep(select) {
  min-height: 30px;
}
</style>
