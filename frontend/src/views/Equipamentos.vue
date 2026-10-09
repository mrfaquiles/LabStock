<template>
  <v-container fluid class="pa-6">
    <!-- Cabeçalho e Botões de Ação -->
    <CabecalhoPagina titulo="Equipamentos" subtitulo="Controle de balanças, centrífugas, microscópios e maquinário por unidade e laboratório" icone="mdi-tools">
      <v-btn v-if="auth.podeEditar" color="primary" class="text-none text-white" prepend-icon="mdi-plus" @click="abrirModalCadastro">
        Cadastrar Equipamento
      </v-btn>
      <v-btn color="grey-darken-2" variant="outlined" class="text-none" prepend-icon="mdi-file-pdf-box" @click="emitirRelatorio">
        Emitir Relatório
      </v-btn>
      <v-btn color="blue-darken-2" variant="tonal" class="text-none" prepend-icon="mdi-sync" @click="carregarTudo">
        Atualizar
      </v-btn>
    </CabecalhoPagina>

    <ModalConfirmacao
      v-model="dialogExcluir"
      :nomeItem="itemParaExcluir?.nome"
      @confirm="deletarItemConfirmado"
    />

    <v-snackbar v-model="snackbar.ativo" :color="snackbar.cor" timeout="4000" location="top right">
      {{ snackbar.texto }}
    </v-snackbar>

    <!-- Equipamentos cadastrados antes do controle por local -->
    <v-alert v-if="semLocal.length && auth.podeEditar" type="warning" variant="tonal" class="mb-4" icon="mdi-map-marker-question-outline">
      {{ semLocal.length }} equipamento(s) com unidades <strong>sem local definido</strong>.
      Use <v-icon size="small">mdi-swap-horizontal-bold</v-icon> <strong>Movimentar → Transferir</strong> para colocá-los no laboratório certo.
    </v-alert>
    <v-alert v-if="locaisCarregados && !laboratorios.length && auth.isAdmin" type="info" variant="tonal" class="mb-4">
      Nenhum laboratório cadastrado. Cadastre as unidades e os laboratórios em
      <router-link to="/configuracoes" class="font-weight-bold">Configurações</router-link> para poder movimentar os equipamentos.
    </v-alert>

    <!-- Tabela de Equipamentos -->
    <v-card class="elevation-1 rounded-lg">
      <div class="d-flex flex-wrap align-center ga-3 pa-4">
        <v-text-field
          v-model="search"
          append-inner-icon="mdi-magnify"
          label="Pesquisar equipamento..."
          single-line
          hide-details
          density="compact"
          variant="outlined"
          style="max-width: 300px; min-width: 200px;"
        ></v-text-field>
        <v-select
          v-model="filtroUnidade"
          :items="[{ idunidade: null, nome: 'Todas as unidades' }, ...unidades]"
          item-title="nome"
          item-value="idunidade"
          prepend-inner-icon="mdi-domain"
          hide-details
          density="compact"
          variant="outlined"
          style="max-width: 260px; min-width: 200px;"
        ></v-select>
      </div>

      <v-data-table
        :headers="headers"
        :items="equipamentosFiltrados"
        :search="search"
        :loading="carregando"
        class="pa-2"
        no-data-text="Nenhum equipamento encontrado"
      >
        <!-- Nome com Menu Flutuante da descrição (parágrafos respeitados) -->
        <template v-slot:[`item.nome`]="{ item }">
          <div class="d-flex align-center">
            <span class="me-2">{{ item.nome }}</span>
            <v-menu v-if="item.descricao" open-on-hover location="top" :close-on-content-click="false">
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" size="small" color="grey-darken-1" class="cursor-pointer">mdi-help-circle-outline</v-icon>
              </template>
              <v-card class="pa-3 elevation-4 rounded-lg" color="blue-lighten-5" style="max-width: 350px; max-height: 200px; overflow-y: auto; border: 1px solid #b0bec5;">
                <p class="text-body-2 mb-0 text-blue-grey-darken-4" style="white-space: pre-wrap; line-height: 1.4;">{{ item.descricao }}</p>
              </v-card>
            </v-menu>
          </div>
        </template>

        <!-- Status de Funcionamento -->
        <template v-slot:[`item.status`]="{ item }">
          <v-chip :color="corStatus(item.status)" size="small" variant="tonal">{{ item.status }}</v-chip>
        </template>

        <!-- Quantidade (na unidade filtrada ou total) -->
        <template v-slot:[`item.quantidade`]="{ item }">
          <strong>{{ quantidadeExibida(item) }}</strong>
          <span v-if="filtroUnidade" class="text-caption text-medium-emphasis"> de {{ item.quantidade }}</span>
        </template>

        <!-- Onde está: um chip por laboratório -->
        <template v-slot:[`item.locais`]="{ item }">
          <div class="d-flex flex-wrap ga-1 py-1">
            <v-chip
              v-for="local in item.locais"
              :key="local.id"
              size="small"
              variant="tonal"
              :color="local.laboratorio ? 'primary' : 'warning'"
              :prepend-icon="local.laboratorio ? 'mdi-map-marker' : 'mdi-map-marker-question-outline'"
            >
              {{ nomeLocal(local.laboratorio) }}: {{ local.quantidade }}
            </v-chip>
            <span v-if="!item.locais.length" class="text-caption text-grey">Nenhuma unidade em estoque</span>
          </div>
          <div v-if="item.localizacao" class="text-caption text-medium-emphasis">{{ item.localizacao }}</div>
        </template>

        <!-- Ações -->
        <template v-slot:[`item.acoes`]="{ item }">
          <div class="d-flex justify-end">
            <v-tooltip v-if="auth.podeEditar" text="Movimentar (transferir, entrada, baixa)" location="top">
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" size="small" class="me-2" color="deep-purple" @click="abrirMovimentacao(item)">mdi-swap-horizontal-bold</v-icon>
              </template>
            </v-tooltip>
            <v-tooltip text="Histórico" location="top">
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" size="small" class="me-2" color="blue-grey" @click="abrirHistorico(item)">mdi-history</v-icon>
              </template>
            </v-tooltip>
            <v-icon v-if="auth.podeEditar" size="small" class="me-2" color="primary" @click="editar(item)">mdi-pencil</v-icon>
            <v-icon v-if="auth.podeEditar" size="small" color="error" @click="excluir(item)">mdi-delete</v-icon>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <!-- ============ Cadastro / Edição ============ -->
    <v-dialog v-model="dialog" max-width="700px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">{{ form.idequipamento ? 'Editar Equipamento' : 'Novo Equipamento' }}</span>
          <v-btn icon variant="text" @click="dialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider class="mb-4"></v-divider>

        <v-card-text>
          <v-form ref="formRef">
            <v-row>
              <v-col cols="12" md="8">
                <v-text-field v-model="form.nome" label="Nome do Equipamento *" variant="outlined" density="comfortable" placeholder="Ex.: Microscópio Óptico Binocular" :rules="[regras.obrigatorio]"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.patrimonio" label="Nº de Patrimônio" variant="outlined" density="comfortable" placeholder="Ex.: PAT-9921"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.catmat" label="Código CATMAT *" variant="outlined" density="comfortable" placeholder="Ex.: 123456" :rules="[regras.obrigatorio]"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select v-model="form.status" :items="['Operacional', 'Em Manutenção', 'Inativo']" label="Status de Operação *" variant="outlined" density="comfortable"></v-select>
              </v-col>

              <!-- Quantidade e local só no cadastro; depois, pela movimentação -->
              <template v-if="!form.idequipamento">
                <v-col cols="12" md="4">
                  <v-text-field v-model.number="form.quantidade" label="Quantidade *" type="number" min="1" step="1" variant="outlined" density="comfortable" :rules="[regras.inteiroPositivo]" @keydown="bloquearSinais"></v-text-field>
                </v-col>
                <v-col cols="12" md="8">
                  <v-select
                    v-model="form.idlaboratorio"
                    :items="opcoesLaboratorios"
                    item-title="titulo"
                    item-value="idlaboratorio"
                    label="Onde fica (unidade › laboratório) *"
                    variant="outlined"
                    density="comfortable"
                    no-data-text="Cadastre os laboratórios em Configurações"
                    :rules="laboratorios.length ? [regras.obrigatorio] : []"
                  ></v-select>
                </v-col>
              </template>
              <v-col v-else cols="12">
                <v-alert type="info" variant="tonal" density="compact">
                  Quantidade: <strong>{{ form.quantidade }}</strong>. Para mudar de lugar, registrar entrada ou dar baixa, use
                  <v-icon size="small">mdi-swap-horizontal-bold</v-icon> <strong>Movimentar</strong>. Assim o histórico fica completo.
                </v-alert>
              </v-col>

              <v-col cols="12">
                <v-text-field v-model="form.localizacao" label="Observação de localização" variant="outlined" density="comfortable" placeholder="Ex.: Armário 2, bancada 4"></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.descricao" label="Descrição" variant="outlined" density="comfortable" rows="2" placeholder="Detalhes adicionais..."></v-textarea>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" variant="flat" class="text-none px-6 text-white" :loading="salvando" @click="salvar">Salvar Equipamento</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============ Movimentar ============ -->
    <v-dialog v-model="dialogMov" max-width="640px" persistent>
      <v-card v-if="equipMov" class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">Movimentar Equipamento</span>
          <v-btn icon variant="text" @click="dialogMov = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-card-subtitle class="pb-2">{{ equipMov.nome }}{{ equipMov.patrimonio ? ` — patrimônio ${equipMov.patrimonio}` : '' }}</v-card-subtitle>
        <v-divider class="mb-4"></v-divider>

        <v-card-text>
          <p class="text-caption text-medium-emphasis mb-1">Onde está agora</p>
          <div class="d-flex flex-wrap ga-1 mb-4">
            <v-chip v-for="local in equipMov.locais" :key="local.id" size="small" variant="tonal" :color="local.laboratorio ? 'primary' : 'warning'">
              {{ nomeLocal(local.laboratorio) }}: {{ local.quantidade }}
            </v-chip>
            <span v-if="!equipMov.locais.length" class="text-caption text-grey">Nenhuma unidade em estoque</span>
          </div>

          <v-btn-toggle v-model="mov.tipo" mandatory color="primary" variant="outlined" density="comfortable" divided class="mb-4 flex-wrap">
            <v-btn value="transferir" class="text-none" prepend-icon="mdi-swap-horizontal" :disabled="!equipMov.locais.length">Transferir</v-btn>
            <v-btn value="entrada" class="text-none" prepend-icon="mdi-plus-box-outline">Registrar entrada</v-btn>
            <v-btn value="baixa" class="text-none" prepend-icon="mdi-minus-box-outline" :disabled="!equipMov.locais.length">Dar baixa</v-btn>
          </v-btn-toggle>

          <v-form ref="formMov">
            <v-row dense>
              <v-col v-if="mov.tipo !== 'entrada'" cols="12" :md="mov.tipo === 'transferir' ? 6 : 12">
                <v-select
                  v-model="mov.origem"
                  :items="opcoesOrigem"
                  item-title="titulo"
                  item-value="valor"
                  :label="mov.tipo === 'baixa' ? 'Sai de *' : 'De (origem) *'"
                  variant="outlined"
                  density="comfortable"
                  :rules="[v => v !== undefined || 'Campo obrigatório']"
                ></v-select>
              </v-col>
              <v-col v-if="mov.tipo !== 'baixa'" cols="12" :md="mov.tipo === 'transferir' ? 6 : 12">
                <v-select
                  v-model="mov.destino"
                  :items="opcoesDestino"
                  item-title="titulo"
                  item-value="idlaboratorio"
                  :label="mov.tipo === 'entrada' ? 'Fica em *' : 'Para (destino) *'"
                  variant="outlined"
                  density="comfortable"
                  no-data-text="Cadastre os laboratórios em Configurações"
                  :rules="[regras.obrigatorio]"
                ></v-select>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field
                  v-model.number="mov.quantidade"
                  label="Quantidade *"
                  type="number"
                  min="1"
                  step="1"
                  variant="outlined"
                  density="comfortable"
                  :hint="mov.tipo !== 'entrada' && maximoOrigem !== null ? `Disponível na origem: ${maximoOrigem}` : ''"
                  persistent-hint
                  :rules="[regras.inteiroPositivo, regraMaximoOrigem]"
                  @keydown="bloquearSinais"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="mov.observacao"
                  :label="mov.tipo === 'baixa' ? 'Motivo da baixa *' : 'Observação'"
                  variant="outlined"
                  density="comfortable"
                  :placeholder="placeholderObservacao"
                  :rules="mov.tipo === 'baixa' ? [regras.obrigatorio, v => (v || '').trim().length >= 5 || 'Mínimo de 5 caracteres'] : []"
                ></v-text-field>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialogMov = false">Cancelar</v-btn>
          <v-btn :color="mov.tipo === 'baixa' ? 'error' : 'primary'" variant="flat" class="text-none px-6" :loading="salvando" @click="salvarMovimentacao">
            {{ { transferir: 'Transferir', entrada: 'Registrar Entrada', baixa: 'Dar Baixa' }[mov.tipo] }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- ============ Histórico ============ -->
    <v-dialog v-model="dialogHist" max-width="720px" scrollable>
      <v-card v-if="equipHist" class="rounded-lg">
        <v-card-title class="d-flex justify-space-between align-center pa-4">
          <div>
            <div class="text-h6 font-weight-bold">Histórico</div>
            <div class="text-body-2 text-medium-emphasis">{{ equipHist.nome }}{{ equipHist.patrimonio ? ` — patrimônio ${equipHist.patrimonio}` : '' }}</div>
          </div>
          <v-btn icon variant="text" @click="dialogHist = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider></v-divider>
        <v-card-text style="max-height: 70vh;">
          <div v-if="carregandoHist" class="text-center pa-6"><v-progress-circular indeterminate color="primary"></v-progress-circular></div>
          <p v-else-if="!historico.length" class="text-center text-medium-emphasis pa-6">Nenhum registro.</p>

          <v-timeline v-else side="end" density="compact" align="start" truncate-line="both">
            <v-timeline-item
              v-for="(evento, i) in historico"
              :key="i"
              :dot-color="EVENTOS[evento.tipo].cor"
              :icon="EVENTOS[evento.tipo].icone"
              size="small"
            >
              <div class="mb-1">
                <strong>{{ EVENTOS[evento.tipo].titulo }}</strong>
                <span class="text-caption text-medium-emphasis ms-2">
                  {{ formatarDataHora(evento.data) }}{{ evento.usuario ? ` · ${evento.usuario}` : '' }}
                </span>
              </div>

              <!-- Edição: campo, antes → depois -->
              <div v-if="evento.tipo === 'alterado'" class="text-body-2">
                <div v-for="(valor, campo) in evento.alteracoes" :key="campo">
                  <span class="text-medium-emphasis">{{ CAMPOS[campo] || campo }}:</span>
                  <span class="text-decoration-line-through text-error mx-1">{{ formatarValor(campo, valor.antes) }}</span>
                  <v-icon size="x-small">mdi-arrow-right</v-icon>
                  <span class="text-success font-weight-medium ms-1">{{ formatarValor(campo, valor.depois) }}</span>
                </div>
              </div>

              <div v-else-if="evento.tipo === 'transferencia'" class="text-body-2">
                {{ evento.quantidade }} un: {{ evento.origem }} <v-icon size="x-small">mdi-arrow-right</v-icon> <strong>{{ evento.destino }}</strong>
              </div>
              <div v-else-if="evento.tipo === 'entrada'" class="text-body-2">+{{ evento.quantidade }} un em <strong>{{ evento.destino }}</strong></div>
              <div v-else-if="evento.tipo === 'baixa'" class="text-body-2">−{{ evento.quantidade }} un de <strong>{{ evento.origem }}</strong></div>
              <div v-else-if="evento.tipo === 'criado'" class="text-body-2 text-medium-emphasis">Equipamento cadastrado no sistema.</div>

              <div v-if="evento.observacao" class="text-caption text-medium-emphasis mt-1">
                <v-icon size="x-small">mdi-comment-text-outline</v-icon> {{ evento.observacao }}
              </div>
            </v-timeline-item>
          </v-timeline>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import api, { mensagemErro } from '../plugins/axios';
import ModalConfirmacao from '../components/ModalConfirmacao.vue';
import CabecalhoPagina from '../components/CabecalhoPagina.vue';
import { baixarPdf } from '../utils/exportarRelatorio';
import { useAuthStore } from '../stores/authStore';

const auth = useAuthStore();

const EVENTOS = {
  criado: { titulo: 'Cadastro', cor: 'blue-grey', icone: 'mdi-plus' },
  alterado: { titulo: 'Edição do cadastro', cor: 'primary', icone: 'mdi-pencil' },
  excluido: { titulo: 'Excluído', cor: 'error', icone: 'mdi-delete' },
  transferencia: { titulo: 'Transferência', cor: 'deep-purple', icone: 'mdi-swap-horizontal' },
  entrada: { titulo: 'Entrada', cor: 'success', icone: 'mdi-plus-box' },
  baixa: { titulo: 'Baixa', cor: 'error', icone: 'mdi-minus-box' },
};
const CAMPOS = {
  nome: 'Nome', descricao: 'Descrição', catmat: 'CATMAT', patrimonio: 'Nº Patrimônio',
  status: 'Status', localizacao: 'Observação de localização', ativo: 'Ativo',
};

const headers = [
  { title: 'Nome do Equipamento', key: 'nome', align: 'start' },
  { title: 'Nº Patrimônio', key: 'patrimonio' },
  { title: 'CATMAT', key: 'catmat' },
  { title: 'Status', key: 'status' },
  { title: 'Qtd.', key: 'quantidade', align: 'center' },
  { title: 'Onde está', key: 'locais', sortable: false },
  { title: 'Ações', key: 'acoes', sortable: false, align: 'end' },
];

const regras = {
  obrigatorio: (v) => (v !== null && v !== undefined && String(v).trim() !== '') || 'Campo obrigatório',
  inteiroPositivo: (v) => (Number.isInteger(Number(v)) && Number(v) >= 1) || 'Informe um número inteiro maior que zero',
};

const snackbar = ref({ ativo: false, texto: '', cor: 'success' });
const avisar = (texto, cor = 'success') => { snackbar.value = { ativo: true, texto, cor }; };

const bloquearSinais = (e) => {
  if (['-', '+', 'e', 'E', ',', '.'].includes(e.key)) e.preventDefault();
};
const formatarDataHora = (valor) => (valor ? new Date(valor).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : '');
const formatarValor = (campo, valor) => {
  if (valor === null || valor === undefined || valor === '') return '(vazio)';
  if (campo === 'ativo') return Number(valor) ? 'Sim' : 'Não';
  return valor;
};
const corStatus = (status) => ({ Operacional: 'success', 'Em Manutenção': 'warning', Inativo: 'grey' }[status] || 'grey');

// "Unidade 1 › Lab. de Química"
const nomeLocal = (lab) => (lab ? `${lab.unidade ? `${lab.unidade.nome} › ` : ''}${lab.nome}` : 'Sem local definido');

// ---------- Dados ----------
const equipamentos = ref([]);
const laboratorios = ref([]);
const unidades = ref([]);
const carregando = ref(false);
const locaisCarregados = ref(false);
const search = ref('');
const filtroUnidade = ref(null);

const carregarEquipamentos = async () => {
  carregando.value = true;
  try {
    equipamentos.value = (await api.get('/equipamentos')).data;
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao carregar equipamentos.'), 'error');
  } finally {
    carregando.value = false;
  }
};

const carregarLocais = async () => {
  try {
    const [labs, sedes] = await Promise.all([api.get('/laboratorios'), api.get('/unidades')]);
    laboratorios.value = labs.data;
    unidades.value = sedes.data;
  } catch { /* sem laboratórios a tela continua funcionando */ }
  locaisCarregados.value = true;
};

const carregarTudo = () => {
  carregarEquipamentos();
  carregarLocais();
};

onMounted(carregarTudo);

const opcoesLaboratorios = computed(() => laboratorios.value
  .map((l) => ({ idlaboratorio: l.idlaboratorio, titulo: nomeLocal(l) }))
  .sort((a, b) => a.titulo.localeCompare(b.titulo)));

const locaisNaUnidade = (item) => item.locais.filter((l) => !filtroUnidade.value || l.laboratorio?.idunidade === filtroUnidade.value);

const equipamentosFiltrados = computed(() => (filtroUnidade.value
  ? equipamentos.value.filter((e) => locaisNaUnidade(e).length)
  : equipamentos.value));

const quantidadeExibida = (item) => locaisNaUnidade(item).reduce((s, l) => s + l.quantidade, 0);

const semLocal = computed(() => equipamentos.value.filter((e) => e.locais.some((l) => !l.laboratorio)));

// ---------- Cadastro / Edição ----------
const dialog = ref(false);
const salvando = ref(false);
const formRef = ref(null);
const form = ref({});
const formVazio = () => ({
  idequipamento: null, nome: '', patrimonio: '', catmat: '', status: 'Operacional',
  quantidade: 1, idlaboratorio: laboratorios.value.length === 1 ? laboratorios.value[0].idlaboratorio : null,
  localizacao: '', descricao: '',
});

const abrirModalCadastro = () => {
  form.value = formVazio();
  dialog.value = true;
  nextTick(() => formRef.value?.resetValidation());
};

const editar = (item) => {
  form.value = { ...item };
  dialog.value = true;
};

const salvar = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  const campos = ['nome', 'patrimonio', 'catmat', 'status', 'localizacao', 'descricao'];
  const dados = Object.fromEntries(campos.map((c) => [c, form.value[c] || null]));
  dados.nome = form.value.nome;
  dados.catmat = form.value.catmat;

  salvando.value = true;
  try {
    if (form.value.idequipamento) {
      await api.put(`/equipamentos/${form.value.idequipamento}`, dados);
      avisar('Equipamento atualizado. A alteração foi registrada no histórico.');
    } else {
      await api.post('/equipamentos', { ...dados, quantidade: form.value.quantidade, idlaboratorio: form.value.idlaboratorio || null });
      avisar('Equipamento cadastrado com sucesso.');
    }
    dialog.value = false;
    carregarEquipamentos();
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao salvar equipamento.'), 'error');
  } finally {
    salvando.value = false;
  }
};

