<!-- Campo de data padrão do sistema: calendário do Vuetify e digitação em dd/mm/aaaa.
     O v-model continua sendo o texto AAAA-MM-DD usado pela API. -->
<template>
  <v-date-input
    v-bind="$attrs"
    v-model:menu="aberto"
    :model-value="paraData(modelValue)"
    :min="min || undefined"
    :max="max || undefined"
    input-format="dd/mm/yyyy"
    placeholder="dd/mm/aaaa"
    prepend-icon=""
    append-inner-icon="mdi-calendar"
    variant="outlined"
    density="comfortable"
    @update:model-value="atualizar"
  ></v-date-input>
</template>

<script setup>
import { ref } from 'vue';
import { paraData, paraIso } from '../utils/datas';

defineOptions({ inheritAttrs: false });
defineProps({
  modelValue: { type: String, default: '' },
  min: { type: String, default: '' },
  max: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

// Texto digitado errado (ex.: "11/11/202601") vira campo vazio, e a regra de obrigatório avisa
const dataValida = (d) => d instanceof Date && !isNaN(d) && d.getFullYear() >= 1900 && d.getFullYear() <= 2999;
// Fecha o calendário assim que a data é escolhida
const aberto = ref(false);
const atualizar = (d) => {
  emit('update:modelValue', dataValida(d) ? paraIso(d) : '');
  aberto.value = false;
};
</script>
