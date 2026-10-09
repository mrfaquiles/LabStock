<template>
  <div class="cabecalho d-flex flex-wrap align-center justify-space-between ga-4 mb-6">
    <div class="d-flex align-center">
      <v-avatar color="primary" variant="tonal" size="52" rounded="lg" class="me-4 flex-shrink-0">
        <v-icon :icon="icone" size="28"></v-icon>
      </v-avatar>
      <div>
        <h1 class="titulo text-primary">{{ titulo }}</h1>
        <p v-if="subtitulo" class="subtitulo text-medium-emphasis">{{ subtitulo }}</p>
      </div>
    </div>

    <!-- Botões de ação da página -->
    <div v-if="$slots.default" class="d-flex flex-wrap align-center ga-2">
      <slot></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  titulo: { type: String, required: true },
  subtitulo: { type: String, default: '' },
  icone: { type: String, default: 'mdi-flask-outline' },
});
</script>

<style scoped>
.titulo {
  font-size: 1.6rem;
  font-weight: 700;
  line-height: 1.2;
  letter-spacing: -0.01em;
  margin: 0;
}

.subtitulo {
  font-size: 0.9rem;
  line-height: 1.4;
  margin: 2px 0 0;
}
</style>
