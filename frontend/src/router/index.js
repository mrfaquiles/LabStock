import { createRouter, createWebHistory } from 'vue-router'
import Home from '../views/Home.vue' // ou o caminho onde salvou
import Reagentes from '../views/Reagentes.vue'
import Vidrarias from '../views/Vidrarias.vue'
import Equipamentos from '../views/Equipamentos.vue'

const routes = [
  { path: '/', name: 'Home', component: Home },
  { path: '/reagentes', name: 'Reagentes', component: Reagentes },
  { path: '/vidrarias', name: 'Vidrarias', component: Vidrarias },
  { path: '/equipamentos', name: 'Equipamentos', component: Equipamentos }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router