// ---------- Movimentar ----------
const dialogMov = ref(false);
const formMov = ref(null);
const equipMov = ref(null);
const mov = ref({});

// Origem: locais onde o equipamento está ("sem local" tem valor null)
const opcoesOrigem = computed(() => (equipMov.value?.locais || []).map((l) => ({
  valor: l.idlaboratorio, titulo: `${nomeLocal(l.laboratorio)} (${l.quantidade} un)`, quantidade: l.quantidade,
})));
const opcoesDestino = computed(() => opcoesLaboratorios.value.filter((l) => mov.value.tipo !== 'transferir' || l.idlaboratorio !== mov.value.origem));
const maximoOrigem = computed(() => opcoesOrigem.value.find((o) => o.valor === mov.value.origem)?.quantidade ?? null);
const regraMaximoOrigem = (v) => mov.value.tipo === 'entrada' || maximoOrigem.value === null || Number(v) <= maximoOrigem.value
  || `Há apenas ${maximoOrigem.value} un na origem`;
const placeholderObservacao = computed(() => ({
  transferir: 'Ex.: Aula prática de microbiologia na Unidade 2',
  entrada: 'Ex.: Doação / compra recebida',
  baixa: 'Ex.: Lente quebrada, sem conserto',
}[mov.value.tipo]));

