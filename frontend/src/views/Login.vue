<template>
  <v-container fluid class="login-fundo fill-height pa-4">
    <v-row justify="center" align="center">
      <v-col cols="12" sm="8" md="5" lg="4" xl="3">
        <v-card class="rounded-lg pa-6 pa-sm-8" elevation="8">
          <!-- Logo -->
          <div class="text-center mb-6">
            <v-avatar color="primary" size="64" class="mb-3">
              <v-icon icon="mdi-flask-outline" size="36" color="white"></v-icon>
            </v-avatar>
            <h1 class="text-h5 font-weight-bold text-primary">LABSTOCK</h1>
            <p class="text-subtitle-2 text-grey-darken-1">Gerenciamento de Laboratório</p>
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
              label="E-mail"
              type="email"
              prepend-inner-icon="mdi-email-outline"
              variant="outlined"
              density="comfortable"
              autocomplete="username"
              autofocus
              :rules="[regras.obrigatorio, regras.email]"
              class="mb-2"
            ></v-text-field>

            <v-text-field
              v-model="senha"
              label="Senha"
              :type="mostrarSenha ? 'text' : 'password'"
              prepend-inner-icon="mdi-lock-outline"
              :append-inner-icon="mostrarSenha ? 'mdi-eye-off' : 'mdi-eye'"
              variant="outlined"
              density="comfortable"
              autocomplete="current-password"
              :rules="[regras.obrigatorio]"
              @click:append-inner="mostrarSenha = !mostrarSenha"
            ></v-text-field>

            <v-btn
              type="submit"
              color="primary"
              block
              size="large"
              class="text-none text-white mt-4"
              :loading="auth.loading"
            >
              Entrar
            </v-btn>
          </v-form>

          <p class="text-caption text-grey text-center mt-6 mb-0">
            Esqueceu a senha? Procure o administrador do sistema.
          </p>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/authStore';

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
.login-fundo {
  min-height: 100vh;
  display: flex;
  align-items: center;
  background: linear-gradient(135deg, #1E3A6B 0%, #14284B 45%, #0B132B 100%);
}
</style>
