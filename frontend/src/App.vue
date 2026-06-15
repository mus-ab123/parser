<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useAuth } from './composables/useAuth';
import LoginView from './components/LoginView.vue';
import AppSidebar from './components/AppSidebar.vue';
import PageHeader from './components/PageHeader.vue';
import ReviewsView from './components/ReviewsView.vue';
import OrganizationModal from './components/OrganizationModal.vue';

const { isAuthenticated, fetchUser, apiRequest } = useAuth();

const organizations = ref([]);
const activeOrg = ref(null);
const reviews = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
});

const initialLoading = ref(true);
const orgListLoading = ref(false);
const reviewsLoading = ref(false);
const refreshLoading = ref(false);
const addModalOpen = ref(false);
const addLoading = ref(false);
const addError = ref(null);

const currentRatingFilter = ref('all');
const currentPage = ref(1);

let pollingInterval = null;

onMounted(async () => {
  await fetchUser();
  if (isAuthenticated.value) {
    await loadOrganizations();
  }
  initialLoading.value = false;
});

async function loadOrganizations() {
  orgListLoading.value = true;
  try {
    const data = await apiRequest('/organizations');
    organizations.value = data;
    
    if (activeOrg.value) {
      const fresh = data.find(o => o.id === activeOrg.value.id);
      if (fresh) {
        activeOrg.value = fresh;
      }
    }
  } catch (err) {
    console.error('Failed to load organizations', err);
  } finally {
    orgListLoading.value = false;
  }
}

async function handleSelectOrg(id) {
  const org = organizations.value.find(o => o.id === id);
  if (!org) return;

  activeOrg.value = org;
  currentPage.value = 1;
  currentRatingFilter.value = 'all';
  reviews.value = [];
  
  stopPolling();

  if (org.status === 'pending' || org.status === 'processing') {
    startPolling(id);
  } else if (org.status === 'completed') {
    await loadReviews();
  }
}

async function loadReviews() {
  if (!activeOrg.value || activeOrg.value.status !== 'completed') return;
  reviewsLoading.value = true;
  try {
    const data = await apiRequest(`/organizations/${activeOrg.value.id}/reviews?page=${currentPage.value}&rating=${currentRatingFilter.value}`);
    reviews.value = data.reviews.data;
    pagination.value = {
      current_page: data.reviews.current_page,
      last_page: data.reviews.last_page,
    };
  } catch (err) {
    console.error('Failed to load reviews', err);
  } finally {
    reviewsLoading.value = false;
  }
}

async function handleAddOrg(url) {
  addLoading.value = true;
  addError.value = null;
  try {
    const data = await apiRequest('/organizations', {
      method: 'POST',
      body: JSON.stringify({ url }),
    });

    await loadOrganizations();
    addModalOpen.value = false;

    if (data.organization) {
      await handleSelectOrg(data.organization.id);
    }
  } catch (err) {
    addError.value = err.message || 'Ошибка валидации или подключения к сети';
  } finally {
    addLoading.value = false;
  }
}

async function handleRefresh() {
  if (!activeOrg.value) return;
  refreshLoading.value = true;
  try {
    const data = await apiRequest(`/organizations/${activeOrg.value.id}/refresh`, {
      method: 'POST',
    });
    
    activeOrg.value.status = 'pending';
    
    const item = organizations.value.find(o => o.id === activeOrg.value.id);
    if (item) item.status = 'pending';

    startPolling(activeOrg.value.id);
  } catch (err) {
    alert(err.message || 'Не удалось обновить данные');
  } finally {
    refreshLoading.value = false;
  }
}

async function handlePageChange(page) {
  currentPage.value = page;
  await loadReviews();
}

async function handleFilterChange(rating) {
  currentRatingFilter.value = rating;
  currentPage.value = 1;
  await loadReviews();
}

function startPolling(id) {
  stopPolling();
  pollingInterval = setInterval(async () => {
    try {
      const data = await apiRequest(`/organizations/${id}/status`);
      
      const index = organizations.value.findIndex(o => o.id === id);
      if (index !== -1) {
        organizations.value[index].status = data.status;
        organizations.value[index].error_message = data.error_message;
        organizations.value[index].rating = data.rating;
        organizations.value[index].review_count = data.review_count;
        organizations.value[index].rating_count = data.rating_count;
        organizations.value[index].last_scraped_at = data.last_scraped_at;
      }

      if (activeOrg.value && activeOrg.value.id === id) {
        activeOrg.value.status = data.status;
        activeOrg.value.error_message = data.error_message;
        activeOrg.value.rating = data.rating;
        activeOrg.value.review_count = data.review_count;
        activeOrg.value.rating_count = data.rating_count;
        activeOrg.value.last_scraped_at = data.last_scraped_at;
        
        if (data.status === 'completed') {
          stopPolling();
          await loadReviews();
        } else if (data.status === 'failed') {
          stopPolling();
        }
      }
    } catch (err) {
      console.error('Error polling status:', err);
    }
  }, 3000);
}

