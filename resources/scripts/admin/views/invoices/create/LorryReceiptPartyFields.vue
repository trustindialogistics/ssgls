<template>
  <div class="mb-8">
    <div class="grid gap-y-6 gap-x-4 grid-cols-1 md:grid-cols-2">
      <div
        v-for="profile in profileInputs"
        :key="profile.key"
        class="relative flex flex-col rounded-md"
      >
        <div
          v-if="selectedProfiles[profile.key]"
          class="flex flex-col p-4 bg-white border border-gray-200 border-solid min-h-[170px] rounded-md"
        >
          <div class="flex relative justify-between gap-3 mb-2">
            <BaseText
              :text="selectedProfiles[profile.key].name"
              class="flex-1 text-base font-medium text-left text-gray-900"
            />
            <div class="flex flex-wrap justify-end gap-x-4 gap-y-2">
              <a
                v-if="selectedProfiles[profile.key].id"
                class="relative my-0 text-sm flex items-center font-medium cursor-pointer text-primary-500"
                @click.stop="openProfileEdit(profile)"
              >
                <BaseIcon name="PencilIcon" class="text-gray-500 h-4 w-4 mr-1" />
                {{ $t('general.edit') }}
              </a>
              <a
                class="relative my-0 text-sm flex items-center font-medium cursor-pointer text-primary-500"
                @click="resetProfile(profile)"
              >
                <BaseIcon name="XCircleIcon" class="text-gray-500 h-4 w-4 mr-1" />
                {{ $t('general.deselect') }}
              </a>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-8 mt-2">
            <div class="flex flex-col">
              <label class="mb-1 text-sm font-medium text-left text-gray-400 uppercase whitespace-nowrap">
                {{ profile.summaryLabel }}
              </label>
              <div class="flex flex-col flex-1 p-0 text-left">
                <label
                  v-for="line in formatProfileLines(selectedProfiles[profile.key])"
                  :key="`${profile.key}-${line}`"
                  class="relative w-11/12 text-sm truncate"
                >
                  {{ line }}
                </label>
              </div>
            </div>
            
            <!-- Document Attachments Section -->
            <div class="flex flex-col">
              <label class="mb-1 text-sm font-medium text-left text-gray-400 uppercase whitespace-nowrap">
                Attached Documents
              </label>
              <div class="flex flex-col flex-1 p-0 text-left">
                <template v-for="doc in getProfileDocuments(selectedProfiles[profile.key], profile.type)" :key="doc.label">
                  <a
                    v-if="doc.path"
                    :href="doc.path"
                    target="_blank"
                    class="relative w-11/12 text-sm truncate text-primary-500 hover:text-primary-600 flex items-center gap-1 mb-1"
                  >
                    <BaseIcon name="DocumentIcon" class="h-4 w-4" />
                    {{ doc.label }}
                  </a>
                  <span v-else class="relative w-11/12 text-sm truncate text-gray-400 mb-1 block">
                    {{ doc.label }} (Not attached)
                  </span>
                </template>
              </div>
            </div>
          </div>
        </div>

        <Popover v-else v-slot="{ open }" class="relative flex flex-col rounded-md">
          <PopoverButton
            :class="{
              'focus:ring-2 focus:ring-primary-400': !open,
            }"
            class="w-full outline-hidden rounded-md"
            @click="ensureProfilesLoaded(profile)"
          >
            <div class="relative flex justify-center px-0 p-0 py-16 bg-white border border-gray-200 border-solid rounded-md min-h-[170px]">
              <BaseIcon
                name="UserIcon"
                class="flex justify-center !w-10 !h-10 p-2 mr-5 text-sm text-white bg-gray-200 rounded-full font-base"
              />
              <div class="mt-1">
                <label class="text-lg font-medium text-gray-900">
                  {{ profile.label }}
                </label>
              </div>
            </div>
          </PopoverButton>

          <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-1 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-1 opacity-0"
          >
            <div v-if="open" class="absolute min-w-full z-10">
              <PopoverPanel
                v-slot="{ close }"
                focus
                static
                class="overflow-hidden rounded-md shadow-lg ring-1 ring-black/5 bg-white"
              >
                <div class="relative">
                  <BaseInput
                    v-model="profileSearch[profile.key]"
                    container-class="m-4"
                    :placeholder="$t('general.search')"
                    type="text"
                    icon="search"
                    @update:model-value="debounceSearchProfiles(profile)"
                  />

                  <ul class="max-h-80 flex flex-col overflow-auto list border-t border-gray-200">
                    <li
                      v-for="option in profileOptions[profile.key]"
                      :key="option.id"
                      class="flex px-6 py-2 border-b border-gray-200 border-solid cursor-pointer hover:cursor-pointer hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100"
                      @click="selectProfile(profile, option, close)"
                    >
                      <div class="flex items-center justify-center h-10 w-10 mr-4 rounded-full bg-gray-100 uppercase text-primary-500">
                        {{ (option.name || profile.singular).charAt(0) }}
                      </div>
                      <div class="flex-1 flex flex-col text-left">
                        <span class="text-sm font-medium text-gray-900">
                          {{ option.name }}
                        </span>
                        <span class="text-xs text-gray-500">
                          {{ option.phone || option.address }}
                        </span>
                      </div>
                    </li>
                  </ul>

                  <button
                    type="button"
                    class="flex items-center justify-center w-full px-6 py-3 bg-gray-100 cursor-pointer"
                    @click="openProfileCreate(profile, close)"
                  >
                    <BaseIcon name="PlusIcon" class="h-5 text-primary-400" />
                    <label class="m-0 ml-3 text-sm leading-none cursor-pointer font-base text-primary-400">
                      Add New {{ profile.singular }}
                    </label>
                  </button>
                </div>
              </PopoverPanel>
            </div>
          </transition>
        </Popover>
      </div>
    </div>
  </div>
  <LorryPartyProfileModal @saved="handleProfileSaved" />
