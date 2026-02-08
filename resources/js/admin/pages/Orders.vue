<template>
    <div class="card">
        <div class="section-title">All Orders</div>

        <div v-if="loading" class="login-subtitle">Loading orders...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="orders.length === 0" class="login-subtitle">No orders yet.</div>

        <div v-else class="order-list">
            <div v-for="order in orders" :key="order.id" class="order-card">
                <div class="order-header">
                    <div>
                        <div class="order-title">Order #{{ order.id }}</div>
                        <div class="order-meta">
                            <span>Customer: {{ order.customer?.name || 'N/A' }}</span>
                            <span>Status: {{ order.order_status }}</span>
                            <span>Payment: {{ order.payment_status }}</span>
                        </div>
                    </div>
                    <div class="order-total">BDT {{ order.grand_total }}</div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Seller</th>
                            <th>Type</th>
                            <th>Pickup Token</th>
                            <th>Subtotal</th>
                            <th>Discount</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sellerOrder in order.seller_orders" :key="sellerOrder.id">
                            <td>{{ sellerOrder.seller?.name || 'Unknown' }}</td>
                            <td>{{ sellerOrder.seller?.type || 'N/A' }}</td>
                            <td>{{ sellerOrder.pickup_token }}</td>
                            <td>BDT {{ sellerOrder.subtotal }}</td>
                            <td>BDT {{ sellerOrder.discount_amount }}</td>
                            <td>BDT {{ sellerOrder.total_after_discount }}</td>
                            <td>{{ sellerOrder.seller_status }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../lib/api';

const loading = ref(true);
const error = ref('');
const orders = ref([]);

const fetchOrders = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/orders');
        orders.value = response.data?.data || [];
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load orders.';
    } finally {
        loading.value = false;
    }
};

onMounted(fetchOrders);
</script>
