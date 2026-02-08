<template>
    <div class="card-grid">
        <div class="card">
            <div class="card-title">Active Sellers</div>
            <div class="card-value">{{ stats.active_sellers }}</div>
        </div>
        <div class="card">
            <div class="card-title">Pending Approvals</div>
            <div class="card-value">{{ stats.pending_approvals }}</div>
        </div>
        <div class="card">
            <div class="card-title">Today Orders</div>
            <div class="card-value">{{ stats.today_orders }}</div>
        </div>
        <div class="card">
            <div class="card-title">Monthly Revenue</div>
            <div class="card-value">BDT {{ money(stats.monthly_revenue) }}</div>
        </div>
    </div>

    <div class="card">
        <div class="section-title">Recent Seller Activity</div>
        <div v-if="loading" class="login-subtitle">Loading dashboard...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="stats.recent_sellers.length === 0" class="login-subtitle">
            No seller activity yet.
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Type</th>
                    <th>Area</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="seller in stats.recent_sellers" :key="seller.id">
                    <td>{{ seller.name }}</td>
                    <td>{{ seller.type }}</td>
                    <td>{{ seller.area?.name || 'N/A' }}</td>
                    <td>{{ sellerStatus(seller) }}</td>
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
const stats = ref({
    active_sellers: 0,
    pending_approvals: 0,
    today_orders: 0,
    monthly_revenue: 0,
    recent_sellers: [],
});

const money = (value) => Number(value || 0).toFixed(2);

const sellerStatus = (seller) => {
    if (seller.is_blocked) {
        return 'Blocked';
    }
    return seller.is_approved ? 'Approved' : 'Pending';
};

const fetchStats = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/dashboard/stats');
        stats.value = response.data;
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load dashboard stats.';
    } finally {
        loading.value = false;
    }
};

onMounted(fetchStats);
</script>
