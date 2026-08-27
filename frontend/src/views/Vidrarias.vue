<template>
  <v-container fluid class="pa-6">
    <!-- Cabeçalho e Botões de Ação -->
    <v-row class="mb-6 align-center">
      <v-col cols="12" md="6">
        <h1 class="text-h5 font-weight-bold" style="color: #004A26;">Gerenciamento de Vidrarias</h1>
        <p class="text-subtitle-2 text-grey-darken-1">Controle de béqueres, provetas, balões e materiais de vidro</p>
      </v-col>
      <v-col cols="12" md="6" class="text-md-right">
        <v-btn color="#004A26" class="text-none me-2 mb-2" prepend-icon="mdi-plus" @click="abrirModalCadastro">
          Cadastrar Vidraria
        </v-btn>
        <v-btn color="grey-darken-2" variant="outlined" class="text-none me-2 mb-2" prepend-icon="mdi-file-pdf-box" @click="emitirRelatorio">
          Emitir Relatório
        </v-btn>
        <v-btn color="blue-darken-2" variant="tonal" class="text-none mb-2" prepend-icon="mdi-sync" @click="carregarVidrarias">
          Atualizar Estoque
        </v-btn>
      </v-col>
    </v-row>

    <ModalConfirmacao
      v-model="dialogExcluir"
      :nomeItem="itemParaExcluir?.nome"
      @confirm="deletarItemConfirmado"
    />

    <!-- Tabela de Vidrarias -->
    <v-card class="elevation-1 rounded-lg">
      <v-data-table
        :headers="headers"
        :items="vidrarias"
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

        <!-- Status / Estado de Conservação -->
        <template v-slot:[`item.estado`]="{ item }">
          <v-chip :color="item.estado === 'Inteiro' ? 'success' : 'error'" size="small">
            {{ item.estado }}
          </v-chip>
        </template>

        <!-- Ações -->
        <template v-slot:[`item.acoes`]="{ item }">
          <v-icon size="small" class="me-2" color="primary" @click="editar(item)">mdi-pencil</v-icon>
          <v-icon size="small" color="error" @click="excluir(item)">mdi-delete</v-icon>
        </template>
      </v-data-table>
    </v-card>

    <!-- Modal de Cadastro / Edição -->
    <v-dialog v-model="dialog" max-width="700px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">{{ form.id ? 'Editar Vidraria' : 'Nova Vidraria' }}</span>
          <v-btn icon variant="text" @click="dialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        
        <v-divider class="mb-4"></v-divider>

        <v-card-text>
          <v-form>
            <v-row>
              <v-col cols="12" md="8">
                <v-text-field v-model="form.nome" label="Nome do Item *" variant="outlined" density="comfortable" placeholder="Ex.: Béquer de 250ml"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.codigo" label="Código CATMAT *" variant="outlined" density="comfortable" placeholder="Ex.: VID-01"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.capacidade" label="Capacidade *" variant="outlined" density="comfortable" placeholder="Ex.: 250 mL"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.quantidade" label="Quantidade em Estoque *" type="number" variant="outlined" density="comfortable"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select v-model="form.estado" :items="['Inteiro', 'Danificado/Quebrado']" label="Estado de Conservação *" variant="outlined" density="comfortable"></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.localizacao" label="Localização (Armário/Prateleira)" variant="outlined" density="comfortable" placeholder="Ex.: Armário de Vidrarias 03"></v-text-field>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialog = false">Cancelar</v-btn>
          <v-btn color="#004A26" class="text-none px-6 text-white" @click="salvar">Salvar Vidraria</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import ModalConfirmacao from '../components/ModalConfirmacao.vue';
import axios from 'axios';

