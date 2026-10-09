<template>
  <v-container fluid class="pa-6">
    <CabecalhoPagina titulo="Compras" subtitulo="Previsão de demanda para a licitação e acompanhamento dos pedidos" icone="mdi-cart-outline">
      <v-btn v-if="auth.podeEditar" color="primary" class="text-none text-white" prepend-icon="mdi-plus" @click="abrirNovoPedido()">
        Novo Pedido
      </v-btn>
    </CabecalhoPagina>

    <v-snackbar v-model="snackbar.ativo" :color="snackbar.cor" timeout="4000" location="top right">
      {{ snackbar.texto }}
    </v-snackbar>

    <v-tabs v-model="aba" color="primary" class="mb-4">
      <v-tab value="previsao" class="text-none" prepend-icon="mdi-chart-timeline-variant">Previsão de compras</v-tab>
      <v-tab value="pedidos" class="text-none" prepend-icon="mdi-truck-delivery-outline">
        Pedidos
        <v-badge v-if="pedidosAndamento.length" :content="pedidosAndamento.length" color="primary" inline></v-badge>
      </v-tab>
    </v-tabs>

    <v-window v-model="aba">
      <!-- ===================== PREVISÃO ===================== -->
      <v-window-item value="previsao">
        <v-card class="elevation-1 rounded-lg pa-4 mb-4">
          <v-row align="center">
            <v-col cols="12" sm="6" md="3">
              <v-select v-model="filtros.meses_base" :items="opcoesBase" label="Consumo usado como base" variant="outlined" density="comfortable" hide-details></v-select>
            </v-col>
            <v-col cols="12" sm="6" md="3">
              <v-select v-model="filtros.meses_cobrir" :items="opcoesCobrir" label="Comprar para" variant="outlined" density="comfortable" hide-details></v-select>
            </v-col>
            <v-col cols="12" sm="4" md="2">
              <v-text-field v-model.number="filtros.margem" label="Margem de segurança" type="number" min="0" max="200" suffix="%" variant="outlined" density="comfortable" hide-details></v-text-field>
            </v-col>
            <v-col cols="12" sm="4" md="2">
              <v-select v-model="filtros.categoria" :items="categorias" label="Categoria" variant="outlined" density="comfortable" hide-details></v-select>
            </v-col>
            <v-col cols="12" sm="4" md="2">
              <v-btn color="primary" variant="flat" block size="large" class="text-none text-white" prepend-icon="mdi-calculator-variant" :loading="carregandoPrevisao" @click="carregarPrevisao">
                Calcular
              </v-btn>
            </v-col>
          </v-row>
          <p class="text-caption text-medium-emphasis mt-3 mb-0">
            Quantidade sugerida = consumo médio × meses a comprar + margem − estoque disponível − o que já foi pedido.
            Tempo médio de compra considerado: <strong>{{ config.config.tempo_compra_meses }} meses</strong> (ajustável em Configurações).
          </p>
        </v-card>

        <!-- Resumo por situação -->
        <v-row v-if="previsao.length" class="mb-2">
          <v-col v-for="s in resumoSituacoes" :key="s.valor" cols="6" md="3">
            <v-card
              class="pa-3 rounded-lg cursor-pointer"
              :variant="filtroSituacao === s.valor ? 'flat' : 'tonal'"
              :color="s.cor"
              @click="filtroSituacao = filtroSituacao === s.valor ? null : s.valor"
            >
              <div class="d-flex align-center">
                <v-icon :icon="s.icone" class="me-2"></v-icon>
                <span class="text-h6 font-weight-bold me-2">{{ s.total }}</span>
                <span class="text-body-2">{{ s.texto }}</span>
              </div>
            </v-card>
          </v-col>
        </v-row>

        <v-card class="elevation-1 rounded-lg">
          <div class="d-flex flex-wrap align-center justify-space-between ga-2 pa-4">
            <v-switch v-model="somenteComprar" color="primary" inset hide-details density="compact" label="Mostrar só itens a comprar"></v-switch>
            <div class="d-flex flex-wrap ga-2">
              <v-btn color="grey-darken-2" variant="outlined" class="text-none" prepend-icon="mdi-file-pdf-box" :disabled="!itensParaExportar.length" @click="exportar('pdf')">
                Salvar PDF
              </v-btn>
              <v-btn color="green-darken-3" variant="tonal" class="text-none" prepend-icon="mdi-file-excel-outline" :disabled="!itensParaExportar.length" @click="exportar('csv')">
                Exportar Excel (CSV)
              </v-btn>
            </div>
          </div>
          <v-divider></v-divider>

          <v-data-table
            :headers="headersPrevisao"
            :items="previsaoFiltrada"
            :loading="carregandoPrevisao"
            item-value="chave"
            class="pa-2"
            no-data-text="Clique em Calcular para gerar a previsão"
          >
            <template v-slot:[`item.situacao`]="{ item }">
              <v-chip size="small" variant="tonal" :color="SITUACOES[item.situacao].cor" :prepend-icon="SITUACOES[item.situacao].icone">
                {{ SITUACOES[item.situacao].curto }}
              </v-chip>
            </template>

            <!-- Nome com a justificativa no mesmo menu flutuante das descrições -->
            <template v-slot:[`item.nome`]="{ item }">
              <div class="d-flex align-center">
                <span class="me-2">{{ item.nome }}</span>
                <v-menu open-on-hover location="top" :close-on-content-click="false">
                  <template v-slot:activator="{ props }">
                    <v-icon v-bind="props" size="small" color="grey-darken-1" class="cursor-pointer">mdi-help-circle-outline</v-icon>
                  </template>
                  <v-card class="pa-3 elevation-4 rounded-lg" color="blue-lighten-5" style="max-width: 380px; max-height: 220px; overflow-y: auto; border: 1px solid #b0bec5;">
                    <p class="text-caption font-weight-bold text-blue-grey-darken-4 mb-1">Justificativa</p>
                    <p class="text-body-2 mb-0 text-blue-grey-darken-4" style="white-space: pre-wrap; line-height: 1.4;">{{ item.justificativa }}</p>
                  </v-card>
                </v-menu>
              </div>
            </template>

            <template v-slot:[`item.media_mensal`]="{ item }">{{ qtd(item.media_mensal, item.unidade) }}/mês</template>
            <template v-slot:[`item.estoque`]="{ item }">
              {{ qtd(item.estoque, item.unidade) }}
              <div v-if="item.cobertura_meses !== null" class="text-caption text-medium-emphasis">dura {{ num(item.cobertura_meses) }} meses</div>
            </template>
            <template v-slot:[`item.em_pedido`]="{ item }">
              <span :class="item.em_pedido ? '' : 'text-grey'">{{ qtd(item.em_pedido, item.unidade) }}</span>
            </template>

            <!-- Quantidade sugerida, ajustável antes de exportar ou pedir -->
            <template v-slot:[`item.quantidade`]="{ item }">
              <v-text-field
                v-model.number="ajustes[item.chave]"
                type="number"
                min="0"
                :step="item.categoria === 'vidrarias' ? 1 : 0.001"
                :suffix="item.unidade"
                variant="outlined"
                density="compact"
                hide-details
                style="min-width: 130px;"
                @keydown="bloquearSinais"
              ></v-text-field>
            </template>

            <template v-slot:[`item.acoes`]="{ item }">
              <v-tooltip v-if="auth.podeEditar" text="Criar pedido com esta quantidade" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn v-bind="props" icon="mdi-cart-plus" size="small" variant="text" color="primary" :disabled="!(ajustes[item.chave] > 0)" @click="abrirNovoPedido(item)"></v-btn>
                </template>
              </v-tooltip>
            </template>
          </v-data-table>
        </v-card>
      </v-window-item>

      <!-- ===================== PEDIDOS ===================== -->
      <v-window-item value="pedidos">
        <v-card class="elevation-1 rounded-lg">
          <div class="d-flex flex-wrap align-center justify-space-between ga-2 pa-4">
            <v-btn-toggle v-model="abaPedidos" mandatory color="primary" variant="outlined" density="comfortable" divided>
              <v-btn value="andamento" class="text-none" prepend-icon="mdi-progress-clock">
                Em andamento
                <v-chip v-if="pedidosAndamento.length" size="x-small" color="primary" class="ms-2">{{ pedidosAndamento.length }}</v-chip>
              </v-btn>
              <v-btn value="historico" class="text-none" prepend-icon="mdi-history">Histórico</v-btn>
            </v-btn-toggle>
            <span class="text-caption text-medium-emphasis">Atualize o andamento conforme a licitação avança. Ao receber, o item entra no estoque.</span>
          </div>
          <v-divider></v-divider>

          <v-data-table
            :headers="headersPedidos"
            :items="abaPedidos === 'andamento' ? pedidosAndamento : pedidosHistorico"
            :loading="carregandoPedidos"
            class="pa-2"
            :no-data-text="abaPedidos === 'andamento' ? 'Nenhum pedido em andamento' : 'Nenhum pedido finalizado'"
          >
            <template v-slot:[`item.data_pedido`]="{ item }">{{ formatarData(item.data_pedido) }}</template>
            <template v-slot:[`item.item`]="{ item }">
              <div>{{ nomeItem(item) }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.categoria === 'reagentes' ? 'Reagente' : 'Vidraria' }}</div>
            </template>
            <template v-slot:[`item.quantidade`]="{ item }">{{ qtd(item.quantidade, unidadePedido(item)) }}</template>

            <template v-slot:[`item.status`]="{ item }">
              <v-chip size="small" variant="tonal" :color="STATUS[item.status].cor" :prepend-icon="STATUS[item.status].icone">
                {{ STATUS[item.status].texto }}
              </v-chip>
              <div v-if="item.status === 'recebido'" class="text-caption text-medium-emphasis">em {{ formatarData(item.data_recebimento) }}</div>
              <div v-else-if="item.previsao_entrega && item.status !== 'cancelado'" class="text-caption" :class="atrasado(item) ? 'text-error' : 'text-medium-emphasis'">
                previsão {{ formatarData(item.previsao_entrega) }}{{ atrasado(item) ? ' (atrasado)' : '' }}
              </div>
            </template>

            <template v-slot:[`item.observacao`]="{ item }">
              <span class="text-caption">{{ item.observacao || '-' }}</span>
            </template>
            <template v-slot:[`item.usuario`]="{ item }">{{ item.usuario?.nome }}</template>

            <template v-slot:[`item.acoes`]="{ item }">
              <div v-if="auth.podeEditar && EM_ANDAMENTO.includes(item.status)" class="d-flex justify-end align-center">
                <v-menu location="bottom end">
                  <template v-slot:activator="{ props }">
                    <v-btn v-bind="props" size="small" variant="tonal" color="primary" class="text-none me-2" append-icon="mdi-chevron-down">
                      Andamento
                    </v-btn>
                  </template>
                  <v-list density="compact">
                    <v-list-item
                      v-for="s in ['solicitado', 'em_licitacao', 'aguardando_entrega']"
                      :key="s"
                      :prepend-icon="STATUS[s].icone"
                      :title="STATUS[s].texto"
                      :active="item.status === s"
                      @click="mudarStatus(item, s)"
                    ></v-list-item>
                    <v-divider></v-divider>
                    <v-list-item prepend-icon="mdi-calendar-edit" title="Previsão de entrega..." @click="abrirEdicao(item)"></v-list-item>
                    <v-list-item prepend-icon="mdi-close-circle-outline" title="Cancelar pedido" base-color="error" @click="mudarStatus(item, 'cancelado')"></v-list-item>
                  </v-list>
                </v-menu>
                <v-btn size="small" color="success" variant="flat" class="text-none" prepend-icon="mdi-package-variant-closed-check" @click="abrirRecebimento(item)">
                  Receber
                </v-btn>
              </div>
            </template>
          </v-data-table>
        </v-card>
      </v-window-item>
    </v-window>

    <!-- Novo pedido -->
    <v-dialog v-model="dialogPedido" max-width="560px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">Novo Pedido de Compra</span>
          <v-btn icon variant="text" @click="dialogPedido = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider class="mb-4"></v-divider>
        <v-card-text>
          <v-form ref="formPedido">
            <v-autocomplete
              v-model="novoPedido.chave"
              :items="opcoesItens"
              item-title="titulo"
              item-value="chave"
              label="Reagente ou vidraria *"
              variant="outlined"
              density="comfortable"
              no-data-text="Nenhum item encontrado"
              :rules="[regras.obrigatorio]"
            ></v-autocomplete>
            <v-row dense>
              <v-col cols="12" sm="7">
                <!-- Reagente: digita em kg/g/mg (ou L/mL/µL) com conversão; vidraria: unidades inteiras -->
                <CampoQuantidade
                  v-if="itemSelecionado?.categoria === 'reagentes'"
                  :key="novoPedido.chave"
                  v-model="novoPedido.quantidade"
                  :sigla-base="itemSelecionado.unidade"
                  label="Quantidade *"
                ></CampoQuantidade>
                <v-text-field
                  v-else
                  v-model.number="novoPedido.quantidade"
                  label="Quantidade *"
                  type="number"
                  min="0"
                  :suffix="itemSelecionado?.unidade"
                  variant="outlined"
                  density="comfortable"
                  :rules="[regras.positivo, regraInteiroVidraria]"
                  @keydown="bloquearSinais"
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="5">
                <v-text-field v-model="novoPedido.previsao_entrega" label="Previsão de entrega" type="date" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
            </v-row>
            <v-text-field v-model="novoPedido.observacao" label="Observação" variant="outlined" density="comfortable" placeholder="Ex.: Incluído no planejamento de compras 2027"></v-text-field>
          </v-form>
        </v-card-text>
        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialogPedido = false">Cancelar</v-btn>
          <v-btn color="primary" variant="flat" class="text-none text-white px-6" :loading="salvando" @click="salvarPedido">Criar Pedido</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Previsão de entrega / observação -->
    <v-dialog v-model="dialogEdicao" max-width="480px">
      <v-card class="rounded-lg pa-4">
        <v-card-title class="text-h6 font-weight-bold">Atualizar pedido</v-card-title>
        <v-card-subtitle class="pb-2">{{ pedidoEdicao && nomeItem(pedidoEdicao) }}</v-card-subtitle>
        <v-card-text>
          <v-text-field v-model="edicao.previsao_entrega" label="Previsão de entrega" type="date" variant="outlined" density="comfortable"></v-text-field>
          <v-text-field v-model="edicao.observacao" label="Observação" variant="outlined" density="comfortable" placeholder="Ex.: Pregão 12/2026 homologado"></v-text-field>
        </v-card-text>
        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialogEdicao = false">Cancelar</v-btn>
          <v-btn color="primary" variant="flat" class="text-none text-white px-6" :loading="salvando" @click="salvarEdicao">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Recebimento -->
    <v-dialog v-model="dialogReceber" max-width="560px" persistent>
      <v-card v-if="pedidoReceber" class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">Receber Pedido</span>
          <v-btn icon variant="text" @click="dialogReceber = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-card-subtitle class="pb-2">
          {{ nomeItem(pedidoReceber) }} — pedido: {{ qtd(pedidoReceber.quantidade, unidadePedido(pedidoReceber)) }}
        </v-card-subtitle>
        <v-divider class="mb-4"></v-divider>
        <v-card-text>
          <v-form ref="formReceber">
            <CampoQuantidade
              v-if="pedidoReceber.categoria === 'reagentes'"
              v-model="recebimento.quantidade"
              :sigla-base="unidadePedido(pedidoReceber)"
              label="Quantidade recebida *"
            ></CampoQuantidade>
            <v-text-field
              v-else
              v-model.number="recebimento.quantidade"
              label="Quantidade recebida *"
              type="number"
              min="0"
              :suffix="unidadePedido(pedidoReceber)"
              variant="outlined"
              density="comfortable"
              hint="Se veio diferente do pedido, informe o que realmente chegou"
              persistent-hint
              :rules="[regras.positivo, v => pedidoReceber.categoria !== 'vidrarias' || Number.isInteger(Number(v)) || 'Vidraria em unidades inteiras']"
              @keydown="bloquearSinais"
            ></v-text-field>
            <template v-if="pedidoReceber.categoria === 'reagentes'">
              <v-row dense class="mt-2">
                <v-col cols="12" sm="6">
                  <v-text-field v-model="recebimento.lote" label="Número do lote *" variant="outlined" density="comfortable" autocomplete="off" :rules="[regras.obrigatorio]"></v-text-field>
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field v-model="recebimento.data_validade" label="Validade do lote *" type="date" variant="outlined" density="comfortable" :rules="[regras.obrigatorio]"></v-text-field>
                </v-col>
              </v-row>
            </template>
          </v-form>
          <v-alert type="info" variant="tonal" density="compact" class="mt-2">
            {{ pedidoReceber.categoria === 'reagentes'
              ? 'O lote será adicionado ao estoque do reagente (todas as unidades com a mesma validade).'
              : 'A quantidade será somada ao estoque da vidraria.' }}
          </v-alert>
        </v-card-text>
        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialogReceber = false">Cancelar</v-btn>
          <v-btn color="success" variant="flat" class="text-none px-6" :loading="salvando" @click="confirmarRecebimento">Confirmar Recebimento</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import api, { mensagemErro } from '../plugins/axios';
