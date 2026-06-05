<template>
  <v-navigation-drawer 
    v-model="drawer" 
    permanent 
    style="background-color: #004A26 !important;" 
    theme="dark" 
    width="260"
  >
    <v-list-item title="LABSTOCK" prepend-icon="mdi-flask-outline" class="text-h5 font-weight-bold my-4"></v-list-item>
    
    <v-divider></v-divider>

    <v-list density="compact" nav>
      <v-list-subheader>MENU PRINCIPAL</v-list-subheader>
      <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" value="dashboard"></v-list-item>
      <v-list-item prepend-icon="mdi-package-variant" title="Produtos" value="produtos"></v-list-item>
      <v-list-item prepend-icon="mdi-clipboard-list" title="Estoque" value="estoque"></v-list-item>
      <v-list-item prepend-icon="mdi-truck" title="Fornecedores" value="fornecedores"></v-list-item>
      <v-list-item prepend-icon="mdi-swap-horizontal" title="Movimentações" value="mov"></v-list-item>
    </v-list>

    <v-divider></v-divider>

    <v-list density="compact" nav>
      <v-list-subheader>CONFIGURAÇÕES</v-list-subheader>
      <v-list-item prepend-icon="mdi-cog" title="Sistema" value="sistema"></v-list-item>
      <v-list-item prepend-icon="mdi-account-group" title="Usuários" value="usuarios"></v-list-item>
    </v-list>

    <template v-slot:append>
      <v-divider></v-divider>
      <v-list-item
        prepend-icon="mdi-account-circle"
        :title="nomeUsuario"
        subtitle="Admin"
        nav
      ></v-list-item>
    </template>
  </v-navigation-drawer>
</template>

<script setup>
import { ref } from 'vue';
const drawer = ref(true);
defineProps({ nomeUsuario: String });
</script>