export default {
  name: 'Vidrarias',
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
        { title: 'Nome do Item', key: 'nome', align: 'start' },
        { title: 'Código', key: 'codigo' },
        { title: 'Capacidade', key: 'capacidade' },
        { title: 'Quantidade', key: 'quantidade' },
        { title: 'Estado', key: 'estado' },
        { title: 'Localização', key: 'localizacao' },
        { title: 'Ações', key: 'acoes', sortable: false, align: 'end' },
      ],
      vidrarias: [], // Começa vazio e puxa do banco
      form: { id: null, nome: '', codigo: '', capacidade: '', quantidade: '', estado: 'Inteiro', localizacao: '' }
    }
  },
  mounted() {
    this.carregarVidrarias(); // Assim que a página abre, busca do banco
  },
  methods: {
    async carregarVidrarias() {
      try {
        const resposta = await axios.get('http://127.0.0.1:8000/api/vidrarias');
        this.vidrarias = resposta.data;
      } catch (error) {
        console.error('Erro ao carregar vidrarias:', error);
      }
    },
    abrirModalCadastro() {
      this.form = { id: null, nome: '', codigo: '', capacidade: '', quantidade: '', estado: 'Inteiro', localizacao: '' };
      this.dialog = true;
    },
    async salvar() {
      try {
        if (this.form.id) {
          // Se tem ID, atualiza (PUT)
          await axios.put(`http://127.0.0.1:8000/api/vidrarias/${this.form.id}`, this.form);
        } else {
          // Se não tem ID, cria novo (POST)
          await axios.post('http://127.0.0.1:8000/api/vidrarias', this.form);
        }
        this.dialog = false;
        this.carregarVidrarias(); // Atualiza a tabela na hora
      } catch (error) {
        console.error('Erro ao salvar:', error);
        alert('Erro ao salvar no banco. Verifique os campos.');
      }
    },
    emitirRelatorio() {
      const janela = window.open('', '', 'width=900,height=650');
      
      const conteudoHTML = `
        <html>
          <head>
            <title>Relatório de Vidrarias - LabStock</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
              h2 { text-align: center; color: #004A26; margin-bottom: 5px; }
              p.sub { text-align: center; color: #666; margin-top: 0; margin-bottom: 30px; font-size: 14px; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; }
              th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 12px; }
              th { background-color: #004A26; color: white; }
              tr:nth-child(even) { background-color: #f9f9f9; }
              .footer { margin-top: 40px; text-align: right; font-size: 11px; color: #777; }
            </style>
          </head>
          <body>
            <h2>LabStock - Relatório de Vidrarias</h2>
            <p class="sub">Sistema de Gestão para Laboratórios Acadêmicos</p>
            
            <table>
              <thead>
                <tr>
                  <th>Nome do Item</th>
                  <th>Código</th>
                  <th>Capacidade</th>
                  <th>Quantidade</th>
                  <th>Estado</th>
                  <th>Localização</th>
                </tr>
              </thead>
              <tbody>
                ${this.vidrarias.map(v => `
                  <tr>
                    <td>${v.nome}</td>
                    <td>${v.codigo}</td>
                    <td>${v.capacidade || '-'}</td>
                    <td>${v.quantidade}</td>
                    <td><b>${v.estado}</b></td>
                    <td>${v.localizacao || '-'}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>

            <div class="footer">
              Gerado em ${new Date().toLocaleDateString('pt-BR')} às ${new Date().toLocaleTimeString('pt-BR')}
            </div>

            ${'<' + 'script>'}
              window.onload = function() {
                window.print();
                window.close();
              }
            ${'<' + '/script>'}
          </body>
        </html>
      `;

      janela.document.write(conteudoHTML);
      janela.document.close();
    },
    editar(item) {
      this.form = { ...item }; // Copia os dados para o formulário
      this.dialog = true; // Abre o modal
    },
    excluir(item) {
      this.itemParaExcluir = item;
      this.dialogExcluir = true;
    },
    async deletarItemConfirmado() {
      if (this.itemParaExcluir) {
        try {
          await axios.delete(`http://127.0.0.1:8000/api/vidrarias/${this.itemParaExcluir.id}`);
          this.carregarVidrarias(); // Atualiza a tabela
          this.itemParaExcluir = null;
        } catch (error) {
          console.error('Erro ao excluir:', error);
          alert('Erro ao excluir o item.');
        }
      }
      this.dialogExcluir = false;
    }
  }
}
</script>