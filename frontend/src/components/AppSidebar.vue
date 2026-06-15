<script setup>
import { useAuth } from '../composables/useAuth';

const props = defineProps({
  organizations: {
    type: Array,
    required: true,
  },
  activeOrgId: {
    type: [Number, String],
    default: null,
  }
});

const emit = defineEmits(['select-org', 'open-add-modal']);

const { user, logout } = useAuth();

function getStatusLabel(status) {
  switch (status) {
    case 'pending': return 'В очереди';
    case 'processing': return 'Сбор...';
    case 'failed': return 'Ошибка';
    default: return '';
  }
}
</script>

<template>
  <aside class="sidebar">
    <div class="sidebar-header">
      <div class="logo">
        <span class="logo-icon">★</span>
        <h2>Yandex Reviews</h2>
      </div>
    </div>

    <div class="sidebar-menu">
      <div class="menu-title-row">
        <h3>Организации</h3>
        <button @click="emit('open-add-modal')" class="btn-add-mini" title="Добавить организацию">
          +
        </button>
      </div>

      <div class="org-list">
        <div v-if="organizations.length === 0" class="empty-orgs">
          Нет отслеживаемых организаций
        </div>

        <button
          v-for="org in organizations"
          :key="org.id"
          class="org-item"
          :class="{ 
            'active': activeOrgId === org.id,
            'status-pending': org.status === 'pending',
            'status-processing': org.status === 'processing',
            'status-failed': org.status === 'failed'
          }"
          @click="emit('select-org', org.id)"
        >
          <div class="org-item-content">
            <div class="org-name">{{ org.name || 'Сбор данных...' }}</div>
            <div class="org-meta">
              <span v-if="org.rating" class="org-rating">
                ★ {{ org.rating.toFixed(1) }}
              </span>
              <span v-if="org.review_count" class="org-count">
                ({{ org.review_count }} отзывов)
              </span>
              <span v-if="org.status !== 'completed'" class="status-badge" :class="org.status">
                {{ getStatusLabel(org.status) }}
              </span>
            </div>
          </div>
        </button>
      </div>
    </div>

    <div class="sidebar-footer">
      <div class="user-profile">
        <div class="user-avatar">
          {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
        </div>
        <div class="user-info">
          <div class="user-name">{{ user?.name || 'Пользователь' }}</div>
          <div class="user-email">{{ user?.email || '' }}</div>
        </div>
      </div>
      <button @click="logout" class="btn-logout" title="Выйти из системы">
        🚪
      </button>
    </div>
  </aside>
</template>

<style scoped>
.sidebar {
  width: 280px;
  background: var(--bg-sidebar);
  border-right: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  z-index: 10;
}

.sidebar-header {
  padding: 24px;
  border-bottom: 1px solid var(--border-color);
}

.logo {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-icon {
  background: var(--accent-gradient);
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: bold;
}

.logo h2 {
  font-size: 18px;
  font-weight: 600;
  background: var(--accent-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.sidebar-menu {
  flex: 1;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  overflow-y: auto;
}

.menu-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.menu-title-row h3 {
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
}

.btn-add-mini {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid var(--border-color);
  color: var(--text-primary);
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  cursor: pointer;
  padding: 0;
  transition: all 0.2s ease;
}

.btn-add-mini:hover {
  background: var(--accent-gradient);
  border-color: transparent;
}

.org-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.empty-orgs {
  font-size: 13px;
  color: var(--text-muted);
  text-align: center;
  padding: 20px 0;
  border: 1px dashed var(--border-color);
  border-radius: 8px;
}

.org-item {
  background: transparent;
  border: 1px solid transparent;
  border-radius: 10px;
  padding: 12px;
  text-align: left;
  color: var(--text-secondary);
  transition: all 0.25s ease;
  width: 100%;
}

.org-item:hover {
  background: rgba(255, 255, 255, 0.03);
  border-color: var(--border-color);
  color: var(--text-primary);
}

.org-item.active {
  background: rgba(124, 58, 237, 0.1);
  border-color: rgba(124, 58, 237, 0.25);
  color: var(--text-primary);
  box-shadow: inset 0 0 10px rgba(124, 58, 237, 0.05);
}

.org-name {
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
}

.org-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
}

.org-rating {
  color: var(--star-color);
  font-weight: 600;
}

.org-count {
  color: var(--text-muted);
}

.status-badge {
  font-size: 10px;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 500;
}

.status-badge.pending {
  background: rgba(245, 158, 11, 0.1);
  color: var(--warning-color);
}

.status-badge.processing {
  background: rgba(59, 130, 246, 0.1);
  color: hsl(210, 100%, 65%);
  animation: pulse 1.5s infinite;
}

.status-badge.failed {
  background: rgba(239, 68, 68, 0.1);
  color: var(--error-color);
}

.sidebar-footer {
  padding: 24px;
  border-top: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 12px;
  overflow: hidden;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--accent-gradient);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 14px;
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.user-name {
  font-size: 14px;
  font-weight: 500;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-email {
  font-size: 12px;
  color: var(--text-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.btn-logout {
  background: transparent;
  border: none;
  font-size: 18px;
  padding: 4px 8px;
  cursor: pointer;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.btn-logout:hover {
  background: rgba(239, 68, 68, 0.15);
}
</style>
