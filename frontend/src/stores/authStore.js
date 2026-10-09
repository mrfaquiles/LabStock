import { defineStore } from 'pinia';
import api, { TOKEN_KEY, USUARIO_KEY } from '../plugins/axios';

const lerUsuarioSalvo = () => {
  try {
    return JSON.parse(localStorage.getItem(USUARIO_KEY));
  } catch {
    return null;
  }
};

export const PERFIS = [
  { value: 'admin', title: 'Administrador' },
  { value: 'tecnico', title: 'Técnico de Laboratório' },
  { value: 'consulta', title: 'Consulta' },
];

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem(TOKEN_KEY),
    usuario: lerUsuarioSalvo(),
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    perfil: (state) => state.usuario?.tipo || null,
    nomePerfil: (state) => PERFIS.find(p => p.value === state.usuario?.tipo)?.title || '',
    isAdmin: (state) => state.usuario?.tipo === 'admin',
    // Admin e técnico podem cadastrar, editar e excluir itens de estoque
    podeEditar: (state) => ['admin', 'tecnico'].includes(state.usuario?.tipo),
    temPerfil: (state) => (perfis) => perfis.includes(state.usuario?.tipo),
  },

  actions: {
    async login(email, password) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/login', { email, password });
        this.salvarSessao(response.data.token, response.data.usuario);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message
          || (err.response ? 'Erro ao realizar login.' : 'Não foi possível conectar ao servidor.');
        return false;
      } finally {
        this.loading = false;
      }
    },

    // Revalida o usuário no backend (perfil pode ter sido alterado pelo admin)
    async fetchMe() {
      if (!this.token) return false;
      try {
        const response = await api.get('/me');
        this.usuario = response.data;
        localStorage.setItem(USUARIO_KEY, JSON.stringify(response.data));
        return true;
      } catch {
        return false;
      }
    },

    async logout() {
      try {
        await api.post('/logout');
      } catch {
        // Token já inválido no servidor: basta limpar localmente
      } finally {
        this.limparSessao();
      }
    },

    salvarSessao(token, usuario) {
      this.token = token;
      this.usuario = usuario;
      localStorage.setItem(TOKEN_KEY, token);
      localStorage.setItem(USUARIO_KEY, JSON.stringify(usuario));
    },

    limparSessao() {
      this.token = null;
      this.usuario = null;
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(USUARIO_KEY);
    },
  }
});
