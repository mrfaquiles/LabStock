<template>
  <!-- Ilustração da tela de login: estante com tubos de ensaio, erlenmeyer e béquer em estilo neon -->
  <svg class="ilustracao" viewBox="0 0 560 480" role="img" aria-label="Vidrarias de laboratório com reagentes coloridos">
    <defs>
      <linearGradient id="liqCiano" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#7DF9FF" />
        <stop offset="1" stop-color="#00B8D4" />
      </linearGradient>
      <linearGradient id="liqAzul" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#82B1FF" />
        <stop offset="1" stop-color="#2962FF" />
      </linearGradient>
      <linearGradient id="liqVerde" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#A7FFEB" />
        <stop offset="1" stop-color="#00BFA5" />
      </linearGradient>
      <linearGradient id="liqRoxo" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#B388FF" />
        <stop offset="1" stop-color="#651FFF" />
      </linearGradient>
      <linearGradient id="vidro" x1="0" y1="0" x2="1" y2="0">
        <stop offset="0" stop-color="#ffffff" stop-opacity="0.22" />
        <stop offset="0.35" stop-color="#ffffff" stop-opacity="0.06" />
        <stop offset="1" stop-color="#ffffff" stop-opacity="0.14" />
      </linearGradient>
      <linearGradient id="estante" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#2A4A7F" />
        <stop offset="1" stop-color="#16294D" />
      </linearGradient>
      <radialGradient id="base" cx="0.5" cy="0.5" r="0.5">
        <stop offset="0" stop-color="#40C4FF" stop-opacity="0.55" />
        <stop offset="0.6" stop-color="#2979FF" stop-opacity="0.15" />
        <stop offset="1" stop-color="#2979FF" stop-opacity="0" />
      </radialGradient>
      <filter id="brilho" x="-50%" y="-50%" width="200%" height="200%">
        <feGaussianBlur stdDeviation="6" result="b" />
        <feMerge><feMergeNode in="b" /><feMergeNode in="SourceGraphic" /></feMerge>
      </filter>
      <filter id="brilhoForte" x="-50%" y="-50%" width="200%" height="200%">
        <feGaussianBlur stdDeviation="12" />
      </filter>
    </defs>

    <!-- Plataforma luminosa -->
    <ellipse cx="280" cy="410" rx="250" ry="58" fill="url(#base)" />
    <ellipse cx="280" cy="410" rx="200" ry="40" fill="none" stroke="#40C4FF" stroke-opacity="0.5" stroke-width="1.5" />
    <ellipse cx="280" cy="410" rx="150" ry="28" fill="none" stroke="#18FFFF" stroke-opacity="0.35" stroke-width="1" stroke-dasharray="4 6" />

    <!-- Molécula flutuante -->
    <g class="flutua" stroke="#7DF9FF" stroke-opacity="0.6" stroke-width="2" fill="#0B132B">
      <line x1="410" y1="70" x2="455" y2="95" />
      <line x1="455" y1="95" x2="450" y2="145" />
      <line x1="455" y1="95" x2="500" y2="72" />
      <circle cx="410" cy="70" r="9" fill="#18FFFF" filter="url(#brilho)" />
      <circle cx="455" cy="95" r="12" fill="#40C4FF" filter="url(#brilho)" />
      <circle cx="450" cy="145" r="8" fill="#7C4DFF" filter="url(#brilho)" />
      <circle cx="500" cy="72" r="7" fill="#64FFDA" filter="url(#brilho)" />
    </g>

    <!-- Erlenmeyer (esquerda) -->
    <g class="flutua-lento">
      <path d="M108 250 h28 v52 l46 80 a12 12 0 0 1 -10 18 h-100 a12 12 0 0 1 -10 -18 l46 -80 z" fill="url(#vidro)" stroke="#9BE7FF" stroke-opacity="0.7" stroke-width="2" />
      <path d="M86 352 h72 l20 34 a10 10 0 0 1 -9 14 h-94 a10 10 0 0 1 -9 -14 z" fill="url(#liqVerde)" opacity="0.85" filter="url(#brilho)" />
      <rect x="104" y="244" width="36" height="8" rx="3" fill="#9BE7FF" fill-opacity="0.5" />
      <circle cx="112" cy="372" r="4" fill="#E0FFF8" opacity="0.8" />
      <circle cx="130" cy="384" r="3" fill="#E0FFF8" opacity="0.7" />
    </g>

    <!-- Estante com tubos de ensaio (centro) -->
    <g>
      <!-- tubos -->
      <g v-for="(t, i) in tubos" :key="i" :class="'tubo tubo-' + i">
        <rect :x="t.x" y="170" width="30" height="190" rx="15" fill="url(#vidro)" stroke="#9BE7FF" stroke-opacity="0.75" stroke-width="2" />
        <rect :x="t.x + 4" :y="360 - t.nivel" width="22" :height="t.nivel - 4" rx="11" :fill="`url(#${t.cor})`" filter="url(#brilho)" />
        <rect :x="t.x - 3" y="164" width="36" height="10" rx="4" fill="#9BE7FF" fill-opacity="0.55" />
        <circle :cx="t.x + 12" :cy="360 - t.nivel + 22" r="3" fill="#ffffff" opacity="0.75" class="bolha" />
        <circle :cx="t.x + 19" :cy="360 - t.nivel + 40" r="2" fill="#ffffff" opacity="0.6" class="bolha bolha-2" />
      </g>
      <!-- suporte da estante -->
      <rect x="196" y="250" width="200" height="16" rx="5" fill="url(#estante)" stroke="#40C4FF" stroke-opacity="0.6" />
      <rect x="196" y="370" width="200" height="20" rx="6" fill="url(#estante)" stroke="#40C4FF" stroke-opacity="0.6" />
      <rect x="200" y="250" width="8" height="130" rx="3" fill="#1E3A6B" />
      <rect x="384" y="250" width="8" height="130" rx="3" fill="#1E3A6B" />
      <rect x="196" y="384" width="200" height="4" rx="2" fill="#18FFFF" opacity="0.7" filter="url(#brilho)" />
    </g>

    <!-- Béquer (direita) -->
    <g class="flutua-lento-2">
      <path d="M418 270 h82 l-6 8 v104 a14 14 0 0 1 -14 14 h-48 a14 14 0 0 1 -14 -14 z" fill="url(#vidro)" stroke="#9BE7FF" stroke-opacity="0.7" stroke-width="2" />
      <path d="M428 330 h62 v52 a10 10 0 0 1 -10 10 h-42 a10 10 0 0 1 -10 -10 z" fill="url(#liqRoxo)" opacity="0.85" filter="url(#brilho)" />
      <g stroke="#9BE7FF" stroke-opacity="0.6" stroke-width="2">
        <line x1="476" y1="300" x2="490" y2="300" />
        <line x1="480" y1="318" x2="490" y2="318" />
        <line x1="476" y1="336" x2="490" y2="336" />
      </g>
    </g>

    <!-- Partículas de luz -->
    <g fill="#7DF9FF">
      <circle cx="70" cy="190" r="3" opacity="0.7" class="pisca" />
      <circle cx="180" cy="120" r="2" opacity="0.6" class="pisca pisca-2" />
      <circle cx="340" cy="110" r="2.5" opacity="0.7" class="pisca pisca-3" />
      <circle cx="520" cy="230" r="3" opacity="0.6" class="pisca" />
      <circle cx="40" cy="330" r="2" opacity="0.5" class="pisca pisca-2" />
    </g>
  </svg>
