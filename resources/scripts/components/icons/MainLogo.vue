<template>
  <svg
    class="main-logo-svg"
    viewBox="0 0 400 100"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    v-bind="$attrs"
  >
    <!-- Speed lines (always orange/yellow for brand consistency) -->
    <g class="logo-speed-lines" stroke="#ff9800" stroke-width="2.5" stroke-linecap="round" opacity="0.8">
      <line x1="5" y1="43" x2="20" y2="43" />
      <line x1="0" y1="52" x2="15" y2="52" />
      <line x1="7" y1="61" x2="22" y2="61" />
    </g>
    
    <!-- Truck cabin & body -->
    <g class="logo-truck-group">
      <!-- Main Chassis/Cab (inherits parent text color) -->
      <path class="logo-chassis" d="M30 45 h45 v15 h30 l8 12 v10 h-83 z" fill="currentColor" />
      <path class="logo-cab" d="M105 72 h13 c2.5 0 4.5-2 4.5-4.5 v-10.5 c0-1.5-0.7-3-2-3.8 l-10.5-7.2 h-5 v26 z" fill="currentColor" opacity="0.9" />
      
      <!-- Cabin Window (semi-transparent white) -->
      <path d="M106 48 h4 l9.5 6.5 c0.5 0.5 0.8 1.1 0.8 1.8 v3.2 h-14.3 z" fill="#ffffff" opacity="0.3" />
      
      <!-- Document Stack in Bed (Grey with lines) -->
      <!-- Sheet 1 (back) -->
      <rect x="42" y="22" width="28" height="34" rx="2" fill="#4b5563" />
      <!-- Sheet 2 (front/overlapping) -->
      <path d="M47 26 h20 l6 6 v24 c0 1.1-0.9 2-2 2 h-24 c-1.1 0-2-0.9-2-2 v-28 c0-1.1 0.9-2 2-2 z" fill="#6b7280" />
      <!-- Document folded corner -->
      <path d="M67 26 l6 6 h-6 z" fill="#374151" />
      <!-- Document text lines -->
      <line x1="52" y1="34" x2="65" y2="34" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" />
      <line x1="52" y1="40" x2="68" y2="40" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" />
      <line x1="52" y1="46" x2="63" y2="46" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" />
      
      <!-- Checkbox container (Orange with white checkmark) -->
      <rect x="74" y="44" width="22" height="22" rx="3" fill="#ff9800" />
      <!-- White Checkmark -->
      <path d="M79 55 l3.5 3.5 l6.5 -6.5" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" fill="none" />
    </g>

    <!-- Wheels (inherits parent text color for outer rubber, grey for rims) -->
    <g class="logo-wheels">
      <circle cx="48" cy="82" r="11" fill="currentColor" />
      <circle cx="48" cy="82" r="6" fill="#9ca3af" />
      <circle cx="48" cy="82" r="2.5" fill="currentColor" />
      
      <circle cx="98" cy="82" r="11" fill="currentColor" />
      <circle cx="98" cy="82" r="6" fill="#9ca3af" />
      <circle cx="98" cy="82" r="2.5" fill="currentColor" />
    </g>

    <!-- Brand Name Text (inherits parent text color for HisabKitabb, 'b's are orange) -->
    <text
      x="125"
      y="58"
      font-family="'Outfit', 'Inter', 'Montserrat', 'Segoe UI', sans-serif"
      font-size="44"
      font-weight="800"
      letter-spacing="-1px"
      fill="currentColor"
    >
      Hisa<tspan fill="#ff9800">b</tspan>Kitab<tspan fill="#ff9800">b</tspan>
    </text>

    <!-- Tagline Text (orange prefix, tagline inherits parent text color with slight opacity) -->
    <text
      x="125"
      y="82"
      font-family="'Outfit', 'Inter', 'Montserrat', 'Segoe UI', sans-serif"
      font-size="14.5"
      font-weight="600"
      letter-spacing="0.1px"
    >
      <tspan fill="#ff9800">AI-Powered</tspan>
      <tspan class="logo-tagline-sub" fill="currentColor" opacity="0.8"> Invoice & LR Management</tspan>
    </text>
  </svg>
</template>

<script setup>
// MainLogo is now a fully vector inline SVG component for perfect scaling and transparency.
</script>

<style scoped>
.main-logo-svg {
  height: 100%;
  width: auto;
  display: inline-block;
  vertical-align: middle;
}
</style>
