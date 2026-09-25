<template>
  <v-container fluid class="pa-6">
    <!-- Cabeçalho e Botões de Ação -->
    <v-row class="mb-6 align-center">
      <v-col cols="12" md="6">
        <h1 class="text-h5 font-weight-bold" style="color: #004A26;">Gerenciamento de Reagentes</h1>
        <p class="text-subtitle-2 text-grey-darken-1">Controle de estoque, lotes e validades de reagentes químicos</p>
      </v-col>
      <v-col cols="12" md="6" class="text-md-right">
        <v-btn color="#004A26" class="text-none me-2 mb-2 text-white" prepend-icon="mdi-plus" @click="abrirModalCadastro">
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

        <!-- Unidade de Medida -->
        <template v-slot:[`item.unidade_medida`]="{ item }">
          <span>{{ item.unidade_medida?.sigla || item.unidade_medida?.nome || '-' }}</span>
        </template>

        <!-- Data de Validade com Alerta Visual -->
        <template v-slot:[`item.data_validade`]="{ item }">
          <v-chip v-if="item.data_validade" :color="verificarCorValidade(item)" size="small" variant="tonal">
            {{ formatarDataExibicao(item.data_validade) }}
          </v-chip>
          <span v-else>-</span>
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
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editedItem.catmat"
                  label="Código CATMAT *"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: 123456"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editedItem.quantidade"
                  label="Quantidade (Ex: 0.5) *"
                  type="number"
                  step="0.001"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: 0.5"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="4">
                <v-select
                  v-model="editedItem.idunidademedida"
                  :items="unidadesMedida"
                  item-title="nome"
                  item-value="idunidademedida"
                  label="Unidade de Medida *"
                  variant="outlined"
                  density="comfortable"
                  no-data-text="Nenhuma unidade cadastrada"
                  placeholder="Selecione"
                ></v-select>
              </v-col>

              <v-col cols="12" md="4">
                <v-text-field
                  v-model="editedItem.lote"
                  label="Número do Lote *"
                  variant="outlined"
                  density="comfortable"
                  placeholder="Ex.: LOT-2026-A"
                  autocomplete="off"
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
                  variant="outlined"
                  density="comfortable"
                  placeholder="Padrão: 4 meses"
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
          <v-btn color="#004A26" class="text-none px-6 text-white" @click="salvarReagente">
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
      menuData: false,
      itemParaExcluir: null,
      dataValidadeObj: null,
      headers: [
        { title: 'Nome do Reagente', key: 'nome', align: 'start' },
        { title: 'CATMAT', key: 'catmat' },
        { title: 'Quantidade', key: 'quantidade' },
        { title: 'Unidade', key: 'unidade_medida' },
        { title: 'Lote', key: 'lote' },
        { title: 'Validade', key: 'data_validade' },
        { title: 'Localização', key: 'localizacao' },
        { title: 'Ações', key: 'acoes', sortable: false, align: 'end' },
      ],
      reagentes: [],
      unidadesMedida: [
        { idunidademedida: 1, nome: 'Quilograma (kg)' },
        { idunidademedida: 2, nome: 'Litro (L)' },
        { idunidademedida: 3, nome: 'Unidade (un)' }
      ],
      editedItem: {
        idreagente: null,
        nome: '',
        catmat: '',
        quantidade: '',
        idunidademedida: null,
        lote: '',
        data_validade: '',
        localizacao: '',
        meses_alerta: 4,
        descricao: '',
        ativo: 1
      }
    }
  },
  computed: {
    dataMinima() {
      return new Date();
    }
  },
  watch: {
    dataValidadeObj(novaData) {
      if (novaData) {
        const ano = novaData.getFullYear();
        const mes = String(novaData.getMonth() + 1).padStart(2, '0');
        const dia = String(novaData.getDate()).padStart(2, '0');
        this.editedItem.data_validade = `${ano}-${mes}-${dia}`;
      } else {
        this.editedItem.data_validade = '';
      }
    }
  },
  mounted() {
    this.carregarReagentes();
    this.carregarUnidadesMedida();
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
    async carregarUnidadesMedida() {
      try {
        const resposta = await axios.get('http://127.0.0.1:8000/api/unidades-medida');
        if (resposta.data && resposta.data.length > 0) {
          this.unidadesMedida = resposta.data.map(u => ({
            idunidademedida: u.idunidademedida || u.id,
            nome: `${u.nome} (${u.sigla})`
          }));
        }
      } catch (erro) {
        console.warn('Usando unidades padrão de segurança:', erro);
      }
    },
    abrirModalCadastro() {
      this.editedItem = { 
        idreagente: null, 
        nome: '', 
        catmat: '', 
        quantidade: '', 
        idunidademedida: null, 
        lote: '', 
        data_validade: '', 
        localizacao: '', 
        meses_alerta: 4,
        descricao: '',
        ativo: 1 
      };
      this.dataValidadeObj = null;
      this.dialog = true;
    },
    async salvarReagente() {
      if (!this.editedItem.data_validade) {
        alert('Por favor, selecione a data de validade!');
        return;
      }

      try {
        if (this.editedItem.idreagente) {
          await axios.put(`http://127.0.0.1:8000/api/reagentes/${this.editedItem.idreagente}`, this.editedItem);
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
      this.editedItem = { ...item, meses_alerta: item.meses_alerta || 4 };
      if (item.data_validade) {
        this.dataValidadeObj = new Date(item.data_validade + 'T00:00:00');
      } else {
        this.dataValidadeObj = null;
      }
      this.dialog = true;
    },
    excluirReagente(item) {
      this.itemParaExcluir = item;
      this.dialogExcluir = true;
    },
    async deletarItemConfirmado() {
      if (this.itemParaExcluir) {
        try {
          await axios.delete(`http://127.0.0.1:8000/api/reagentes/${this.itemParaExcluir.idreagente}`);
          this.carregarReagentes();
        } catch (erro) {
          console.error('Erro ao excluir reagente:', erro);
        }
        this.itemParaExcluir = null;
      }
      this.dialogExcluir = false;
    },
    formatarDataExibicao(dataStr) {
      if (!dataStr) return '';
      const partes = dataStr.split('-');
      if (partes.length === 3) {
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
      }
      return dataStr;
    },
    verificarCorValidade(item) {
      if (!item.data_validade) return 'success';
      const hoje = new Date();
      const validade = new Date(item.data_validade + 'T00:00:00');
      const mesesMargem = Number(item.meses_alerta) || 4;
      const diffMeses = (validade.getFullYear() - hoje.getFullYear()) * 12 + (validade.getMonth() - hoje.getMonth());

      if (validade < hoje) return 'error'; // Vencido (Vermelho)
      if (diffMeses <= mesesMargem) return 'warning'; // Próximo de vencer (Amarelo)
      return 'success'; // OK (Verde)
    },
    emitirRelatorio() {
      const janela = window.open('', '', 'width=900,height=650');
      
      const conteudoHTML = `
        <html>
          <head>
            <title>Relatório de Reagentes - LabStock</title>
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
            <h2>LabStock - Relatório de Reagentes</h2>
            <p class="sub">Sistema de Gestão para Laboratórios Acadêmicos</p>
            
            <table>
              <thead>
                <tr>
                  <th>Nome do Reagente</th>
                  <th>CATMAT</th>
                  <th>Quantidade</th>
                  <th>Lote</th>
                  <th>Validade</th>
                  <th>Localização</th>
                </tr>
              </thead>
              <tbody>
                ${this.reagentes.map(r => `
                  <tr>
                    <td>${r.nome}</td>
                    <td>${r.catmat || '-'}</td>
                    <td>${r.quantidade || '0'}</td>
                    <td>${r.lote || '-'}</td>
                    <td>${this.formatarDataExibicao(r.data_validade)}</td>
                    <td>${r.localizacao || '-'}</td>
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
    atualizarEstoque() {
      this.carregarReagentes();
    }
  }
}
</script>