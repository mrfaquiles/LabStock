<template>
  <v-app class="bg-background">
    <Sidebar v-if="!route.meta.publica" />

    <v-main>
      <div class="app-pagina">
        <div class="app-pagina__conteudo"><router-view /></div>
        <RodapeCreditos v-if="!route.meta.publica" />
      </div>
    </v-main>
  </v-app>
</template>

<script setup>
import { watch } from 'vue';
import { useRoute } from 'vue-router';
import Sidebar from './components/Sidebar.vue';
import RodapeCreditos from './components/RodapeCreditos.vue';
import { useAuthStore } from './stores/authStore';
import { useConfigStore } from './stores/configStore';

const route = useRoute();
const auth = useAuthStore();
const config = useConfigStore();

// Ao abrir o sistema (ou logo após o login): confirma o token no backend
// e carrega as configurações usadas nos relatórios e formulários
watch(() => auth.isAuthenticated, (logado) => {
  if (logado) {
    auth.fetchMe();
    config.carregar();
  }
}, { immediate: true });
</script>

<style>
/* Rodapé sempre no fim da tela, mesmo em páginas curtas */
.app-pagina { display: flex; flex-direction: column; min-height: 100vh; }
.app-pagina__conteudo { flex: 1; }
</style>
