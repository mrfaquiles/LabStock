<template>
  <v-container fluid class="pa-6">
    <CabecalhoPagina titulo="Produtos Controlados" subtitulo="Prestação de contas: quanto entrou, quanto foi usado, por quem, quando, de qual lote e por quê" icone="mdi-shield-alert">
      <v-btn color="grey-darken-2" variant="outlined" class="text-none" prepend-icon="mdi-file-pdf-box" :disabled="!dados" @click="exportar('pdf')">
        Salvar PDF
      </v-btn>
      <v-btn color="green-darken-3" variant="tonal" class="text-none" prepend-icon="mdi-file-excel-outline" :disabled="!dados" @click="exportar('csv')">
        Exportar Excel (CSV)
      </v-btn>
    </CabecalhoPagina>

    <!-- Filtros -->
    <v-card class="elevation-1 rounded-lg pa-4 mb-4">
      <v-form ref="formRef">
        <v-row align="center">
          <v-col cols="12" sm="6" md="3">
            <campo-data v-model="dataInicio" label="Data inicial *" :max="dataFim" hide-details="auto" :rules="[regras.obrigatorio]"></campo-data>
          </v-col>
          <v-col cols="12" sm="6" md="3">
            <campo-data v-model="dataFim" label="Data final *" :min="dataInicio" hide-details="auto" :rules="[regras.obrigatorio, regras.periodo]"></campo-data>
          </v-col>
          <v-col cols="12" sm="6" md="4">
            <v-autocomplete
              v-model="idreagente"
              :items="produtos"
              item-title="nome"
              item-value="idreagente"
              label="Produto"
              placeholder="Todos os produtos controlados"
              variant="outlined"
              density="comfortable"
              clearable
              hide-details
              no-data-text="Nenhum produto marcado como controlado"
            ></v-autocomplete>
          </v-col>
          <v-col cols="12" sm="6" md="2">
            <v-btn color="primary" variant="flat" block size="large" class="text-none text-white" prepend-icon="mdi-magnify" :loading="carregando" @click="gerar">
              Consultar
            </v-btn>
          </v-col>
          <v-col cols="12" class="pt-0">
            <span class="text-caption text-medium-emphasis me-2">Períodos rápidos:</span>
            <v-chip v-for="a in atalhos" :key="a.titulo" size="small" variant="tonal" color="primary" class="me-2 mb-1" @click="aplicarAtalho(a)">
              {{ a.titulo }}
            </v-chip>
          </v-col>
        </v-row>
      </v-form>
    </v-card>

    <v-alert v-if="erro" type="error" variant="tonal" class="mb-4" closable @click:close="erro = ''">{{ erro }}</v-alert>

    <v-alert v-if="totalSemJustificativa" type="warning" variant="tonal" class="mb-4" icon="mdi-alert">
      {{ totalSemJustificativa }} uso(s) no período sem justificativa, registrados antes de o produto ser marcado como controlado.
    </v-alert>

    <template v-if="dados">
      <!-- Resumo por produto -->
      <v-card class="elevation-1 rounded-lg mb-6">
        <v-card-title class="pa-4">
          <span class="text-subtitle-1 font-weight-bold">Resumo por produto</span>
          <span class="text-body-2 text-medium-emphasis ms-2">{{ textoPeriodo }}</span>
        </v-card-title>
        <v-divider></v-divider>
        <v-data-table :headers="headersResumo" :items="dados.resumo" class="pa-2" no-data-text="Nenhum produto marcado como controlado. Marque em Reagentes, na edição do cadastro.">
          <template v-slot:[`item.classificacao_controle`]="{ item }">{{ item.classificacao_controle || '-' }}</template>
          <template v-slot:[`item.saldo_inicial`]="{ item }">{{ qtd(item.saldo_inicial, item.unidade) }}</template>
          <template v-slot:[`item.entradas`]="{ item }"><span class="text-green-darken-2">+{{ qtd(item.entradas, item.unidade) }}</span></template>
          <template v-slot:[`item.usos`]="{ item }"><span class="text-deep-orange-darken-2">-{{ qtd(item.usos, item.unidade) }}</span></template>
          <template v-slot:[`item.saldo_final`]="{ item }"><strong>{{ qtd(item.saldo_final, item.unidade) }}</strong></template>
        </v-data-table>
      </v-card>

      <!-- Cada entrada e uso -->
      <v-card class="elevation-1 rounded-lg">
        <div class="d-flex flex-wrap align-center justify-space-between ga-2 pa-4">
          <span class="text-subtitle-1 font-weight-bold">Movimentações</span>
          <v-text-field
            v-model="busca"
            append-inner-icon="mdi-magnify"
            label="Buscar por usuário, lote, justificativa..."
            single-line
            hide-details
            density="compact"
            variant="outlined"
            style="max-width: 340px; min-width: 220px;"
          ></v-text-field>
        </div>
        <v-divider></v-divider>
        <v-data-table :headers="headersMov" :items="dados.movimentacoes" :search="busca" class="pa-2" no-data-text="Nenhuma movimentação no período">
          <template v-slot:[`item.data`]="{ item }">{{ formatarData(item.data) }}</template>
          <template v-slot:[`item.tipo`]="{ item }">
            <v-chip size="small" variant="tonal" :color="item.tipo === 'uso' ? 'deep-orange-darken-2' : 'success'">
              {{ item.tipo === 'uso' ? 'Uso' : 'Entrada' }}
            </v-chip>
          </template>
          <template v-slot:[`item.reagente`]="{ item }">
            <div>{{ item.reagente }}</div>
          </template>
          <template v-slot:[`item.lote`]="{ item }">
            <div>{{ item.lote || '-' }}</div>
            <div v-if="item.data_validade" class="text-caption text-medium-emphasis">val. {{ formatarData(item.data_validade) }}</div>
          </template>
          <template v-slot:[`item.quantidade`]="{ item }">{{ qtd(item.quantidade, item.unidade) }}</template>
          <template v-slot:[`item.justificativa`]="{ item }">
            <span v-if="item.justificativa" style="white-space: pre-wrap;">{{ item.justificativa }}</span>
            <span v-else class="text-warning">Não informada</span>
          </template>
        </v-data-table>
      </v-card>
    </template>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api, { mensagemErro } from '../plugins/axios';
