import { createApp } from 'vue'
import App from './App.vue'
import vuetify from './plugins/vuetify'
import router from './router/index.js'
import './style.css'
import api from './plugins/axios.js'


createApp(App)
  .use(vuetify)
  .use(router)
  .config.globalProperties.$api = api
  .mount('#app')
