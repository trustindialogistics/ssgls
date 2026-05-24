<template>
  <BaseDropdown>
    <template #activator>
      <BaseButton v-if="route.name === 'invoices.view'" variant="primary">
        <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-white" />
      </BaseButton>
      <BaseIcon v-else name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- Edit Invoice  -->
    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_INVOICE)"
      :to="`${resourceBasePath}/${row.id}/edit`"
    >
      <BaseDropdownItem v-show="row.allow_edit">
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Copy PDF url  -->
    <BaseDropdownItem v-if="route.name === 'invoices.view'" @click="copyPdfUrl">
      <BaseIcon
        name="LinkIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.copy_pdf_url') }}
    </BaseDropdownItem>

    <!-- View Invoice  -->
    <router-link
      v-if="
        !isViewRoute &&
        userStore.hasAbilities(abilities.VIEW_INVOICE)
      "
      :to="`${resourceBasePath}/${row.id}/view`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="EyeIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.view') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Send Invoice Mail  -->
    <BaseDropdownItem v-if="canSendInvoice(row)" @click="sendInvoice(row)">
      <BaseIcon
        name="PaperAirplaneIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ sendLabel }}
    </BaseDropdownItem>

    <!-- Resend Invoice -->
    <BaseDropdownItem v-if="canReSendInvoice(row)" @click="sendInvoice(row)">
      <BaseIcon
        name="PaperAirplaneIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ resendLabel }}
    </BaseDropdownItem>

    <!-- Record payment  -->
    <router-link v-if="showPaymentAction" :to="`/admin/payments/${row.id}/create`">
      <BaseDropdownItem
        v-if="row.status == 'SENT' && !isViewRoute"
      >
        <BaseIcon
          name="CreditCardIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('invoices.record_payment') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Mark as sent Invoice -->
    <BaseDropdownItem v-if="canSendInvoice(row)" @click="onMarkAsSent(row.id)">
      <BaseIcon
        name="CheckCircleIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('invoices.mark_as_sent') }}
    </BaseDropdownItem>

    <!-- Clone Invoice into new invoice  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.CREATE_INVOICE)"
      @click="cloneInvoiceData(row)"
    >
      <BaseIcon
        name="DocumentTextIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ cloneLabel }}
    </BaseDropdownItem>

    <!--  Delete Invoice  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.DELETE_INVOICE)"
      @click="removeInvoice(row.id)"
    >
      <BaseIcon
        name="TrashIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ $t('general.delete') }}
    </BaseDropdownItem>
  </BaseDropdown>
</template>

<script setup>
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useModalStore } from '@/scripts/stores/modal'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import { computed, inject } from 'vue'
import abilities from '@/scripts/admin/stub/abilities'

const props = defineProps({
  row: {
    type: Object,
    default: null,
  },
  table: {
    type: Object,
    default: null,
  },
  loadData: {
    type: Function,
    default: () => {},
  },
  resourceBasePath: {
    type: String,
    default: '',
  },
  afterDeletePath: {
    type: String,
    default: '',
  },
  showPaymentAction: {
    type: Boolean,
    default: true,
  },
})

const invoiceStore = useInvoiceStore()
const modalStore = useModalStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const utils = inject('utils')
const isLrReceiptRoute = computed(() => route.name?.startsWith('lr-receipts'))
const resourceBasePath = computed(() => props.resourceBasePath || (isLrReceiptRoute.value ? '/admin/lr-receipts' : '/admin/invoices'))
const afterDeletePath = computed(() => props.afterDeletePath || resourceBasePath.value)
const isViewRoute = computed(() => route.name === 'invoices.view' || route.name === 'lr-receipts.view')
const isLrReceipt = computed(() => isLrReceiptRoute.value || props.row?.template_name === 'lr_receipt')
const sendLabel = computed(() => isLrReceipt.value ? 'Send LR Receipt' : t('invoices.send_invoice'))
const resendLabel = computed(() => isLrReceipt.value ? 'Resend LR Receipt' : t('invoices.resend_invoice'))
const cloneLabel = computed(() => isLrReceipt.value ? 'Clone LR Receipt' : t('invoices.clone_invoice'))

function canReSendInvoice(row) {
  return (
    (row.status == 'SENT' || row.status == 'VIEWED') &&
    userStore.hasAbilities(abilities.SEND_INVOICE)
  )
}

function canSendInvoice(row) {
  return (
    row.status == 'DRAFT' &&
    !isViewRoute.value &&
    userStore.hasAbilities(abilities.SEND_INVOICE)
  )
}

async function removeInvoice(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      id = id
      if (res) {
        invoiceStore.deleteInvoice({ ids: [id] }).then((res) => {
          if (res.data.success) {
            router.push(afterDeletePath.value)
            props.table && props.table.refresh()

            invoiceStore.$patch((state) => {
              state.selectedInvoices = []
              state.selectAllField = false
            })
          }
        })
      }
    })
}

async function cloneInvoiceData(data) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.confirm_clone'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        invoiceStore.cloneInvoice(data).then((res) => {
          const basePath = data.template_name === 'lr_receipt' ? '/admin/lr-receipts' : '/admin/invoices'
          router.push(`${basePath}/${res.data.data.id}/edit`)
        })
      }
    })
}

async function onMarkAsSent(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('invoices.invoice_mark_as_sent'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then((response) => {
      const data = {
        id: id,
        status: 'SENT',
      }
      if (response) {
        invoiceStore.markAsSent(data).then((response) => {
          props.table && props.table.refresh()
        })
      }
    })
}

async function sendInvoice(invoice) {
  modalStore.openModal({
    title: t('invoices.send_invoice'),
    componentName: 'SendInvoiceModal',
    id: invoice.id,
    data: invoice,
    variant: 'sm',
  })
}

function copyPdfUrl() {
  let pdfUrl = `${window.location.origin}/invoices/pdf/${props.row.unique_hash}`

  utils.copyTextToClipboard(pdfUrl)

  notificationStore.showNotification({
    type: 'success',
    message: t('general.copied_pdf_url_clipboard'),
  })
}
</script>
