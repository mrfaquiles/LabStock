<template>
  <v-navigation-drawer 
    v-model="drawer" 
    permanent 
    style="background-color: #0B132B !important; color: #FFFFFF;" 
    theme="dark" 
    width="260"
    elevation="2"
  >
    <!-- Topo / Logo -->
    <v-list-item 
      title="LABSTOCK" 
      subtitle="Gerenciamento de Laboratório"
      prepend-icon="mdi-flask-outline" 
      class="text-h6 font-weight-bold py-4 px-4 text-cyan-accent-3"
    ></v-list-item>
    
    <v-divider class="border-opacity-25"></v-divider>

    <!-- Menu Principal com Links de Navegação -->
    <v-list density="compact" nav class="px-3 mt-2">
      <v-list-subheader class="text-grey-lighten-1 font-weight-bold text-caption">MENU PRINCIPAL</v-list-subheader>
      
      <v-list-item prepend-icon="mdi-view-dashboard" title="Dashboard" to="/" exact rounded="lg" class="mb-1"></v-list-item>
      <v-list-item prepend-icon="mdi-flask" title="Reagentes" to="/reagentes" rounded="lg" class="mb-1"></v-list-item>
      <v-list-item prepend-icon="mdi-cup" title="Vidrarias" to="/vidrarias" rounded="lg" class="mb-1"></v-list-item>
      <v-list-item prepend-icon="mdi-tools" title="Equipamentos" to="/equipamentos" rounded="lg" class="mb-1"></v-list-item>
    </v-list>

    <v-divider class="border-opacity-25 my-2"></v-divider>

    <!-- Configurações -->
    <v-list density="compact" nav class="px-3">
      <v-list-subheader class="text-grey-lighten-1 font-weight-bold text-caption">CONFIGURAÇÕES</v-list-subheader>
      <v-list-item prepend-icon="mdi-cog" title="Sistema" value="sistema" rounded="lg" class="mb-1"></v-list-item>
      <v-list-item prepend-icon="mdi-account-group" title="Usuários" value="usuarios" rounded="lg" class="mb-1"></v-list-item>
    </v-list>

    <!-- Rodapé do Menu com o Usuário -->
    <template v-slot:append>
      <v-divider class="border-opacity-25"></v-divider>
      <div class="pa-3">
        <v-list-item
          prepend-icon="mdi-account-circle"
          :title="nomeUsuario"
          subtitle="Admin"
          rounded="lg"
          class="bg-blue-grey-darken-4 text-white"
        ></v-list-item>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script setup>
import { ref } from 'vue';
const drawer = ref(true);
defineProps({ nomeUsuario: String });
</script>