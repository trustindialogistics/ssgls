<template>
  <BaseInputGroup
    v-if="!isHiddenTransportField"
    :label="field.label"
    :required="field.is_required ? true : false"
    :error="v$.value.$error && v$.value.$errors[0].$message"
  >
    <component
      :is="getTypeComponent"
      v-model="field.value"
      :options="field.options"
      :invalid="v$.value.$error"
      :placeholder="field.placeholder"
      @input="handleInput"
    />
  </BaseInputGroup>
</template>

<script setup>
import { defineAsyncComponent, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { helpers, requiredIf } from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  customFieldScope: {
    type: String,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
  store: {
    type: Object,
    required: true,
  },
  storeProp: {
    type: String,
    required: true,
  },
})

const { t } = useI18n()

const rules = {
  value: {
    required: helpers.withMessage(
      t('validation.required'),
      requiredIf(() => props.field.is_required && !isHiddenTransportField.value)
    ),
  },
}

const v$ = useVuelidate(
  rules,
  computed(() => props.field),
  { $scope: props.customFieldScope }
)

const getTypeComponent = computed(() => {
  if (props.field.type) {
    return defineAsyncComponent(() =>
      import(`./types/${props.field.type}Type.vue`)
    )
  }

  return false
})

const isHiddenLrField = computed(() => {
  return (
    props.store[props.storeProp]?.template_name === 'lr_receipt' &&
    [
      'Time',
      'Consignor',
      'Consignor Email',
      'Consignor Phone No',
      'Consignor GST No',
      'Consignee',
      'Consignee Phone No',
      'Consignee GST No',
    ].includes(props.field.label)
  )
})

const isHiddenLorryField = computed(() => {
  return (
    props.store[props.storeProp]?.template_name === 'lorry_receipt' &&
    [
      'Owner Code',
      'Owner Name',
      'Driver Name',
      'Broker Name',
      'Broker Code',
      'Paid To',
      'Gross Hire Amount',
      'Balance Rupees',
      'Advance Received By',
      'Final Paid To',
    ].includes(props.field.label)
  )
})

const isHiddenOfficeField = computed(() => {
  return (
    props.store[props.storeProp]?.template_name === 'office_invoice' &&
    props.field.label === 'GST Tax Through'
  )
})

const isHiddenTransportField = computed(() => isHiddenLrField.value || isHiddenLorryField.value || isHiddenOfficeField.value)

onMounted(() => {
  if (isHiddenLrField.value && props.field.label === 'Time') {
    props.field.value = ''
  }
})

watch(
  () => props.field.value,
  (val) => {
    if (val) {
      if (props.field.label === 'Consignment Number') {
        const normalized = String(val).replace(/[^0-9]/g, '')
        if (props.field.value !== normalized) {
          props.field.value = normalized
        }
      } else if (props.field.label === 'Received No Of Bilties' || props.field.label === 'Received No. of Bilties' || props.field.label === 'Docket No') {
        const normalized = String(val).replace(/[^0-9,]/g, '').replace(/,+/g, ',')
        if (props.field.value !== normalized) {
          props.field.value = normalized
        }
      }
    }
  }
)

function handleInput(event) {
  if (props.field.label === 'Received No Of Bilties' || props.field.label === 'Received No. of Bilties' || props.field.label === 'Docket No') {
    normalizeBilties(event)
  } else if (props.field.label === 'Consignment Number') {
    normalizeConsignment(event)
  }
}

function normalizeBilties(event) {
  let value = event.target.value
  value = value.replace(/[^0-9,]/g, '')
  value = value.replace(/,+/g, ',')
  event.target.value = value
  props.field.value = value
}

function normalizeConsignment(event) {
  let value = event.target.value
  value = value.replace(/[^0-9]/g, '')
  event.target.value = value
  props.field.value = value
}
</script>
