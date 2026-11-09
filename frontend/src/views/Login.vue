<template>
  <div class="login-tela">
    <!-- Fundo: grade em perspectiva e brilhos -->
    <div class="login-grade" aria-hidden="true"></div>
    <div class="login-brilho login-brilho--1" aria-hidden="true"></div>
    <div class="login-brilho login-brilho--2" aria-hidden="true"></div>

    <!-- Marca no topo -->
    <header class="login-marca">
      <span class="login-marca__icone"><v-icon icon="mdi-flask-outline" size="22"></v-icon></span>
      <span class="login-marca__nome">LabStock</span>
      <span class="login-marca__sub">Gerenciamento de Laboratório</span>
    </header>

    <main class="login-conteudo">
      <!-- Ilustração (some em telas pequenas) -->
      <section class="login-ilustracao">
        <IlustracaoLaboratorio />
        <p class="login-frase">
          Reagentes, vidrarias e equipamentos<br>
          <strong>organizados e rastreáveis.</strong>
        </p>
      </section>

      <!-- Cartão de vidro com o formulário -->
      <section class="login-cartao">
        <div class="text-center mb-6">
          <h1 class="login-titulo">Bem-vindo(a)</h1>
          <div class="login-subtitulo"><span></span>acesse sua conta<span></span></div>
        </div>

        <v-alert
          v-if="auth.error"
          type="error"
          variant="tonal"
          density="compact"
          class="mb-4"
          closable
          @click:close="auth.error = null"
        >
          {{ auth.error }}
        </v-alert>

        <v-form ref="form" @submit.prevent="entrar">
          <v-text-field
            v-model="email"
            placeholder="E-mail"
            type="email"
            prepend-inner-icon="mdi-account"
            variant="solo-filled"
            flat
            bg-color="rgba(255,255,255,0.08)"
            base-color="white"
            color="cyan-accent-2"
            theme="dark"
            autocomplete="username"
            autofocus
            :rules="[regras.obrigatorio, regras.email]"
            class="mb-3 login-campo"
          ></v-text-field>

          <v-text-field
            v-model="senha"
            placeholder="Senha"
            :type="mostrarSenha ? 'text' : 'password'"
            prepend-inner-icon="mdi-lock"
            :append-inner-icon="mostrarSenha ? 'mdi-eye-off' : 'mdi-eye'"
            variant="solo-filled"
            flat
            bg-color="rgba(255,255,255,0.08)"
            base-color="white"
            color="cyan-accent-2"
            theme="dark"
            autocomplete="current-password"
            :rules="[regras.obrigatorio]"
            class="login-campo"
            @click:append-inner="mostrarSenha = !mostrarSenha"
          ></v-text-field>

          <v-btn
            type="submit"
            block
            size="large"
            class="login-botao text-none mt-6"
            :loading="auth.loading"
          >
            Entrar
          </v-btn>
        </v-form>

        <p class="login-ajuda">
          <v-icon icon="mdi-help-circle-outline" size="14" class="me-1"></v-icon>
          Esqueceu a senha? Procure o administrador do sistema.
        </p>
      </section>
    </main>

    <RodapeCreditos escuro />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';
import IlustracaoLaboratorio from '../components/IlustracaoLaboratorio.vue';
import RodapeCreditos from '../components/RodapeCreditos.vue';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const form = ref(null);
const email = ref('');
const senha = ref('');
const mostrarSenha = ref(false);

const regras = {
  obrigatorio: (v) => !!v || 'Campo obrigatório',
  email: (v) => /.+@.+\..+/.test(v) || 'E-mail inválido',
};

const entrar = async () => {
  const { valid } = await form.value.validate();
  if (!valid) return;

  if (await auth.login(email.value, senha.value)) {
    // Só aceita redirecionamento interno
    const destino = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
      && !route.query.redirect.startsWith('//')
      ? route.query.redirect
      : '/';
    router.replace(destino);
  } else {
    senha.value = '';
    form.value.resetValidation();
  }
};
</script>

