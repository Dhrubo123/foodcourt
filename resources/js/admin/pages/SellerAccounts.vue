<template>
    <div class="card">
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
            </div>
        </div>

        <div v-if="error" class="login-subtitle">{{ error }}</div>

        <div v-if="summary" class="card-grid">
            <div class="card">
                <div class="card-title">Revenue</div>
                <div class="card-value">BDT {{ format(summary.revenue) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Gross Sales</div>
                <div class="card-value">BDT {{ format(summary.gross_sales) }}</div>
            </div>
            <div class="card">
                <div class="card-title">COGS</div>
                <div class="card-value">BDT {{ format(summary.cogs) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Profit</div>
                <div class="card-value">BDT {{ format(summary.profit) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Loss (Cancelled)</div>
                <div class="card-value">BDT {{ format(summary.loss) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Delivered Orders</div>
                <div class="card-value">{{ summary.delivered_orders }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="section-title">Daily Accounts</div>
        <div v-if="daily.length === 0" class="login-subtitle">No daily account records yet.</div>
        <table v-else class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Revenue</th>
                    <th>Cancelled Value</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in daily" :key="row.date">
                    <td>{{ row.date }}</td>
                    <td>BDT {{ format(row.revenue) }}</td>
                    <td>BDT {{ format(row.cancelled_value) }}</td>
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
const summary = ref(null);
const daily = ref([]);
const filters = ref({
    from: '',
    to: '',
});

const format = (value) => Number(value || 0).toFixed(2);

const fetchReport = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/seller/reports/summary', {
            params: {
                from: filters.value.from || undefined,
                to: filters.value.to || undefined,
            },
        });
        summary.value = response.data?.summary || null;
        daily.value = response.data?.daily || [];
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load account report.';
    } finally {
        loading.value = false;
    }
};

onMounted(fetchReport);
</script>