import CabecalhoPagina from '../components/CabecalhoPagina.vue';
import { useAuthStore } from '../stores/authStore';
import { useConfigStore } from '../stores/configStore';
import { baixarPdf, baixarCsv } from '../utils/exportarRelatorio';
import CampoQuantidade from '../components/CampoQuantidade.vue';
import { formatarQuantidade } from '../utils/unidades';

const auth = useAuthStore();
const config = useConfigStore();

const SITUACOES = {
  pedir_agora: { curto: 'Pedir agora', texto: 'pedir agora', cor: 'error', icone: 'mdi-alert' },
  planejar: { curto: 'Incluir na compra', texto: 'para a próxima compra', cor: 'warning', icone: 'mdi-calendar-clock' },
  em_pedido: { curto: 'Já pedido', texto: 'já pedidos', cor: 'primary', icone: 'mdi-truck-outline' },
  ok: { curto: 'Estoque ok', texto: 'com estoque suficiente', cor: 'success', icone: 'mdi-check-circle' },
  sem_consumo: { curto: 'Sem consumo', texto: 'sem consumo no período', cor: 'grey', icone: 'mdi-minus-circle-outline' },
};
const STATUS = {
  solicitado: { texto: 'Solicitado', cor: 'blue-grey', icone: 'mdi-file-document-edit-outline' },
  em_licitacao: { texto: 'Em licitação', cor: 'warning', icone: 'mdi-gavel' },
  aguardando_entrega: { texto: 'Aguardando entrega', cor: 'primary', icone: 'mdi-truck-outline' },
  recebido: { texto: 'Recebido', cor: 'success', icone: 'mdi-check-circle' },
  cancelado: { texto: 'Cancelado', cor: 'error', icone: 'mdi-close-circle' },
};
const EM_ANDAMENTO = ['solicitado', 'em_licitacao', 'aguardando_entrega'];

