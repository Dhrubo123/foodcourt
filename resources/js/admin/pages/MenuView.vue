<template>
    <div class="card">
        <div class="section-title">Menu View (Read Only)</div>

        <div class="filters">
            <div class="field">
                <label>Seller</label>
                <select v-model="filters.seller_id">
                    <option value="">All</option>
                    <option v-for="seller in sellers" :key="seller.id" :value="String(seller.id)">
                        {{ seller.name }} ({{ seller.type }})
                    </option>
                </select>
            </div>
            <div class="field">
                <label>Availability</label>
                <select v-model="filters.is_available">
                    <option value="">All</option>
                    <option value="1">In stock</option>
                    <option value="0">Out of stock</option>
                </select>
            </div>
            <div class="field">
                <label>Search</label>
                <input v-model="filters.search" type="text" placeholder="Item / seller / description" />
            </div>
            <div class="filters-actions">
                <button class="pill" @click="fetchMenu" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Apply' }}
                </button>
            </div>
        </div>

        <div v-if="loading" class="login-subtitle">Loading menu items...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="items.length === 0" class="login-subtitle">No menu items found.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Category</th>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Cost</th>
                    <th>In Stock</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in items" :key="item.id">
                    <td>{{ item.seller?.name || 'N/A' }}</td>
                    <td>{{ item.category?.name || '-' }}</td>
                    <td>
                        {{ item.name }}
                        <div class="login-subtitle">{{ item.description || '' }}</div>
                    </td>
                    <td>BDT {{ money(item.price) }}</td>
                    <td>BDT {{ money(item.cost_price) }}</td>
                    <td>{{ item.is_available ? 'Yes' : 'No' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../lib/api';

const loading = ref(false);
const error = ref('');
const items = ref([]);
const sellers = ref([]);

const filters = ref({
    seller_id: '',
    is_available: '',
    search: '',
});

const money = (value) => Number(value || 0).toFixed(2);

const extractErrorMessage = (err, fallback) => {
    const responseData = err?.response?.data;
    if (responseData?.message) {
        return responseData.message;
    }
    return fallback;
};

const fetchMeta = async () => {
    const response = await api.get('/admin/reports/meta');
    sellers.value = response.data?.sellers || [];
};

const fetchMenu = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/menu-items', {
            params: {
                seller_id: filters.value.seller_id || undefined,
                is_available: filters.value.is_available || undefined,
                search: filters.value.search || undefined,
            },
        });

        items.value = response.data?.data || [];
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load menu items.');
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await fetchMeta();
    await fetchMenu();
});
</script>

