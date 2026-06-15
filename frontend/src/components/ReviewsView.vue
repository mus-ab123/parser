<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  reviews: {
    type: Array,
    required: true,
  },
  pagination: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(['change-page', 'change-filter']);

const selectedRating = ref('all');

watch(selectedRating, (newVal) => {
  emit('change-filter', newVal);
});

function getFormattedDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  });
}

function getAvatarInitials(name) {
  if (!name) return 'А';
  const parts = name.split(' ');
  if (parts.length >= 2) {
    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
}

function handlePageChange(page) {
  if (page >= 1 && page <= props.pagination.last_page) {
    emit('change-page', page);
  }
}
</script>

<template>
  <div class="reviews-section">
    <!-- Filter Row -->
    <div class="filter-row">
      <div class="filter-title">Фильтр по оценке:</div>
      <div class="filter-buttons">
        <label 
          v-for="val in ['all', '5', '4', '3', '2', '1']" 
          :key="val" 
          class="filter-tab"
          :class="{ 'active': selectedRating === val }"
        >
          <input 
            type="radio" 
            :value="val" 
            v-model="selectedRating" 
            class="hidden-radio"
          />
          <span v-if="val === 'all'">Все</span>
          <span v-else class="star-tab-content">
            {{ val }} <span class="tab-star">★</span>
          </span>
        </label>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="reviews-list">
      <div v-for="i in 3" :key="i" class="review-card skeleton-card">
        <div class="review-card-header">
          <div class="skeleton-avatar"></div>
          <div class="skeleton-header-meta">
            <div class="skeleton-line short"></div>
            <div class="skeleton-line xs"></div>
          </div>
        </div>
        <div class="skeleton-line full"></div>
        <div class="skeleton-line medium"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="reviews.length === 0" class="empty-reviews">
      Отзывы не найдены. Попробуйте изменить параметры фильтрации.
    </div>

    <!-- Reviews list -->
    <div v-else class="reviews-list">
      <div v-for="rev in reviews" :key="rev.id" class="review-card">
        <div class="review-card-header">
          <div class="review-avatar">
            {{ getAvatarInitials(rev.author_name) }}
          </div>
          <div class="review-author-info">
            <div class="author-name">{{ rev.author_name }}</div>
            <div class="review-meta">
              <div class="review-stars">
                <span 
                  v-for="star in 5" 
                  :key="star" 
                  class="star"
                  :class="{ 'filled': star <= rev.rating }"
                >★</span>
              </div>
              <span class="review-date">{{ getFormattedDate(rev.published_at) }}</span>
            </div>
          </div>
        </div>
        
        <div class="review-text">
          {{ rev.text }}
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1 && !loading" class="pagination-container">
      <button 
        @click="handlePageChange(pagination.current_page - 1)"
        class="pagination-btn"
        :disabled="pagination.current_page === 1"
      >
        ‹ Назад
      </button>

      <div class="pagination-pages">
        <button
          v-for="page in pagination.last_page"
          :key="page"
          @click="handlePageChange(page)"
          class="page-num-btn"
          :class="{ 'active': page === pagination.current_page }"
        >
          {{ page }}
        </button>
      </div>

      <button 
        @click="handlePageChange(pagination.current_page + 1)"
        class="pagination-btn"
        :disabled="pagination.current_page === pagination.last_page"
      >
        Вперед ›
      </button>
    </div>
  </div>
</template>

<style scoped>
.reviews-section {
  padding: 30px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 16px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  padding: 12px 20px;
  border-radius: 12px;
}

.filter-title {
  font-size: 14px;
  color: var(--text-secondary);
  font-weight: 500;
}

.filter-buttons {
  display: flex;
  gap: 8px;
}

.filter-tab {
  cursor: pointer;
  padding: 6px 14px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid var(--border-color);
  font-size: 13px;
  color: var(--text-secondary);
  transition: all 0.2s ease;
  user-select: none;
}

.filter-tab:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: var(--border-hover);
}

.filter-tab.active {
  background: var(--accent-gradient);
  color: white;
  border-color: transparent;
  box-shadow: 0 2px 10px rgba(124, 58, 237, 0.3);
}

.hidden-radio {
  display: none;
}

.star-tab-content {
  display: flex;
  align-items: center;
  gap: 4px;
}

.tab-star {
  color: var(--star-color);
  font-size: 14px;
}

.filter-tab.active .tab-star {
  color: #fff;
}

.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.review-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 14px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  animation: fadeIn 0.3s ease-out;
  transition: border-color 0.25s ease;
}

.review-card:hover {
  border-color: var(--border-hover);
}

.review-card-header {
  display: flex;
  align-items: center;
  gap: 16px;
}

.review-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid var(--border-color);
  color: var(--text-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 14px;
}

.review-author-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.author-name {
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
}

.review-meta {
  display: flex;
  align-items: center;
  gap: 12px;
}

.review-stars {
  display: flex;
  gap: 2px;
}

.star {
  color: rgba(255, 255, 255, 0.1);
  font-size: 16px;
  line-height: 1;
}

.star.filled {
  color: var(--star-color);
}

.review-date {
  font-size: 12px;
  color: var(--text-muted);
}

.review-text {
  font-size: 14px;
  color: var(--text-secondary);
  line-height: 1.6;
  white-space: pre-line;
}

/* Skeleton Loading styles */
.skeleton-card {
  pointer-events: none;
}

.skeleton-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.05);
  animation: pulse 1.5s infinite;
}

.skeleton-header-meta {
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}

.skeleton-line {
  height: 12px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 4px;
  animation: pulse 1.5s infinite;
}

.skeleton-line.short { width: 120px; }
.skeleton-line.xs { width: 80px; }
.skeleton-line.full { width: 100%; height: 14px; margin-top: 8px; }
.skeleton-line.medium { width: 70%; height: 14px; }

.empty-reviews {
  padding: 40px;
  text-align: center;
  border: 1px dashed var(--border-color);
  border-radius: 12px;
  color: var(--text-muted);
  font-size: 14px;
}

/* Pagination styles */
.pagination-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-top: 10px;
  margin-bottom: 20px;
}

.pagination-btn {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid var(--border-color);
  color: var(--text-primary);
  font-size: 13px;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.08);
  border-color: var(--border-hover);
}

.pagination-pages {
  display: flex;
  gap: 6px;
  max-width: 320px;
  overflow-x: auto;
  padding: 4px 0;
}

.page-num-btn {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  background: transparent;
  border: 1px solid transparent;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 0;
  flex-shrink: 0;
}

.page-num-btn:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: var(--border-color);
  color: var(--text-primary);
}

.page-num-btn.active {
  background: var(--accent-gradient);
  color: white;
  border-color: transparent;
  box-shadow: 0 2px 8px var(--accent-glow);
}
</style>
