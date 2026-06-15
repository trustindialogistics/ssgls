<template>
  <BaseDropdown :content-loading="customerStore.isFetchingViewData">
    <template #activator>
      <BaseButton v-if="isViewPage" variant="primary">
        <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-white" />
      </BaseButton>
      <BaseIcon v-else name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
    </template>

    <!-- Edit Customer  -->
    <router-link
      v-if="userStore.hasAbilities(abilities.EDIT_CUSTOMER)"
      :to="editLink"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="PencilIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.edit') }}
      </BaseDropdownItem>
    </router-link>

    <!-- View Customer -->
    <router-link
      v-if="
        !isViewPage &&
        userStore.hasAbilities(abilities.VIEW_CUSTOMER)
      "
      :to="viewLink"
    >
      <BaseDropdownItem>
        <BaseIcon
          name="EyeIcon"
          class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
        />
        {{ $t('general.view') }}
      </BaseDropdownItem>
    </router-link>

    <!-- Delete Customer  -->
    <BaseDropdownItem
      v-if="userStore.hasAbilities(abilities.DELETE_CUSTOMER)"
      @click="removeCustomer(row.id)"
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
import { useCustomerStore } from '@/scripts/admin/stores/customer'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useModalStore } from '@/scripts/stores/modal'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import { inject, computed } from 'vue'
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
})

const customerStore = useCustomerStore()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const utils = inject('utils')

const isConsignee = computed(() => route.name?.startsWith('consignees'))
const editLink = computed(() => isConsignee.value ? `/admin/consignees/${props.row.id}/edit` : `/admin/customers/${props.row.id}/edit`)
const viewLink = computed(() => isConsignee.value ? `/admin/consignees/${props.row.id}/view` : `/admin/customers/${props.row.id}/view`)
const isViewPage = computed(() => ['customers.view', 'consignees.view'].includes(route.name))

function removeCustomer(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('customers.confirm_delete', 1),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then((res) => {
      if (res) {
        customerStore.deleteCustomer({ ids: [id] }).then((response) => {
          if (response.data.success) {
            props.loadData && props.loadData()
            return true
          }
        })
      }
    })
}
</script>
