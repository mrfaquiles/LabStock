<template>
  <v-container fluid class="pa-6">
    <!-- Cabeçalho e Botões de Ação -->
    <v-row class="mb-6 align-center">
      <v-col cols="12" md="6">
        <h1 class="text-h5 font-weight-bold" style="color: #004A26;">Gerenciamento de Reagentes</h1>
        <p class="text-subtitle-2 text-grey-darken-1">Controle de estoque, lotes e validades de reagentes químicos</p>
      </v-col>
      <v-col cols="12" md="6" class="text-md-right">
        <v-btn color="#004A26" class="text-none me-2 mb-2" prepend-icon="mdi-plus" @click="abrirModalCadastro">
          Cadastrar Reagente
        </v-btn>
        <v-btn color="grey-darken-2" variant="outlined" class="text-none me-2 mb-2" prepend-icon="mdi-file-pdf-box" @click="emitirRelatorio">
          Emitir Relatório
        </v-btn>
        <v-btn color="blue-darken-2" variant="tonal" class="text-none mb-2" prepend-icon="mdi-sync" @click="atualizarEstoque">
          Atualizar Estoque
        </v-btn>
      </v-col>
    </v-row>

    <ModalConfirmacao
      v-model="dialogExcluir"
      :nomeItem="itemParaExcluir?.nome"
      @confirm="deletarItemConfirmado"
    />

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

        <!-- Ações na Tabela -->
        <template v-slot:[`item.acoes`]="{ item }">
          <v-icon size="small" class="me-2" color="primary" @click="editarReagente(item)">mdi-pencil</v-icon>
          <v-icon size="small" color="error" @click="excluirReagente(item)">mdi-delete</v-icon>
        </template>
      </v-data-table>
    </v-card>

    <!-- Modal de Cadastro / Edição -->
    <v-dialog v-model="dialog" max-width="800px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">{{ editedItem.id ? 'Editar Reagente' : 'Novo Reagente' }}</span>
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
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editedItem.formula_quimica"
                  label="Fórmula Química"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: HCl"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.cas_number"
                  label="Número CAS"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: 7647-01-0"
                ></v-text-field>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialog = false">
            Cancelar
          </v-btn>
          <v-btn color="#004A26" class="text-none px-6" @click="salvarReagente">
            Salvar Reagente
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import axios from 'axios';
import ModalConfirmacao from '../components/ModalConfirmacao.vue';

export default {
  name: 'Reagentes',
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
        { title: 'Nome do Reagente', key: 'nome', align: 'start' },
        { title: 'Fórmula', key: 'formula_quimica' },
        { title: 'CAS', key: 'cas_number' },
        { title: 'Ações', key: 'acoes', sortable: false, align: 'end' },
      ],
      reagentes: [],
      editedItem: {
        id: null,
        nome: '',
        formula_quimica: '',
        cas_number: ''
      }
    }
  },
  mounted() {
    this.carregarReagentes();
  },
  methods: {
    async carregarReagentes() {
      try {
        const resposta = await axios.get('http://127.0.0.1:8000/api/reagentes');
        this.reagentes = resposta.data;
      } catch (erro) {
        console.error('Erro ao carregar reagentes:', erro);
      }
    },
    abrirModalCadastro() {
      this.editedItem = { id: null, nome: '', formula_quimica: '', cas_number: '' };
      this.dialog = true;
    },
    async salvarReagente() {
      try {
        if (this.editedItem.id) {
          await axios.put(`http://127.0.0.1:8000/api/reagentes/${this.editedItem.id}`, this.editedItem);
        } else {
          await axios.post('http://127.0.0.1:8000/api/reagentes', this.editedItem);
        }
        this.carregarReagentes();
        this.dialog = false;
      } catch (erro) {
        console.error('Erro ao salvar reagente:', erro);
      }
    },
    editarReagente(item) {
      this.editedItem = { ...item };
      this.dialog = true;
    },
    excluirReagente(item) {
      this.itemParaExcluir = item;
      this.dialogExcluir = true;
    },
    async deletarItemConfirmado() {
      if (this.itemParaExcluir) {
        try {
          await axios.delete(`http://127.0.0.1:8000/api/reagentes/${this.itemParaExcluir.id}`);
          this.carregarReagentes();
        } catch (erro) {
          console.error('Erro ao excluir reagente:', erro);
        }
        this.itemParaExcluir = null;
      }
      this.dialogExcluir = false;
    },
    emitirRelatorio() {
      alert('Gerando relatório de reagentes e validades...');
    },
    atualizarEstoque() {
      this.carregarReagentes();
    }
  }
}
</script>