const abrirMovimentacao = (item) => {
  equipMov.value = item;
  const temLocal = item.locais.length > 0;
  mov.value = {
    tipo: temLocal ? 'transferir' : 'entrada',
    // Sugere a origem com mais unidades
    origem: temLocal ? [...item.locais].sort((a, b) => b.quantidade - a.quantidade)[0].idlaboratorio : undefined,
    destino: null,
    quantidade: 1,
    observacao: '',
  };
  dialogMov.value = true;
  nextTick(() => formMov.value?.resetValidation());
};

// Ao trocar o tipo, o destino escolhido pode não valer mais
watch(() => mov.value.tipo, () => { if (mov.value) mov.value.destino = null; });

const salvarMovimentacao = async () => {
  const { valid } = await formMov.value.validate();
  if (!valid) return;

  const { tipo, origem, destino, quantidade, observacao } = mov.value;
  const id = equipMov.value.idequipamento;
  salvando.value = true;
  try {
    if (tipo === 'transferir') {
      await api.post(`/equipamentos/${id}/transferir`, { idorigem: origem, iddestino: destino, quantidade, observacao: observacao || null });
      avisar(`Transferido: ${quantidade} un para ${nomeLocal(laboratorios.value.find((l) => l.idlaboratorio === destino))}.`);
    } else if (tipo === 'entrada') {
      await api.post('/entradaequipamentos', { idequipamento: id, idlaboratorio: destino, quantidade, observacao: observacao || null });
      avisar(`Entrada registrada: +${quantidade} un.`);
    } else {
      await api.post('/saidaequipamentos', { idequipamento: id, idlaboratorio: origem, quantidade, observacao });
      avisar(`Baixa registrada: −${quantidade} un.`);
    }
    dialogMov.value = false;
    carregarEquipamentos();
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao registrar a movimentação.'), 'error');
  } finally {
    salvando.value = false;
  }
};

