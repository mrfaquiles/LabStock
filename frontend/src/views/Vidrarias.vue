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

        <!-- Status / Ativo -->
        <template v-slot:[`item.ativo`]="{ item }">
          <v-chip :color="item.ativo ? 'success' : 'error'" size="small">
            {{ item.ativo ? 'Ativo' : 'Inativo' }}
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
                <v-text-field v-model="form.descricao" label="Descrição *" variant="outlined" density="comfortable" placeholder="Ex.: Béquer de 250ml"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.catmat" label="Código CATMAT *" variant="outlined" density="comfortable" placeholder="Ex.: VID-01"></v-text-field>
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
          <v-btn color="#004A26" class="text-none px-6 text-white" @click="salvar">Salvar Vidraria</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import ModalConfirmacao from '../components/ModalConfirmacao.vue';
import { useVidrariaStore } from '../stores/vidrariaStore.js';
import { ref, onMounted } from 'vue';

const vidrariaStore = useVidrariaStore();

const dialog = ref(false);
const dialogExcluir = ref(false);
const itemParaExcluir = ref(null);
const form = ref({ idvidraria: null, nome: '', descricao: '', quantidade: 0, catmat: '', ativo: true });
const search = ref('');
const headers = [
  { text: 'Nome do Item', value: 'nome' },
  { text: 'Código', value: 'catmat' },
  { text: 'Ativo', value: 'ativo' },
  { text: 'Ações', value: 'acoes', sortable: false }
];

onMounted(() => {
  vidrariaStore.fetchVidrarias();
})

const carregarVidrarias = () => {
  vidrariaStore.fetchVidrarias();
}

const abrirModalCadastro = () => {
    form.value = { idvidraria: null, nome: '', descricao: '', quantidade: 0, catmat: '', ativo: true };
    dialog.value = true;
}

const salvar = async () => {
    try {
      if (form.value.idvidraria) {
        // Se tem ID, atualiza (PUT)
        await vidrariaStore.updateVidraria(form.value.idvidraria, form.value);
      } else {
        // Se não tem ID, cria novo (POST)
        await vidrariaStore.createVidraria(form.value);
      }
      dialog.value = false;
      vidrariaStore.fetchVidrarias(); // Atualiza a tabela na hora
    } catch (error) {
      console.error('Erro ao salvar:', error);
      alert('Erro ao salvar no banco. Verifique os campos.');
    }
}

const emitirRelatorio = () => {
    const janela = window.open('', '', 'width=900,height=650');

    if (!janela) {
      alert('Não foi possível abrir a janela do relatório. Verifique se o navegador está bloqueando pop-ups para este site.');
      return;
    }

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
                <th>Quantidade</th>
              </tr>
            </thead>
            <tbody>
              ${vidrariaStore.getAllVidrarias.map(v => `
                <tr>
                  <td>${v.nome}</td>
                  <td>${v.catmat}</td>
                  <td>${v.quantidade}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>

          <div class="footer">
            Gerado em ${new Date().toLocaleDateString('pt-BR')} às ${new Date().toLocaleTimeString('pt-BR')}
          </div>
        </body>
      </html>
    `;

    //console.log('Conteúdo do relatório:', conteudoHTML); // Log para depuração
    janela.document.write(conteudoHTML);
    janela.document.close();
  }

const editar = (item) => {
  item.ativo = item.ativo === 1 ? true : false; // Converte para booleano
  form.value = { ...item }; // Copia os dados para o formulário
  dialog.value = true; // Abre o modal
}

const excluir = (item) => {
  itemParaExcluir.value = item;
  dialogExcluir.value = true;
}

const deletarItemConfirmado = async () => {
  if (itemParaExcluir.value) {
    try {
      await vidrariaStore.deleteVidraria(itemParaExcluir.value.idvidraria);
      vidrariaStore.fetchVidrarias(); // Atualiza a tabela
      itemParaExcluir.value = null;
    } catch (error) {
      console.error('Erro ao excluir:', error);
      alert('Erro ao excluir o item.');
    }
  }
  dialogExcluir.value = false;
}
</script>