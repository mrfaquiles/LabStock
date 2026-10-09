import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { pt } from 'vuetify/locale'

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
        },
      },
    },
  },
  components,
  directives,
})