<template>
  <BasePage>
    <BasePageHeader :title="title">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/dashboard" />
        <BaseBreadcrumbItem :title="title" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-5">
          <BaseButton
            v-show="store.totalProfiles"
            variant="primary-outline"
            @click="toggleFilter"
          >
            {{ $t('general.filter') }}
            <template #right="slotProps">
              <BaseIcon
                v-if="!showFilters"
                name="FunnelIcon"
                :class="slotProps.class"
              />
              <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
            </template>
          </BaseButton>

          <BaseButton @click="$router.push(`${basePath}/create`)">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            New {{ singularTitle }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper :show="showFilters" class="mt-5" @clear="clearFilter">
      <BaseInputGroup :label="`${singularTitle} Name`" class="text-left">
        <BaseInput v-model="filters.search" type="text" autocomplete="off" />
      </BaseInputGroup>

      <BaseInputGroup label="Phone No." class="text-left">
        <BaseInput v-model="filters.phone" type="text" autocomplete="off" />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="`No ${singularTitle.toLowerCase()}s found`"
      :description="`Create ${singularTitle.toLowerCase()} profiles for Lorry Receipt Section B.`"
    >
      <AstronautIcon class="mt-5 mb-4" />

      <template #actions>
        <BaseButton variant="primary-outline" @click="$router.push(`${basePath}/create`)">
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          Add New {{ singularTitle }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <div class="relative flex items-center justify-end h-5">
        <BaseDropdown v-if="store.selectedProfiles.length">
          <template #activator>
            <span
              class="flex text-sm font-medium cursor-pointer select-none text-primary-400"
            >
              {{ $t('general.actions') }}
              <BaseIcon name="ChevronDownIcon" />
            </span>
          </template>

          <BaseDropdownItem @click="removeMultipleProfiles">
            <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <BaseTable
        :key="type"
        ref="tableComponent"
        class="mt-3"
        :data="fetchData"
        :columns="columns"
      >
        <template #header>
          <div class="absolute z-10 items-center left-6 top-2.5 select-none">
            <BaseCheckbox
              v-model="selectAllFieldStatus"
              variant="primary"
              @change="store.selectAllProfiles"
            />
          </div>
        </template>

        <template #cell-status="{ row }">
          <div class="relative block">
            <BaseCheckbox
              :id="row.data.id"
              v-model="selectField"
              :value="row.data.id"
              variant="primary"
            />
          </div>
        </template>

        <template #cell-name="{ row }">
          <router-link :to="`${basePath}/${row.data.id}/edit`">
            <BaseText
              :text="row.data.name || '-'"
              tag="span"
              class="font-medium text-primary-500 flex flex-col"
            />
            <BaseText
              :text="secondaryText(row.data)"
              tag="span"
              class="text-xs text-gray-400"
            />
          </router-link>
        </template>

        <template #cell-phone="{ row }">
          <span>{{ row.data.phone || '-' }}</span>
        </template>

        <template #cell-created_at="{ row }">
          <span>{{ row.data.formatted_created_at || '-' }}</span>
        </template>

        <template #cell-actions="{ row }">
          <BaseDropdown>
            <template #activator>
              <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
            </template>

            <router-link :to="`${basePath}/${row.data.id}/edit`">
              <BaseDropdownItem>
                <BaseIcon
                  name="PencilIcon"
                  class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
                />
                {{ $t('general.edit') }}
              </BaseDropdownItem>
            </router-link>

            <BaseDropdownItem @click="removeProfile(row.data.id)">
              <BaseIcon
                name="TrashIcon"
                class="w-5 h-5 mr-3 text-gray-400 group-hover:text-gray-500"
              />
              {{ $t('general.delete') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { debouncedWatch } from '@vueuse/core'
import { computed, nextTick, onUnmounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useLorryPartyProfileStore } from '@/scripts/admin/stores/lorry-party-profile'
import AstronautIcon from '@/scripts/components/icons/empty/AstronautIcon.vue'

defineOptions({ name: 'LorryPartyProfileIndex' })

const route = useRoute()
const { t } = useI18n()
const store = useLorryPartyProfileStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

const tableComponent = ref(null)
const showFilters = ref(false)
const isFetchingInitialData = ref(true)

const filters = reactive({
  search: '',
  phone: '',
})

const type = computed(() => route.meta.profileType)
const portalTitle = {
  OWNER: 'Owner List',
  DRIVER: 'Driver List',
  BROKER: 'Broker List',
}
const singularByType = {
  OWNER: 'Owner',
  DRIVER: 'Driver',
  BROKER: 'Broker',
}
const basePathByType = {
  OWNER: '/admin/owner-portal',
  DRIVER: '/admin/driver-portal',
  BROKER: '/admin/broker-portal',
}

const title = computed(() => portalTitle[type.value] || 'Party List')
const singularTitle = computed(() => singularByType[type.value] || 'Party')
const basePath = computed(() => basePathByType[type.value] || '/admin/owner-portal')
const showEmptyScreen = computed(
  () => !store.totalProfiles && !isFetchingInitialData.value
)

const selectField = computed({
  get: () => store.selectedProfiles,
  set: (value) => store.selectProfile(value),
})

const selectAllFieldStatus = computed({
  get: () => store.selectAllField,
  set: (value) => store.setSelectAllState(value),
})

const columns = computed(() => [
  {
    key: 'status',
    thClass: 'extra w-10 pr-0',
    sortable: false,
    tdClass: 'font-medium text-gray-900 pr-0',
  },
  {
    key: 'name',
    label: `${singularTitle.value} Name`,
    thClass: 'extra',
    tdClass: 'font-medium text-gray-900',
  },
  { key: 'phone', label: 'Phone No.' },
  {
    key: 'created_at',
    label: t('items.added_on'),
  },
  {
    key: 'actions',
    tdClass: 'text-right text-sm font-medium pl-0',
    thClass: 'pl-0',
    sortable: false,
  },
])

debouncedWatch(
  filters,
  () => {
    refreshTable()
  },
  { debounce: 500 }
)

watch(
  type,
  async () => {
    store.resetProfiles()
    clearFilter()
    showFilters.value = false
    isFetchingInitialData.value = true
    await nextTick()
    refreshTable()
  }
)

onUnmounted(() => {
  store.resetSelection()
})

function secondaryText(profile) {
  if (type.value === 'OWNER') {
    return profile.financer_name || profile.address || ''
  }

  if (type.value === 'DRIVER') {
    return profile.licence_no || profile.place || ''
  }

  return profile.destination_broker_name || profile.address || ''
}

function refreshTable() {
  tableComponent.value?.refresh()
}

async function fetchData({ page, sort }) {
  const requestedType = type.value
  isFetchingInitialData.value = true

  const response = await store.fetchProfiles({
    type: requestedType,
    search: filters.search,
    phone: filters.phone,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  })

  if (requestedType !== type.value) {
    return {
      data: [],
      pagination: {
        totalPages: 1,
        currentPage: 1,
        totalCount: 0,
        limit: 10,
      },
    }
  }

  isFetchingInitialData.value = false

  return {
    data: response.data.data || [],
    pagination: {
      totalPages: response.data.meta?.last_page || 1,
      currentPage: page,
      totalCount: response.data.meta?.total || 0,
      limit: 10,
    },
  }
}

function clearFilter() {
  filters.search = ''
  filters.phone = ''
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }

  showFilters.value = !showFilters.value
}

function confirmDelete(count) {
  return dialogStore.openDialog({
    title: t('general.are_you_sure'),
    message: `This will delete ${count === 1 ? 'this profile' : 'these profiles'}.`,
    yesLabel: t('general.ok'),
    noLabel: t('general.cancel'),
    variant: 'danger',
    hideNoButton: false,
    size: 'lg',
  })
}

async function removeProfile(id) {
  const confirmed = await confirmDelete(1)

  if (!confirmed) {
    return
  }

  await store.deleteProfile(id)
  notificationStore.showNotification({
    type: 'success',
    message: `${singularTitle.value} profile deleted successfully.`,
  })
  refreshTable()
}

async function removeMultipleProfiles() {
  const confirmed = await confirmDelete(store.selectedProfiles.length)

  if (!confirmed) {
    return
  }

  await store.deleteSelectedProfiles()
  notificationStore.showNotification({
    type: 'success',
    message: `${singularTitle.value} profiles deleted successfully.`,
  })
  refreshTable()
}
</script>
