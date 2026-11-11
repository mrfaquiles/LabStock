import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { pt } from 'vuetify/locale'

// Cores próprias do LabStock, usadas no CSS como rgb(var(--v-theme-<nome>))
const CORES_SISTEMA = {
  marinho: '#14284B', // dicas, caixas de informação
  menu: '#0B132B',    // fundo do menu lateral e da tela de login
  destaque: '#18FFFF', // ciano do logo e do avatar
};

export default createVuetify({
  // Textos internos dos componentes (paginação, "sem dados", calendário) em português
  locale: {
    locale: 'pt',
    messages: { pt },
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#14284B',   // Azul-marinho institucional (botões, títulos, abas)
          secondary: '#004A26', // Verde institucional (destaques)
          background: '#F5F7FB',
          ...CORES_SISTEMA,
        },
      },
      // O menu lateral usa o tema escuro; as cores próprias do sistema valem lá também
      dark: {
        colors: { ...CORES_SISTEMA },
      },
    },
  },
  components,
  directives,
})