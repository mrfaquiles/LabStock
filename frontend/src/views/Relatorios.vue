<template>
  <v-container fluid class="pa-6">
    <!-- Cabeçalho -->
    <CabecalhoPagina titulo="Relatórios" subtitulo="Estoque disponível e gastos por período, para apoiar a previsão de compras" icone="mdi-file-chart">
      <template v-if="resultado">
        <v-btn color="grey-darken-2" variant="outlined" class="text-none" prepend-icon="mdi-file-pdf-box" @click="salvarPdf">
          Salvar PDF
        </v-btn>
        <v-btn color="green-darken-3" variant="tonal" class="text-none" prepend-icon="mdi-file-excel-outline" @click="exportarCsv">
          Exportar Excel (CSV)
        </v-btn>
      </template>
    </CabecalhoPagina>

    <!-- Filtros -->
    <v-card class="elevation-1 rounded-lg pa-4 mb-6">
      <v-form ref="formRef">
        <v-row align="center">
          <v-col cols="12">
            <v-btn-toggle v-model="tipo" mandatory color="primary" variant="outlined" divided class="flex-wrap">
              <v-btn value="estoque" class="text-none" prepend-icon="mdi-package-variant-closed">Estoque disponível</v-btn>
              <v-btn value="gastos" class="text-none" prepend-icon="mdi-chart-line">Gastos / Consumo</v-btn>
            </v-btn-toggle>
          </v-col>

          <v-col cols="12" sm="6" md="3">
            <v-menu v-model="menuInicio" :close-on-content-click="false" min-width="auto">
              <template v-slot:activator="{ props }">
                <v-text-field
                  v-bind="props"
                  :model-value="formatarData(dataInicio)"
                  label="Data inicial *"
                  readonly
                  variant="outlined"
                  density="comfortable"
                  append-inner-icon="mdi-calendar"
                  hide-details="auto"
                  :rules="[regras.obrigatorio]"
                ></v-text-field>
              </template>
              <v-date-picker
                :model-value="paraData(dataInicio)"
                :max="paraData(dataFim) || undefined"
                class="elevation-3 rounded-lg"
                density="compact"
                hide-header
                @update:model-value="d => { dataInicio = paraIso(d); menuInicio = false; }"
              ></v-date-picker>
            </v-menu>
          </v-col>

          <v-col cols="12" sm="6" md="3">
            <v-menu v-model="menuFim" :close-on-content-click="false" min-width="auto">
              <template v-slot:activator="{ props }">
                <v-text-field
                  v-bind="props"
                  :model-value="formatarData(dataFim)"
                  label="Data final *"
                  readonly
                  variant="outlined"
                  density="comfortable"
                  append-inner-icon="mdi-calendar"
                  hide-details="auto"
                  :rules="[regras.obrigatorio, regras.periodo]"
                ></v-text-field>
              </template>
              <v-date-picker
                :model-value="paraData(dataFim)"
                :min="paraData(dataInicio) || undefined"
                class="elevation-3 rounded-lg"
                density="compact"
                hide-header
                @update:model-value="d => { dataFim = paraIso(d); menuFim = false; }"
              ></v-date-picker>
            </v-menu>
          </v-col>

          <v-col cols="12" sm="6" md="3">
            <v-select
              v-model="categoria"
              :items="categorias"
              label="Categoria"
              variant="outlined"
              density="comfortable"
              hide-details
            ></v-select>
          </v-col>

          <v-col cols="12" sm="6" md="3">
            <v-btn color="primary" variant="flat" block size="large" class="text-none text-white" prepend-icon="mdi-file-chart" :loading="loading" @click="gerar">
              Gerar Relatório
            </v-btn>
          </v-col>

          <v-col cols="12" class="pt-0">
            <span class="text-caption text-grey-darken-1 me-2">Períodos rápidos:</span>
            <v-chip
              v-for="atalho in atalhos"
              :key="atalho.titulo"
              size="small"
              variant="tonal"
              color="primary"
              class="me-2 mb-1"
              @click="aplicarAtalho(atalho)"
            >
              {{ atalho.titulo }}
            </v-chip>
          </v-col>
        </v-row>
      </v-form>
    </v-card>

    <v-alert v-if="erro" type="error" variant="tonal" class="mb-4" closable @click:close="erro = ''">{{ erro }}</v-alert>

    <!-- Resultado: Estoque disponível -->
    <template v-if="resultado?.tipo === 'estoque'">
      <v-card class="elevation-1 rounded-lg">
        <v-card-title class="pa-4">
          <span class="text-subtitle-1 font-weight-bold">Estoque disponível</span>
          <span class="text-body-2 text-grey-darken-1 ms-2">{{ textoPeriodo }}</span>
        </v-card-title>
        <v-divider></v-divider>
        <v-data-table :headers="headersEstoque" :items="resultado.dados.itens" density="comfortable" class="pa-2" no-data-text="Nenhum item encontrado">
          <template v-slot:[`item.categoria`]="{ item }">
            <v-chip size="small" variant="tonal" :color="corCategoria(item.categoria)">{{ nomeCategoria(item.categoria) }}</v-chip>
          </template>
          <template v-slot:[`item.saldo_inicial`]="{ item }">{{ num(item.saldo_inicial) }} {{ item.unidade }}</template>
          <template v-slot:[`item.entradas`]="{ item }">
            <span :class="item.entradas ? 'text-green-darken-2' : 'text-grey'">+{{ num(item.entradas) }}</span>
          </template>
          <template v-slot:[`item.saidas`]="{ item }">
            <span :class="item.saidas ? 'text-deep-orange-darken-2' : 'text-grey'">-{{ num(item.saidas) }}</span>
          </template>
          <template v-slot:[`item.saldo_final`]="{ item }">
            <strong>{{ num(item.saldo_final) }} {{ item.unidade }}</strong>
          </template>
          <template v-slot:[`item.detalhe`]="{ item }">
            <span v-if="item.lotes.length" class="text-caption">
              <div v-for="l in item.lotes" :key="l.identradareagente">
                {{ l.lote }}: {{ num(l.saldo) }} {{ item.unidade }} — val. {{ formatarData(l.data_validade) }}
              </div>
            </span>
            <span v-else class="text-caption">{{ [item.status, item.localizacao].filter(Boolean).join(' — ') || '-' }}</span>
          </template>
        </v-data-table>
      </v-card>
    </template>

    <!-- Resultado: Gastos -->
    <template v-if="resultado?.tipo === 'gastos'">
      <v-card class="elevation-1 rounded-lg mb-6">
        <v-card-title class="pa-4">
          <span class="text-subtitle-1 font-weight-bold">Resumo de gastos por item</span>
          <span class="text-body-2 text-grey-darken-1 ms-2">{{ textoPeriodo }}</span>
        </v-card-title>
        <v-divider></v-divider>
        <v-data-table :headers="headersResumo" :items="resultado.dados.resumo" density="comfortable" class="pa-2" no-data-text="Nenhum gasto registrado no período">
          <template v-slot:[`item.categoria`]="{ item }">
            <v-chip size="small" variant="tonal" :color="corCategoria(item.categoria)">{{ nomeCategoria(item.categoria) }}</v-chip>
          </template>
          <template v-slot:[`item.total`]="{ item }"><strong>{{ num(item.total) }} {{ item.unidade }}</strong></template>
          <template v-slot:[`item.media_mensal`]="{ item }">{{ num(item.media_mensal) }} {{ item.unidade }}/mês</template>
          <template v-slot:[`item.estoque_atual`]="{ item }">{{ num(item.estoque_atual) }} {{ item.unidade }}</template>
          <template v-slot:[`item.cobertura_meses`]="{ item }">
            <v-chip v-if="item.cobertura_meses !== null" size="small" variant="tonal" :color="corCobertura(item.cobertura_meses)">
              {{ num(item.cobertura_meses) }} {{ item.cobertura_meses === 1 ? 'mês' : 'meses' }}
            </v-chip>
            <span v-else>-</span>
          </template>
        </v-data-table>
        <p class="text-caption text-grey-darken-1 px-4 pb-3 mb-0">
          <strong>Cobertura</strong>: quantos meses o estoque atual dura mantendo a média de consumo do período.
          Abaixo de {{ config.config.cobertura_minima_meses }} meses (ajustável em Configurações), considere incluir na próxima compra.
        </p>
      </v-card>

      <v-card class="elevation-1 rounded-lg">
        <v-card-title class="pa-4">
          <span class="text-subtitle-1 font-weight-bold">Registros de uso, quebras e saídas</span>
        </v-card-title>
        <v-divider></v-divider>
        <v-data-table :headers="headersDetalhes" :items="resultado.dados.detalhes" density="compact" class="pa-2" no-data-text="Nenhum registro no período">
          <template v-slot:[`item.data`]="{ item }">{{ formatarData(item.data) }}</template>
          <template v-slot:[`item.categoria`]="{ item }">
            <v-chip size="small" variant="tonal" :color="corCategoria(item.categoria)">{{ nomeCategoria(item.categoria) }}</v-chip>
          </template>
          <template v-slot:[`item.quantidade`]="{ item }">{{ num(item.quantidade) }} {{ item.unidade }}</template>
          <template v-slot:[`item.origem`]="{ item }">{{ item.lote ? `Lote ${item.lote}` : (item.laboratorio || '-') }}</template>
        </v-data-table>
      </v-card>
    </template>

    <v-card v-if="!resultado && !loading" class="elevation-0 rounded-lg pa-8 text-center bg-grey-lighten-4">
      <v-icon icon="mdi-file-chart-outline" size="48" color="grey"></v-icon>
      <p class="text-body-1 text-grey-darken-1 mt-2 mb-0">Escolha o tipo de relatório e o período e clique em <strong>Gerar Relatório</strong>.</p>
    </v-card>

    <!-- Escolha do formato logo após gerar -->
    <v-dialog v-model="dialogExportar" max-width="460px">
      <v-card v-if="resultado" class="rounded-lg pa-4">
        <v-card-title class="d-flex align-center">
          <v-icon icon="mdi-check-circle" color="success" class="me-2"></v-icon>
          <span class="text-h6 font-weight-bold">Relatório pronto</span>
        </v-card-title>
        <v-card-text class="pb-2">
          <p class="text-body-2 mb-4">
            <strong>{{ tituloRelatorio() }}</strong><br>
            {{ textoPeriodo }} — {{ textoCategoria() }}
          </p>
          <p class="text-body-2 text-medium-emphasis mb-3">Como você quer salvar?</p>

          <v-btn block size="large" color="primary" variant="flat" class="text-none text-white mb-3 justify-start" prepend-icon="mdi-file-pdf-box" @click="salvarPdf">
            Salvar em PDF
            <span class="text-caption ms-2 opacity-70">para imprimir ou arquivar</span>
          </v-btn>
          <v-btn block size="large" color="green-darken-3" variant="tonal" class="text-none mb-3 justify-start" prepend-icon="mdi-file-excel-outline" @click="exportarCsv">
            Baixar planilha (Excel/CSV)
            <span class="text-caption ms-2 opacity-70">para editar e somar</span>
          </v-btn>
          <v-btn block variant="text" color="grey-darken-2" class="text-none" prepend-icon="mdi-eye-outline" @click="dialogExportar = false">
            Só visualizar na tela
          </v-btn>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, computed } from 'vue';
