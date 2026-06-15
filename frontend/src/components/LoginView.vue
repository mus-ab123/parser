<script setup>
import { ref } from 'vue';
import { useAuth } from '../composables/useAuth';

const emit = defineEmits(['login-success']);

const email = ref('test@example.com');
const password = ref('password');
const localError = ref(null);

const { login, authLoading, authError } = useAuth();

async function handleSubmit() {
  localError.value = null;
  if (!email.value || !password.value) {
    localError.value = 'Заполните все поля';
    return;
  }

  const result = await login(email.value, password.value);
  if (result.success) {
    emit('login-success');
  }
}
</script>

<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <div class="logo-glow"></div>
        <h2>Yandex Reviews</h2>
        <p>Панель управления отзывами Яндекс.Карт</p>
      </div>

      <form @submit.prevent="handleSubmit" class="login-form">
        <div class="form-group">
          <label for="email">Электронная почта</label>
          <input 
            type="email" 
            id="email" 
            v-model="email" 
            placeholder="name@example.com"
            required
            :disabled="authLoading"
          />
        </div>

        <div class="form-group">
          <label for="password">Пароль</label>
          <input 
            type="password" 
            id="password" 
            v-model="password" 
            placeholder="••••••••"
            required
            :disabled="authLoading"
          />
        </div>

        <div v-if="authError || localError" class="error-alert">
          {{ authError || localError }}
        </div>

        <button type="submit" class="btn-primary login-btn" :disabled="authLoading">
          <span v-if="authLoading" class="spinner btn-spinner"></span>
          <span v-else>Войти в систему</span>
        </button>
      </form>

      <div class="login-footer">
        <p class="demo-creds">Для теста: <strong>test@example.com</strong> / <strong>password</strong></p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  padding: 20px;
}

.login-card {
  width: 100%;
  max-width: 440px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  animation: fadeIn 0.4s ease-out;
  position: relative;
  overflow: hidden;
}

.logo-glow {
  position: absolute;
  top: -50px;
  left: 50%;
  transform: translateX(-50%);
  width: 150px;
  height: 150px;
  background: var(--accent-color);
  filter: blur(80px);
  opacity: 0.3;
  pointer-events: none;
}

.login-header {
  text-align: center;
  margin-bottom: 32px;
}

.login-header h2 {
  font-size: 28px;
  margin-bottom: 8px;
  background: var(--accent-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.login-header p {
  color: var(--text-secondary);
  font-size: 14px;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 13px;
  color: var(--text-secondary);
  font-weight: 500;
}

.error-alert {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid var(--error-color);
  color: var(--error-color);
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
  text-align: center;
  animation: fadeIn 0.2s ease;
}

.login-btn {
  margin-top: 10px;
  font-weight: 600;
  height: 48px;
}

.btn-spinner {
  margin-right: 8px;
}

.login-footer {
  margin-top: 24px;
  text-align: center;
  font-size: 13px;
  color: var(--text-muted);
}

.demo-creds {
  background: rgba(255, 255, 255, 0.02);
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.03);
}
</style>
