<template>
    <div class="card-grid">
        <div class="card">
            <div class="card-title">Today's Orders</div>
            <div class="card-value">{{ stats.today_orders }}</div>
        </div>
        <div class="card">
            <div class="card-title">Today's Earnings</div>
            <div class="card-value">BDT {{ money(stats.today_earnings) }}</div>
        </div>
        <div class="card">
            <div class="card-title">Pending Orders</div>
            <div class="card-value">{{ stats.pending_orders }}</div>
        </div>
        <div class="card">
            <div class="card-title">Active Orders</div>
            <div class="card-value">{{ stats.active_orders }}</div>
        </div>
        <div class="card">
            <div class="card-title">Average Rating</div>
            <div class="card-value">{{ stats.average_rating }}</div>
        </div>
        <div class="card">
            <div class="card-title">Low Stock Items (&lt;10)</div>
            <div class="card-value">{{ stats.low_stock_count }}</div>
        </div>
    </div>

    <div class="card">
        <div class="section-title">New Order Notifications</div>

        <div class="actions-row">
            <router-link class="pill" to="/seller/orders">Open Order Management</router-link>
        </div>

        <div v-if="loading" class="login-subtitle">Loading seller dashboard...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="stats.new_orders.length === 0" class="login-subtitle">No new orders right now.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Pickup Token</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in stats.new_orders" :key="row.id">
                    <td>#{{ row.order_id }}</td>
                    <td>{{ row.pickup_token }}</td>
                    <td>{{ row.order?.customer?.name || row.order?.customer?.phone || 'N/A' }}</td>
                    <td>BDT {{ money(row.total_after_discount) }}</td>
                    <td>{{ shortDate(row.created_at) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="section-title">Inventory Alerts</div>

        <div class="actions-row">
            <router-link class="pill" to="/seller/menu">Open Menu & Stock</router-link>
        </div>

        <div v-if="loading" class="login-subtitle">Loading inventory alerts...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="stats.low_stock_items.length === 0" class="login-subtitle">
            Inventory looks good. No item is below 10 units.
        </div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Stock Qty</th>
                    <th>Availability</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in stats.low_stock_items" :key="item.id">
                    <td>{{ item.name }}</td>
                    <td>{{ Number(item.stock_quantity || 0) }}</td>
                    <td>{{ item.is_available ? 'Active' : 'Out of stock' }}</td>
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
    today_orders: 0,
    today_earnings: 0,
    pending_orders: 0,
    active_orders: 0,
    average_rating: 0,
    low_stock_count: 0,
    low_stock_items: [],
    new_orders: [],
});

const money = (value) => Number(value || 0).toFixed(2);
const shortDate = (value) => String(value || '').replace('T', ' ').slice(0, 16);

const fetchDashboard = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/seller/dashboard');
        stats.value = {
            ...stats.value,
            ...response.data,
            low_stock_count: Number(response.data?.low_stock_count || 0),
            low_stock_items: response.data?.low_stock_items || [],
            new_orders: response.data?.new_orders || [],
        };
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load seller dashboard.';
    } finally {
        loading.value = false;
    }
};

onMounted(fetchDashboard);
</script>
