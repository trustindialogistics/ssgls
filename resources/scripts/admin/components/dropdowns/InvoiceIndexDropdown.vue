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

    <!-- View Lorry Receipt -->
    <router-link
      v-if="
        isLrReceipt &&
        row.matching_lorry_receipt_invoice_id &&
        userStore.hasAbilities(abilities.VIEW_INVOICE)
      "
      :to="`/admin/lorry-receipts/${row.matching_lorry_receipt_invoice_id}/view`"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="EyeIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        View Lorry Receipt
      </BaseDropdownItem>
    </router-link>

    <!-- Download PDF -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.VIEW_INVOICE)"
      @click="downloadPdf"
    >
      <BaseIcon
        name="ArrowDownTrayIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      {{ downloadLabel }}
    </BaseDropdownItem>

    <!-- Download Lorry Receipt Document -->
    <BaseDropdownItem
      v-if="isLorryReceipt && userStore.hasAbilities(abilities.VIEW_INVOICE)"
      @click="downloadLorryReceiptWithDocuments"
    >
      <BaseIcon
        name="DocumentArrowDownIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      Download Lorry Receipt Document
    </BaseDropdownItem>

    <!-- Download Multi LR -->
    <BaseDropdownItem
      v-if="isLrReceipt && userStore.hasAbilities(abilities.VIEW_INVOICE)"
      @click="downloadMultiPdf"
    >
      <BaseIcon
        name="ArrowDownTrayIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      Download Multi LR
    </BaseDropdownItem>

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
        v-slot="{ active }"
        v-if="['DRAFT', 'SENT', 'VIEWED'].includes(row.status) && !isViewRoute"
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

    <BaseDropdownItem
      v-if="!isTransportReceipt && userStore.hasAbilities(abilities.EDIT_INVOICE)"
      @click="openPodUpload(row)"
    >
      <BaseIcon
        name="ArrowUpTrayIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      Upload POD
    </BaseDropdownItem>

    <BaseDropdownItem v-if="!isTransportReceipt && row.pod_url" @click="viewPod(row)">
      <BaseIcon
        name="PhotoIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      View POD
    </BaseDropdownItem>

    <!-- View Attached Documents -->
    <BaseDropdownItem
      v-slot="{ active }"
      v-if="isLorryReceipt && hasLorryDocuments"
      @click="viewAttachedDocuments(row)"
    >
      <BaseIcon
        name="PhotoIcon"
        class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
      />
      View Attached Document
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
import http from '@/scripts/http'
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
  lrCopyType: {
    type: String,
    default: null,
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
const isLorryReceiptRoute = computed(() => route.name?.startsWith('lorry-receipts'))
const rowResourceBasePath = computed(() => {
  if (props.row?.template_name === 'lorry_receipt') {
    return '/admin/lorry-receipts'
  }

  if (props.row?.template_name === 'lr_receipt') {
    return '/admin/lr-receipts'
  }

  return '/admin/invoices'
})
const resourceBasePath = computed(() => props.resourceBasePath || (isLorryReceiptRoute.value ? '/admin/lorry-receipts' : isLrReceiptRoute.value ? '/admin/lr-receipts' : rowResourceBasePath.value))
const afterDeletePath = computed(() => props.afterDeletePath || resourceBasePath.value)
const isViewRoute = computed(() => ['invoices.view', 'lr-receipts.view', 'lorry-receipts.view'].includes(route.name))
const isLrReceipt = computed(() => isLrReceiptRoute.value || props.row?.template_name === 'lr_receipt')
const isLorryReceipt = computed(() => isLorryReceiptRoute.value || props.row?.template_name === 'lorry_receipt')
const isTransportReceipt = computed(() => isLrReceipt.value || isLorryReceipt.value)
const hasLorryDocuments = computed(() => {
  return props.row?.lorry_documents && Object.values(props.row.lorry_documents).some(doc => doc !== null)
})
const receiptTitle = computed(() => isLorryReceipt.value ? 'Lorry Receipt' : 'LR Receipt')
const sendLabel = computed(() => isTransportReceipt.value ? `Send ${receiptTitle.value}` : t('invoices.send_invoice'))
const resendLabel = computed(() => isTransportReceipt.value ? `Resend ${receiptTitle.value}` : t('invoices.resend_invoice'))
const cloneLabel = computed(() => isTransportReceipt.value ? `Clone ${receiptTitle.value}` : t('invoices.clone_invoice'))
const deleteMessage = computed(() => isTransportReceipt.value ? `Are you sure you want to delete this ${receiptTitle.value}?` : t('invoices.confirm_delete'))

const downloadLabel = computed(() => {
  if (isLorryReceipt.value) {
    return 'Download Lorry Receipt'
  }
  if (isLrReceipt.value) {
    return 'Download LR Receipt'
  }
  return 'Download Invoice'
})

function downloadPdf() {
  let templateParam = props.row.template_name ? `&template_name=${props.row.template_name}` : ''
  let copyParam = props.lrCopyType ? `&copy=${props.lrCopyType}` : ''
  let downloadUrl = `${window.location.origin}/invoices/pdf/${props.row.unique_hash}?download=1${templateParam}${copyParam}`
  window.open(downloadUrl, '_blank')
}

function downloadMultiPdf() {
  let templateParam = props.row.template_name ? `&template_name=${props.row.template_name}` : ''
  let downloadUrl = `${window.location.origin}/invoices/pdf/${props.row.unique_hash}?download=1${templateParam}&copy=multi`
  window.open(downloadUrl, '_blank')
}

async function downloadLorryReceiptWithDocuments() {
  try {
    const response = await http.get(`/api/v1/invoices/${props.row.id}/download-with-documents`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `lorry-receipt-${props.row.id}-with-documents.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Failed to download lorry receipt with documents', error)
  }
}

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
      message: deleteMessage.value,
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      id = id
      if (res) {
        invoiceStore.deleteInvoice({
          ids: [id],
          template_name: isTransportReceipt.value ? props.row?.template_name : props.row?.template_name,
        }).then((res) => {
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
          const basePath = data.template_name === 'lorry_receipt'
            ? '/admin/lorry-receipts'
            : data.template_name === 'lr_receipt'
              ? '/admin/lr-receipts'
              : '/admin/invoices'
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
    title: isTransportReceipt.value ? `Send ${receiptTitle.value}` : t('invoices.send_invoice'),
    componentName: 'SendInvoiceModal',
    id: invoice.id,
    data: {
      ...invoice,
      copy_type: isLrReceipt.value ? props.lrCopyType : null,
    },
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

function openPodUpload(invoice) {
  modalStore.openModal({
    title: `Upload POD - ${invoice.invoice_number}`,
    componentName: 'UploadPodModal',
    id: invoice.id,
    data: invoice,
    size: 'sm',
    refreshData: () => {
      props.table && props.table.refresh()
      props.loadData && props.loadData()
    },
  })
}

function viewPod(invoice) {
  window.open(invoice.pod_url, '_blank', 'noopener')
}

function viewAttachedDocuments(invoice) {
  let pdfUrl = `${window.location.origin}/invoices/pdf/${invoice.unique_hash}?include_documents=1`
  window.open(pdfUrl, '_blank', 'noopener')
}
</script>