<style scoped>
.login-tela {
  position: relative;
  min-height: 100vh;
  overflow: hidden;
  color: #fff;
  background:
    radial-gradient(ellipse at 20% 60%, rgba(41, 121, 255, 0.25) 0%, transparent 55%),
    radial-gradient(ellipse at 85% 30%, rgba(0, 229, 255, 0.12) 0%, transparent 50%),
    linear-gradient(160deg, #13284d 0%, #0b1730 55%, #070f22 100%);
}

/* Grade em perspectiva, como um piso de laboratório */
.login-grade {
  position: absolute;
  inset: 40% -20% -20% -20%;
  background-image:
    linear-gradient(rgba(64, 196, 255, 0.12) 1px, transparent 1px),
    linear-gradient(90deg, rgba(64, 196, 255, 0.12) 1px, transparent 1px);
  background-size: 60px 60px;
  transform: perspective(600px) rotateX(60deg);
  transform-origin: top center;
  mask-image: linear-gradient(to bottom, transparent 0%, #000 30%, #000 70%, transparent 100%);
  pointer-events: none;
}
.login-brilho {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
}
.login-brilho--1 { width: 420px; height: 420px; left: 8%; top: 25%; background: rgba(41, 121, 255, 0.25); }
.login-brilho--2 { width: 300px; height: 300px; right: 10%; top: 15%; background: rgba(24, 255, 255, 0.1); }

/* Marca */
.login-marca {
  position: relative;
  z-index: 2;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 24px 32px;
}
.login-marca__icone {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  color: #0b132b;
  background: linear-gradient(135deg, #18ffff, #40c4ff);
}
.login-marca__nome { font-size: 1.15rem; font-weight: 700; letter-spacing: 0.02em; }
.login-marca__sub { font-size: 0.8rem; color: rgba(255, 255, 255, 0.55); padding-left: 10px; border-left: 1px solid rgba(255, 255, 255, 0.2); }

/* Conteúdo: ilustração + cartão */
.login-conteudo {
  position: relative;
  z-index: 2;
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(320px, 400px);
  align-items: center;
  gap: 48px;
  max-width: 1180px;
  margin: 0 auto;
  padding: 8px 32px 32px;
  min-height: calc(100vh - 84px - 50px); /* marca + rodapé */
}
.login-ilustracao { display: flex; flex-direction: column; align-items: center; }
.login-frase {
  margin-top: 8px;
  text-align: center;
  font-size: 1.05rem;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.65);
}
.login-frase strong { color: #7df9ff; font-weight: 600; }

/* Cartão de vidro */
.login-cartao {
  padding: 40px 32px 28px;
  border-radius: 18px;
  background: linear-gradient(160deg, rgba(255, 255, 255, 0.14), rgba(255, 255, 255, 0.04));
  border: 1px solid rgba(130, 200, 255, 0.35);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}
.login-titulo { font-size: 1.6rem; font-weight: 700; letter-spacing: 0.02em; }
.login-subtitulo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-top: 4px;
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.6);
}
.login-subtitulo span { width: 28px; height: 1px; background: rgba(255, 255, 255, 0.35); }

.login-campo :deep(.v-field) { border-radius: 10px; }
.login-campo :deep(input) { color: #fff; }
.login-campo :deep(.v-field__prepend-inner .v-icon) { color: #7df9ff; opacity: 0.9; }

.login-botao {
  color: #fff !important;
  font-weight: 600;
  letter-spacing: 0.03em;
  border-radius: 10px;
  background: linear-gradient(90deg, #00b8d4 0%, #2979ff 100%) !important;
  box-shadow: 0 8px 24px rgba(41, 121, 255, 0.45);
}
.login-botao:hover { filter: brightness(1.1); }

.login-ajuda {
  margin: 20px 0 0;
  text-align: center;
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.5);
}

/* Telas menores: só o cartão */
@media (max-width: 960px) {
  .login-conteudo { grid-template-columns: 1fr; justify-items: center; }
  .login-ilustracao { display: none; }
  .login-cartao { width: 100%; max-width: 420px; }
  .login-marca__sub { display: none; }
}
</style>
