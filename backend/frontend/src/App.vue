<template>
  <v-app class="bg-background">
    <Sidebar nomeUsuario="Cindy Lopes" />

    <v-main>
      <v-container fluid>
        <div class="d-flex justify-space-between align-center mb-5">
          <h1>Inventário de Produtos</h1>
          <BotaoInserir @clique="dialog = true" />
        </div>

        <v-card class="pa-4" elevation="0">
          <TabelaItens />
        </v-card>

        <v-dialog v-model="dialog" max-width="500px">
          <v-card>
            <v-card-title>Novo Produto</v-card-title>
            <v-card-text>
              <v-text-field v-model="novoItem.nome" label="Nome do Produto"></v-text-field>
              <v-text-field v-model="novoItem.quantidade" label="Quantidade"></v-text-field>
            </v-card-text>
            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn color="error" @click="dialog = false">Cancelar</v-btn>
              <v-btn color="primary" @click="salvarProduto">Salvar</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref } from 'vue';
import Sidebar from './components/Sidebar.vue';
import TabelaItens from './components/TabelaItens.vue';
import BotaoInserir from './components/BotaoInserir.vue';
import axios from 'axios';

const dialog = ref(false);
const novoItem = ref({ nome: '', quantidade: '' });

const salvarProduto = async () => {
  try {
    await axios.post('http://127.0.0.1:8000/api/itens', novoItem.value);
    dialog.value = false;
    alert('Sucesso!');
    window.location.reload();
  } catch (error) {
    console.error(error);
  }
};
</script>