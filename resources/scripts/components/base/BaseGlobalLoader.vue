<template>
  <div
    class="flex flex-col items-center justify-center h-screen bg-gray-50"
    :class="{ 'bg-white/80 backdrop-blur-xs': showBgOverlay }"
  >
    <div class="truck-loader-container">
      <!-- Dotted road track -->
      <div class="road-track"></div>
      
      <!-- Moving Truck -->
      <div class="moving-truck">
        <svg class="truck-svg" viewBox="0 0 150 100" xmlns="http://www.w3.org/2000/svg">
          <!-- Speed lines -->
          <g class="speed-lines" stroke="#ff9800" stroke-width="2.5" stroke-linecap="round" opacity="0.8">
            <line x1="5" y1="43" x2="20" y2="43" class="speed-line-1" />
            <line x1="0" y1="52" x2="15" y2="52" class="speed-line-2" />
            <line x1="7" y1="61" x2="22" y2="61" class="speed-line-3" />
          </g>
          
          <!-- Truck body -->
          <g class="truck-cabin-body">
            <!-- Main Chassis/Cab (Dark charcoal) -->
            <path d="M30 45 h45 v15 h30 l8 12 v10 h-83 z" fill="#242424" />
            <path d="M105 72 h13 c2.5 0 4.5-2 4.5-4.5 v-10.5 c0-1.5-0.7-3-2-3.8 l-10.5-7.2 h-5 v26 z" fill="#1a1a1a" />
            
            <!-- Cabin Window -->
            <path d="M106 48 h4 l9.5 6.5 c0.5 0.5 0.8 1.1 0.8 1.8 v3.2 h-14.3 z" fill="#ffffff" opacity="0.3" />
            
            <!-- Document Stack in Bed (Grey with lines) -->
            <!-- Sheet 1 (back) -->
            <rect x="42" y="22" width="28" height="34" rx="2" fill="#3e3e3e" />
            <!-- Sheet 2 (front/overlapping) -->
            <path d="M47 26 h20 l6 6 v24 c0 1.1-0.9 2-2 2 h-24 c-1.1 0-2-0.9-2-2 v-28 c0-1.1 0.9-2 2-2 z" fill="#505050" />
            <!-- Document folded corner -->
            <path d="M67 26 l6 6 h-6 z" fill="#353535" />
            <!-- Document text lines -->
            <line x1="52" y1="34" x2="65" y2="34" stroke="#8c8c8c" stroke-width="2" stroke-linecap="round" />
            <line x1="52" y1="40" x2="68" y2="40" stroke="#8c8c8c" stroke-width="2" stroke-linecap="round" />
            <line x1="52" y1="46" x2="63" y2="46" stroke="#8c8c8c" stroke-width="2" stroke-linecap="round" />
            
            <!-- Checkbox container (Orange with white checkmark) -->
            <rect x="74" y="44" width="22" height="22" rx="3" fill="#ff9800" />
            <!-- White Checkmark -->
            <path d="M79 55 l3.5 3.5 l6.5 -6.5" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none" />
          </g>

          <!-- Rotating Wheels -->
          <g class="wheel-back">
            <circle cx="48" cy="82" r="11" fill="#1a1a1a" />
            <circle cx="48" cy="82" r="6" fill="#666666" />
            <!-- Wheel spokes detail to show rotation -->
            <line x1="48" y1="76" x2="48" y2="88" stroke="#1a1a1a" stroke-width="2" />
            <line x1="42" y1="82" x2="54" y2="82" stroke="#1a1a1a" stroke-width="2" />
            <circle cx="48" cy="82" r="2.5" fill="#1a1a1a" />
          </g>
          
          <g class="wheel-front">
            <circle cx="98" cy="82" r="11" fill="#1a1a1a" />
            <circle cx="98" cy="82" r="6" fill="#666666" />
            <!-- Wheel spokes detail to show rotation -->
            <line x1="98" y1="76" x2="98" y2="88" stroke="#1a1a1a" stroke-width="2" />
            <line x1="92" y1="82" x2="104" y2="82" stroke="#1a1a1a" stroke-width="2" />
            <circle cx="98" cy="82" r="2.5" fill="#1a1a1a" />
          </g>
        </svg>
      </div>
    </div>
    
    <!-- Loading Text -->
    <div class="mt-4 text-xs font-semibold text-gray-400 tracking-widest uppercase loading-text">
      Loading
    </div>
  </div>
</template>

<script setup>
defineProps({
  showBgOverlay: {
    default: false,
    type: Boolean,
  },
})
</script>

<style scoped>
.truck-loader-container {
  position: relative;
  width: 320px;
  height: 120px;
  overflow: hidden;
}

.road-track {
  position: absolute;
  bottom: 18px;
  left: 0;
  width: 100%;
  height: 2px;
  background-image: linear-gradient(to right, #cbd5e1 50%, rgba(255, 255, 255, 0) 0%);
  background-position: bottom;
  background-size: 15px 2px;
  background-repeat: repeat-x;
}

.moving-truck {
  position: absolute;
  width: 130px;
  height: 86px;
  bottom: 6px;
  animation: drive-across 2.8s cubic-bezier(0.42, 0, 0.58, 1) infinite;
}

.truck-svg {
  width: 100%;
  height: 100%;
}

/* Bobbing animation on the truck cabin/body to simulate suspension/road bumps */
.truck-cabin-body {
  animation: truck-bob 0.15s linear infinite;
}

/* Rotating wheels */
.wheel-back {
  animation: spin-wheel 0.7s linear infinite;
  transform-origin: 48px 82px;
}

.wheel-front {
  animation: spin-wheel 0.7s linear infinite;
  transform-origin: 98px 82px;
}

/* Speed line scaling and pulsing */
.speed-line-1 { animation: speed-line 0.4s ease-in-out infinite; }
.speed-line-2 { animation: speed-line 0.4s ease-in-out infinite 0.1s; }
.speed-line-3 { animation: speed-line 0.4s ease-in-out infinite 0.2s; }

/* Loader travel across screen */
@keyframes drive-across {
  0% {
    transform: translateX(-140px);
  }
  100% {
    transform: translateX(330px);
  }
}

@keyframes truck-bob {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-1.2px) rotate(-0.5deg); }
}

@keyframes spin-wheel {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes speed-line {
  0%, 100% { transform: scaleX(0.7); opacity: 0.4; }
  50% { transform: scaleX(1.3); opacity: 1; }
}

.loading-text {
  animation: pulse-text 1.5s ease-in-out infinite;
}

@keyframes pulse-text {
  0%, 100% { opacity: 0.4; }
  50% { opacity: 0.9; }
}
</style>
