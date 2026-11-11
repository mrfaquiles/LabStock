import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import vuetify from './plugins/vuetify'
import router from './router/index.js'
import './style.css'
import PopoverInfo from './components/PopoverInfo.vue'
import CampoData from './components/CampoData.vue'


createApp(App)
  // Caixa de informação usada nas tabelas de todas as telas
  .component('PopoverInfo', PopoverInfo)
  .component('CampoData', CampoData)
  .use(createPinia())
  .use(vuetify)
  .use(router)
  .mount('#app')
