import { defineStore } from 'pinia';
import api, { mensagemErro } from '../plugins/axios';

// Mesmos padrões do backend (App\Models\Configuracao::PADROES), usados até a API responder
const PADROES = {
  instituicao_nome: 'Instituto Federal',
  laboratorio_nome: 'Laboratório de Química',
  responsavel_tecnico: '',
  meses_alerta_padrao: 4,
  cobertura_minima_meses: 3,
  tempo_compra_meses: 6,
  margem_seguranca_percentual: 20,
  vidraria_exige_aprovacao: true,
  sessao_horas: 8,
};

export const useConfigStore = defineStore('config', {
  state: () => ({
    config: { ...PADROES },
    carregado: false,
    loading: false,
    error: null,
  }),

  getters: {
    // Linha de identificação usada no cabeçalho dos relatórios em PDF
    identificacao: (state) => [state.config.instituicao_nome, state.config.laboratorio_nome].filter(Boolean).join(' - '),
  },

  actions: {
    async carregar() {
      try {
        const response = await api.get('/configuracoes');
        this.config = { ...PADROES, ...response.data };
        this.carregado = true;
        return true;
      } catch {
        return false;
      }
    },

    async salvar(dados) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put('/configuracoes', dados);
        this.config = { ...PADROES, ...response.data };
        return true;
      } catch (err) {
        this.error = mensagemErro(err, 'Erro ao salvar as configurações.');
        return false;
      } finally {
        this.loading = false;
      }
    },
  },
});
