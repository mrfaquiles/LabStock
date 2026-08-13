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

        <!-- Status / Validade -->
        <template v-slot:[`item.data_validade`]="{ item }">
          <v-chip :color="isVencido(item.data_validade) ? 'error' : 'success'" size="small">
            {{ item.data_validade }}
          </v-chip>
        </template>

        <!-- Ações na Tabela -->
        <template v-slot:[`item.acoes`]="{ item }">
          <v-icon size="small" class="me-2" color="primary" @click="editarReagente(item)">mdi-pencil</v-icon>
          <v-icon size="small" color="error" @click="excluirReagente(item)">mdi-delete</v-icon>
        </template>
      </v-data-table>
    </v-card>

    <!-- Modal de Cadastro / Edição de Reagente (Estilo do Print) -->
    <v-dialog v-model="dialog" max-width="800px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">Novo Reagente</span>
          <v-btn icon variant="text" @click="dialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        
        <v-divider class="mb-4"></v-divider>

        <v-card-text>
          <v-form ref="form">
            <v-row>
              <!-- Nome do Reagente -->
              <v-col cols="12" md="8">
                <v-text-field
                  v-model="editedItem.nome"
                  label="Nome do Reagente *"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: Ácido Clorídrico 37%"
                ></v-text-field>
              </v-col>

              <!-- Fórmula Química / Código -->
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editedItem.codigo"
                  label="Código / Fórmula *"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: HCl-001"
                ></v-text-field>
              </v-col>

              <!-- Fornecedor -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.fornecedor"
                  label="Fornecedor"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: Sigma-Aldrich"
                ></v-text-field>
              </v-col>

              <!-- Número do Lote -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.lote"
                  label="Número do Lote *"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: LOTE-2026-A"
                ></v-text-field>
              </v-col>

              <!-- Quantidade e Unidade -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.quantidade"
                  label="Quantidade Atual *"
                  type="number"
                  variant="outlined"
                  density="comfortable"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-select
                  v-model="editedItem.unidade"
                  :items="['mL', 'L', 'g', 'kg', 'frascos']"
                  label="Unidade de Medida *"
                  variant="outlined"
                  density="comfortable"
                ></v-select>
              </v-col>

              <!-- Quantidade Mínima para Alerta -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.quantidade_minima"
                  label="Quantidade Mínima de Alerta"
                  type="number"
                  variant="outlined"
                  density="comfortable"
                ></v-text-field>
              </v-col>

              <!-- Data de Validade -->
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="editedItem.data_validade"
                  label="Data de Validade *"
                  type="date"
                  variant="outlined"
                  density="comfortable"
                ></v-text-field>
              </v-col>

              <!-- Localização no Estoque -->
              <v-col cols="12">
                <v-text-field
                  v-model="editedItem.localizacao"
                  label="Localização no Estoque (Armário/Prateleira)"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: Armário de Ácidos, Prateleira 2"
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
        { title: 'Código', key: 'codigo' },
        { title: 'Lote', key: 'lote' },
        { title: 'Qtd. Atual', key: 'quantidade' },
        { title: 'Unidade', key: 'unidade' },
        { title: 'Validade', key: 'data_validade' },
        { title: 'Localização', key: 'localizacao' },
        { title: 'Ações', key: 'acoes', sortable: false, align: 'end' },
      ],
      reagentes: [
        // Exemplo de dados visuais simulando o banco
        { id: 1, nome: 'Ácido Clorídrico 37%', codigo: 'REAG-01', lote: 'LOT9823', quantidade: '500', unidade: 'mL', data_validade: '2027-10-15', localizacao: 'Armário de Ácidos 01' },
        { id: 2, nome: 'Hidróxido de Sódio (Pernil)', codigo: 'REAG-02', lote: 'LOT4412', quantidade: '1000', unidade: 'g', data_validade: '2026-05-10', localizacao: 'Prateleira de Bases 02' }      ],
      editedItem: {
        nome: '',
        codigo: '',
        fornecedor: '',
        lote: '',
        quantidade: '',
        unidade: 'mL',
        quantidade_minima: '',
        data_validade: '',
        localizacao: ''
      }
    }
  },
  methods: {
    abrirModalCadastro() {
      this.editedItem = { nome: '', codigo: '', fornecedor: '', lote: '', quantidade: '', unidade: 'mL', quantidade_minima: '', data_validade: '', localizacao: '' };
      this.dialog = true;
    },
    salvarReagente() {
      if (this.editedItem.id) {
        const index = this.reagentes.findIndex(r => r.id === this.editedItem.id);
        if (index !== -1) {
          this.reagentes[index] = { ...this.editedItem };
        }
      } else {
        this.reagentes.push({ ...this.editedItem, id: Date.now() });
      }
      this.dialog = false;
    },
    emitirRelatorio() {
      alert('Gerando relatório de reagentes e validades...');
    },
    atualizarEstoque() {
      alert('Abrindo painel de movimentação/atualização rápida de estoque...');
    },
    isVencido(dataStr) {
      const hoje = new Date();
      const validade = new Date(dataStr);
      return validade < hoje;
    },
    editarReagente(item) {
      this.editedItem = { ...item };
      this.dialog = true;
    },
    excluirReagente(item) {
      this.itemParaExcluir = item;
      this.dialogExcluir = true;
    },
    deletarItemConfirmado() {
      if (this.itemParaExcluir) {
        this.reagentes = this.reagentes.filter(r => r.id !== this.itemParaExcluir.id);
        this.itemParaExcluir = null;
      }
      this.dialogExcluir = false;
    }
  }
}
</script>