</template>

<script setup>
import { reactive, watch } from 'vue'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import { useDebounceFn } from '@vueuse/core'
import { useRouter } from 'vue-router'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useLorryPartyProfileStore } from '@/scripts/admin/stores/lorry-party-profile'
import { useModalStore } from '@/scripts/stores/modal'
import LorryPartyProfileModal from '@/scripts/admin/components/modal-components/LorryPartyProfileModal.vue'

const invoiceStore = useInvoiceStore()
const profileStore = useLorryPartyProfileStore()
const modalStore = useModalStore()
const router = useRouter()

const selectedProfiles = reactive({
  owner: null,
  driver: null,
  broker: null,
})

const profileOptions = reactive({
  owner: [],
  driver: [],
  broker: [],
})

const profileSearch = reactive({
  owner: '',
  driver: '',
  broker: '',
})

const profileResolveInProgress = reactive({
  owner: false,
  driver: false,
  broker: false,
})

const profileInputs = [
  {
    key: 'owner',
    type: 'OWNER',
    label: 'Owner Name',
    singular: 'Owner',
    summaryLabel: 'Owner Details',
  },
  {
    key: 'driver',
    type: 'DRIVER',
    label: 'Driver Name',
    singular: 'Driver',
    summaryLabel: 'Driver Details',
  },
  {
    key: 'broker',
    type: 'BROKER',
    label: 'Broker Name',
    singular: 'Broker',
    summaryLabel: 'Broker Details',
  },
]

const createPathByType = {
  OWNER: 'owner-portal.create',
  DRIVER: 'driver-portal.create',
  BROKER: 'broker-portal.create',
}

const editPathByType = {
  OWNER: 'owner-portal.edit',
  DRIVER: 'driver-portal.edit',
  BROKER: 'broker-portal.edit',
}

const fieldMappingsByType = {
  OWNER: [
    ['Owner Name', 'name'],
    ['Owner Address', 'address'],
    ['Owner Phone No', 'phone'],
    ['Owner PAN No', 'financer_name'],
    ['Owner Bank Account No', 'bank_account_no'],
  ],
  DRIVER: [
    ['Driver Name', 'name'],
    ['Driver Address', 'address'],
    ['Driver Licence No', 'licence_no'],
    ['Issued Dt.', 'licence_date'],
    ['Driver RTO', 'rto_address'],
    ['Driver Valid Up To', 'valid_up_to'],
    ['Driver Bank Account No', 'bank_account_no'],
  ],
  BROKER: [
    ['Broker Name', 'name'],
    ['Broker Address', 'address'],
    ['Broker Pan No', 'advice_no'],
    ['Advice Date', 'advice_date'],
    ['Broker Phone No', 'phone'],
    ['Broker Bank Account No', 'bank_account_no'],
  ],
}

const debounceSearchProfiles = useDebounceFn((profile) => {
  fetchProfileOptions(profile)
}, 500)

function setField(label, value) {
  const field = invoiceStore.newInvoice.customFields?.find((_field) => _field.label === label)

  if (field) {
    field.value = label === 'Broker Pan No'
      ? String(value || '').toUpperCase()
      : value || ''
  }
}

async function fetchProfileOptions(profile) {
  const response = await profileStore.fetchProfiles({
    search: profileSearch[profile.key],
    type: profile.type,
    limit: 'all',
  })

  profileOptions[profile.key] = response.data.data
}

function ensureProfilesLoaded(profile) {
  if (!profileOptions[profile.key].length) {
    fetchProfileOptions(profile)
  }
}

