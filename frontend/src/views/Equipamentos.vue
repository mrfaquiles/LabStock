<template>
  <v-container fluid class="pa-6">
    <!-- Cabeçalho e Botões de Ação -->
    <CabecalhoPagina titulo="Equipamentos" subtitulo="Controle de balanças, centrífugas, microscópios e maquinário" icone="mdi-tools">
      <v-btn v-if="auth.podeEditar" color="primary" class="text-none text-white" prepend-icon="mdi-plus" @click="abrirModalCadastro">
        Cadastrar Equipamento
      </v-btn>
      <v-btn color="grey-darken-2" variant="outlined" class="text-none" prepend-icon="mdi-file-pdf-box" @click="emitirRelatorio">
        Emitir Relatório
      </v-btn>
      <v-btn color="blue-darken-2" variant="tonal" class="text-none" prepend-icon="mdi-sync" @click="carregarEquipamentos">
        Atualizar
      </v-btn>
    </CabecalhoPagina>

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
          <v-icon v-if="auth.podeEditar" size="small" class="me-2" color="primary" @click="editar(item)">mdi-pencil</v-icon>
          <v-icon v-if="auth.podeEditar" size="small" color="error" @click="excluir(item)">mdi-delete</v-icon>
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
                <v-text-field v-model="form.catmat" label="Código CATMAT *" variant="outlined" density="comfortable" placeholder="Ex.: 123456"></v-text-field>
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
          <v-btn color="primary" variant="flat" class="text-none px-6 text-white" @click="salvar">Salvar Equipamento</v-btn>
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

export default {
  name: 'Equipamentos',
  components: {
    ModalConfirmacao,
    CabecalhoPagina
  },
  setup() {
    return { auth: useAuthStore() };
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
        const resposta = await api.get('/equipamentos');
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
          await api.put(`/equipamentos/${this.form.idequipamento}`, this.form);
        } else {
          await api.post('/equipamentos', this.form);
        }
        this.carregarEquipamentos();
        this.dialog = false;
      } catch (erro) {
        alert(mensagemErro(erro, 'Erro ao salvar equipamento.'));
      }
    },
    // PDF gerado direto no navegador (o antigo window.open era bloqueado como pop-up)
    emitirRelatorio() {
      baixarPdf({
        titulo: 'Relatório de Equipamentos',
        subtitulo: `Posição em ${new Date().toLocaleDateString('pt-BR')}`,
        nomeArquivo: `labstock-equipamentos-${new Date().toISOString().slice(0, 10)}`,
        tabelas: [{
          colunas: ['Nome do Equipamento', 'Nº Patrimônio', 'CATMAT', 'Status', 'Localização'],
          linhas: this.equipamentos.map(e => [e.nome, e.patrimonio || '-', e.catmat || '-', e.status || '-', e.localizacao || '-']),
        }],
      });
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
          await api.delete(`/equipamentos/${this.itemParaExcluir.idequipamento}`);
          this.carregarEquipamentos();
        } catch (erro) {
          alert(mensagemErro(erro, 'Erro ao excluir equipamento.'));
        }
        this.itemParaExcluir = null;
      }
      this.dialogExcluir = false;
    }
  }
}
</script>