const opcoesBase = [
  { title: 'Últimos 6 meses', value: 6 },
  { title: 'Últimos 12 meses', value: 12 },
  { title: 'Últimos 24 meses', value: 24 },
];
const opcoesCobrir = [
  { title: '6 meses', value: 6 },
  { title: '12 meses (compra anual)', value: 12 },
  { title: '18 meses', value: 18 },
  { title: '24 meses', value: 24 },
];
const categorias = [
  { title: 'Reagentes e vidrarias', value: 'todas' },
  { title: 'Reagentes', value: 'reagentes' },
  { title: 'Vidrarias', value: 'vidrarias' },
];

const aba = ref('previsao');
const snackbar = ref({ ativo: false, texto: '', cor: 'success' });
const avisar = (texto, cor = 'success') => { snackbar.value = { ativo: true, texto, cor }; };

const regras = {
  obrigatorio: (v) => (v !== null && v !== undefined && String(v).trim() !== '') || 'Campo obrigatório',
  positivo: (v) => Number(v) > 0 || 'Informe uma quantidade maior que zero',
};

const num = (v) => Number(v || 0).toLocaleString('pt-BR', { maximumFractionDigits: 3 });
// Quantidade na unidade mais legível: 0,0005 kg -> "0,5 g"
const qtd = (valor, sigla) => formatarQuantidade(valor, sigla);
const formatarData = (iso) => {
  if (!iso) return '';
  const [a, m, d] = String(iso).substring(0, 10).split('-');
  return `${d}/${m}/${a}`;
};
const bloquearSinais = (e) => {
  if (['-', '+', 'e', 'E'].includes(e.key)) e.preventDefault();
};