function stopPolling() {
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }
}

onUnmounted(() => {
  stopPolling();
});

watch(isAuthenticated, async (newVal) => {
  if (newVal) {
    await loadOrganizations();
  } else {
    organizations.value = [];
    activeOrg.value = null;
    reviews.value = [];
    stopPolling();
  }
});
</script>

<template>
  <div v-if="initialLoading" class="app-loader">
    <div class="spinner loader-spinner"></div>
    <p>Загрузка панели управления...</p>
  </div>

  <template v-else>
    <LoginView v-if="!isAuthenticated" />

    <div v-else class="app-layout">
      <AppSidebar 
        :organizations="organizations" 
        :activeOrgId="activeOrg?.id"
        @select-org="handleSelectOrg"
        @open-add-modal="addModalOpen = true"
      />

      <main class="main-content-area">
        <div v-if="!activeOrg" class="welcome-screen">
          <div class="welcome-box">
            <span class="welcome-stars">★★★</span>
            <h2>Добро пожаловать в панель отзывов!</h2>
            <p>Выберите организацию в меню слева или добавьте новую по ссылке из Яндекс.Карт.</p>
            <button @click="addModalOpen = true" class="btn-primary welcome-add-btn">
              + Добавить организацию
            </button>
          </div>
        </div>

        <div v-else class="org-dashboard">
          <PageHeader 
            :organization="activeOrg"
            :refreshLoading="refreshLoading"
            @refresh="handleRefresh"
          />

          <div v-if="activeOrg.status === 'pending' || activeOrg.status === 'processing'" class="parser-loading-state">
            <div class="spinner parser-spinner"></div>
            <h3>Сбор данных из Яндекс.Карт...</h3>
            <p>Мы собираем и обрабатываем отзывы для вашей организации. Это может занять до 1-2 минут.</p>
          </div>

          <div v-else-if="activeOrg.status === 'failed'" class="parser-failed-state">
            <span class="error-emoji">⚠️</span>
            <h3>Не удалось загрузить отзывы</h3>
            <p>Произошла ошибка при парсинге страницы организации в Яндекс.Картах.</p>
            <div class="error-details" v-if="activeOrg.error_message">
              <strong>Детали ошибки:</strong> {{ activeOrg.error_message }}
            </div>
            <button @click="handleRefresh" class="btn-primary retry-btn">
              Попробовать снова
            </button>
          </div>

          <ReviewsView 
            v-else
            :reviews="reviews"
            :pagination="pagination"
            :loading="reviewsLoading"
            @change-page="handlePageChange"
            @change-filter="handleFilterChange"
          />
        </div>
      </main>

      <OrganizationModal 
        :isOpen="addModalOpen"
        :loading="addLoading"
        :error="addError"
        @close="addModalOpen = false"
        @submit="handleAddOrg"
      />
    </div>
  </template>
</template>

<style>
.app-loader {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  gap: 16px;
  color: var(--text-secondary);
}

.loader-spinner {
  width: 32px;
  height: 32px;
  border-width: 3px;
}

.app-layout {
  display: flex;
  min-height: 100vh;
}

.main-content-area {
  margin-left: 280px;
  flex-grow: 1;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.welcome-screen {
  flex-grow: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

.welcome-box {
  max-width: 500px;
  text-align: center;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  padding: 50px 40px;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.welcome-stars {
  color: var(--star-color);
  font-size: 24px;
  letter-spacing: 0.1em;
}

.welcome-box p {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.5;
}

.welcome-add-btn {
  margin-top: 10px;
  font-weight: 500;
}

.org-dashboard {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

.parser-loading-state,
.parser-failed-state {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 50px;
  text-align: center;
  gap: 16px;
  max-width: 600px;
  margin: 0 auto;
}

.parser-spinner {
  width: 40px;
  height: 40px;
  border-width: 3px;
}

.parser-loading-state h3,
.parser-failed-state h3 {
  font-size: 20px;
}

.parser-loading-state p,
.parser-failed-state p {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.6;
}

.error-emoji {
  font-size: 48px;
}

.error-details {
  background: rgba(239, 68, 68, 0.05);
  border: 1px solid rgba(239, 68, 68, 0.15);
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 13px;
  color: var(--error-color);
  font-family: monospace;
  width: 100%;
  text-align: left;
  word-break: break-all;
}

.retry-btn {
  margin-top: 10px;
}
</style>