import api, { mensagemErro } from '../plugins/axios';
import CabecalhoPagina from '../components/CabecalhoPagina.vue';
import { baixarPdf, baixarCsv } from '../utils/exportarRelatorio';
import { useConfigStore } from '../stores/configStore';

const NOMES = { reagentes: 'Reagente', vidrarias: 'Vidraria', equipamentos: 'Equipamento' };
const CORES = { reagentes: 'green-darken-3', vidrarias: 'blue-darken-2', equipamentos: 'blue-grey-darken-2' };

const categorias = [
  { title: 'Todas', value: 'todas' },
  { title: 'Reagentes', value: 'reagentes' },
  { title: 'Vidrarias', value: 'vidrarias' },
  { title: 'Equipamentos', value: 'equipamentos' },
];

const headersEstoque = [
  { title: 'Categoria', key: 'categoria' },
  { title: 'Item', key: 'nome' },
  { title: 'CATMAT', key: 'catmat' },
  { title: 'Saldo inicial', key: 'saldo_inicial', align: 'end' },
  { title: 'Entradas', key: 'entradas', align: 'end' },
  { title: 'Saídas', key: 'saidas', align: 'end' },
  { title: 'Saldo final', key: 'saldo_final', align: 'end' },
  { title: 'Lotes / Localização', key: 'detalhe', sortable: false },
];
const headersResumo = [
  { title: 'Categoria', key: 'categoria' },
  { title: 'Item', key: 'item' },
  { title: 'CATMAT', key: 'catmat' },
  { title: 'Registros', key: 'registros', align: 'end' },
  { title: 'Total gasto', key: 'total', align: 'end' },
  { title: 'Média mensal', key: 'media_mensal', align: 'end' },
  { title: 'Estoque atual', key: 'estoque_atual', align: 'end' },
  { title: 'Cobertura', key: 'cobertura_meses', align: 'end' },
];
const headersDetalhes = [
  { title: 'Data', key: 'data' },
  { title: 'Categoria', key: 'categoria' },
  { title: 'Item', key: 'item' },
  { title: 'Lote / Laboratório', key: 'origem', sortable: false },
  { title: 'Quantidade', key: 'quantidade', align: 'end' },
  { title: 'Usuário', key: 'usuario' },
  { title: 'Finalidade / Observação', key: 'observacao' },
];