// ---------------- Previsão ----------------
const filtros = ref({ meses_base: 12, meses_cobrir: 12, margem: config.config.margem_seguranca_percentual, categoria: 'todas' });
const previsao = ref([]);
const parametros = ref(null);
const carregandoPrevisao = ref(false);
const ajustes = ref({});
const somenteComprar = ref(false);
const filtroSituacao = ref(null);

const headersPrevisao = [
  { title: 'Situação', key: 'situacao' },
  { title: 'Item', key: 'nome' },
  { title: 'CATMAT', key: 'catmat' },
  { title: 'Média de consumo', key: 'media_mensal', align: 'end' },
  { title: 'Estoque', key: 'estoque', align: 'end' },
  { title: 'Já pedido', key: 'em_pedido', align: 'end' },
  { title: 'Quantidade a comprar', key: 'quantidade', sortable: false, width: 170 },
  { title: '', key: 'acoes', sortable: false, align: 'end' },
];

const previsaoFiltrada = computed(() => previsao.value.filter((i) =>
  (!somenteComprar.value || ['pedir_agora', 'planejar'].includes(i.situacao))
  && (!filtroSituacao.value || i.situacao === filtroSituacao.value)));

const resumoSituacoes = computed(() => ['pedir_agora', 'planejar', 'em_pedido', 'ok'].map((s) => ({
  valor: s, ...SITUACOES[s], total: previsao.value.filter((i) => i.situacao === s).length,
})));