function openProfileCreate(profile, close) {
  close?.()
  profileStore.$patch((state) => {
    state.current = {
      type: profile.type,
      name: '',
      phone: '',
      address: '',
      bank_account_no: '',
    }
  })
  modalStore.openModal({
    title: `New ${profile.singular}`,
    componentName: 'LorryPartyProfileModal',
  })
}

async function openProfileEdit(profile) {
  if (!selectedProfiles[profile.key]?.id) {
    await resolveSelectedProfile(profile)
  }

  const selectedProfile = selectedProfiles[profile.key]

  if (!selectedProfile?.id) {
    return
  }

  const response = await profileStore.fetchProfile(selectedProfile.id)
  if (response.data?.data) {
    profileStore.$patch((state) => {
      state.current = response.data.data
    })
  }

  modalStore.openModal({
    title: `Edit ${profile.singular}`,
    componentName: 'LorryPartyProfileModal',
  })
}

function handleProfileSaved({ type, profile }) {
  const key = type.toLowerCase()
  selectedProfiles[key] = profile
  fillProfileFields(type, profile)
}

function selectProfile(profile, option, close) {
  selectedProfiles[profile.key] = option
  fillProfileFields(profile.type, selectedProfiles[profile.key])
  close()
  profileSearch[profile.key] = ''
}

function resetProfile(profile) {
  selectedProfiles[profile.key] = null
  fillProfileFields(profile.type, null)
}

function compact(value) {
  return value ? String(value).trim() : ''
}

function normalize(value) {
  return compact(value).toLowerCase().replace(/[^a-z0-9]+/g, '')
}

function formatProfileLines(profile) {
  return [
    compact(profile?.address),
    compact(profile?.phone),
    compact(profile?.code),
  ].filter(Boolean)
}

function getProfileDocuments(profile, type) {
  if (!profile) return []
  
  if (type === 'OWNER') {
    return [
      { label: 'RC Front', path: profile.rc_front_path },
      { label: 'RC Back', path: profile.rc_back_path },
      { label: 'PAN Front', path: profile.pan_front_path },
      { label: 'Insurance Copy', path: profile.insurance_path },
    ]
  }
  
  if (type === 'DRIVER') {
    return [
      { label: 'License Front', path: profile.license_front_path },
      { label: 'License Back', path: profile.license_back_path },
    ]
  }
  
  if (type === 'BROKER') {
    return [
      { label: 'PAN Front', path: profile.pan_front_path_broker },
    ]
  }
  
  return []
}

function fillProfileFields(type, profile) {
  fieldMappingsByType[type].forEach(([label, key]) => {
    setField(label, profile?.[key])
  })

  if (type === 'OWNER') {
    setField('Paid To', profile?.name)
    setField('Final Paid To', profile?.name)
  }
}

function getFieldValue(label) {
  return invoiceStore.newInvoice.customFields?.find((field) => field.label === label)?.value || ''
}

function hydrateSelectedProfilesFromFields() {
  profileInputs.forEach((profile) => {
    if (selectedProfiles[profile.key]?.id) {
      return
    }

    if (selectedProfiles[profile.key]) {
      resolveSelectedProfile(profile)
      return
    }

    const hydratedProfile = { type: profile.type }

    fieldMappingsByType[profile.type].forEach(([label, key]) => {
      hydratedProfile[key] = getFieldValue(label)
    })

    if (
      compact(hydratedProfile.name) ||
      compact(hydratedProfile.address) ||
      compact(hydratedProfile.phone)
    ) {
      selectedProfiles[profile.key] = hydratedProfile
      resolveSelectedProfile(profile)
    }
  })
}

async function resolveSelectedProfile(profile) {
  const selectedProfile = selectedProfiles[profile.key]
  const profileName = compact(selectedProfile?.name)

  if (
    !profileName ||
    selectedProfile?.id ||
    profileResolveInProgress[profile.key]
  ) {
    return
  }

  profileResolveInProgress[profile.key] = true

  try {
    const response = await profileStore.fetchProfiles({
      search: profileName,
      type: profile.type,
      limit: 'all',
    })

    const matchedProfile = response.data.data.find((option) => {
      return normalize(option.name) === normalize(profileName)
    })

    if (matchedProfile && !selectedProfiles[profile.key]?.id) {
      selectedProfiles[profile.key] = matchedProfile
      fillProfileFields(profile.type, matchedProfile)
    }
  } finally {
    profileResolveInProgress[profile.key] = false
  }
}

watch(
  () => invoiceStore.newInvoice.customFields,
  () => {
    hydrateSelectedProfilesFromFields()
  },
  { deep: true, immediate: true }
)
</script>
