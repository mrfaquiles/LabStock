<template>
  <v-app class="bg-background">
    <Sidebar v-if="!route.meta.publica" />

    <v-main>
      <router-view />
    </v-main>
  </v-app>
</template>

<script setup>
import { watch } from 'vue';
import { useRoute } from 'vue-router';
import Sidebar from './components/Sidebar.vue';
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
