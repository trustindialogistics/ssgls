<template>
  <div
    class="flex flex-col min-h-0 mt-8 overflow-hidden"
    style="height: 75vh"
  >
    <iframe
      v-if="normalizedSrc && !shouldRenderWithPdfJs"
      :key="frameKey"
      :src="normalizedSrc"
      :class="iframeClass"
    />

    <div
      v-else-if="normalizedSrc"
      class="flex-1 overflow-auto border border-gray-400 border-solid rounded-md bg-gray-100"
    >
      <div
        v-if="isLoading"
        class="flex h-full items-center justify-center p-6 text-sm text-gray-600"
      >
        {{ loadingLabel }}
      </div>

      <div
        v-else-if="errorMessage"
        class="flex h-full flex-col items-center justify-center gap-3 p-6 text-center text-sm text-gray-600"
      >
        <p>{{ errorMessage }}</p>
        <a
          :href="normalizedSrc"
          target="_blank"
          rel="noopener"
          class="font-medium text-primary-500 hover:text-primary-600"
        >
          {{ openLabel }}
        </a>
      </div>

      <div
        v-show="!isLoading && !errorMessage"
        ref="pagesContainer"
        class="flex flex-col items-center gap-3 p-3"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { getDocument, GlobalWorkerOptions } from 'pdfjs-dist'
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url'
import Ls from '@/scripts/services/ls'

GlobalWorkerOptions.workerSrc = pdfWorkerUrl

const props = defineProps({
  src: {
    type: [String, Boolean],
    default: '',
  },
  frameKey: {
    type: [String, Number],
    default: 0,
  },
  iframeClass: {
    type: String,
    default: 'flex-1 border border-gray-400 border-solid rounded-md bg-white frame-style',
  },
  loadingLabel: {
    type: String,
    default: 'Loading PDF...',
  },
  openLabel: {
    type: String,
    default: 'Open PDF',
  },
})

const pagesContainer = ref(null)
const isLoading = ref(false)
const errorMessage = ref('')

const normalizedSrc = computed(() => {
  return typeof props.src === 'string' ? props.src.trim() : ''
})

let loadingTask = null
let pdfDocument = null
let renderTasks = []
let renderRun = 0

const shouldRenderWithPdfJs = computed(() => {
  if (!normalizedSrc.value || normalizedSrc.value === 'about:blank') {
    return false
  }

  const userAgent = window.navigator?.userAgent || ''
  const isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent)
  const isEmbeddedWebView =
    Boolean(window.ReactNativeWebView || window.Capacitor || window.cordova) ||
    (/Android/i.test(userAgent) && /; wv\)/i.test(userAgent))

  return isMobileDevice || isEmbeddedWebView
})

watch(
  () => [normalizedSrc.value, shouldRenderWithPdfJs.value],
  async () => {
    cleanupPdf()

    if (shouldRenderWithPdfJs.value) {
      await renderPdf()
    }
  },
  { immediate: true }
)

onBeforeUnmount(() => {
  cleanupPdf()
})

function getPdfHeaders() {
  const headers = {}
  const authToken = Ls.get('auth.token')
  const companyId = Ls.get('selectedCompany')

  if (authToken) {
    headers.Authorization = authToken
  }

  if (companyId) {
    headers.company = companyId
  }

  return headers
}

async function renderPdf() {
  if (!normalizedSrc.value) {
    return
  }

  const currentRun = ++renderRun
  isLoading.value = true
  errorMessage.value = ''

  await nextTick()

  if (!pagesContainer.value) {
    isLoading.value = false
    return
  }

  pagesContainer.value.innerHTML = ''

  try {
    loadingTask = getDocument({
      url: normalizedSrc.value,
      withCredentials: true,
      httpHeaders: getPdfHeaders(),
    })
    pdfDocument = await loadingTask.promise

    for (let pageNumber = 1; pageNumber <= pdfDocument.numPages; pageNumber += 1) {
      if (currentRun !== renderRun) {
        return
      }

      await renderPage(pageNumber)
    }
  } catch (error) {
    if (error?.name !== 'RenderingCancelledException') {
      errorMessage.value = 'PDF preview is not available in this view.'
    }
  } finally {
    if (currentRun === renderRun) {
      isLoading.value = false
    }
  }
}

async function renderPage(pageNumber) {
  if (!pdfDocument || !pagesContainer.value) {
    return
  }

  const page = await pdfDocument.getPage(pageNumber)
  const initialViewport = page.getViewport({ scale: 1 })
  const containerWidth = Math.max((pagesContainer.value?.clientWidth || window.innerWidth) - 24, 280)
  const scale = Math.min(containerWidth / initialViewport.width, 2)
  const viewport = page.getViewport({ scale })
  const outputScale = window.devicePixelRatio || 1
  const canvas = document.createElement('canvas')
  const context = canvas.getContext('2d')

  if (!context) {
    throw new Error('Canvas rendering is not available.')
  }

  canvas.width = Math.floor(viewport.width * outputScale)
  canvas.height = Math.floor(viewport.height * outputScale)
  canvas.style.width = `${Math.floor(viewport.width)}px`
  canvas.style.height = `${Math.floor(viewport.height)}px`
  canvas.className = 'max-w-full bg-white shadow-sm'

  context.setTransform(outputScale, 0, 0, outputScale, 0, 0)
  pagesContainer.value.appendChild(canvas)

  const renderTask = page.render({
    canvasContext: context,
    viewport,
  })

  renderTasks.push(renderTask)
  await renderTask.promise
}

function cleanupPdf() {
  renderRun += 1
  renderTasks.forEach((task) => {
    try {
      task.cancel()
    } catch (error) {
      if (error?.name === 'RenderingCancelledException') {
        return
      }
    }
  })
  renderTasks = []

  if (loadingTask) {
    loadingTask.destroy()
    loadingTask = null
  }

  if (pdfDocument) {
    pdfDocument.destroy()
    pdfDocument = null
  }

  if (pagesContainer.value) {
    pagesContainer.value.innerHTML = ''
  }

  isLoading.value = false
  errorMessage.value = ''
}
</script>
