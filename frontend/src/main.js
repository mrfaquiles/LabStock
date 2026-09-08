import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import vuetify from './plugins/vuetify'
import router from './router/index.js'
import './style.css'
import './plugins/axios'


createApp(App)
  .use(vuetify)
  .use(router)
  .use(createPinia())
  .mount('#app')
