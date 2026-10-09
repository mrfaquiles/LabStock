<template>
  <v-container fluid class="pa-6">
    <CabecalhoPagina titulo="Configurações do Sistema" subtitulo="Dados da instituição, padrões do estoque, laboratórios e unidades de medida" icone="mdi-cog" />

    <ModalConfirmacao v-model="dialogExcluir" :nomeItem="itemParaExcluir?.nome" @confirm="excluirConfirmado" />

    <v-snackbar v-model="snackbar.ativo" :color="snackbar.cor" timeout="4000" location="top right">
      {{ snackbar.texto }}
    </v-snackbar>

    <v-tabs v-model="aba" color="primary" class="mb-4">
      <v-tab value="geral" class="text-none" prepend-icon="mdi-tune-variant">Geral</v-tab>
      <v-tab value="laboratorios" class="text-none" prepend-icon="mdi-home-city-outline">Laboratórios</v-tab>
      <v-tab value="unidades" class="text-none" prepend-icon="mdi-scale-balance">Unidades de medida</v-tab>
    </v-tabs>

    <v-window v-model="aba">
      <!-- ================= Aba Geral ================= -->
      <v-window-item value="geral">
        <v-form ref="formGeral">
          <v-row>
            <v-col cols="12" lg="6">
              <v-card class="elevation-1 rounded-lg pa-4 h-100">
                <v-card-title class="d-flex align-center px-0 pt-0">
                  <v-icon icon="mdi-domain" color="primary" class="me-2"></v-icon>
                  <span class="text-subtitle-1 font-weight-bold">Instituição</span>
                </v-card-title>
                <p class="text-caption text-medium-emphasis mb-4">Aparece no cabeçalho de todos os relatórios em PDF.</p>

                <v-text-field
                  v-model="geral.instituicao_nome"
                  label="Nome da instituição / campus *"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: Instituto Federal Goiano - Campus Rio Verde"
                  :rules="[regras.obrigatorio]"
                ></v-text-field>
                <v-text-field
                  v-model="geral.laboratorio_nome"
                  label="Nome do laboratório / setor *"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: Laboratório de Química Geral"
                  :rules="[regras.obrigatorio]"
                ></v-text-field>
                <v-text-field
                  v-model="geral.responsavel_tecnico"
                  label="Responsável técnico"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: Prof.ª Maria Silva"
                ></v-text-field>
              </v-card>
            </v-col>

            <v-col cols="12" lg="6">
              <v-card class="elevation-1 rounded-lg pa-4 h-100">
                <v-card-title class="d-flex align-center px-0 pt-0">
                  <v-icon icon="mdi-tune" color="primary" class="me-2"></v-icon>
                  <span class="text-subtitle-1 font-weight-bold">Padrões do sistema</span>
                </v-card-title>
                <p class="text-caption text-medium-emphasis mb-4">Regras aplicadas em cadastros, alertas e relatórios.</p>

                <v-row dense>
                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model.number="geral.meses_alerta_padrao"
                      label="Alerta de vencimento padrão *"
                      type="number"
                      min="1"
                      max="36"
                      suffix="meses"
                      variant="outlined"
                      density="comfortable"
                      hint="Sugerido ao cadastrar um reagente (cada um pode ter o seu)"
                      persistent-hint
                      :rules="[regras.inteiroEntre(1, 36)]"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-text-field
                      v-model.number="geral.cobertura_minima_meses"
                      label="Cobertura mínima do estoque *"
                      type="number"
                      min="1"
                      max="24"
                      suffix="meses"
                      variant="outlined"
                      density="comfortable"
                      hint="Abaixo disso, o relatório de gastos marca o item para compra"
                      persistent-hint
                      :rules="[regras.inteiroEntre(1, 24)]"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" sm="6" class="mt-2">
                    <v-text-field
                      v-model.number="geral.sessao_horas"
                      label="Duração do login *"
                      type="number"
                      min="1"
                      max="72"
                      suffix="horas"
                      variant="outlined"
                      density="comfortable"
                      hint="Depois disso o usuário precisa entrar de novo"
                      persistent-hint
                      :rules="[regras.inteiroEntre(1, 72)]"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" class="mt-2">
                    <v-switch
                      v-model="geral.vidraria_exige_aprovacao"
                      color="primary"
                      inset
                      label="Baixa de vidraria exige aprovação do administrador"
                      :hint="geral.vidraria_exige_aprovacao
                        ? 'Ligado: a solicitação fica pendente até o administrador aprovar.'
                        : 'Desligado: a baixa é descontada do estoque assim que é registrada (a justificativa continua obrigatória).'"
                      persistent-hint
                    ></v-switch>
                  </v-col>
                </v-row>
              </v-card>
            </v-col>
          </v-row>

          <div class="d-flex justify-end mt-4">
            <v-btn variant="outlined" color="grey-darken-1" class="text-none me-2" @click="restaurarGeral">Descartar alterações</v-btn>
            <v-btn color="primary" variant="flat" class="text-none text-white px-6" prepend-icon="mdi-content-save" :loading="config.loading" @click="salvarGeral">
              Salvar Configurações
            </v-btn>
          </div>
        </v-form>
      </v-window-item>

      <!-- ================= Aba Laboratórios ================= -->
      <v-window-item value="laboratorios">
        <v-card class="elevation-1 rounded-lg">
          <div class="d-flex flex-wrap align-center justify-space-between ga-2 pa-4">
            <span class="text-body-2 text-medium-emphasis">Locais usados nas movimentações de equipamentos e nas baixas de vidraria.</span>
            <v-btn color="primary" class="text-none text-white" prepend-icon="mdi-plus" @click="abrirCadastro('laboratorio')">
              Novo Laboratório
            </v-btn>
          </div>
          <v-divider></v-divider>
          <v-data-table :headers="headersLaboratorios" :items="laboratorios" :loading="carregando" class="pa-2" no-data-text="Nenhum laboratório cadastrado">
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
            <template v-slot:[`item.acoes`]="{ item }">
              <v-icon size="small" class="me-2" color="primary" @click="abrirEdicao('laboratorio', item)">mdi-pencil</v-icon>
              <v-icon size="small" color="error" @click="pedirExclusao('laboratorio', item)">mdi-delete</v-icon>
            </template>
          </v-data-table>
        </v-card>
      </v-window-item>

      <!-- ================= Aba Unidades de medida ================= -->
      <v-window-item value="unidades">
        <v-card class="elevation-1 rounded-lg">
          <div class="d-flex flex-wrap align-center justify-space-between ga-2 pa-4">
            <span class="text-body-2 text-medium-emphasis">
              Use sempre a unidade base (kg, L, un). Frações são cadastradas em decimal: 500 g = 0,5 kg.
            </span>
            <v-btn color="primary" class="text-none text-white" prepend-icon="mdi-plus" @click="abrirCadastro('unidade')">
              Nova Unidade
            </v-btn>
          </div>
          <v-divider></v-divider>
          <v-data-table :headers="headersUnidades" :items="unidades" :loading="carregando" class="pa-2" no-data-text="Nenhuma unidade cadastrada">
            <template v-slot:[`item.sigla`]="{ item }">
              <v-chip size="small" variant="tonal" color="primary">{{ item.sigla }}</v-chip>
            </template>
            <template v-slot:[`item.acoes`]="{ item }">
              <v-icon size="small" class="me-2" color="primary" @click="abrirEdicao('unidade', item)">mdi-pencil</v-icon>
              <v-icon size="small" color="error" @click="pedirExclusao('unidade', item)">mdi-delete</v-icon>
            </template>
          </v-data-table>
        </v-card>
      </v-window-item>
    </v-window>

    <!-- Cadastro / edição de laboratório ou unidade -->
    <v-dialog v-model="dialog" max-width="560px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">{{ tituloDialog }}</span>
          <v-btn icon variant="text" @click="dialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-card-title>
        <v-divider class="mb-4"></v-divider>
        <v-card-text>
          <v-form ref="formItem">
            <template v-if="tipoItem === 'laboratorio'">
              <v-text-field v-model="item.nome" label="Nome do laboratório *" variant="outlined" density="comfortable" placeholder="Ex.: Lab. de Química Geral (Bloco B, sala 12)" :rules="[regras.obrigatorio]"></v-text-field>
              <v-textarea v-model="item.descricao" label="Descrição" variant="outlined" density="comfortable" rows="3" placeholder="Ex.: Bancadas 1 a 6, capela de exaustão, armário de ácidos..."></v-textarea>
            </template>
            <template v-else>
              <v-row dense>
                <v-col cols="8">
                  <v-text-field v-model="item.nome" label="Nome *" variant="outlined" density="comfortable" placeholder="Ex.: Mililitro" :rules="[regras.obrigatorio]"></v-text-field>
                </v-col>
                <v-col cols="4">
                  <v-text-field v-model="item.sigla" label="Sigla *" variant="outlined" density="comfortable" maxlength="3" placeholder="Ex.: mL" :rules="[regras.obrigatorio]"></v-text-field>
                </v-col>
              </v-row>
            </template>
          </v-form>
        </v-card-text>
        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="primary" variant="flat" class="text-none text-white px-6" :loading="salvando" @click="salvarItem">Salvar</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import api, { mensagemErro } from '../plugins/axios';