// ---------- Datas ----------
const paraIso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const paraData = (iso) => (iso ? new Date(iso + 'T00:00:00') : null);
const formatarData = (iso) => {
  if (!iso) return '';
  const [a, m, d] = iso.split('-');
  return `${d}/${m}/${a}`;
};

const hoje = new Date();
const atalhos = [
  { titulo: 'Este mês', inicio: () => new Date(hoje.getFullYear(), hoje.getMonth(), 1) },
  { titulo: 'Mês passado', inicio: () => new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1), fim: () => new Date(hoje.getFullYear(), hoje.getMonth(), 0) },
  { titulo: 'Últimos 3 meses', inicio: () => new Date(hoje.getFullYear(), hoje.getMonth() - 3, hoje.getDate()) },
  { titulo: 'Últimos 6 meses', inicio: () => new Date(hoje.getFullYear(), hoje.getMonth() - 6, hoje.getDate()) },
  { titulo: 'Este ano', inicio: () => new Date(hoje.getFullYear(), 0, 1) },
];

// ---------- Estado ----------
const tipo = ref('estoque');
const categoria = ref('todas');
const dataInicio = ref(paraIso(new Date(hoje.getFullYear(), hoje.getMonth(), 1)));
const dataFim = ref(paraIso(hoje));
const menuInicio = ref(false);
const menuFim = ref(false);
const loading = ref(false);
const erro = ref('');
const resultado = ref(null);
const formRef = ref(null);
const dialogExportar = ref(false);

