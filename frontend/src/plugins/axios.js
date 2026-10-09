import axios from "axios";

export const TOKEN_KEY = 'labstock_token';
export const USUARIO_KEY = 'labstock_usuario';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/',
  timeout: 20000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});

// Envia o token Sanctum em todas as requisições
api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY);
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Token expirado/revogado: limpa a sessão e volta para o login
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const ehLogin = error.config?.url?.includes('login');
    if (error.response?.status === 401 && !ehLogin) {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(USUARIO_KEY);
      if (window.location.pathname !== '/login') {
        window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
      }
    }
    return Promise.reject(error);
  }
);

// Primeira mensagem de validação do Laravel (422) ou a mensagem enviada pela API
export const mensagemErro = (err, padrao = 'Ocorreu um erro inesperado.') => {
  const erros = err.response?.data?.errors;
  if (erros) return Object.values(erros)[0][0];
  return err.response?.data?.message || padrao;
};

export default api;
