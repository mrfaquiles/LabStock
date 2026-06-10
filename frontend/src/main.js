import { createApp } from 'vue'
import App from './App.vue'
import vuetify from './plugins/vuetify' // Importa o plugin que criamos acima

const app = createApp(App)
app.use(vuetify)
app.mount('#app')