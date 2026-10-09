<template>
  <v-container fluid class="pa-6">
    <!-- Cabeçalho e Botões de Ação -->
    <CabecalhoPagina titulo="Usuários" subtitulo="Contas de acesso e perfis de permissão do sistema" icone="mdi-account-group">
      <v-btn color="primary" class="text-none text-white" prepend-icon="mdi-account-plus" @click="abrirModalCadastro">
        Cadastrar Usuário
      </v-btn>
      <v-btn color="blue-darken-2" variant="tonal" class="text-none" prepend-icon="mdi-sync" @click="carregarUsuarios">
        Atualizar
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

    <!-- Legenda dos perfis -->
    <v-row class="mb-2">
      <v-col v-for="p in legendaPerfis" :key="p.value" cols="12" md="4">
        <v-card variant="tonal" :color="p.cor" class="pa-3 rounded-lg h-100">
          <div class="d-flex align-center mb-1">
            <v-icon :icon="p.icone" size="small" class="me-2"></v-icon>
            <span class="font-weight-bold text-body-2">{{ p.title }}</span>
          </div>
          <p class="text-caption mb-0">{{ p.descricao }}</p>
        </v-card>
      </v-col>
    </v-row>

    <!-- Tabela de Usuários -->
    <v-card class="elevation-1 rounded-lg">
      <v-data-table
        :headers="headers"
        :items="usuarios"
        :search="search"
        :loading="loading"
        class="pa-2"
      >
        <template v-slot:top>
          <v-toolbar flat class="px-4 bg-white">
            <v-text-field
              v-model="search"
              append-inner-icon="mdi-magnify"
              label="Pesquisar usuário..."
              single-line
              hide-details
              density="compact"
              variant="outlined"
              style="max-width: 300px;"
            ></v-text-field>
          </v-toolbar>
        </template>

        <template v-slot:[`item.tipo`]="{ item }">
          <v-chip :color="perfilInfo(item.tipo).cor" size="small" variant="tonal" :prepend-icon="perfilInfo(item.tipo).icone">
            {{ perfilInfo(item.tipo).title }}
          </v-chip>
        </template>

        <template v-slot:[`item.ativo`]="{ item }">
          <v-chip :color="item.ativo ? 'success' : 'error'" size="small">
            {{ item.ativo ? 'Ativo' : 'Inativo' }}
          </v-chip>
        </template>

        <template v-slot:[`item.acoes`]="{ item }">
          <v-icon size="small" class="me-2" color="primary" @click="editar(item)">mdi-pencil</v-icon>
          <v-icon v-if="item.id !== auth.usuario?.id" size="small" color="error" @click="excluir(item)">mdi-delete</v-icon>
        </template>
      </v-data-table>
    </v-card>

    <!-- Modal de Cadastro / Edição -->
    <v-dialog v-model="dialog" max-width="700px" persistent>
      <v-card class="rounded-lg pa-4">
        <v-card-title class="d-flex justify-space-between align-center">
          <span class="text-h6 font-weight-bold">{{ form.id ? 'Editar Usuário' : 'Novo Usuário' }}</span>
          <v-btn icon variant="text" @click="dialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>

        <v-divider class="mb-4"></v-divider>

        <v-card-text>
          <v-alert v-if="erroForm" type="error" variant="tonal" density="compact" class="mb-4">
            {{ erroForm }}
          </v-alert>

          <v-form ref="formRef">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.nome"
                  label="Nome completo *"
                  variant="outlined"
                  density="comfortable"
                  :rules="[regras.obrigatorio]"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.email"
                  label="E-mail *"
                  type="email"
                  variant="outlined"
                  density="comfortable"
                  autocomplete="off"
                  :rules="[regras.obrigatorio, regras.email]"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-text-field
                  v-model="form.password"
                  :label="form.id ? 'Nova senha (deixe em branco para manter)' : 'Senha *'"
                  :type="mostrarSenha ? 'text' : 'password'"
                  :append-inner-icon="mostrarSenha ? 'mdi-eye-off' : 'mdi-eye'"
                  variant="outlined"
                  density="comfortable"
                  autocomplete="new-password"
                  :rules="form.id ? [regras.senhaOpcional] : [regras.obrigatorio, regras.senhaOpcional]"
                  @click:append-inner="mostrarSenha = !mostrarSenha"
                ></v-text-field>
              </v-col>

              <v-col cols="12" md="6">
                <v-select
                  v-model="form.tipo"
                  :items="PERFIS"
                  item-title="title"
                  item-value="value"
                  label="Perfil de acesso *"
                  variant="outlined"
                  density="comfortable"
                  :disabled="editandoProprioUsuario"
                  :hint="editandoProprioUsuario ? 'Você não pode alterar o seu próprio perfil' : ''"
                  persistent-hint
                ></v-select>
              </v-col>

              <v-col cols="12">
                <v-switch
                  v-model="form.ativo"
                  color="primary"
                  label="Usuário ativo"
                  hide-details
                  inset
                  :disabled="editandoProprioUsuario"
                ></v-switch>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="px-4 pb-4">
          <v-spacer></v-spacer>
          <v-btn variant="outlined" color="grey-darken-1" class="text-none" @click="dialog = false">
            Cancelar
          </v-btn>
          <v-btn color="primary" variant="flat" class="text-none px-6 text-white" :loading="salvando" @click="salvar">
            Salvar Usuário
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api, { mensagemErro } from '../plugins/axios';
import ModalConfirmacao from '../components/ModalConfirmacao.vue';
import CabecalhoPagina from '../components/CabecalhoPagina.vue';
import { useAuthStore, PERFIS } from '../stores/authStore';