const regras = {
  obrigatorio: (v) => !!v || 'Campo obrigatório',
  periodo: () => !dataInicio.value || !dataFim.value || dataFim.value >= dataInicio.value || 'A data final deve ser depois da inicial',
};

const textoPeriodo = computed(() => resultado.value
  ? `${formatarData(resultado.value.dados.periodo.inicio)} a ${formatarData(resultado.value.dados.periodo.fim)}`
  : '');

const aplicarAtalho = (atalho) => {
  dataInicio.value = paraIso(atalho.inicio());
  dataFim.value = paraIso(atalho.fim ? atalho.fim() : hoje);
};

const gerar = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  loading.value = true;
  erro.value = '';
  try {
    const resposta = await api.get(`/relatorios/${tipo.value}`, {
      params: { data_inicio: dataInicio.value, data_fim: dataFim.value, categoria: categoria.value },
    });
    resultado.value = { tipo: tipo.value, categoria: categoria.value, dados: resposta.data };
    dialogExportar.value = true; // Pergunta em que formato salvar
  } catch (err) {
    erro.value = mensagemErro(err, 'Erro ao gerar relatório.');
  } finally {
    loading.value = false;
  }
};

// ---------- Formatação ----------
const num = (v) => Number(v || 0).toLocaleString('pt-BR', { maximumFractionDigits: 3 });
const nomeCategoria = (c) => NOMES[c] || c;
const corCategoria = (c) => CORES[c] || 'grey';
// Limite definido em Configurações do Sistema (cobertura mínima)
const config = useConfigStore();
const corCobertura = (meses) => {
  const minimo = config.config.cobertura_minima_meses;
  return meses < minimo ? 'error' : meses < minimo * 2 ? 'warning' : 'success';
};

