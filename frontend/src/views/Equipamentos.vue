<template>
  <v-container fluid class="pa-8">
    <!-- Cabeçalho Simples sem Colunas -->
    <div class="d-flex justify-space-between align-center mb-6 flex-wrap gap-4">
      <div>
        <h1 class="text-h4 font-weight-bold text-grey-darken-4">Gerenciamento de Equipamentos</h1>
        <p class="text-subtitle-1 text-grey-darken-1 mt-1">Controle de balanças, centrífugas, microscópios e maquinário</p>
      </div>
      
      <div class="d-flex align-center flex-wrap gap-2">
        <v-btn color="#0B132B" class="text-none me-2" prepend-icon="mdi-plus" elevation="1" @click="abrirModalCadastro">
          Cadastrar
        </v-btn>
        <v-btn color="grey-darken-2" variant="outlined" class="text-none me-2" prepend-icon="mdi-file-pdf-box" @click="emitirRelatorio">
          Relatório
        </v-btn>
        <v-btn color="blue-darken-3" variant="tonal" class="text-none" prepend-icon="mdi-sync" @click="atualizarEstoque">
          Atualizar
        </v-btn>
      </div>
    </div>

    <ModalConfirmacao
      v-model="dialogExcluir"
      :nomeItem="itemParaExcluir?.nome"
      @confirm="deletarItemConfirmado"
    />

    <!-- Tabela de Equipamentos -->
    <v-card class="elevation-1 rounded-lg">
      <v-data-table
        :headers="headers"
        :items="equipamentos"
        :search="search"
        class="pa-2"
      >
        <template v-slot:top>
          <v-toolbar flat class="px-4 bg-white">
            <v-text-field
              v-model="search"
              append-inner-icon="mdi-magnify"
              label="Pesquisar equipamento..."
              single-line
              hide-details
              density="compact"
              variant="outlined"
              style="max-width: 300px;"
            ></v-text-field>
          </v-toolbar>
        </template>

        <!-- Status de Funcionamento -->
        <template v-slot:[`item.status`]="{ item }">
          <v-chip :color="item.status === 'Operacional' ? 'success' : 'warning'" size="small" variant="tonal">
            {{ item.status }}
          </v-chip>
        </template>

        <!-- Ações -->
        <template v-slot:[`item.acoes`]="{ item }">
          <v-icon size="small" class="me-2" color="primary" @click="editar(item)">mdi-pencil</v-icon>
          <v-icon size="small" color="error" @click="excluir(item)">mdi-delete</v-icon>
        </template>
      </v-data-table>
    </v-card>

    <!-- Modal de Cadastro -->
    <v-dialog v-model="dialog" max-width="700px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">Novo Equipamento</span>
          <v-btn icon variant="text" @click="dialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        
        <v-divider class="mb-4"></v-divider>

        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="8">
                <v-text-field v-model="form.nome" label="Nome do Equipamento *" variant="outlined" density="comfortable" placeholder="Ex.: Balança Analítica de Precisão"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.patrimonio" label="Nº de Patrimônio *" variant="outlined" density="comfortable" placeholder="Ex.: PAT-9921"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select v-model="form.status" :items="['Operacional', 'Em Manutenção', 'Inativo']" label="Status de Operação *" variant="outlined" density="comfortable"></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.ultima_calibracao" label="Última Calibração" type="date" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12">
                <v-text-field v-model="form.localizacao" label="Laboratório / Localização no Prédio" variant="outlined" density="comfortable" placeholder="Ex.: Lab de Química Geral, Bancada 01"></v-text-field>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="#0B132B" class="text-none px-6 text-white" @click="salvar">Salvar Equipamento</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import ModalConfirmacao from '../components/ModalConfirmacao.vue';

export default {
  name: 'Equipamentos',
  components: {
    ModalConfirmacao
  },
  data() {
    return {
      search: '',
      dialog: false,
      dialogExcluir: false,
      itemParaExcluir: null,
      headers: [
        { title: 'Nome do Equipamento', key: 'nome', align: 'start' },
        { title: 'Nº Patrimônio', key: 'patrimonio' },
        { title: 'Status', key: 'status' },
        { title: 'Última Calibração', key: 'ultima_calibracao' },
        { title: 'Localização', key: 'localizacao' },
        { title: 'Ações', key: 'acoes', sortable: false, align: 'end' },
      ],
      equipamentos: [
        { id: 1, nome: 'Balança Analítica Semi-Micro', patrimonio: 'PAT-0012', status: 'Operacional', ultima_calibracao: '2026-01-10', localizacao: 'Sala de Instrumentação' },
        { id: 2, nome: 'Centrífuga de Bancada', patrimonio: 'PAT-0045', status: 'Em Manutenção', ultima_calibracao: '2025-08-15', localizacao: 'Lab de Biologia' }
      ],
      form: { nome: '', patrimonio: '', status: 'Operacional', ultima_calibracao: '', localizacao: '' }
    }
  },
  methods: {
    abrirModalCadastro() {
      this.form = { nome: '', patrimonio: '', status: 'Operacional', ultima_calibracao: '', localizacao: '' };
      this.dialog = true;
    },
    salvar() {
      if (this.form.id) {
        const index = this.equipamentos.findIndex(e => e.id === this.form.id);
        if (index !== -1) {
          this.equipamentos[index] = { ...this.form };
        }
      } else {
        this.equipamentos.push({ ...this.form, id: Date.now() });
      }
      this.dialog = false;
    },
    emitirRelatorio() { alert('Emitindo relatório de equipamentos e calibrações...'); },
    atualizarEstoque() { alert('Atualizando status dos equipamentos...'); },
    editar(item) {
      this.form = { ...item };
      this.dialog = true;
    },
    excluir(item) {
      this.itemParaExcluir = item;
      this.dialogExcluir = true;
    },
    deletarItemConfirmado() {
      if (this.itemParaExcluir) {
        this.equipamentos = this.equipamentos.filter(e => e.id !== this.itemParaExcluir.id);
        this.itemParaExcluir = null;
      }
      this.dialogExcluir = false;
    }
  }
}
</script>