import CabecalhoPagina from '../components/CabecalhoPagina.vue';
import ModalConfirmacao from '../components/ModalConfirmacao.vue';
import { useConfigStore } from '../stores/configStore';

const config = useConfigStore();

const aba = ref('geral');
const snackbar = ref({ ativo: false, texto: '', cor: 'success' });
const avisar = (texto, cor = 'success') => { snackbar.value = { ativo: true, texto, cor }; };

const regras = {
  obrigatorio: (v) => (v !== null && v !== undefined && String(v).trim() !== '') || 'Campo obrigatório',
  inteiroEntre: (min, max) => (v) => (Number.isInteger(Number(v)) && Number(v) >= min && Number(v) <= max) || `Informe um valor de ${min} a ${max}`,
};

// ---------- Aba Geral ----------
const formGeral = ref(null);
const geral = ref({ ...config.config });
const restaurarGeral = () => { geral.value = { ...config.config }; };

const salvarGeral = async () => {
  const { valid } = await formGeral.value.validate();
  if (!valid) return;

  if (await config.salvar(geral.value)) {
    geral.value = { ...config.config };
    avisar('Configurações salvas com sucesso.');
  } else {
    avisar(config.error, 'error');
  }
};

// ---------- Laboratórios e unidades ----------
const laboratorios = ref([]);
const unidades = ref([]);
const carregando = ref(false);

