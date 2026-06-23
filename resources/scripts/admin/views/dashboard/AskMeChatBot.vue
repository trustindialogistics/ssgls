<template>
  <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="bg-white/20 p-2 rounded-lg">
            <BaseIcon name="ChatBubbleLeftRightIcon" class="h-6 w-6 text-white" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-white">Ask Me</h3>
            <p class="text-xs text-white/80">Your business assistant</p>
          </div>
        </div>
        <div class="flex space-x-2">
          <button @click="showHelp" title="Help" class="text-white/80 hover:text-white transition">
            <BaseIcon name="QuestionMarkCircleIcon" class="h-5 w-5" />
          </button>
          <button @click="clearChat" title="Clear Chat" class="text-white/80 hover:text-white transition">
            <BaseIcon name="TrashIcon" class="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- Chat Messages Area with Scrolling -->
    <div ref="messagesContainer" class="h-96 overflow-y-auto p-6 space-y-4 bg-gray-50">
      <!-- Welcome Message (if no chat) -->
      <div v-if="messages.length === 0" class="text-center py-12">
        <div class="bg-white/50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
          <BaseIcon name="SparklesIcon" class="h-10 w-10 text-primary-500" />
        </div>
        <h4 class="text-lg font-semibold text-gray-700 mb-2">
          👋 Hi! I'm your Business Assistant
        </h4>
        <p class="text-gray-500 mb-6">
          Ask me anything about your invoices, customers, payments, or expenses
        </p>
        
        <!-- Quick Question Buttons -->
        <div class="flex flex-wrap justify-center gap-2">
          <button 
            v-for="question in quickQuestions" 
            :key="question"
            @click="askQuestion(question)"
            class="px-4 py-2 bg-white border border-gray-300 rounded-full text-sm text-gray-600 hover:bg-primary-50 hover:border-primary-300 transition"
          >
            {{ question }}
          </button>
        </div>
      </div>

      <!-- Chat Messages -->
      <div v-else>
        <div v-for="(message, index) in messages" :key="index" 
             :class="['flex', message.type === 'user' ? 'justify-end' : 'justify-start']">
          
          <!-- User Message (Right aligned) -->
          <div v-if="message.type === 'user'"
               class="max-w-[80%] bg-primary-500 text-white rounded-2xl rounded-tr-sm px-4 py-3 shadow-md">
            <p class="text-sm">{{ message.text }}</p>
          </div>
          
          <!-- Assistant Message (Left aligned) -->
          <div v-else class="max-w-[80%] bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm">
            <div class="flex items-start justify-between">
              <div class="flex items-start space-x-3 flex-1">
                <div class="bg-primary-100 p-1.5 rounded-lg flex-shrink-0">
                  <BaseIcon name="RobotIcon" class="h-4 w-4 text-primary-600" />
                </div>
                <div class="flex-1">
                  <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ message.text }}</p>
                  
                  <!-- Loading Indicator -->
                  <div v-if="message.loading" class="flex space-x-2 mt-2">
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                  </div>
                  
                  <!-- Action Buttons (Copy & Download) -->
                  <div v-if="!message.loading && message.text" class="flex space-x-2 mt-3 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button 
                      @click="copyToClipboard(message.text)"
                      class="p-1.5 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded transition"
                      title="Copy response"
                    >
                      <BaseIcon name="ClipboardIcon" class="h-4 w-4" />
                    </button>
                    <button 
                      @click="downloadAsPDF(message.text)"
                      class="p-1.5 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded transition"
                      title="Download as PDF"
                    >
                      <BaseIcon name="DocumentArrowDownIcon" class="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Questions (if chat exists) -->
    <div v-if="messages.length > 0" class="px-6 py-3 bg-white border-t border-gray-100">
      <div class="flex items-center space-x-2 overflow-x-auto">
        <span class="text-xs text-gray-500 flex-shrink-0">Quick:</span>
        <button 
          v-for="question in quickQuestions" 
          :key="question"
          @click="askQuestion(question)"
          class="px-3 py-1.5 bg-gray-100 text-gray-600 rounded-full text-xs hover:bg-primary-50 hover:text-primary-600 transition flex-shrink-0 whitespace-nowrap"
        >
          {{ question }}
        </button>
      </div>
    </div>

    <!-- Input Area -->
    <div class="px-4 py-3 sm:px-6 sm:py-4 bg-white border-t border-gray-200">
      <div class="flex items-center space-x-2 sm:space-x-3">
        <input 
          v-model="userInput"
          @keyup.enter="sendMessage"
          type="text"
          placeholder="Ask about invoices, customers, payments..."
          class="flex-1 min-w-0 px-3 py-2.5 sm:px-4 sm:py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm"
          :disabled="isLoading"
        />
        <button 
          @click="sendMessage"
          :disabled="!userInput.trim() || isLoading"
          class="px-4 py-2.5 sm:px-6 sm:py-3 bg-primary-500 text-white rounded-xl font-medium hover:bg-primary-600 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-1.5 sm:space-x-2 flex-shrink-0"
        >
          <BaseIcon name="PaperAirplaneIcon" class="h-5 w-5" />
          <span class="hidden sm:inline">Send</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, nextTick, watch } from 'vue'
import http from '@/scripts/http'

const messagesContainer = ref(null)
const userInput = ref('')
const isLoading = ref(false)
const messages = reactive([])

const quickQuestions = [
  "Total sales this month?",
  "Unpaid invoices?",
  "Top 5 customers?",
  "Expenses this week?",
  "Payments received today?",
]

// Scroll to bottom when messages change
watch(() => messages.length, () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
})

async function askQuestion(question) {
  userInput.value = question
  await sendMessage()
}

async function sendMessage() {
  const text = userInput.value.trim()
  if (!text || isLoading.value) return

  // Add user message
  messages.push({
    type: 'user',
    text: text,
  })

  userInput.value = ''
  isLoading.value = true

  // Add loading message
  const loadingIndex = messages.length
  messages.push({
    type: 'assistant',
    text: '',
    loading: true,
  })

  try {
    const response = await http.post('/api/v1/dashboard/chat', {
      message: text,
    })

    // Update loading message with actual response
    messages[loadingIndex] = {
      type: 'assistant',
      text: response.data.answer,
      loading: false,
    }
  } catch (error) {
    console.error('ChatBOT error:', error)
    messages[loadingIndex] = {
      type: 'assistant',
      text: error.response?.data?.answer || 'Sorry, I encountered an error. Please try again.',
      loading: false,
    }
  } finally {
    isLoading.value = false
  }
}

function clearChat() {
  messages.length = 0
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    // Show success feedback (you could use a toast notification here)
    alert('Response copied to clipboard!')
  }).catch(err => {
    console.error('Failed to copy:', err)
    alert('Failed to copy response')
  })
}

function downloadAsPDF(text) {
  // Create a simple text file with the response
  const blob = new Blob([text], { type: 'text/plain' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `chatbot-response-${new Date().toISOString().slice(0, 19)}.txt`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

function showHelp() {
  alert(`Examples of questions you can ask:

📊 Data Questions:
• "Show me total invoices this month"
• "Who are my top 5 customers?"
• "What are my unpaid invoices?"
• "Total expenses this week?"
• "Payments received today?"

💬 General Questions:
• "Hello!"
• "What can you do?"
• "Help me with something"

The assistant can access your invoices, customers, payments, and expenses data.`)
}
</script>

<style scoped>
/* Custom scrollbar for chat messages */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
