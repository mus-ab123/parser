<script setup>
import { computed } from 'vue';

const props = defineProps({
  organization: {
    type: Object,
    required: true,
  },
  refreshLoading: {
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(['refresh']);

const formattedDate = computed(() => {
  if (!props.organization.last_scraped_at) return 'ни разу';
  const date = new Date(props.organization.last_scraped_at);
  return date.toLocaleString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
});

const canRefresh = computed(() => {
  return props.organization.status !== 'pending' && props.organization.status !== 'processing';
});
</script>

<template>
  <header class="page-header">
    <div class="org-header-info">
      <div class="title-row">
        <h2>{{ organization.name || 'Сбор данных об организации...' }}</h2>
        <span v-if="organization.status && organization.status !== 'completed'" class="status-indicator" :class="organization.status">
          {{ organization.status === 'failed' ? 'Ошибка сбора' : 'Идет сбор отзывов...' }}
        </span>
      </div>
      
      <div class="stats-row" v-if="organization.name">
        <div class="stat-badge rating">
          <span class="star-icon">★</span>
          <span class="rating-value">{{ organization.rating ? organization.rating.toFixed(1) : '0.0' }}</span>
        </div>
        
        <div class="stat-divider"></div>
        
        <div class="stat-badge count">
          <span class="count-value">{{ organization.review_count }}</span>
          <span class="stat-label">отзывов</span>
        </div>
        
        <div class="stat-divider"></div>

        <div class="stat-badge count">
          <span class="count-value">{{ organization.rating_count || organization.review_count }}</span>
          <span class="stat-label">оценок</span>
        </div>
        
        <div class="stat-divider"></div>

        <div class="sync-time">
          Синхронизировано: <strong>{{ formattedDate }}</strong>
        </div>
      </div>
    </div>

    <div class="header-actions">
      <button 
        @click="emit('refresh')" 
        class="btn-secondary refresh-btn"
        :disabled="!canRefresh || refreshLoading"
        title="Обновить отзывы"
      >
        <span v-if="refreshLoading || organization.status === 'processing'" class="spinner btn-spinner"></span>
        <span v-else class="refresh-icon">🔄</span>
        <span>Обновить данные</span>
      </button>
    </div>
  </header>
</template>

<style scoped>
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30px;
  background: rgba(30, 37, 51, 0.2);
  border-bottom: 1px solid var(--border-color);
  -webkit-backdrop-filter: blur(10px);
  backdrop-filter: blur(10px);
  gap: 20px;
}

.org-header-info {
  display: flex;
  flex-direction: column;
  gap: 12px;
  overflow: hidden;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.title-row h2 {
  font-size: 24px;
  font-weight: 600;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.status-indicator {
  font-size: 12px;
  padding: 4px 10px;
  border-radius: 20px;
  font-weight: 500;
}

.status-indicator.pending,
.status-indicator.processing {
  background: rgba(59, 130, 246, 0.1);
  color: hsl(210, 100%, 65%);
  border: 1px solid rgba(59, 130, 246, 0.2);
  animation: pulse 1.5s infinite;
}

.status-indicator.failed {
  background: rgba(239, 68, 68, 0.1);
  color: var(--error-color);
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.stats-row {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.stat-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
}

.star-icon {
  color: var(--star-color);
  font-size: 18px;
  line-height: 1;
}

.rating-value {
  font-weight: 700;
  color: var(--text-primary);
  font-size: 16px;
}

.count-value {
  font-weight: 600;
  color: var(--text-primary);
}

.stat-label {
  color: var(--text-secondary);
  font-size: 13px;
}

.stat-divider {
  width: 1px;
  height: 16px;
  background: var(--border-color);
}

.sync-time {
  font-size: 13px;
  color: var(--text-muted);
}

.sync-time strong {
  color: var(--text-secondary);
}

.refresh-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
  padding: 10px 18px;
  height: 42px;
  font-size: 14px;
}

.refresh-icon {
  font-size: 14px;
}

.btn-spinner {
  width: 14px;
  height: 14px;
  border-width: 1.5px;
}
</style>
