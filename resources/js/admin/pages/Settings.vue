<template>
    <div class="card">
        <div class="section-title">System Settings</div>

        <form @submit.prevent="save">
            <div class="grid">
                <div class="field">
                    <label>Commission %</label>
                    <input v-model.number="form.commission_percent" type="number" min="0" max="100" step="0.01" />
                </div>
                <div class="field">
                    <label>Tax %</label>
                    <input v-model.number="form.tax_percent" type="number" min="0" max="100" step="0.01" />
                </div>
                <div class="field">
                    <label>Payment Methods (comma separated)</label>
                    <input v-model="paymentMethodsInput" type="text" placeholder="cash, sslcommerz" />
                </div>
            </div>

            <div v-if="error" class="login-subtitle">{{ error }}</div>

            <div class="modal-actions">
                <button type="button" class="pill outline" @click="fetchSettings" :disabled="loading">
                    Reload
                </button>
                <button type="submit" class="pill" :disabled="loading">
                    {{ loading ? 'Saving...' : 'Save Settings' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../lib/api';

const loading = ref(false);
const error = ref('');

const form = ref({
    commission_percent: 10,
    tax_percent: 0,
    payment_methods: ['cash', 'sslcommerz'],
});

const paymentMethodsInput = ref('cash, sslcommerz');

const extractErrorMessage = (err, fallback) => {
    const responseData = err?.response?.data;
    if (responseData?.message) {
        return responseData.message;
    }

    const validation = responseData?.errors;
    if (validation && typeof validation === 'object') {
        const firstGroup = Object.values(validation)[0];
        if (Array.isArray(firstGroup) && firstGroup[0]) {
            return firstGroup[0];
        }
    }

    return fallback;
};

const parsePaymentMethods = () => {
    return paymentMethodsInput.value
        .split(',')
        .map((value) => value.trim())
        .filter((value) => value.length > 0);
};

const fetchSettings = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/settings');
        form.value = {
            commission_percent: Number(response.data?.commission_percent ?? 10),
            tax_percent: Number(response.data?.tax_percent ?? 0),
            payment_methods: response.data?.payment_methods || ['cash', 'sslcommerz'],
        };
        paymentMethodsInput.value = form.value.payment_methods.join(', ');
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load settings.');
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    loading.value = true;
    error.value = '';
    try {
        const paymentMethods = parsePaymentMethods();
        await api.put('/admin/settings', {
            commission_percent: form.value.commission_percent,
            tax_percent: form.value.tax_percent,
            payment_methods: paymentMethods,
        });

        await fetchSettings();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to save settings.');
    } finally {
        loading.value = false;
    }
};

onMounted(fetchSettings);
</script>
