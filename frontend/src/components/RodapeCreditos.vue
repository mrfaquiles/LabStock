<template>
  <footer class="rodape" :class="{ 'rodape--escuro': escuro }">
    <span><strong>LabStock</strong> · Desenvolvido por Mayara F. Aquiles e Pedro H. R. Fortini</span>
    <span class="rodape__sep">·</span>
    <span>Orientação: Prof. Me. Diego Rossi</span>
    <span class="rodape__sep">·</span>
    <span>IF Sudeste MG – Campus Muriaé · {{ ano }}</span>
  </footer>
</template>

<script setup>
// Créditos do TCC, discretos no fim da página
defineProps({ escuro: { type: Boolean, default: false } });
const ano = new Date().getFullYear();
</script>

<style scoped>
.rodape {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 4px 8px;
  padding: 14px 16px;
  font-size: 0.72rem;
  line-height: 1.4;
  text-align: center;
  color: rgba(var(--v-theme-on-surface), 0.5);
  border-top: 1px solid rgba(var(--v-border-color), 0.08);
}
.rodape strong { font-weight: 600; color: rgba(var(--v-theme-on-surface), 0.7); }

.rodape--escuro {
  position: relative;
  z-index: 2;
  color: rgba(255, 255, 255, 0.45);
  border-top-color: rgba(255, 255, 255, 0.08);
}
.rodape--escuro strong { color: #7df9ff; }

@media (max-width: 600px) {
  .rodape__sep { display: none; }
  .rodape { flex-direction: column; }
}
</style>
