<template>
  <v-app class="bg-background">
    <Sidebar nomeUsuario="Cindy Lopes" />

    <v-main>
      <router-view />
    </v-main>
  </v-app>
</template>

<script setup>
import Sidebar from './components/Sidebar.vue';
</script>