<template>
    <div class="card">
        <div class="section-title">Food Court Orders Report</div>

        <div class="filters">
            <div class="field">
                <label>From</label>
                <input v-model="filters.from" type="date" />
            </div>
            <div class="field">
                <label>To</label>
                <input v-model="filters.to" type="date" />
            </div>
            <div class="filters-actions">
                <button class="pill" @click="fetchReport" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Apply' }}
                </button>
                <button class="pill outline" @click="resetFilters" :disabled="loading">Reset</button>
            </div>
        </div>

        <div v-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="rows.length === 0" class="login-subtitle">No food court orders yet.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Food Court</th>
                    <th>Total Orders</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id">
                    <td>{{ row.name }}</td>
                    <td>{{ row.total_orders }}</td>
                    <td>BDT {{ Number(row.total_revenue || 0).toFixed(2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../lib/api';

const rows = ref([]);
const loading = ref(false);
const error = ref('');

const filters = ref({
    from: '',
    to: '',
});

const fetchReport = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/reports/food-courts', {
            params: {
                from: filters.value.from || undefined,
                to: filters.value.to || undefined,
            },
        });
        rows.value = response.data?.data || [];
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load report.';
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    filters.value = { from: '', to: '' };
    fetchReport();
};

onMounted(fetchReport);
</script>