// Itens com quantidade a comprar (já com os ajustes feitos na tela)
const itensParaExportar = computed(() => previsao.value.filter((i) => Number(ajustes.value[i.chave]) > 0));

const carregarPrevisao = async () => {
  carregandoPrevisao.value = true;
  try {
    const resposta = await api.get('/compras/previsao', { params: filtros.value });
    parametros.value = resposta.data.parametros;
    previsao.value = resposta.data.itens.map((i) => ({ ...i, chave: `${i.categoria}-${i.id}` }));
    ajustes.value = Object.fromEntries(previsao.value.map((i) => [i.chave, i.sugerido]));
    filtroSituacao.value = null;
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao calcular a previsão.'), 'error');
  } finally {
    carregandoPrevisao.value = false;
  }
};

const exportar = (formato) => {
  const p = parametros.value;
  const conteudo = {
    titulo: 'Previsão de Compras',
    subtitulo: `Base: consumo dos últimos ${p.meses_base} meses   |   Compra para ${p.meses_cobrir} meses   |   Margem de segurança: ${p.margem}%`,
    nomeArquivo: `labstock-previsao-compras-${new Date().toISOString().slice(0, 10)}`,
    tabelas: [{
      titulo: 'Itens a comprar',
      colunas: ['CATMAT', 'Item', 'Tipo', 'Quantidade', 'Unidade', 'Média mensal', 'Estoque atual', 'Justificativa'],
      linhas: itensParaExportar.value.map((i) => [
        i.catmat || '-', i.nome, i.categoria === 'reagentes' ? 'Reagente' : 'Vidraria',
        // Quantidade na unidade base do CATMAT (como vai para a licitação); média e estoque legíveis
        Number(ajustes.value[i.chave] || 0).toLocaleString('pt-BR', { maximumFractionDigits: 6 }), i.unidade,
        `${qtd(i.media_mensal, i.unidade)}/mês`, qtd(i.estoque, i.unidade), i.justificativa,
      ]),
    }],
  };
  formato === 'pdf' ? baixarPdf(conteudo) : baixarCsv(conteudo);
};

