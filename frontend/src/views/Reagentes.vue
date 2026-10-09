<template>
  <v-container fluid class="pa-6">
    <!-- Cabeçalho e Botões de Ação -->
    <CabecalhoPagina titulo="Reagentes" subtitulo="Controle de estoque, lotes e validades de reagentes químicos" icone="mdi-flask">
      <v-btn v-if="auth.podeEditar" color="primary" class="text-none text-white" prepend-icon="mdi-plus" @click="abrirModalCadastro">
        Cadastrar Reagente
      </v-btn>
      <v-btn color="grey-darken-2" variant="outlined" class="text-none" prepend-icon="mdi-file-pdf-box" @click="emitirRelatorio">
        Emitir Relatório
      </v-btn>
      <v-btn color="blue-darken-2" variant="tonal" class="text-none" prepend-icon="mdi-sync" @click="atualizarEstoque">
        Atualizar Estoque
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

    <!-- Tabela de Reagentes -->
    <v-card class="elevation-1 rounded-lg">
      <v-data-table
        :headers="headers"
        :items="reagentes"
        :search="search"
        class="pa-2"
      >
        <template v-slot:top>
          <v-toolbar flat class="px-4 bg-white">
            <v-text-field
              v-model="search"
              append-inner-icon="mdi-magnify"
              label="Pesquisar reagente..."
              single-line
              hide-details
              density="compact"
              variant="outlined"
              style="max-width: 300px;"
            ></v-text-field>
          </v-toolbar>
        </template>

        <!-- Nome do Reagente com Menu Flutuante Estilizado e Parágrafos Respeitados -->
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

        <!-- Quantidade total (soma dos saldos dos lotes) -->
        <template v-slot:[`item.quantidade`]="{ item }">
          {{ formatarQuantidade(item.quantidade, sigla(item)) }}
        </template>

        <!-- Lotes com saldo (menu com o detalhe de cada lote) -->
        <template v-slot:[`item.lote`]="{ item }">
          <span v-if="!item.lotes?.length" class="text-grey">Sem saldo</span>
          <v-menu v-else open-on-hover location="top" :close-on-content-click="false">
            <template v-slot:activator="{ props }">
              <span v-bind="props" class="cursor-pointer">
                {{ item.lotes[0].lote }}
                <v-chip v-if="item.lotes.length > 1" size="x-small" variant="tonal" color="primary" class="ms-1">
                  +{{ item.lotes.length - 1 }}
                </v-chip>
              </span>
            </template>
            <v-card class="pa-3 elevation-4 rounded-lg" color="blue-lighten-5" style="max-width: 350px; max-height: 200px; overflow-y: auto; border: 1px solid #b0bec5;">
              <p class="text-caption font-weight-bold text-blue-grey-darken-4 mb-1">Lotes em estoque</p>
              <div v-for="lote in item.lotes" :key="lote.identradareagente" class="text-body-2 text-blue-grey-darken-4">
                <strong>{{ lote.lote }}</strong> — {{ formatarQuantidade(lote.saldo, sigla(item)) }}
                (val. {{ formatarDataExibicao(lote.data_validade) }})
              </div>
            </v-card>
          </v-menu>
        </template>

        <!-- Validade mais próxima entre os lotes com saldo, com Alerta Visual -->
        <template v-slot:[`item.data_validade`]="{ item }">
          <v-chip v-if="validadeExibida(item)" :color="verificarCorValidade(item)" size="small" variant="tonal">
            {{ formatarDataExibicao(validadeExibida(item)) }}
          </v-chip>
          <span v-else>-</span>
        </template>

        <!-- Ações na Tabela -->
        <template v-slot:[`item.acoes`]="{ item }">
          <div v-if="auth.podeEditar" class="d-flex justify-end">
            <v-tooltip text="Registrar uso" location="top">
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" size="small" class="me-2" color="deep-orange-darken-2" :disabled="!item.lotes?.length" @click="abrirUso(item)">mdi-flask-minus-outline</v-icon>
              </template>
            </v-tooltip>
            <v-tooltip text="Nova entrada de lote" location="top">
              <template v-slot:activator="{ props }">
                <v-icon v-bind="props" size="small" class="me-2" color="green-darken-2" @click="abrirEntrada(item)">mdi-package-variant-plus</v-icon>
              </template>
            </v-tooltip>
            <v-icon size="small" class="me-2" color="primary" @click="editarReagente(item)">mdi-pencil</v-icon>
            <v-icon size="small" color="error" @click="excluirReagente(item)">mdi-delete</v-icon>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <!-- Modal de Cadastro / Edição -->
    <v-dialog v-model="dialog" max-width="800px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">{{ editedItem.idreagente ? 'Editar Reagente' : 'Novo Reagente' }}</span>
          <v-btn icon variant="text" @click="dialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-divider class="mb-4"></v-divider>

        <v-card-text>
          <v-form ref="form">
            <v-row>
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="editedItem.nome"
                  label="Nome do Reagente *"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: Ácido Clorídrico 37%"
                  :rules="[regras.obrigatorio]"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editedItem.catmat"
                  label="Código CATMAT *"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: 123456"
                  :rules="[regras.obrigatorio]"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="3">
                <v-select
                  v-model="editedItem.idunidademedida"
                  :items="unidadesMedida"
                  item-title="nome"
                  item-value="idunidademedida"
                  label="Unidade de Medida *"
                  variant="outlined"
                  density="comfortable"
                  no-data-text="Nenhuma unidade cadastrada (rode php artisan db:seed)"
                  placeholder="Selecione"
                  hint="Unidade em que o estoque é controlado"
                  persistent-hint
                  :rules="[regras.obrigatorio]"
                ></v-select>
              </v-col>

              <v-col cols="12" md="5">
                <!-- Quantidade só no cadastro (lote inicial); depois muda por uso ou nova entrada de lote -->
                <v-text-field
                  v-if="editedItem.idreagente"
                  :model-value="formatarQuantidade(editedItem.quantidade, siglaCadastro)"
                  label="Estoque atual"
                  variant="outlined"
                  density="comfortable"
                  disabled
                  hint="Altere pelo registro de uso ou nova entrada de lote"
                  persistent-hint
                ></v-text-field>
                <CampoQuantidade
                  v-else
                  v-model="editedItem.quantidade"
                  :sigla-base="siglaCadastro"
                  :disabled="!siglaCadastro"
                  label="Quantidade inicial *"
                  placeholder="Ex.: 500"
                  permite-zero
                ></CampoQuantidade>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editedItem.lote"
                  label="Número do Lote *"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: LOT-2026-A"
                  autocomplete="off"
                  :rules="[regras.obrigatorio]"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.localizacao"
                  label="Localização / Armário"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: Armário de Ácidos, Prateleira 2"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.meses_alerta"
                  label="Alerta de Vencimento (Meses antes)"
                  type="number"
                  min="1"
                  max="36"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Padrão: 4 meses"
                  :rules="[regras.mesesAlerta]"
                  @keydown="bloquearSinais"
                ></v-text-field>
              </v-col>

              <!-- Campo de Data com Pop-up do Calendário Padronizado -->
              <v-col cols="12" md="6">
                <v-menu
                  v-model="menuData"
                  :close-on-content-click="false"
                  transition="scale-transition"
                  offset-y
                  min-width="auto"
                >
                  <template v-slot:activator="{ props }">
                    <v-text-field
                      v-bind="props"
                      :model-value="formatarDataExibicao(editedItem.data_validade)"
                      label="Data de Validade *"
                      readonly
                      variant="outlined"
                      density="comfortable"
                      append-inner-icon="mdi-calendar"
                      placeholder="Selecione a data"
                      :rules="[regras.obrigatorio]"
                    ></v-text-field>
                  </template>
                  <v-date-picker
                    v-model="dataValidadeObj"
                    :min="dataMinima"
                    class="elevation-3 rounded-lg"
                    density="compact"
                    hide-header
                    @update:model-value="menuData = false"
                  ></v-date-picker>
                </v-menu>
              </v-col>

              <v-col cols="12" class="mt-2">
                <v-textarea
                  v-model="editedItem.descricao"
                  label="Descrição / Precauções"
                  variant="outlined"
                  density="comfortable"
                  rows="2"
                  placeholder="Detalhes adicionais ou cuidados de manuseio..."
                ></v-textarea>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialog = false">
            Cancelar
          </v-btn>
          <!-- Botão de Salvar padronizado com a cor institucional do LabStock -->
          <v-btn color="primary" variant="flat" class="text-none px-6 text-white" :loading="salvando" @click="salvarReagente">
            Salvar Reagente
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Registro de Uso (desconta do lote escolhido) -->
    <v-dialog v-model="dialogUso" max-width="600px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">Registrar Uso</span>
          <v-btn icon variant="text" @click="dialogUso = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-subtitle class="pb-2">
          {{ reagenteMov?.nome }} — estoque total: {{ formatarQuantidade(reagenteMov?.quantidade, sigla(reagenteMov)) }}
        </v-card-subtitle>

        <v-divider class="mb-4"></v-divider>

        <v-card-text>
          <v-form ref="formUso">
            <v-row>
              <v-col cols="12">
                <v-select
                  v-model="uso.identrada"
                  :items="reagenteMov?.lotes || []"
                  item-value="identradareagente"
                  :item-title="tituloLote"
                  label="Lote utilizado *"
                  variant="outlined"
                  density="comfortable"
                  hint="Os lotes estão ordenados pela validade: use primeiro o que vence antes"
                  persistent-hint
                  :rules="[regras.obrigatorio]"
                ></v-select>
              </v-col>

              <v-col cols="12">
                <!-- Digita em g/mg (ou mL/µL) e o sistema converte para a unidade do reagente -->
                <CampoQuantidade
                  v-model="uso.quantidade"
                  :sigla-base="sigla(reagenteMov)"
                  :unidade-inicial="subunidadeUso"
                  :maximo="loteSelecionado ? loteSelecionado.saldo : null"
                  label="Quantidade utilizada *"
                  placeholder="Ex.: 500"
                ></CampoQuantidade>
              </v-col>

              <v-col cols="12">
                <v-textarea
                  v-model="uso.observacao"
                  label="Finalidade / Observação"
                  variant="outlined"
                  density="comfortable"
                  rows="2"
                  placeholder="Ex.: Aula prática de Química Analítica - Turma B"
                ></v-textarea>
              </v-col>
            </v-row>
          </v-form>

          <v-alert v-if="loteSelecionado && quantidadeUsoBase > 0 && quantidadeUsoBase <= loteSelecionado.saldo + 1e-9" type="info" variant="tonal" density="compact">
            Será descontado <strong>{{ formatarQuantidade(quantidadeUsoBase, sigla(reagenteMov)) }}</strong>
            ({{ formatarNaBase(quantidadeUsoBase, sigla(reagenteMov)) }}) do lote <strong>{{ loteSelecionado.lote }}</strong>.
            Saldo do lote após o uso: <strong>{{ formatarQuantidade(loteSelecionado.saldo - quantidadeUsoBase, sigla(reagenteMov)) }}</strong>.
          </v-alert>
        </v-card-text>

        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialogUso = false">Cancelar</v-btn>
          <v-btn color="primary" variant="flat" class="text-none px-6 text-white" :loading="salvando" @click="salvarUso">
            Registrar Uso
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Modal de Nova Entrada de Lote -->
    <v-dialog v-model="dialogEntrada" max-width="600px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">Nova Entrada de Lote</span>
          <v-btn icon variant="text" @click="dialogEntrada = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        <v-card-subtitle class="pb-2">{{ reagenteMov?.nome }}</v-card-subtitle>

        <v-divider class="mb-4"></v-divider>

        <v-card-text>
          <v-form ref="formEntrada">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="entrada.lote"
                  label="Número do Lote *"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: LOT-2026-B"
                  autocomplete="off"
                  :rules="[regras.obrigatorio]"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-menu v-model="menuDataEntrada" :close-on-content-click="false" min-width="auto">
                  <template v-slot:activator="{ props }">
                    <v-text-field
                      v-bind="props"
                      :model-value="formatarDataExibicao(entrada.data_validade)"
                      label="Validade do Lote *"
                      readonly
                      variant="outlined"
                      density="comfortable"
                      append-inner-icon="mdi-calendar"
                      placeholder="Selecione a data"
                      :rules="[regras.obrigatorio]"
                    ></v-text-field>
                  </template>
                  <v-date-picker
                    :model-value="null"
                    :min="dataMinima"
                    class="elevation-3 rounded-lg"
                    density="compact"
                    hide-header
                    @update:model-value="d => { entrada.data_validade = paraIso(d); menuDataEntrada = false; }"
                  ></v-date-picker>
                </v-menu>
              </v-col>

              <v-col cols="12">
                <!-- Ex.: 10 frascos de 500 mL = 5000 mL (convertido para 5 L) -->
                <CampoQuantidade
                  v-model="entrada.quantidade"
                  :sigla-base="sigla(reagenteMov)"
                  label="Quantidade recebida *"
                  placeholder="Ex.: 10"
                ></CampoQuantidade>
              </v-col>

              <v-col cols="12">
                <v-text-field
                  v-model="entrada.observacao"
                  label="Observação"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: Nota fiscal 1234 / Fornecedor X"
                ></v-text-field>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialogEntrada = false">Cancelar</v-btn>
          <v-btn color="primary" variant="flat" class="text-none px-6 text-white" :loading="salvando" @click="salvarEntrada">
            Registrar Entrada
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import api, { mensagemErro } from '../plugins/axios';
import ModalConfirmacao from '../components/ModalConfirmacao.vue';
import { baixarPdf } from '../utils/exportarRelatorio';
import CabecalhoPagina from '../components/CabecalhoPagina.vue';
import { useAuthStore } from '../stores/authStore';
import { useConfigStore } from '../stores/configStore';