import CabecalhoPagina from '../components/CabecalhoPagina.vue';
import { baixarPdf, baixarCsv } from '../utils/exportarRelatorio';
import { formatarQuantidade } from '../utils/unidades';
import { paraIso, formatarData } from '../utils/datas';

const qtd = (valor, sigla) => formatarQuantidade(valor, sigla);

const hoje = new Date();
const atalhos = [
  { titulo: 'Este mês', inicio: new Date(hoje.getFullYear(), hoje.getMonth(), 1), fim: hoje },
  { titulo: 'Mês passado', inicio: new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1), fim: new Date(hoje.getFullYear(), hoje.getMonth(), 0) },
  { titulo: 'Este ano', inicio: new Date(hoje.getFullYear(), 0, 1), fim: hoje },
  { titulo: 'Ano passado', inicio: new Date(hoje.getFullYear() - 1, 0, 1), fim: new Date(hoje.getFullYear() - 1, 11, 31) },
];

const headersResumo = [
  { title: 'Produto', key: 'nome' },
  { title: 'CATMAT', key: 'catmat' },
  { title: 'CAS', key: 'cas' },
  { title: 'Lista / código', key: 'classificacao_controle' },
  { title: 'Saldo inicial', key: 'saldo_inicial', align: 'end' },
  { title: 'Entradas', key: 'entradas', align: 'end' },
  { title: 'Usado', key: 'usos', align: 'end' },
  { title: 'Saldo final', key: 'saldo_final', align: 'end' },
];
const headersMov = [
  { title: 'Data', key: 'data' },
  { title: 'Tipo', key: 'tipo' },
  { title: 'Produto', key: 'reagente' },
  { title: 'Lote', key: 'lote' },
  { title: 'Quantidade', key: 'quantidade', align: 'end' },
  { title: 'Usuário', key: 'usuario' },
  { title: 'Justificativa / origem', key: 'justificativa', sortable: false },
];

const dataInicio = ref(paraIso(new Date(hoje.getFullYear(), 0, 1)));
const dataFim = ref(paraIso(hoje));
const idreagente = ref(null);
const produtos = ref([]);
const dados = ref(null);
const carregando = ref(false);
const erro = ref('');
const busca = ref('');
const formRef = ref(null);

const regras = {
  obrigatorio: (v) => !!v || 'Campo obrigatório',
  periodo: () => !dataInicio.value || !dataFim.value || dataFim.value >= dataInicio.value || 'A data final deve ser depois da inicial',
};

const textoPeriodo = computed(() => (dados.value
  ? `${formatarData(dados.value.periodo.inicio)} a ${formatarData(dados.value.periodo.fim)}`
  : ''));
const totalSemJustificativa = computed(() => (dados.value?.resumo || []).reduce((s, r) => s + r.usos_sem_justificativa, 0));

const aplicarAtalho = (a) => {
  dataInicio.value = paraIso(a.inicio);
  dataFim.value = paraIso(a.fim);
};

const gerar = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  carregando.value = true;
  erro.value = '';
  try {
    const params = { data_inicio: dataInicio.value, data_fim: dataFim.value };
    if (idreagente.value) params.idreagente = idreagente.value;
    dados.value = (await api.get('/relatorios/controlados', { params })).data;
  } catch (err) {
    erro.value = mensagemErro(err, 'Erro ao consultar os produtos controlados.');
  } finally {
    carregando.value = false;
  }
};

const exportar = (formato) => {
  const conteudo = {
    titulo: 'Relatório de Produtos Controlados',
    subtitulo: `Período: ${textoPeriodo.value}`,
    nomeArquivo: `labstock-controlados-${dados.value.periodo.inicio}-a-${dados.value.periodo.fim}`,
    tabelas: [
      {
        titulo: 'Resumo por produto',
        colunas: ['Produto', 'CATMAT', 'CAS', 'Lista / código', 'Saldo inicial', 'Entradas', 'Usado', 'Saldo final'],
        linhas: dados.value.resumo.map((r) => [
          r.nome, r.catmat || '-', r.cas || '-', r.classificacao_controle || '-',
          qtd(r.saldo_inicial, r.unidade), qtd(r.entradas, r.unidade), qtd(r.usos, r.unidade), qtd(r.saldo_final, r.unidade),
        ]),
      },
      {
        titulo: 'Movimentações',
        colunas: ['Data', 'Tipo', 'Produto', 'Lote', 'Validade', 'Quantidade', 'Usuário', 'Justificativa / origem'],
        linhas: dados.value.movimentacoes.map((m) => [
          formatarData(m.data), m.tipo === 'uso' ? 'Uso' : 'Entrada', m.reagente, m.lote || '-',
          formatarData(m.data_validade) || '-', qtd(m.quantidade, m.unidade), m.usuario || '-', m.justificativa || 'Não informada',
        ]),
      },
    ],
  };
  formato === 'pdf' ? baixarPdf(conteudo) : baixarCsv(conteudo);
};

onMounted(async () => {
  try {
    produtos.value = (await api.get('/reagentes')).data.filter((r) => r.controlado);
  } catch { /* o relatório abaixo mostra o erro, se houver */ }
  gerar();
});
</script>
