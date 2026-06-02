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
    />
  </BaseInputGroup>
</template>

<script setup>
import { defineAsyncComponent, computed, onMounted } from 'vue'
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
      requiredIf(props.field.is_required)
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

const isHiddenTransportField = computed(() => isHiddenLrField.value || isHiddenLorryField.value)

onMounted(() => {
  if (isHiddenLrField.value && props.field.label === 'Time') {
    props.field.value = ''
  }
})
</script>