import CampoQuantidade from '../components/CampoQuantidade.vue';
import { formatarQuantidade, formatarNaBase } from '../utils/unidades';

export default {
  name: 'Reagentes',
  components: {
    ModalConfirmacao,
    CabecalhoPagina,
    CampoQuantidade
  },
  setup() {
    return { auth: useAuthStore(), config: useConfigStore() };
  },
  data() {
    return {
      search: '',
      dialog: false,
      dialogExcluir: false,
      dialogUso: false,
      dialogEntrada: false,
      menuData: false,
      menuDataEntrada: false,
      salvando: false,
      itemParaExcluir: null,
      reagenteMov: null,
      dataValidadeObj: null,
      snackbar: { ativo: false, texto: '', cor: 'success' },
      headers: [
        { title: 'Nome do Reagente', key: 'nome', align: 'start' },
        { title: 'CATMAT', key: 'catmat' },
        { title: 'Quantidade', key: 'quantidade' },
        { title: 'Lote', key: 'lote' },
        { title: 'Validade', key: 'data_validade' },
        { title: 'Localização', key: 'localizacao' },
        { title: 'Ações', key: 'acoes', sortable: false, align: 'end' },
      ],
      reagentes: [],
      unidadesMedida: [],
      editedItem: {},
      // Quantidades já na unidade base (o CampoQuantidade faz a conversão)
      uso: { identrada: null, quantidade: null, observacao: '' },
      entrada: { lote: '', data_validade: '', quantidade: null, observacao: '' },
      regras: {
        obrigatorio: (v) => (v !== null && v !== undefined && String(v).trim() !== '') || 'Campo obrigatório',
        naoNegativo: (v) => Number(v) >= 0 || 'A quantidade não pode ser negativa',
        positivo: (v) => Number(v) > 0 || 'Informe uma quantidade maior que zero',
        mesesAlerta: (v) => v === '' || v === null || v === undefined
          || (Number.isInteger(Number(v)) && Number(v) >= 1 && Number(v) <= 36) || 'Informe de 1 a 36 meses',
      },
    }
  },
  computed: {
    dataMinima() {
      return new Date();
    },
    loteSelecionado() {
      return this.reagenteMov?.lotes?.find(l => l.identradareagente === this.uso.identrada) || null;
    },
    // Já convertida pelo CampoQuantidade (ex.: 500 g -> 0.5 kg)
    quantidadeUsoBase() {
      return Number(this.uso.quantidade || 0);
    },
    // Uso costuma ser pesado/medido em g ou mL
    subunidadeUso() {
      return { kg: 'g', L: 'mL' }[this.sigla(this.reagenteMov)] || null;
    },
    // Sigla da unidade escolhida no cadastro (kg, L, un)
    siglaCadastro() {
      return this.unidadesMedida.find(u => u.idunidademedida === this.editedItem.idunidademedida)?.sigla || '';
    },
  },
  watch: {
    dataValidadeObj(novaData) {
      this.editedItem.data_validade = novaData ? this.paraIso(novaData) : '';
    }
  },
  mounted() {
    this.carregarReagentes();
    this.carregarUnidadesMedida();
  },
  methods: {
    avisar(texto, cor = 'success') {
      this.snackbar = { ativo: true, texto, cor };
    },
    async carregarReagentes() {
      try {
        const resposta = await api.get('/reagentes');
        this.reagentes = resposta.data;
      } catch (erro) {
        this.avisar(mensagemErro(erro, 'Erro ao carregar reagentes.'), 'error');
      }
    },
    async carregarUnidadesMedida() {
      try {
        const resposta = await api.get('/unidademedidas');
        this.unidadesMedida = resposta.data.map(u => ({
          idunidademedida: u.idunidademedida,
          sigla: u.sigla,
          nome: `${u.nome} (${u.sigla})`
        }));
      } catch (erro) {
        this.avisar(mensagemErro(erro, 'Erro ao carregar unidades de medida.'), 'error');
      }
    },
    novoReagente() {
      return {
        idreagente: null,
        nome: '',
        catmat: '',
        quantidade: null,
        idunidademedida: null,
        lote: '',
        data_validade: '',
        localizacao: '',
        meses_alerta: this.config.config.meses_alerta_padrao, // Configurações do Sistema
        descricao: '',
        ativo: 1
      };
    },
    abrirModalCadastro() {
      this.editedItem = this.novoReagente();
      this.dataValidadeObj = null;
      this.dialog = true;
      this.$nextTick(() => this.$refs.form?.resetValidation());
    },
    async salvarReagente() {
      const { valid } = await this.$refs.form.validate();
      if (!valid) return;

      // Envia só os campos do cadastro (sem lotes/relacionamentos devolvidos pela API)
      const campos = ['nome', 'catmat', 'idunidademedida', 'lote', 'data_validade', 'localizacao', 'meses_alerta', 'descricao', 'ativo'];
      const dados = Object.fromEntries(campos.map(c => [c, this.editedItem[c]]));
      if (dados.meses_alerta === '') dados.meses_alerta = null;
      if (!this.editedItem.idreagente) dados.quantidade = Number(this.editedItem.quantidade);

      this.salvando = true;
      try {
        if (this.editedItem.idreagente) {
          await api.put(`/reagentes/${this.editedItem.idreagente}`, dados);
          this.avisar('Reagente atualizado com sucesso.');
        } else {
          await api.post('/reagentes', dados);
          this.avisar('Reagente cadastrado com sucesso.');
        }
        this.carregarReagentes();
        this.dialog = false;
      } catch (erro) {
        this.avisar(mensagemErro(erro, 'Erro ao salvar reagente.'), 'error');
      } finally {
        this.salvando = false;
      }
    },
    editarReagente(item) {
      this.editedItem = { ...item, meses_alerta: item.meses_alerta || 4 };
      this.dataValidadeObj = item.data_validade ? new Date(item.data_validade + 'T00:00:00') : null;
      this.dialog = true;
    },
    excluirReagente(item) {
      this.itemParaExcluir = item;
      this.dialogExcluir = true;
    },
    async deletarItemConfirmado() {
      if (this.itemParaExcluir) {
        try {
          await api.delete(`/reagentes/${this.itemParaExcluir.idreagente}`);
          this.avisar('Reagente excluído com sucesso.');
          this.carregarReagentes();
        } catch (erro) {
          this.avisar(mensagemErro(erro, 'Erro ao excluir reagente.'), 'error');
        }
        this.itemParaExcluir = null;
      }
      this.dialogExcluir = false;
    },

    // ---------- Registro de uso ----------
    abrirUso(item) {
      this.reagenteMov = item;
      // Sugere o lote que vence primeiro (FEFO)
      this.uso = { identrada: item.lotes[0]?.identradareagente ?? null, quantidade: null, observacao: '' };
      this.dialogUso = true;
      this.$nextTick(() => this.$refs.formUso?.resetValidation());
    },
    async salvarUso() {
      const { valid } = await this.$refs.formUso.validate();
      if (!valid) return;

      this.salvando = true;
      try {
        await api.post('/saidareagentes', {
          idreagente: this.reagenteMov.idreagente,
          identrada: this.uso.identrada,
          quantidade: this.quantidadeUsoBase,
          observacao: this.uso.observacao || null,
        });
        this.avisar(`Uso registrado: ${this.formatarQuantidade(this.quantidadeUsoBase, this.sigla(this.reagenteMov))} descontado(s) do estoque.`);
        this.dialogUso = false;
        this.carregarReagentes();
      } catch (erro) {
        this.avisar(mensagemErro(erro, 'Erro ao registrar uso.'), 'error');
      } finally {
        this.salvando = false;
      }
    },

    // ---------- Entrada de novo lote ----------
    abrirEntrada(item) {
      this.reagenteMov = item;
      this.entrada = { lote: '', data_validade: '', quantidade: null, observacao: '' };
      this.dialogEntrada = true;
      this.$nextTick(() => this.$refs.formEntrada?.resetValidation());
    },
    async salvarEntrada() {
      const { valid } = await this.$refs.formEntrada.validate();
      if (!valid) return;

      const quantidade = Number(this.entrada.quantidade); // já na unidade base

      this.salvando = true;
      try {
        await api.post('/entradareagentes', {
          idreagente: this.reagenteMov.idreagente,
          lote: this.entrada.lote,
          data_validade: this.entrada.data_validade,
          quantidade,
          observacao: this.entrada.observacao || null,
        });
        this.avisar(`Lote ${this.entrada.lote} adicionado ao estoque.`);
        this.dialogEntrada = false;
        this.carregarReagentes();
      } catch (erro) {
        this.avisar(mensagemErro(erro, 'Erro ao registrar entrada.'), 'error');
      } finally {
        this.salvando = false;
      }
    },

    // ---------- Utilitários ----------
    sigla(item) {
      return item?.unidade_medida?.sigla || '';
    },
    // "0,5 g" em vez de "0,0005 kg"
    formatarQuantidade,
    formatarNaBase,
    tituloLote(lote) {
      if (!lote) return '';
      return `${lote.lote} — saldo ${this.formatarQuantidade(lote.saldo, this.sigla(this.reagenteMov))} — val. ${this.formatarDataExibicao(lote.data_validade)}`;
    },
    // Impede digitar sinal negativo/exponencial em campos numéricos
    bloquearSinais(e) {
      if (['-', '+', 'e', 'E'].includes(e.key)) e.preventDefault();
    },
    paraIso(data) {
      const ano = data.getFullYear();
      const mes = String(data.getMonth() + 1).padStart(2, '0');
      const dia = String(data.getDate()).padStart(2, '0');
      return `${ano}-${mes}-${dia}`;
    },
    formatarNumero(valor) {
      return Number(valor || 0).toLocaleString('pt-BR', { maximumFractionDigits: 3 });
    },
    formatarDataExibicao(dataStr) {
      if (!dataStr) return '';
      const partes = dataStr.split('-');
      if (partes.length === 3) {
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
      }
      return dataStr;
    },
    validadeExibida(item) {
      return item.proxima_validade || item.data_validade;
    },
    verificarCorValidade(item) {
      const data = this.validadeExibida(item);
      if (!data) return 'success';
      const hoje = new Date();
      const validade = new Date(data + 'T00:00:00');
      const mesesMargem = Number(item.meses_alerta) || 4;
      const diffMeses = (validade.getFullYear() - hoje.getFullYear()) * 12 + (validade.getMonth() - hoje.getMonth());

      if (validade < hoje) return 'error'; // Vencido (Vermelho)
      if (diffMeses <= mesesMargem) return 'warning'; // Próximo de vencer (Amarelo)
      return 'success'; // OK (Verde)
    },
    // PDF gerado direto no navegador (o antigo window.open era bloqueado como pop-up)
    emitirRelatorio() {
      baixarPdf({
        titulo: 'Relatório de Reagentes',
        subtitulo: `Posição do estoque em ${new Date().toLocaleDateString('pt-BR')}`,
        nomeArquivo: `labstock-reagentes-${new Date().toISOString().slice(0, 10)}`,
        tabelas: [{
          colunas: ['Nome do Reagente', 'CATMAT', 'Quantidade', 'Lotes (saldo / validade)', 'Localização'],
          linhas: this.reagentes.map(r => [
            r.nome,
            r.catmat || '-',
            this.formatarQuantidade(r.quantidade, this.sigla(r)),
            (r.lotes || []).map(l => `${l.lote}: ${this.formatarQuantidade(l.saldo, this.sigla(r))} (${this.formatarDataExibicao(l.data_validade)})`).join('\n') || '-',
            r.localizacao || '-',
          ]),
        }],
      });
    },
    atualizarEstoque() {
      this.carregarReagentes();
    }
  }
}
</script>
