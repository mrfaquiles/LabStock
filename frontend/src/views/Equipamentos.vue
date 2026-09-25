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
        <v-btn color="blue-darken-3" variant="tonal" class="text-none" prepend-icon="mdi-sync" @click="carregarEquipamentos">
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

    <!-- Modal de Cadastro / Edição -->
    <v-dialog v-model="dialog" max-width="700px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">{{ form.idequipamento ? 'Editar Equipamento' : 'Novo Equipamento' }}</span>
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
                <v-text-field v-model="form.catmat" label="Código CATMAT" variant="outlined" density="comfortable" placeholder="Ex.: 123456"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select v-model="form.status" :items="['Operacional', 'Em Manutenção', 'Inativo']" label="Status de Operação *" variant="outlined" density="comfortable"></v-select>
              </v-col>
              <v-col cols="12">
                <v-text-field v-model="form.localizacao" label="Laboratório / Localização no Prédio" variant="outlined" density="comfortable" placeholder="Ex.: Lab de Química Geral, Bancada 01"></v-text-field>
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
          <v-btn color="#0B132B" class="text-none px-6 text-white" @click="salvar">Salvar Equipamento</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script>
import axios from 'axios';
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
        { title: 'CATMAT', key: 'catmat' },
        { title: 'Status', key: 'status' },
        { title: 'Localização', key: 'localizacao' },
        { title: 'Ações', key: 'acoes', sortable: false, align: 'end' },
      ],
      equipamentos: [],
      form: { 
        idequipamento: null, 
        nome: '', 
        patrimonio: '', 
        catmat: '', 
        status: 'Operacional', 
        localizacao: '',
        descricao: '' 
      }
    }
  },
  mounted() {
    this.carregarEquipamentos();
  },
  methods: {
    async carregarEquipamentos() {
      try {
        const resposta = await axios.get('http://127.0.0.1:8000/api/equipamentos');
        this.equipamentos = resposta.data;
      } catch (erro) {
        console.error('Erro ao carregar equipamentos:', erro);
      }
    },
    abrirModalCadastro() {
      this.form = { 
        idequipamento: null, 
        nome: '', 
        patrimonio: '', 
        catmat: '', 
        status: 'Operacional', 
        localizacao: '',
        descricao: '' 
      };
      this.dialog = true;
    },
    async salvar() {
      try {
        if (this.form.idequipamento) {
          await axios.put(`http://127.0.0.1:8000/api/equipamentos/${this.form.idequipamento}`, this.form);
        } else {
          await axios.post('http://127.0.0.1:8000/api/equipamentos', this.form);
        }
        this.carregarEquipamentos();
        this.dialog = false;
      } catch (erro) {
        console.error('Erro ao salvar equipamento:', erro);
      }
    },
    emitirRelatorio() { 
      const janela = window.open('', '', 'width=900,height=650');
      
      const conteudoHTML = `
        <html>
          <head>
            <title>Relatório de Equipamentos - LabStock</title>
            <style>
              body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
              h2 { text-align: center; color: #0B132B; margin-bottom: 5px; }
              p.sub { text-align: center; color: #666; margin-top: 0; margin-bottom: 30px; font-size: 14px; }
              table { width: 100%; border-collapse: collapse; margin-top: 20px; }
              th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 12px; }
              th { background-color: #0B132B; color: white; }
              tr:nth-child(even) { background-color: #f9f9f9; }
              .footer { margin-top: 40px; text-align: right; font-size: 11px; color: #777; }
            </style>
          </head>
          <body>
            <h2>LabStock - Relatório de Equipamentos</h2>
            <p class="sub">Sistema de Gestão para Laboratórios Acadêmicos</p>
            
            <table>
              <thead>
                <tr>
                  <th>Nome do Equipamento</th>
                  <th>Nº Patrimônio</th>
                  <th>CATMAT</th>
                  <th>Status</th>
                  <th>Localização</th>
                </tr>
              </thead>
              <tbody>
                ${this.equipamentos.map(e => `
                  <tr>
                    <td>${e.nome}</td>
                    <td>${e.patrimonio || '-'}</td>
                    <td>${e.catmat || '-'}</td>
                    <td><b>${e.status}</b></td>
                    <td>${e.localizacao || '-'}</td>
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
      this.form = { ...item };
      this.dialog = true;
    },
    excluir(item) {
      this.itemParaExcluir = item;
      this.dialogExcluir = true;
    },
    async deletarItemConfirmado() {
      if (this.itemParaExcluir) {
        try {
          await axios.delete(`http://127.0.0.1:8000/api/equipamentos/${this.itemParaExcluir.idequipamento}`);
          this.carregarEquipamentos();
        } catch (erro) {
          console.error('Erro ao excluir equipamento:', erro);
        }
        this.itemParaExcluir = null;
      }
      this.dialogExcluir = false;
    }
  }
}
</script>