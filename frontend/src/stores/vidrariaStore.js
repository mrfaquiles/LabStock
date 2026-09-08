import { defineStore } from 'pinia';
import api  from '../plugins/axios';


export const useVidrariaStore = defineStore('vidraria', {
  // `state` é onde você define os dados reativos do seu store.
  state: () => ({
    vidararias: [], // Array para armazenar todos os vidararias
    selectedVidraria: null, // Para uma vidraria específica selecionada/editada
    loading: false,      // Para gerenciar o estado de carregamento da API
    error: null,         // Para armazenar erros da API
  }),

  // `getters` são como propriedades computadas para seu store.
  getters: {
    getAllVidrarias: (state) => state.vidararias,
    getActiveVidrarias: (state) => state.vidararias.filter(vidraria => vidraria.ativo),
    getVidrariaById: (state) => (id) => state.vidararias.find(vidraria => vidraria.id_vidraria === id),
    getSelectedVidraria: (state) => state.selectedVidraria,
    isLoadingVidrarias: (state) => state.loading,
    getVidrariaErrors: (state) => state.error,
    getTotal: (state) => state.length,
  },

  // `actions` são onde você define métodos para interagir com a API e alterar o estado.
  actions: {
    // Listando todas as vidrarias
    async fetchVidrarias() {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.get('/vidrarias');
        this.vidararias = response.data; 
        console.log('Vidrarias carregadas:', this.vidararias);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erro ao carregar vidrarias.';
        console.error('Erro ao buscar vidrarias:', err);
        return false;
      } finally {
        this.loading = false;
      }
    },

    //Novo evento
    async createVidraria(vidraria) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/vidrarias', {
          nome: vidraria.nome, //Atributos da entidade vidrarias
          descricao: vidraria.descricao,
          quantidade: vidraria.quantidade,
          catmat: vidraria.catmat,
          ativo: vidraria.ativo==1 ? "1" : "0",
        });

        //Colocando o retorno da API no array de vidrarias
        this.vidararias.push(response.data);
        console.log('Vidraria criada com sucesso:', response.data);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erro ao criar vidraria.';
        console.error('Erro ao criar vidraria:', err);
        return false;
      } finally {
        this.loading = false;
      }
    },

    async updateVidraria(id_vidraria, updated) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put(`/vidrarias/${id_vidraria}`, {
          nome: updated.nome,
          descricao: updated.descricao,
          quantidade: updated.quantidade,
          catmat: updated.catmat,
          ativo: updated.ativo==1 ? "1" : "0",	
        });

        //Atualizando os dados na Array local
        const index = this.vidararias.findIndex(vidraria => vidraria.id_vidraria === id_vidraria );
        if (index !== -1) {
          this.vidararias[index] = { ...this.vidararias[index], ...response.data };
        }
        //console.log('Vidraria atualizada com sucesso:', response.data);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erro ao atualizar vidraria.';
        console.error('Erro ao atualizar vidraria:', err);
        return false;
      } finally {
        this.loading = false;
      }
    },

    async deleteVidraria(id_vidraria) {
      this.loading = true;
      this.error = null;
      try {
        //Apagando na API
        await api.delete(`/vidrarias/${id_vidraria}`);

        //Remove o evento do array local
        this.vidararias = this.vidararias.filter(vidraria => vidraria.id_vidraria !== id_vidraria);
        //console.log(`Vidraria com ID ${id_vidraria} deletada com sucesso.`);
        return true;
      } catch (err) {
        this.error = err.response?.data?.message || 'Erro ao deletar vidraria.';
        //console.error('Erro ao deletar vidraria :', err);
        return false;
      } finally {
        this.loading = false;
      }
    },

    // Ação para selecionar uma vidraria (útil para formulários de edição)
    selectVidraria(id_vidraria) {
      this.selectedVidraria = this.getVidrariaById(id_vidraria);
    },

    // Ação para limpar a vidraria selecionada
    clearSelectedVidraria() {
      this.selectedVidraria = null;
    }
  }
});