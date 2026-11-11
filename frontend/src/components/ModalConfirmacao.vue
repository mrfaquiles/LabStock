<template>
  <v-dialog v-model="isOpen" max-width="450px" persistent>
    <v-card class="pa-4 rounded-lg">
      <v-card-title class="text-h6 font-weight-bold text-error d-flex align-center">
        <v-icon icon="mdi-alert-circle" class="me-2" color="error"></v-icon>
        {{ titulo }}
      </v-card-title>

      <v-card-text class="text-body-1 py-4">
        {{ pergunta }} <strong>{{ nomeItem }}</strong>?
      </v-card-text>

      <v-card-actions class="px-4 pb-2">
        <v-spacer></v-spacer>
        <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="cancelar">
          Não
        </v-btn>
        <v-btn color="error" variant="flat" class="text-none px-4" @click="confirmar">
          {{ textoConfirmar }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: Boolean,
  nomeItem: String,
  // Por padrão é a confirmação de exclusão; outras telas trocam os textos (ex.: cancelar pedido)
  titulo: { type: String, default: 'Confirmar Exclusão' },
  pergunta: { type: String, default: 'Você tem certeza que deseja excluir' },
  textoConfirmar: { type: String, default: 'Sim, excluir' },
});

const emit = defineEmits(['update:modelValue', 'confirm']);

const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const cancelar = () => {
  emit('update:modelValue', false);
};

const confirmar = () => {
  emit('confirm');
  emit('update:modelValue', false);
};
</script>