</template>

<script setup>
// Tubos da estante: posição, altura do líquido e cor
const tubos = [
  { x: 214, nivel: 120, cor: 'liqCiano' },
  { x: 260, nivel: 80, cor: 'liqAzul' },
  { x: 306, nivel: 140, cor: 'liqVerde' },
  { x: 352, nivel: 100, cor: 'liqRoxo' },
];
</script>

<style scoped>
.ilustracao {
  width: 100%;
  height: auto;
  max-width: 600px;
  overflow: visible;
}

/* Movimentos suaves (desligados para quem prefere menos animação) */
@media (prefers-reduced-motion: no-preference) {
  .flutua { animation: flutuar 6s ease-in-out infinite; }
  .flutua-lento { animation: flutuar 8s ease-in-out infinite; }
  .flutua-lento-2 { animation: flutuar 7s ease-in-out -2s infinite; }
  .bolha { animation: subir 3s ease-in infinite; }
  .bolha-2 { animation-delay: -1.5s; }
  .pisca { animation: piscar 3s ease-in-out infinite; }
  .pisca-2 { animation-delay: -1s; }
  .pisca-3 { animation-delay: -2s; }
}

@keyframes flutuar {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}
@keyframes subir {
  0% { transform: translateY(0); opacity: 0.8; }
  100% { transform: translateY(-30px); opacity: 0; }
}
@keyframes piscar {
  0%, 100% { opacity: 0.2; }
  50% { opacity: 0.9; }
}
</style>
