<template>
    <div class="login-wrap">
        <div class="login-card">
            <div class="login-title">Welcome back</div>
            <div class="login-subtitle">Sign in to manage carts and food courts.</div>

            <form @submit.prevent="submit">
                <div class="field">
                    <label for="login">Email or phone</label>
                    <input id="login" v-model="form.login" type="text" autocomplete="username" />
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" v-model="form.password" type="password" autocomplete="current-password" />
                </div>
                <div v-if="error" class="login-subtitle">{{ error }}</div>
                <button class="pill" type="submit" :disabled="loading">
                    {{ loading ? 'Signing in...' : 'Sign in' }}
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import api from '../lib/api';

const router = useRouter();
const auth = useAuthStore();
const loading = ref(false);
const error = ref('');

const form = ref({
    login: '',
    password: '',
});

const submit = async () => {
    error.value = '';
    loading.value = true;
    try {
        const response = await api.post('/auth/login', form.value);
        auth.setToken(response.data.token);
        auth.setUser(response.data.user);

        if (auth.hasAnyRole(['seller_owner', 'vendor'])) {
            router.push({ name: 'seller.menu' });
        } else {
            router.push({ name: 'dashboard' });
        }
    } catch (err) {
        error.value = err?.response?.data?.message || 'Login failed.';
    } finally {
        loading.value = false;
    }
};
</script>
