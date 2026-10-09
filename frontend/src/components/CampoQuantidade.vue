<template>
  <!-- Quantidade com escolha de unidade (kg/g/mg, L/mL/µL) e conversão automática para a unidade base -->
  <div class="d-flex ga-2 align-start">
    <v-text-field
      v-model="digitado"
      :label="label"
      type="number"
      min="0"
      step="any"
      variant="outlined"
      density="comfortable"
      :placeholder="placeholder"
      :disabled="disabled"
      :hint="dica"
      persistent-hint
      :rules="regrasCompletas"
      class="flex-grow-1"
      @keydown="bloquearSinais"
    ></v-text-field>
    <v-select
      v-model="fator"
      :items="opcoes"
      item-title="sigla"
      item-value="fator"
      label="Unidade"
      variant="outlined"
      density="comfortable"
      :disabled="disabled || opcoes.length === 1"
      hide-details
      style="max-width: 110px;"
    ></v-select>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { unidadesDisponiveis, paraBase, formatarNaBase, formatarQuantidade, melhorUnidade } from '../utils/unidades';

const props = defineProps({
  // Valor sempre na unidade base (kg, L, un)
  modelValue: { type: [Number, String, null], default: null },
  siglaBase: { type: String, default: '' },
  label: { type: String, default: 'Quantidade' },
  placeholder: { type: String, default: '' },
  // Unidade que aparece primeiro, ex.: 'g' para registrar uso
  unidadeInicial: { type: String, default: null },
  // Máximo na unidade base (ex.: saldo do lote)
  maximo: { type: Number, default: null },
  // Aceita zero (cadastro com estoque vazio)
  permiteZero: { type: Boolean, default: false },
  obrigatorio: { type: Boolean, default: true },
  disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const opcoes = computed(() => unidadesDisponiveis(props.siglaBase));
const fatorDaSigla = (sigla) => opcoes.value.find((o) => o.sigla === sigla)?.fator;

const digitado = ref('');
const fator = ref(1);

// Valor atual convertido para a unidade base
const valorBase = computed(() => (digitado.value === '' || digitado.value === null ? null : paraBase(digitado.value, fator.value)));

// Ao abrir com um valor (ex.: quantidade sugerida), mostra na unidade mais legível
const carregar = () => {
  const inicial = props.modelValue;
  if (inicial === null || inicial === undefined || inicial === '') {
    digitado.value = '';
    fator.value = fatorDaSigla(props.unidadeInicial) ?? opcoes.value[0].fator;
    return;
  }
  const { valor, sigla } = props.unidadeInicial
    ? { valor: Number(inicial) / (fatorDaSigla(props.unidadeInicial) ?? 1), sigla: props.unidadeInicial }
    : melhorUnidade(inicial, props.siglaBase);
  fator.value = fatorDaSigla(sigla) ?? 1;
  digitado.value = Math.round(valor * 1e6) / 1e6;
};
carregar();

// Recarrega quando o reagente muda (outra unidade base) ou o valor é trocado por fora
watch(() => props.siglaBase, carregar);
watch(() => props.modelValue, (novo) => { if (novo !== valorBase.value) carregar(); });
watch(valorBase, (v) => emit('update:modelValue', v));

const siglaAtual = computed(() => opcoes.value.find((o) => o.fator === fator.value)?.sigla);
const dica = computed(() => {
  if (valorBase.value === null) return props.siglaBase ? `Pode digitar em ${opcoes.value.map((o) => o.sigla).join(', ')}` : '';
  if (siglaAtual.value === props.siglaBase) return '';
  return `= ${formatarNaBase(valorBase.value, props.siglaBase)}`;
});

const regrasCompletas = computed(() => [
  (v) => !props.obrigatorio || (v !== '' && v !== null && v !== undefined) || 'Campo obrigatório',
  () => valorBase.value === null || (props.permiteZero ? valorBase.value >= 0 : valorBase.value > 0) || 'Informe uma quantidade maior que zero',
  // Menor que 1 mg / 1 µL some no arredondamento
  (v) => !v || Number(v) === 0 || valorBase.value > 0 || 'Quantidade muito pequena (mínimo de 1 mg ou 1 µL)',
  () => props.maximo === null || valorBase.value === null || valorBase.value <= props.maximo + 1e-9
    || `Disponível: ${formatarQuantidade(props.maximo, props.siglaBase)}`,
]);

const bloquearSinais = (e) => {
  if (['-', '+', 'e', 'E'].includes(e.key)) e.preventDefault();
};
</script>
