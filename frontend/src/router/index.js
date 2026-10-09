import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import Home from '../views/Home.vue'
import Login from '../views/Login.vue'
import Reagentes from '../views/Reagentes.vue'
import Vidrarias from '../views/Vidrarias.vue'
import Equipamentos from '../views/Equipamentos.vue'
import Usuarios from '../views/Usuarios.vue'
import Relatorios from '../views/Relatorios.vue'
import Configuracoes from '../views/Configuracoes.vue'
import Compras from '../views/Compras.vue'

const routes = [
  { path: '/login', name: 'Login', component: Login, meta: { publica: true } },
  { path: '/', name: 'Home', component: Home },
  { path: '/reagentes', name: 'Reagentes', component: Reagentes },
  { path: '/vidrarias', name: 'Vidrarias', component: Vidrarias },
  { path: '/equipamentos', name: 'Equipamentos', component: Equipamentos },
  { path: '/compras', name: 'Compras', component: Compras },
  { path: '/relatorios', name: 'Relatorios', component: Relatorios },
  { path: '/configuracoes', name: 'Configuracoes', component: Configuracoes, meta: { perfis: ['admin'] } },
  { path: '/usuarios', name: 'Usuarios', component: Usuarios, meta: { perfis: ['admin'] } },
  { path: '/:pathMatch(.*)*', redirect: '/' }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.publica) {
    // Já logado não precisa ver a tela de login
    return auth.isAuthenticated && to.name === 'Login' ? { name: 'Home' } : true
  }

  if (!auth.isAuthenticated) {
    return { name: 'Login', query: { redirect: to.fullPath } }
  }

  if (to.meta.perfis && !auth.temPerfil(to.meta.perfis)) {
    return { name: 'Home' }
  }

  return true
})

export default router