// ---------------- Pedidos ----------------
const pedidos = ref([]);
const carregandoPedidos = ref(false);
const abaPedidos = ref('andamento');
const salvando = ref(false);

const headersPedidos = [
  { title: 'Data', key: 'data_pedido' },
  { title: 'Item', key: 'item', sortable: false },
  { title: 'Quantidade', key: 'quantidade', align: 'end' },
  { title: 'Andamento', key: 'status' },
  { title: 'Observação', key: 'observacao', sortable: false },
  { title: 'Pedido por', key: 'usuario', sortable: false },
  { title: '', key: 'acoes', sortable: false, align: 'end' },
];

const pedidosAndamento = computed(() => pedidos.value.filter((p) => EM_ANDAMENTO.includes(p.status)));
const pedidosHistorico = computed(() => pedidos.value.filter((p) => !EM_ANDAMENTO.includes(p.status)));

const nomeItem = (p) => p.reagente?.nome || p.vidraria?.nome || '-';
const unidadePedido = (p) => (p.categoria === 'reagentes' ? p.reagente?.unidade_medida?.sigla || '' : 'un');
const atrasado = (p) => EM_ANDAMENTO.includes(p.status) && p.previsao_entrega && p.previsao_entrega < new Date().toISOString().slice(0, 10);

