import { ref, computed, readonly } from 'vue';

const STORAGE_KEY = 'auth_token';

const token = ref(localStorage.getItem(STORAGE_KEY) || null);
const user = ref(null);
const authLoading = ref(false);
const authError = ref(null);

const apiBase = () => {
    return import.meta.env.VITE_API_URL || '';
};

async function apiRequest(path, options = {}) {
    const url = `${apiBase()}/api${path}`;
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...options.headers,
    };

    if (token.value) {
        headers.Authorization = `Bearer ${token.value}`;
    }

    try {
        const res = await fetch(url, { ...options, headers });
        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            if (res.status === 401) {
                token.value = null;
                user.value = null;
                localStorage.removeItem(STORAGE_KEY);
            }
            throw { status: res.status, message: data.message || 'Ошибка сервера' };
        }
        return data;
    } catch (err) {
        throw err;
    }
}

export function useAuth() {
    const isAuthenticated = computed(() => !!user.value);

    async function fetchUser() {
        if (!token.value) {
            user.value = null;
            return;
        }

        authLoading.value = true;
        authError.value = null;
        try {
            const data = await apiRequest('/user');
            user.value = data.user || null;
        } catch (err) {
            user.value = null;
            token.value = null;
            localStorage.removeItem(STORAGE_KEY);
        } finally {
            authLoading.value = false;
        }
    }

    async function login(email, password) {
        authLoading.value = true;
        authError.value = null;
        try {
            const data = await apiRequest('/login', {
                method: 'POST',
                body: JSON.stringify({ email, password }),
            });
            token.value = data.token;
            user.value = data.user;
            localStorage.setItem(STORAGE_KEY, data.token);
            return { success: true };
        } catch (err) {
            const msg = err.message || 'Неверный логин или пароль';
            authError.value = msg;
            return { success: false, error: msg };
        } finally {
            authLoading.value = false;
        }
    }

    async function logout() {
        authLoading.value = true;
        try {
            await apiRequest('/logout', { method: 'POST' });
        } catch (err) {
        } finally {
            token.value = null;
            user.value = null;
            localStorage.removeItem(STORAGE_KEY);
            authLoading.value = false;
        }
    }

    return {
        user: readonly(user),
        token: readonly(token),
        authLoading: readonly(authLoading),
        authError: readonly(authError),
        isAuthenticated,
        fetchUser,
        login,
        logout,
        apiRequest,
    };
}