const auth = useAuthStore();

const legendaPerfis = [
  { ...PERFIS[0], cor: 'deep-purple', icone: 'mdi-shield-crown', descricao: 'Acesso total, incluindo o cadastro de usuários e definição de perfis.' },
  { ...PERFIS[1], cor: 'green-darken-3', icone: 'mdi-flask', descricao: 'Cadastra, edita e movimenta reagentes, vidrarias e equipamentos.' },
  { ...PERFIS[2], cor: 'blue-grey', icone: 'mdi-eye', descricao: 'Apenas visualiza o estoque e emite relatórios.' },
];
const perfilInfo = (tipo) => legendaPerfis.find(p => p.value === tipo) || { title: tipo, cor: 'grey', icone: 'mdi-account' };

const headers = [
  { title: 'Nome', key: 'nome', align: 'start' },
  { title: 'E-mail', key: 'email' },
  { title: 'Perfil', key: 'tipo' },
  { title: 'Status', key: 'ativo' },
  { title: 'Ações', key: 'acoes', sortable: false, align: 'end' },
];

const formVazio = () => ({ id: null, nome: '', email: '', password: '', tipo: 'tecnico', ativo: true });

const usuarios = ref([]);
const search = ref('');
const loading = ref(false);
const salvando = ref(false);
const dialog = ref(false);
const dialogExcluir = ref(false);
const itemParaExcluir = ref(null);
const mostrarSenha = ref(false);
const erroForm = ref('');
const formRef = ref(null);
const form = ref(formVazio());
const snackbar = ref({ ativo: false, texto: '', cor: 'success' });

const editandoProprioUsuario = computed(() => !!form.value.id && form.value.id === auth.usuario?.id);

const regras = {
  obrigatorio: (v) => !!v || 'Campo obrigatório',
  email: (v) => /.+@.+\..+/.test(v) || 'E-mail inválido',
  senhaOpcional: (v) => !v || v.length >= 8 || 'A senha deve ter no mínimo 8 caracteres',
};

const avisar = (texto, cor = 'success') => {
  snackbar.value = { ativo: true, texto, cor };
};

const carregarUsuarios = async () => {
  loading.value = true;
  try {
    const resposta = await api.get('/usuarios');
    usuarios.value = resposta.data;
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao carregar usuários.'), 'error');
  } finally {
    loading.value = false;
  }
};

const abrirModalCadastro = () => {
  form.value = formVazio();
  erroForm.value = '';
  mostrarSenha.value = false;
  dialog.value = true;
};

const editar = (item) => {
  form.value = { ...item, password: '', ativo: !!item.ativo };
  erroForm.value = '';
  mostrarSenha.value = false;
  dialog.value = true;
};

const salvar = async () => {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  salvando.value = true;
  erroForm.value = '';
  const dados = {
    nome: form.value.nome,
    email: form.value.email,
    tipo: form.value.tipo,
    ativo: form.value.ativo,
  };
  if (form.value.password) dados.password = form.value.password;

  try {
    if (form.value.id) {
      await api.put(`/usuarios/${form.value.id}`, dados);
      avisar('Usuário atualizado com sucesso.');
    } else {
      await api.post('/usuarios', dados);
      avisar('Usuário cadastrado com sucesso.');
    }
    dialog.value = false;
    carregarUsuarios();
  } catch (err) {
    erroForm.value = mensagemErro(err, 'Erro ao salvar usuário.');
  } finally {
    salvando.value = false;
  }
};

const excluir = (item) => {
  itemParaExcluir.value = item;
  dialogExcluir.value = true;
};

const deletarItemConfirmado = async () => {
  if (!itemParaExcluir.value) return;
  try {
    await api.delete(`/usuarios/${itemParaExcluir.value.id}`);
    avisar('Usuário removido com sucesso.');
    carregarUsuarios();
  } catch (err) {
    avisar(mensagemErro(err, 'Erro ao excluir usuário.'), 'error');
  }
  itemParaExcluir.value = null;
};

onMounted(carregarUsuarios);
</script>
