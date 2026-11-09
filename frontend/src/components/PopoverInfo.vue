<template>
  <!-- Caixa de informação que abre ao passar o mouse (descrição, lotes, justificativa...) -->
  <v-menu
    open-on-hover
    location="top"
    offset="8"
    :open-delay="80"
    :close-delay="120"
    :close-on-content-click="false"
    transition="scale-transition"
  >
    <template #activator="{ props }">
      <slot name="activator" :props="props">
        <v-icon v-bind="props" size="18" class="popover-info__gatilho cursor-pointer ms-1">
          {{ icone }}
        </v-icon>
      </slot>
    </template>

    <div class="popover-info" :class="`popover-info--${cor}`">
      <div v-if="titulo" class="popover-info__titulo">
        <v-icon :icon="iconeTitulo || icone" size="16" class="me-2"></v-icon>
        <span>{{ titulo }}</span>
      </div>
      <div class="popover-info__corpo">
        <slot></slot>
      </div>
    </div>
  </v-menu>
</template>

<script setup>
defineProps({
  titulo: { type: String, default: '' },
  icone: { type: String, default: 'mdi-information-outline' },
  iconeTitulo: { type: String, default: '' },
  // primary (padrão) | warning | error
  cor: { type: String, default: 'primary' },
});
</script>