const carregarPedidos = async () => {
  carregandoPedidos.value = true;
  try {
    pedidos.value = (await api.get('/pedidoscompra')).data;
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao carregar pedidos.'), 'error');
  } finally {
    carregandoPedidos.value = false;
  }
};

// Lista de itens para o novo pedido
const reagentes = ref([]);
const vidrarias = ref([]);
const opcoesItens = computed(() => [
  ...reagentes.value.map((r) => ({ chave: `reagentes-${r.idreagente}`, titulo: `${r.nome} (reagente)`, unidade: r.unidade_medida?.sigla || '', categoria: 'reagentes', id: r.idreagente })),
  ...vidrarias.value.map((v) => ({ chave: `vidrarias-${v.idvidraria}`, titulo: `${v.nome} (vidraria)`, unidade: 'un', categoria: 'vidrarias', id: v.idvidraria })),
]);

const carregarItens = async () => {
  try {
    const [r, v] = await Promise.all([api.get('/reagentes'), api.get('/vidrarias')]);
    reagentes.value = r.data;
    vidrarias.value = v.data;
  } catch { /* a lista só é necessária no novo pedido */ }
};

const dialogPedido = ref(false);
const formPedido = ref(null);
const novoPedido = ref({});
const itemSelecionado = computed(() => opcoesItens.value.find((o) => o.chave === novoPedido.value.chave));
const regraInteiroVidraria = (v) => itemSelecionado.value?.categoria !== 'vidrarias' || Number.isInteger(Number(v)) || 'Vidraria em unidades inteiras';