// ---------- Histórico ----------
const dialogHist = ref(false);
const equipHist = ref(null);
const historico = ref([]);
const carregandoHist = ref(false);

const abrirHistorico = async (item) => {
  equipHist.value = item;
  historico.value = [];
  dialogHist.value = true;
  carregandoHist.value = true;
  try {
    historico.value = (await api.get(`/equipamentos/${item.idequipamento}/historico`)).data;
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao carregar o histórico.'), 'error');
  } finally {
    carregandoHist.value = false;
  }
};

// ---------- Excluir ----------
const dialogExcluir = ref(false);
const itemParaExcluir = ref(null);
const excluir = (item) => {
  itemParaExcluir.value = item;
  dialogExcluir.value = true;
};
const deletarItemConfirmado = async () => {
  if (itemParaExcluir.value) {
    try {
      await api.delete(`/equipamentos/${itemParaExcluir.value.idequipamento}`);
      avisar('Equipamento excluído.');
      carregarEquipamentos();
    } catch (err) {
      // 409: tem movimentações; o ideal é marcar como Inativo
      avisar(mensagemErro(err, 'Erro ao excluir equipamento.'), 'error');
    }
    itemParaExcluir.value = null;
  }
  dialogExcluir.value = false;
};

// ---------- Relatório ----------
// PDF gerado direto no navegador (o antigo window.open era bloqueado como pop-up)
const emitirRelatorio = () => {
  const unidadeFiltrada = unidades.value.find((u) => u.idunidade === filtroUnidade.value);
  baixarPdf({
    titulo: 'Relatório de Equipamentos',
    subtitulo: `Posição em ${new Date().toLocaleDateString('pt-BR')}${unidadeFiltrada ? `   |   ${unidadeFiltrada.nome}` : ''}`,
    nomeArquivo: `labstock-equipamentos-${new Date().toISOString().slice(0, 10)}`,
    tabelas: [{
      colunas: ['Nome do Equipamento', 'Nº Patrimônio', 'CATMAT', 'Status', 'Qtd.', 'Onde está'],
      linhas: equipamentosFiltrados.value.map((e) => [
        e.nome, e.patrimonio || '-', e.catmat || '-', e.status || '-', quantidadeExibida(e),
        locaisNaUnidade(e).map((l) => `${nomeLocal(l.laboratorio)}: ${l.quantidade}`).join('\n') || '-',
      ]),
    }],
  });
};
</script>