const headersLaboratorios = [
  { title: 'Laboratório', key: 'nome' },
  { title: 'Ações', key: 'acoes', sortable: false, align: 'end' },
];
const headersUnidades = [
  { title: 'Nome', key: 'nome' },
  { title: 'Sigla', key: 'sigla' },
  { title: 'Ações', key: 'acoes', sortable: false, align: 'end' },
];

// Endpoint e chave primária de cada tipo de cadastro
const RECURSOS = {
  laboratorio: { url: '/laboratorios', id: 'idlaboratorio', nome: 'laboratório', novo: 'Novo laboratório', salvo: 'Laboratório salvo' },
  unidade: { url: '/unidademedidas', id: 'idunidademedida', nome: 'unidade de medida', novo: 'Nova unidade de medida', salvo: 'Unidade de medida salva' },
};

const carregarListas = async () => {
  carregando.value = true;
  try {
    const [labs, unids] = await Promise.all([api.get('/laboratorios'), api.get('/unidademedidas')]);
    laboratorios.value = labs.data;
    unidades.value = unids.data;
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao carregar os cadastros.'), 'error');
  } finally {
    carregando.value = false;
  }
};

const dialog = ref(false);
const salvando = ref(false);
const formItem = ref(null);
const tipoItem = ref('laboratorio');
const item = ref({});

const tituloDialog = computed(() => {
  const recurso = RECURSOS[tipoItem.value];
  return item.value[recurso.id] ? `Editar ${recurso.nome}` : recurso.novo;
});

const abrirCadastro = (tipo) => {
  tipoItem.value = tipo;
  item.value = tipo === 'laboratorio' ? { nome: '', descricao: '' } : { nome: '', sigla: '' };
  dialog.value = true;
  nextTick(() => formItem.value?.resetValidation());
};

const abrirEdicao = (tipo, registro) => {
  tipoItem.value = tipo;
  item.value = { ...registro };
  dialog.value = true;
};

const salvarItem = async () => {
  const { valid } = await formItem.value.validate();
  if (!valid) return;

  const recurso = RECURSOS[tipoItem.value];
  const id = item.value[recurso.id];
  const dados = tipoItem.value === 'laboratorio'
    ? { nome: item.value.nome, descricao: item.value.descricao || null }
    : { nome: item.value.nome, sigla: item.value.sigla };

  salvando.value = true;
  try {
    if (id) {
      await api.put(`${recurso.url}/${id}`, dados);
    } else {
      await api.post(recurso.url, dados);
    }
    avisar(`${recurso.salvo} com sucesso.`);
    dialog.value = false;
    carregarListas();
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao salvar.'), 'error');
  } finally {
    salvando.value = false;
  }
};

const dialogExcluir = ref(false);
const itemParaExcluir = ref(null);
const tipoExcluir = ref(null);

const pedirExclusao = (tipo, registro) => {
  tipoExcluir.value = tipo;
  itemParaExcluir.value = registro;
  dialogExcluir.value = true;
};

const excluirConfirmado = async () => {
  const recurso = RECURSOS[tipoExcluir.value];
  try {
    await api.delete(`${recurso.url}/${itemParaExcluir.value[recurso.id]}`);
    avisar('Excluído com sucesso.');
    carregarListas();
  } catch (err) {
    // 409: está em uso por reagentes ou movimentações
    avisar(mensagemErro(err, 'Erro ao excluir.'), 'error');
  }
  itemParaExcluir.value = null;
};

onMounted(async () => {
  carregarListas();
  // Garante os valores atuais do servidor no formulário
  if (await config.carregar()) restaurarGeral();
});
</script>
