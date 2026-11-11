<template>
  <!-- Em telas pequenas o menu fica escondido e abre pelo botão desta barra -->
  <v-app-bar v-if="!telaGrande" color="menu" theme="dark" density="compact" elevation="2">
    <v-app-bar-nav-icon aria-label="Abrir menu" @click="drawer = !drawer"></v-app-bar-nav-icon>
    <v-app-bar-title class="font-weight-bold text-cyan-accent-3">LABSTOCK</v-app-bar-title>
  </v-app-bar>

  <v-navigation-drawer
    v-model="drawer"
    :permanent="telaGrande"
    :temporary="!telaGrande"
    color="menu"
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
      <v-list-item prepend-icon="mdi-bottle-tonic" title="Reagentes" to="/reagentes" rounded="lg" class="mb-1"></v-list-item>
      <v-list-item prepend-icon="mdi-flask" title="Vidrarias" to="/vidrarias" rounded="lg" class="mb-1"></v-list-item>
      <v-list-item prepend-icon="mdi-tools" title="Equipamentos" to="/equipamentos" rounded="lg" class="mb-1"></v-list-item>
      <v-list-item prepend-icon="mdi-cart-outline" title="Compras" to="/compras" rounded="lg" class="mb-1"></v-list-item>
      <v-list-item prepend-icon="mdi-file-chart" title="Relatórios" to="/relatorios" rounded="lg" class="mb-1"></v-list-item>
      <v-list-item prepend-icon="mdi-shield-alert" title="Prod. Controlados" to="/produtos-controlados" rounded="lg" class="mb-1"></v-list-item>
    </v-list>

    <!-- Configurações (somente administrador) -->
    <template v-if="auth.isAdmin">
      <v-divider class="border-opacity-25 my-2"></v-divider>

      <v-list density="compact" nav class="px-3">
        <v-list-subheader class="text-grey-lighten-1 font-weight-bold text-caption">CONFIGURAÇÕES</v-list-subheader>
        <v-list-item prepend-icon="mdi-cog" title="Sistema" to="/configuracoes" rounded="lg" class="mb-1"></v-list-item>
        <v-list-item prepend-icon="mdi-account-group" title="Usuários" to="/usuarios" rounded="lg" class="mb-1"></v-list-item>
      </v-list>
    </template>

    <!-- Rodapé do Menu com o Usuário -->
    <template v-slot:append>
      <div class="usuario-rodape">
        <v-avatar size="38" class="usuario-avatar">{{ iniciais }}</v-avatar>
        <div class="usuario-texto">
          <div class="usuario-nome" :title="auth.usuario?.nome">{{ auth.usuario?.nome }}</div>
          <div class="usuario-perfil">{{ auth.nomePerfil }}</div>
        </div>
        <v-tooltip text="Sair do sistema" location="top">
          <template v-slot:activator="{ props }">
            <v-btn v-bind="props" icon="mdi-logout" variant="text" size="small" class="usuario-sair" aria-label="Sair" @click="sair"></v-btn>
          </template>
        </v-tooltip>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useDisplay } from 'vuetify';
import { useAuthStore } from '../stores/authStore';

// Menu fixo a partir de telas médias (notebook); no celular vira gaveta
const { mdAndUp: telaGrande } = useDisplay();
const drawer = ref(telaGrande.value);
watch(telaGrande, (grande) => { drawer.value = grande; });
const auth = useAuthStore();
const router = useRouter();

// "Ana Paula Ribeiro" -> "AR"
const iniciais = computed(() => {
  const partes = (auth.usuario?.nome || '?').replace(/\(.*?\)/g, '').trim().split(/\s+/);
  return ((partes[0]?.[0] || '') + (partes.length > 1 ? partes[partes.length - 1][0] : '')).toUpperCase();
});

const sair = async () => {
  await auth.logout();
  router.replace('/login');
};
</script>
<style scoped>
/* Cartão do usuário logado, no rodapé do menu */
.usuario-rodape {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 12px;
  padding: 10px 8px 10px 12px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.usuario-avatar {
  flex-shrink: 0;
  font-size: 0.85rem;
  font-weight: 700;
  color: rgb(var(--v-theme-menu));
  background: linear-gradient(135deg, rgb(var(--v-theme-destaque)) 0%, #40c4ff 100%);
}
.usuario-texto {
  flex: 1;
  min-width: 0; /* deixa o nome longo terminar em "..." */
  line-height: 1.25;
}
.usuario-nome {
  font-size: 0.9rem;
  font-weight: 600;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.usuario-perfil {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.6);
}
.usuario-sair {
  flex-shrink: 0;
  color: rgba(255, 255, 255, 0.7) !important;
}
.usuario-sair:hover {
  color: #ff8a80 !important;
}
</style>