// Linhas no formato de tabela (usadas na impressão e no CSV)
const tabelas = () => {
  const d = resultado.value.dados;
  if (resultado.value.tipo === 'estoque') {
    return [{
      titulo: 'Estoque disponível',
      colunas: ['Categoria', 'Item', 'CATMAT', 'Unidade', 'Saldo inicial', 'Entradas', 'Saídas', 'Saldo final', 'Lotes / Localização'],
      linhas: d.itens.map(i => [
        nomeCategoria(i.categoria), i.nome, i.catmat || '-', i.unidade || '',
        num(i.saldo_inicial), num(i.entradas), num(i.saidas), num(i.saldo_final),
        i.lotes.length
          ? i.lotes.map(l => `${l.lote}: ${num(l.saldo)} ${i.unidade} (val. ${formatarData(l.data_validade)})`).join(' | ')
          : [i.status, i.localizacao].filter(Boolean).join(' — ') || '-',
      ]),
    }];
  }
  return [
    {
      titulo: 'Resumo de gastos por item',
      colunas: ['Categoria', 'Item', 'CATMAT', 'Unidade', 'Registros', 'Total gasto', 'Média mensal', 'Estoque atual', 'Cobertura (meses)'],
      linhas: d.resumo.map(r => [
        nomeCategoria(r.categoria), r.item, r.catmat || '-', r.unidade || '', r.registros,
        num(r.total), num(r.media_mensal), num(r.estoque_atual), r.cobertura_meses === null ? '-' : num(r.cobertura_meses),
      ]),
    },
    {
      titulo: 'Registros de uso, quebras e saídas',
      colunas: ['Data', 'Categoria', 'Item', 'Lote / Laboratório', 'Quantidade', 'Unidade', 'Usuário', 'Finalidade / Observação'],
      linhas: d.detalhes.map(x => [
        formatarData(x.data), nomeCategoria(x.categoria), x.item, x.lote ? `Lote ${x.lote}` : (x.laboratorio || '-'),
        num(x.quantidade), x.unidade || '', x.usuario || '-', x.observacao || '-',
      ]),
    },
  ];
};

const tituloRelatorio = () => (resultado.value.tipo === 'estoque' ? 'Relatório de Estoque Disponível' : 'Relatório de Gastos');
const textoCategoria = () => categorias.find(c => c.value === resultado.value.categoria)?.title;

// Dados no formato do utilitário de exportação (PDF e CSV)
const conteudoExportacao = () => ({
  titulo: tituloRelatorio(),
  subtitulo: `Período: ${textoPeriodo.value}   |   Categoria: ${textoCategoria()}`,
  nomeArquivo: `labstock-${resultado.value.tipo}-${resultado.value.dados.periodo.inicio}-a-${resultado.value.dados.periodo.fim}`,
  tabelas: tabelas(),
});

const salvarPdf = () => {
  try {
    baixarPdf(conteudoExportacao());
    dialogExportar.value = false;
  } catch (err) {
    console.error(err);
    erro.value = 'Não foi possível gerar o PDF.';
  }
};

const exportarCsv = () => {
  baixarCsv(conteudoExportacao());
  dialogExportar.value = false;
};
</script>
