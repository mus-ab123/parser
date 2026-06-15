<script setup>
import { ref } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: null,
  }
});

const emit = defineEmits(['close', 'submit']);

const url = ref('');

function handleSubmit() {
  if (url.value.trim()) {
    emit('submit', url.value.trim());
  }
}

function handleClose() {
  url.value = '';
  emit('close');
}
</script>

<template>
  <div v-if="isOpen" class="modal-overlay" @click.self="handleClose">
    <div class="modal-card">
      <div class="modal-header">
        <h3>Добавить организацию</h3>
        <button @click="handleClose" class="btn-close-modal">×</button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-form">
        <div class="form-group">
          <label for="org-url">Ссылка на организацию в Яндекс.Картах</label>
          <input 
            type="url" 
            id="org-url" 
            v-model="url" 
            placeholder="https://yandex.ru/maps/org/..."
            required
            :disabled="loading"
            autocomplete="off"
          />
          <span class="url-hint">
            Например: https://yandex.ru/maps/org/yandeks/1124715036/
          </span>
        </div>

        <div v-if="error" class="error-alert">
          {{ error }}
        </div>

        <div class="modal-footer-buttons">
          <button 
            type="button" 
            @click="handleClose" 
            class="btn-secondary"
            :disabled="loading"
          >
            Отмена
          </button>
          <button 
            type="submit" 
            class="btn-primary" 
            :disabled="loading || !url.trim()"
          >
            <span v-if="loading" class="spinner btn-spinner"></span>
            <span v-else>Добавить</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.6);
  -webkit-backdrop-filter: blur(8px);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
  animation: fadeIn 0.25s ease-out;
}

.modal-card {
  width: 100%;
  max-width: 500px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 30px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
  display: flex;
  flex-direction: column;
  gap: 20px;
  margin: 20px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 16px;
}

.modal-header h3 {
  font-size: 18px;
  font-weight: 600;
}

.btn-close-modal {
  background: transparent;
  border: none;
  font-size: 24px;
  color: var(--text-muted);
  cursor: pointer;
  padding: 4px;
  line-height: 1;
  transition: color 0.2s ease;
}

.btn-close-modal:hover {
  color: var(--text-primary);
}

.modal-form {
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

.url-hint {
  font-size: 11px;
  color: var(--text-muted);
  margin-top: 2px;
}

.error-alert {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid var(--error-color);
  color: var(--error-color);
  padding: 12px;
  border-radius: 8px;
  font-size: 13px;
  animation: fadeIn 0.2s ease;
}

.modal-footer-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  border-top: 1px solid var(--border-color);
  padding-top: 20px;
}

.btn-spinner {
  width: 14px;
  height: 14px;
  border-width: 1.5px;
  margin-right: 6px;
}
</style>