// Abre vazio ou já preenchido a partir de uma linha da previsão
const abrirNovoPedido = (linha = null) => {
  novoPedido.value = {
    chave: linha?.chave ?? null,
    quantidade: linha ? ajustes.value[linha.chave] : null,
    previsao_entrega: '',
    observacao: linha ? `Previsão de compras: ${linha.justificativa}`.slice(0, 255) : '',
  };
  dialogPedido.value = true;
  nextTick(() => formPedido.value?.resetValidation());
};

const salvarPedido = async () => {
  const { valid } = await formPedido.value.validate();
  if (!valid) return;

  const item = itemSelecionado.value;
  salvando.value = true;
  try {
    await api.post('/pedidoscompra', {
      [item.categoria === 'reagentes' ? 'idreagente' : 'idvidraria']: item.id,
      quantidade: novoPedido.value.quantidade,
      previsao_entrega: novoPedido.value.previsao_entrega || null,
      observacao: novoPedido.value.observacao || null,
    });
    avisar('Pedido criado. Acompanhe na aba Pedidos.');
    dialogPedido.value = false;
    await carregarPedidos();
    if (previsao.value.length) carregarPrevisao(); // atualiza "já pedido"
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao criar pedido.'), 'error');
  } finally {
    salvando.value = false;
  }
};

const mudarStatus = async (pedido, status) => {
  if (status === 'cancelado' && !confirm(`Cancelar o pedido de ${nomeItem(pedido)}?`)) return;
  try {
    await api.put(`/pedidoscompra/${pedido.idpedido}`, { status });
    avisar(`Pedido atualizado: ${STATUS[status].texto}.`);
    carregarPedidos();
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao atualizar pedido.'), 'error');
  }
};

const dialogEdicao = ref(false);
const pedidoEdicao = ref(null);
const edicao = ref({});
const abrirEdicao = (pedido) => {
  pedidoEdicao.value = pedido;
  edicao.value = { previsao_entrega: pedido.previsao_entrega || '', observacao: pedido.observacao || '' };
  dialogEdicao.value = true;
};
const salvarEdicao = async () => {
  salvando.value = true;
  try {
    await api.put(`/pedidoscompra/${pedidoEdicao.value.idpedido}`, {
      previsao_entrega: edicao.value.previsao_entrega || null,
      observacao: edicao.value.observacao || null,
    });
    avisar('Pedido atualizado.');
    dialogEdicao.value = false;
    carregarPedidos();
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao atualizar pedido.'), 'error');
  } finally {
    salvando.value = false;
  }
};

const dialogReceber = ref(false);
const formReceber = ref(null);
const pedidoReceber = ref(null);
const recebimento = ref({});
const abrirRecebimento = (pedido) => {
  pedidoReceber.value = pedido;
  recebimento.value = { quantidade: Number(pedido.quantidade), lote: '', data_validade: '' };
  dialogReceber.value = true;
  nextTick(() => formReceber.value?.resetValidation());
};
const confirmarRecebimento = async () => {
  const { valid } = await formReceber.value.validate();
  if (!valid) return;

  salvando.value = true;
  try {
    await api.post(`/pedidoscompra/${pedidoReceber.value.idpedido}/receber`, recebimento.value);
    avisar(`${nomeItem(pedidoReceber.value)} recebido e adicionado ao estoque.`);
    dialogReceber.value = false;
    carregarPedidos();
    if (previsao.value.length) carregarPrevisao();
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao registrar recebimento.'), 'error');
  } finally {
    salvando.value = false;
  }
};

onMounted(async () => {
  carregarPedidos();
  carregarItens();
  // Usa a margem configurada pelo admin assim que as configurações chegarem
  if (!config.carregado) await config.carregar();
  filtros.value.margem = config.config.margem_seguranca_percentual;
  carregarPrevisao();
});
</script>
