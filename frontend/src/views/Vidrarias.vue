<template>
  <v-container fluid class="pa-6">
    <!-- Cabeçalho e Botões de Ação -->
    <CabecalhoPagina titulo="Vidrarias" subtitulo="Controle de béqueres, provetas, balões e materiais de vidro" icone="mdi-cup">
      <v-btn v-if="auth.podeEditar" color="primary" class="text-none text-white" prepend-icon="mdi-plus" @click="abrirModalCadastro">
        Cadastrar Vidraria
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

    <!-- Aviso de pendências para o administrador -->
    <v-alert
      v-if="auth.isAdmin && pendentes.length"
      type="warning"
      variant="tonal"
      class="mb-4"
      icon="mdi-glass-fragile"
    >
      {{ pendentes.length }} solicitação(ões) de baixa aguardando a sua aprovação.
      <a href="#" class="font-weight-bold" @click.prevent="irParaSolicitacoes">Ver solicitações</a>
    </v-alert>

    <!-- Abas principais: estoque e solicitações de baixa -->
    <v-tabs v-model="abaPrincipal" color="primary" class="mb-4">
      <v-tab value="estoque" class="text-none" prepend-icon="mdi-cup">Estoque</v-tab>
      <v-tab value="solicitacoes" class="text-none" prepend-icon="mdi-glass-fragile">
        Solicitações de baixa
        <v-badge v-if="pendentes.length" :content="pendentes.length" color="warning" inline></v-badge>
      </v-tab>
    </v-tabs>

    <v-window v-model="abaPrincipal">
    <!-- Aba 1: Tabela de Vidrarias -->
    <v-window-item value="estoque">
    <v-card class="elevation-1 rounded-lg">
      <v-data-table
        :headers="headers"
        :items="vidrariaStore.getAllVidrarias"
        :search="search"
        class="pa-2"
      >
        <template v-slot:top>
          <v-toolbar flat class="px-4 bg-white">
            <v-text-field
              v-model="search"
              append-inner-icon="mdi-magnify"
              label="Pesquisar vidraria..."
              single-line
              hide-details
              density="compact"
              variant="outlined"
              style="max-width: 300px;"
            ></v-text-field>
          </v-toolbar>
        </template>

        <!-- Nome com Menu Flutuante da descrição (parágrafos respeitados) -->
        <template v-slot:[`item.nome`]="{ item }">
          <div class="d-flex align-center">
            <span class="me-2">{{ item.nome }}</span>

            <v-menu v-if="item.descricao" open-on-hover location="top" :close-on-content-click="false">
              <template v-slot:activator="{ props }">
                <v-icon
                  v-bind="props"
                  size="small"
                  color="grey-darken-1"
                  class="cursor-pointer"
                >
                  mdi-help-circle-outline
                </v-icon>
              </template>
              <v-card class="pa-3 elevation-4 rounded-lg" color="blue-lighten-5" style="max-width: 350px; max-height: 200px; overflow-y: auto; border: 1px solid #b0bec5;">
                <p class="text-body-2 mb-0 text-blue-grey-darken-4" style="white-space: pre-wrap; line-height: 1.4;">
                  {{ item.descricao }}
                </p>
              </v-card>
            </v-menu>
          </div>
        </template>

        <!-- Quantidade, com aviso de baixas pendentes -->
        <template v-slot:[`item.quantidade`]="{ item }">
          {{ item.quantidade }}
          <v-chip v-if="pendentePorVidraria[item.idvidraria]" size="x-small" color="warning" variant="tonal" class="ms-1">
            -{{ pendentePorVidraria[item.idvidraria] }} pendente
          </v-chip>
        </template>

        <!-- Status / Ativo -->
        <template v-slot:[`item.ativo`]="{ item }">
          <v-chip :color="Number(item.ativo) ? 'success' : 'error'" size="small">
            {{ Number(item.ativo) ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>

        <!-- Ações -->
        <template v-slot:[`item.acoes`]="{ item }">
          <div class="d-flex justify-end">
            <v-tooltip text="Solicitar baixa (quebra, perda...)" location="top">
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" size="small" class="me-2" color="deep-orange-darken-2" :disabled="!(item.quantidade > 0)" @click="abrirBaixa(item)">mdi-glass-fragile</v-icon>
              </template>
            </v-tooltip>
            <v-icon v-if="auth.podeEditar" size="small" class="me-2" color="primary" @click="editar(item)">mdi-pencil</v-icon>
            <v-icon v-if="auth.podeEditar" size="small" color="error" @click="excluir(item)">mdi-delete</v-icon>
          </div>
        </template>
      </v-data-table>
    </v-card>
    </v-window-item>

    <!-- Aba 2: Solicitações de baixa -->
    <v-window-item value="solicitacoes">
    <v-card class="elevation-1 rounded-lg">
      <div class="d-flex flex-wrap align-center justify-space-between ga-2 pa-4">
        <v-btn-toggle v-model="abaSolicitacoes" mandatory color="primary" variant="outlined" density="comfortable" divided>
          <v-btn value="pendente" class="text-none" prepend-icon="mdi-clock-outline">
            Pendentes
            <v-chip v-if="pendentes.length" size="x-small" color="warning" class="ms-2">{{ pendentes.length }}</v-chip>
          </v-btn>
          <v-btn value="historico" class="text-none" prepend-icon="mdi-history">Histórico</v-btn>
        </v-btn-toggle>
        <span class="text-caption text-medium-emphasis">
          {{ auth.isAdmin ? 'Aprove ou recuse as baixas solicitadas.' : 'Acompanhe as baixas que você solicitou.' }}
        </span>
      </div>
      <v-divider></v-divider>

      <v-data-table
        :headers="headersSolicitacoes"
        :items="abaSolicitacoes === 'pendente' ? pendentes : historico"
        :loading="carregandoSolicitacoes"
        density="comfortable"
        class="pa-2"
        :no-data-text="abaSolicitacoes === 'pendente' ? 'Nenhuma solicitação pendente' : 'Nenhuma solicitação decidida'"
      >
        <template v-slot:[`item.data`]="{ item }">{{ formatarData(item.data) }}</template>
        <template v-slot:[`item.vidraria`]="{ item }">{{ item.vidraria?.nome }}</template>
        <template v-slot:[`item.quantidade`]="{ item }">{{ item.quantidade }} un</template>

        <!-- Justificativa com o mesmo menu flutuante das descrições -->
        <template v-slot:[`item.observacao`]="{ item }">
          <div class="d-flex align-center">
            <span class="text-truncate" style="max-width: 220px;">{{ item.motivo ? `${item.motivo}: ` : '' }}{{ item.observacao }}</span>
            <v-menu open-on-hover location="top" :close-on-content-click="false">
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" size="small" color="grey-darken-1" class="cursor-pointer ms-1">mdi-help-circle-outline</v-icon>
              </template>
              <v-card class="pa-3 elevation-4 rounded-lg" color="blue-lighten-5" style="max-width: 350px; max-height: 200px; overflow-y: auto; border: 1px solid #b0bec5;">
                <p class="text-caption font-weight-bold text-blue-grey-darken-4 mb-1">{{ item.motivo || 'Justificativa' }}</p>
                <p class="text-body-2 mb-0 text-blue-grey-darken-4" style="white-space: pre-wrap; line-height: 1.4;">{{ item.observacao }}</p>
                <template v-if="item.motivo_recusa">
                  <p class="text-caption font-weight-bold text-error mt-2 mb-1">Motivo da recusa</p>
                  <p class="text-body-2 mb-0 text-blue-grey-darken-4" style="white-space: pre-wrap; line-height: 1.4;">{{ item.motivo_recusa }}</p>
                </template>
              </v-card>
            </v-menu>
          </div>
        </template>

        <template v-slot:[`item.usuario`]="{ item }">{{ item.usuario?.nome }}</template>

        <template v-slot:[`item.status`]="{ item }">
          <v-chip size="small" variant="tonal" :color="STATUS[item.status].cor" :prepend-icon="STATUS[item.status].icone">
            {{ STATUS[item.status].texto }}
          </v-chip>
          <div v-if="item.aprovador" class="text-caption text-grey-darken-1">
            por {{ item.aprovador.nome }} em {{ formatarData(item.data_decisao?.substring(0, 10)) }}
          </div>
        </template>

        <template v-slot:[`item.acoes`]="{ item }">
          <div class="d-flex justify-end">
            <template v-if="item.status === 'pendente' && auth.isAdmin">
              <v-btn size="small" color="success" variant="tonal" class="text-none me-2" prepend-icon="mdi-check" :loading="decidindo === item.idsaidavidraria" @click="aprovar(item)">
                Aprovar
              </v-btn>
              <v-btn size="small" color="error" variant="tonal" class="text-none me-2" prepend-icon="mdi-close" @click="abrirRecusa(item)">
                Recusar
              </v-btn>
            </template>
            <v-tooltip v-if="podeCancelar(item)" :text="item.status === 'aprovada' ? 'Estornar baixa (devolve ao estoque)' : 'Cancelar solicitação'" location="top">
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" size="small" color="grey-darken-1" @click="cancelar(item)">
                  {{ item.status === 'aprovada' ? 'mdi-undo' : 'mdi-cancel' }}
                </v-icon>
              </template>
            </v-tooltip>
          </div>
        </template>
      </v-data-table>
    </v-card>
    </v-window-item>
    </v-window>

    <!-- Modal de Cadastro / Edição -->
    <v-dialog v-model="dialog" max-width="700px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">{{ form.idvidraria ? 'Editar Vidraria' : 'Nova Vidraria' }}</span>
          <v-btn icon variant="text" @click="dialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-divider class="mb-4"></v-divider>

        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="12">
                <v-text-field v-model="form.nome" label="Nome do Item *" variant="outlined" density="comfortable" placeholder="Ex.: Béquer de 250ml"></v-text-field>
              </v-col>
              <v-col cols="12" md="12">
                <v-textarea v-model="form.descricao" label="Descrição" variant="outlined" density="comfortable" rows="2" placeholder="Ex.: Béquer de vidro borossilicato, forma baixa, graduado. Pode ir à chapa de aquecimento."></v-textarea>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.catmat" label="Código CATMAT *" variant="outlined" density="comfortable" placeholder="Ex.: VID-01"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.quantidade" label="Quantidade (un) *" type="number" min="0" step="1" variant="outlined" density="comfortable" @keydown="bloquearSinais"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-switch v-model="form.ativo" label="Ativo *" color="success" density="comfortable"></v-switch>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" variant="flat" class="text-none px-6 text-white" @click="salvar">Salvar Vidraria</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Solicitação de Baixa -->
    <v-dialog v-model="dialogBaixa" max-width="600px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">{{ baixaDireta ? 'Registrar Baixa' : 'Solicitar Baixa' }}</span>
          <v-btn icon variant="text" @click="dialogBaixa = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-subtitle class="pb-2">
          {{ vidrariaBaixa?.nome }} — em estoque: {{ vidrariaBaixa?.quantidade }} un
        </v-card-subtitle>

        <v-divider class="mb-4"></v-divider>

        <v-card-text>
          <v-form ref="formBaixa">
            <v-row>
              <v-col cols="12" md="5">
                <v-text-field
                  v-model="baixa.quantidade"
                  label="Quantidade *"
                  type="number"
                  min="1"
                  step="1"
                  suffix="un"
                  variant="outlined"
                  density="comfortable"
                  :rules="[regras.obrigatorio, regras.inteiroPositivo, regras.estoque]"
                  @keydown="bloquearSinais"
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="7">
                <v-select
                  v-model="baixa.motivo"
                  :items="MOTIVOS"
                  label="Motivo *"
                  variant="outlined"
                  density="comfortable"
                  :rules="[regras.obrigatorio]"
                ></v-select>
              </v-col>
              <v-col cols="12">
                <v-textarea
                  v-model="baixa.observacao"
                  label="Justificativa da baixa *"
                  variant="outlined"
                  density="comfortable"
                  rows="3"
                  counter="255"
                  maxlength="255"
                  placeholder="Ex.: O béquer caiu da bancada durante a aula prática de Química Geral (Turma B)."
                  :rules="[regras.obrigatorio, regras.justificativa]"
                ></v-textarea>
              </v-col>
            </v-row>
          </v-form>

          <v-alert type="info" variant="tonal" density="compact">
            <template v-if="auth.isAdmin">Como administrador, a baixa é aplicada ao estoque imediatamente.</template>
            <template v-else-if="baixaDireta">A baixa será descontada do estoque imediatamente.</template>
            <template v-else>A baixa só será descontada do estoque depois que um administrador aprovar.</template>
          </v-alert>
        </v-card-text>

        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialogBaixa = false">Cancelar</v-btn>
          <v-btn color="primary" variant="flat" class="text-none px-6 text-white" :loading="salvandoBaixa" @click="salvarBaixa">
            {{ baixaDireta ? 'Registrar Baixa' : 'Enviar Solicitação' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Recusa -->
    <v-dialog v-model="dialogRecusa" max-width="500px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="text-h6 font-weight-bold">Recusar solicitação</v-card-title>
        <v-card-subtitle class="pb-2">
          {{ solicitacaoRecusa?.vidraria?.nome }} — {{ solicitacaoRecusa?.quantidade }} un, solicitado por {{ solicitacaoRecusa?.usuario?.nome }}
        </v-card-subtitle>
        <v-card-text>
          <v-form ref="formRecusa">
            <v-textarea
              v-model="motivoRecusa"
              label="Motivo da recusa *"
              variant="outlined"
              density="comfortable"
              rows="3"
              counter="255"
              maxlength="255"
              placeholder="Ex.: O item foi encontrado inteiro no armário 2."
              :rules="[regras.obrigatorio, v => (v || '').trim().length >= 5 || 'Mínimo de 5 caracteres']"
            ></v-textarea>
          </v-form>
        </v-card-text>
        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialogRecusa = false">Voltar</v-btn>
          <v-btn color="error" variant="flat" class="text-none px-6" :loading="!!decidindo" @click="confirmarRecusa">Recusar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import ModalConfirmacao from '../components/ModalConfirmacao.vue';
import { baixarPdf } from '../utils/exportarRelatorio';
import CabecalhoPagina from '../components/CabecalhoPagina.vue';
import { useVidrariaStore } from '../stores/vidrariaStore.js';
import { useAuthStore } from '../stores/authStore';
import { useConfigStore } from '../stores/configStore';
import api, { mensagemErro } from '../plugins/axios';
import { ref, computed, onMounted, nextTick } from 'vue';

const vidrariaStore = useVidrariaStore();
const auth = useAuthStore();
const config = useConfigStore();
// Baixa aplicada na hora: feita pelo admin ou com a aprovação desligada em Configurações
const baixaDireta = computed(() => auth.isAdmin || !config.config.vidraria_exige_aprovacao);

const MOTIVOS = ['Quebra', 'Perda', 'Defeito', 'Descarte', 'Outro'];
const STATUS = {
  pendente: { texto: 'Pendente', cor: 'warning', icone: 'mdi-clock-outline' },
  aprovada: { texto: 'Aprovada', cor: 'success', icone: 'mdi-check-circle' },
  recusada: { texto: 'Recusada', cor: 'error', icone: 'mdi-close-circle' },
};

const dialog = ref(false);
const dialogExcluir = ref(false);
const itemParaExcluir = ref(null);
const form = ref({ idvidraria: null, nome: '', descricao: '', quantidade: 0, catmat: '', ativo: true });
const search = ref('');
const snackbar = ref({ ativo: false, texto: '', cor: 'success' });
const headers = [
  { title: 'Nome do Item', key: 'nome', align: 'start' },
  { title: 'CATMAT', key: 'catmat' },
  { title: 'Quantidade', key: 'quantidade' },
  { title: 'Ativo', key: 'ativo' },
  { title: 'Ações', key: 'acoes', sortable: false, align: 'end' }
];

// ---------- Solicitações de baixa ----------
const solicitacoes = ref([]);
const carregandoSolicitacoes = ref(false);
const abaPrincipal = ref('estoque');
const abaSolicitacoes = ref('pendente');
const dialogBaixa = ref(false);
const salvandoBaixa = ref(false);
const vidrariaBaixa = ref(null);
const baixa = ref({ quantidade: 1, motivo: null, observacao: '' });
const formBaixa = ref(null);
const dialogRecusa = ref(false);
const solicitacaoRecusa = ref(null);
const motivoRecusa = ref('');
const formRecusa = ref(null);
const decidindo = ref(null);

const headersSolicitacoes = [
  { title: 'Data', key: 'data' },
  { title: 'Vidraria', key: 'vidraria' },
  { title: 'Qtd.', key: 'quantidade' },
  { title: 'Motivo / Justificativa', key: 'observacao', sortable: false },
  { title: 'Solicitante', key: 'usuario' },
  { title: 'Status', key: 'status' },
  { title: 'Ações', key: 'acoes', sortable: false, align: 'end' },
];

const pendentes = computed(() => solicitacoes.value.filter(s => s.status === 'pendente'));
const historico = computed(() => solicitacoes.value.filter(s => s.status !== 'pendente'));
// Quantidade já pedida e ainda não decidida, por vidraria
const pendentePorVidraria = computed(() => pendentes.value.reduce((acc, s) => {
  acc[s.idvidraria] = (acc[s.idvidraria] || 0) + Number(s.quantidade);
  return acc;
}, {}));

const regras = {
  obrigatorio: (v) => (v !== null && v !== undefined && String(v).trim() !== '') || 'Campo obrigatório',
  inteiroPositivo: (v) => (Number.isInteger(Number(v)) && Number(v) >= 1) || 'Informe um número inteiro maior que zero',
  estoque: (v) => !vidrariaBaixa.value || Number(v) <= Number(vidrariaBaixa.value.quantidade)
    || `Há apenas ${vidrariaBaixa.value.quantidade} un em estoque`,
  justificativa: (v) => (v || '').trim().length >= 10 || 'Descreva a justificativa com pelo menos 10 caracteres',
};

const avisar = (texto, cor = 'success') => {
  snackbar.value = { ativo: true, texto, cor };
};

const formatarData = (iso) => {
  if (!iso) return '';
  const [a, m, d] = String(iso).substring(0, 10).split('-');
  return `${d}/${m}/${a}`;
};

// Impede digitar sinal negativo/decimal/exponencial em quantidades inteiras
const bloquearSinais = (e) => {
  if (['-', '+', 'e', 'E', ',', '.'].includes(e.key)) e.preventDefault();
};

const carregarSolicitacoes = async () => {
  carregandoSolicitacoes.value = true;
  try {
    const resposta = await api.get('/saidavidrarias');
    solicitacoes.value = resposta.data;
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao carregar solicitações de baixa.'), 'error');
  } finally {
    carregandoSolicitacoes.value = false;
  }
};

const carregarTudo = () => {
  vidrariaStore.fetchVidrarias();
  carregarSolicitacoes();
};

onMounted(carregarTudo);

const irParaSolicitacoes = () => {
  abaPrincipal.value = 'solicitacoes';
  abaSolicitacoes.value = 'pendente';
};

const abrirBaixa = (item) => {
  vidrariaBaixa.value = item;
  baixa.value = { quantidade: 1, motivo: null, observacao: '' };
  dialogBaixa.value = true;
  nextTick(() => formBaixa.value?.resetValidation());
};

const salvarBaixa = async () => {
  const { valid } = await formBaixa.value.validate();
  if (!valid) return;

  salvandoBaixa.value = true;
  try {
    const resposta = await api.post('/saidavidrarias', {
      idvidraria: vidrariaBaixa.value.idvidraria,
      quantidade: Number(baixa.value.quantidade),
      motivo: baixa.value.motivo,
      observacao: baixa.value.observacao.trim(),
    });
    avisar(resposta.data.status === 'aprovada'
      ? 'Baixa registrada e descontada do estoque.'
      : 'Solicitação enviada. Aguardando aprovação do administrador.');
    dialogBaixa.value = false;
    carregarTudo();
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao enviar solicitação.'), 'error');
  } finally {
    salvandoBaixa.value = false;
  }
};

const aprovar = async (item) => {
  decidindo.value = item.idsaidavidraria;
  try {
    await api.post(`/saidavidrarias/${item.idsaidavidraria}/aprovar`);
    avisar(`Baixa aprovada: ${item.quantidade} un de ${item.vidraria?.nome} descontada(s) do estoque.`);
    carregarTudo();
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao aprovar.'), 'error');
  } finally {
    decidindo.value = null;
  }
};

const abrirRecusa = (item) => {
  solicitacaoRecusa.value = item;
  motivoRecusa.value = '';
  dialogRecusa.value = true;
  nextTick(() => formRecusa.value?.resetValidation());
};

const confirmarRecusa = async () => {
  const { valid } = await formRecusa.value.validate();
  if (!valid) return;

  decidindo.value = solicitacaoRecusa.value.idsaidavidraria;
  try {
    await api.post(`/saidavidrarias/${solicitacaoRecusa.value.idsaidavidraria}/recusar`, {
      motivo_recusa: motivoRecusa.value.trim(),
    });
    avisar('Solicitação recusada. O estoque não foi alterado.');
    dialogRecusa.value = false;
    carregarSolicitacoes();
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao recusar.'), 'error');
  } finally {
    decidindo.value = null;
  }
};

// Pendente: quem pediu ou o admin cancela. Aprovada: só o admin estorna.
const podeCancelar = (item) => {
  if (item.status === 'pendente') return auth.isAdmin || Number(item.idusuario) === Number(auth.usuario?.id);
  return item.status === 'aprovada' && auth.isAdmin;
};

const cancelar = async (item) => {
  const pergunta = item.status === 'aprovada'
    ? `Estornar a baixa de ${item.quantidade} un de ${item.vidraria?.nome}? A quantidade volta ao estoque.`
    : 'Cancelar esta solicitação de baixa?';
  if (!confirm(pergunta)) return;

  try {
    const resposta = await api.delete(`/saidavidrarias/${item.idsaidavidraria}`);
    avisar(resposta.data.message);
    carregarTudo();
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao cancelar.'), 'error');
  }
};

// ---------- Cadastro de vidrarias ----------
const abrirModalCadastro = () => {
    form.value = { idvidraria: null, nome: '', descricao: '', quantidade: 0, catmat: '', ativo: true };
    dialog.value = true;
}

const salvar = async () => {
    // O store devolve false em caso de erro e guarda a mensagem em vidrariaStore.error
    const ok = form.value.idvidraria
      ? await vidrariaStore.updateVidraria(form.value.idvidraria, form.value) // Se tem ID, atualiza (PUT)
      : await vidrariaStore.createVidraria(form.value); // Se não tem ID, cria novo (POST)

    if (!ok) {
      alert(vidrariaStore.error);
      return;
    }
    dialog.value = false;
    vidrariaStore.fetchVidrarias(); // Atualiza a tabela na hora
}

// PDF gerado direto no navegador (o antigo window.open era bloqueado como pop-up)
const emitirRelatorio = () => {
  baixarPdf({
    titulo: 'Relatório de Vidrarias',
    subtitulo: `Posição do estoque em ${new Date().toLocaleDateString('pt-BR')}`,
    nomeArquivo: `labstock-vidrarias-${new Date().toISOString().slice(0, 10)}`,
    tabelas: [{
      colunas: ['Nome do Item', 'CATMAT', 'Quantidade', 'Baixas pendentes', 'Situação'],
      linhas: vidrariaStore.getAllVidrarias.map(v => [
        v.nome,
        v.catmat || '-',
        `${v.quantidade} un`,
        pendentePorVidraria.value[v.idvidraria] ? `${pendentePorVidraria.value[v.idvidraria]} un` : '-',
        Number(v.ativo) ? 'Ativo' : 'Inativo',
      ]),
    }],
  });
};

const editar = (item) => {
  // Copia os dados para o formulário (sem alterar a linha da tabela), com ativo em booleano
  form.value = { ...item, ativo: !!Number(item.ativo) };
  dialog.value = true; // Abre o modal
}

const excluir = (item) => {
  itemParaExcluir.value = item;
  dialogExcluir.value = true;
}

const deletarItemConfirmado = async () => {
  if (itemParaExcluir.value) {
    if (await vidrariaStore.deleteVidraria(itemParaExcluir.value.idvidraria)) {
      vidrariaStore.fetchVidrarias(); // Atualiza a tabela
    } else {
      alert(vidrariaStore.error); // Ex.: vidraria com histórico de baixas
    }
    itemParaExcluir.value = null;
  }
  dialogExcluir.value = false;
}
</script>
