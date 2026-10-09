<template>
  <v-container fluid class="pa-6">
    <!-- Cabeçalho -->
    <CabecalhoPagina
      titulo="Dashboard"
      :subtitulo="`Olá, ${auth.usuario?.nome?.split(' ')[0] || ''}! Resumo do estoque e alertas de vencimento.`"
      icone="mdi-view-dashboard"
    >
      <v-btn color="blue-darken-2" variant="tonal" class="text-none" prepend-icon="mdi-sync" :loading="loading" @click="carregar">
        Atualizar
      </v-btn>
    </CabecalhoPagina>

    <v-alert v-if="erro" type="error" variant="tonal" class="mb-4">{{ erro }}</v-alert>

    <v-alert
      v-if="dados?.compras_pedir_agora"
      type="error"
      variant="tonal"
      icon="mdi-cart-alert"
      class="mb-4"
    >
      <strong>{{ dados.compras_pedir_agora }} item(ns) precisam ser pedidos agora</strong>:
      o estoque não dura até uma compra nova chegar (cerca de {{ config.config.tempo_compra_meses }} meses de licitação).
      <span class="text-medium-emphasis">{{ dados.compras_pedir_agora_itens.join(', ') }}{{ dados.compras_pedir_agora > 5 ? '…' : '' }}.</span>
      <router-link to="/compras" class="font-weight-bold">Ver previsão de compras</router-link>
    </v-alert>

    <v-alert
      v-if="auth.isAdmin && dados?.baixas_vidraria_pendentes"
      type="warning"
      variant="tonal"
      icon="mdi-glass-fragile"
      class="mb-4"
    >
      {{ dados.baixas_vidraria_pendentes }} solicitação(ões) de baixa de vidraria aguardando aprovação.
      <router-link to="/vidrarias" class="font-weight-bold">Analisar agora</router-link>
    </v-alert>

    <!-- Indicadores -->
    <v-row>
      <v-col v-for="card in cards" :key="card.titulo" cols="12" sm="6" lg="3">
        <v-card class="elevation-1 rounded-lg pa-4 h-100" :to="card.rota">
          <div class="d-flex align-center">
            <v-avatar :color="card.cor" variant="tonal" size="48" class="me-4">
              <v-icon :icon="card.icone"></v-icon>
            </v-avatar>
            <div>
              <div class="text-h5 font-weight-bold">{{ card.valor }}</div>
              <div class="text-body-2 text-grey-darken-1">{{ card.titulo }}</div>
              <div v-if="card.extra" class="text-caption" :class="`text-${card.corExtra}`">{{ card.extra }}</div>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Alertas de vencimento -->
    <v-row class="mt-2">
      <v-col v-for="painel in paineis" :key="painel.titulo" cols="12" lg="6">
        <v-card class="elevation-1 rounded-lg h-100">
          <v-card-title class="d-flex align-center pa-4">
            <v-icon :icon="painel.icone" :color="painel.cor" class="me-2"></v-icon>
            <span class="text-subtitle-1 font-weight-bold">{{ painel.titulo }}</span>
            <v-chip :color="painel.cor" size="small" variant="tonal" class="ms-2">{{ painel.itens.length }}</v-chip>
          </v-card-title>
          <v-divider></v-divider>

          <v-data-table
            :headers="painel.headers"
            :items="painel.itens"
            :loading="loading"
            density="compact"
            items-per-page="5"
            :no-data-text="painel.vazio"
            class="pa-2"
          >
            <template v-slot:[`item.quantidade`]="{ item }">
              {{ Number(item.quantidade) }} {{ item.unidade || '' }}
            </template>
            <template v-slot:[`item.data_validade`]="{ item }">
              <v-chip :color="painel.cor" size="small" variant="tonal">{{ formatarData(item.data_validade) }}</v-chip>
            </template>
            <template v-slot:[`item.dias_restantes`]="{ item }">
              <span :class="`text-${painel.cor} font-weight-medium`">{{ textoDias(item.dias_restantes) }}</span>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../plugins/axios';
import CabecalhoPagina from '../components/CabecalhoPagina.vue';
import { useAuthStore } from '../stores/authStore';
import { useConfigStore } from '../stores/configStore';

const auth = useAuthStore();
const config = useConfigStore();
const dados = ref(null);
const loading = ref(false);
const erro = ref('');

const headersAlerta = [
  { title: 'Reagente', key: 'nome' },
  { title: 'Lote', key: 'lote' },
  { title: 'Qtd.', key: 'quantidade' },
  { title: 'Validade', key: 'data_validade' },
  { title: 'Prazo', key: 'dias_restantes' },
  { title: 'Localização', key: 'localizacao' },
];

const cards = computed(() => {
  const t = dados.value?.totais || {};
  return [
    { titulo: 'Reagentes ativos', valor: t.reagentes ?? '-', icone: 'mdi-flask', cor: 'green-darken-3', rota: '/reagentes' },
    { titulo: 'Vidrarias ativas', valor: t.vidrarias ?? '-', icone: 'mdi-cup', cor: 'blue-darken-2', rota: '/vidrarias' },
    {
      titulo: 'Equipamentos ativos', valor: t.equipamentos ?? '-', icone: 'mdi-tools', cor: 'blue-grey-darken-2', rota: '/equipamentos',
      extra: t.equipamentos_manutencao ? `${t.equipamentos_manutencao} em manutenção` : '', corExtra: 'warning',
    },
    { titulo: 'Quebras de vidraria (30 dias)', valor: dados.value?.quebras_vidraria_30_dias ?? '-', icone: 'mdi-glass-fragile', cor: 'deep-orange' },
  ];
});

const paineis = computed(() => [
  {
    titulo: 'Reagentes vencidos', icone: 'mdi-alert-octagon', cor: 'error', headers: headersAlerta,
    itens: dados.value?.alertas.vencidos || [], vazio: 'Nenhum reagente vencido',
  },
  {
    titulo: 'Próximos do vencimento', icone: 'mdi-clock-alert-outline', cor: 'warning', headers: headersAlerta,
    itens: dados.value?.alertas.proximos_vencimento || [], vazio: 'Nenhum reagente dentro do prazo de alerta',
  },
]);

const formatarData = (dataStr) => {
  if (!dataStr) return '';
  const [ano, mes, dia] = dataStr.split('-');
  return `${dia}/${mes}/${ano}`;
};

const textoDias = (dias) => {
  if (dias < 0) return `vencido há ${Math.abs(dias)} dia(s)`;
  if (dias === 0) return 'vence hoje';
  return `${dias} dia(s)`;
};

const carregar = async () => {
  loading.value = true;
  erro.value = '';
  try {
    const resposta = await api.get('/dashboard');
    dados.value = resposta.data;
  } catch (err) {
    erro.value = err.response?.data?.message || 'Erro ao carregar o dashboard.';
  } finally {
    loading.value = false;
  }
};

onMounted(carregar);
</script>
