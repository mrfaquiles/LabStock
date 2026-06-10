<template>
  <v-data-table
    :headers="headers"
    :items="itens"
    class="elevation-1"
  ></v-data-table>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const itens = ref([]);
const headers = [
  { title: 'ID', key: 'id_item' },
  { title: 'Nome', key: 'nome' },
  { title: 'Qtd', key: 'quantidade' }
];

onMounted(async () => {
  const res = await axios.get('http://127.0.0.1:8000/api/itens');
  itens.value = res.data;
});
</script>