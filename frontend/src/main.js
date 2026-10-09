import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import vuetify from './plugins/vuetify'
import router from './router/index.js'
import './style.css'


createApp(App)
  .use(createPinia())
  .use(vuetify)
  .use(router)
  .mount('#app')
