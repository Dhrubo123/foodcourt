<template>
    <div class="card">
        <div class="section-title">All Orders</div>

        <div class="filters">
            <div class="field">
                <label>Search (Order ID / customer)</label>
                <input v-model="filters.search" type="text" placeholder="e.g. 102" />
            </div>
            <div class="field">
                <label>From</label>
                <input v-model="filters.from" type="date" />
            </div>
            <div class="field">
                <label>To</label>
                <input v-model="filters.to" type="date" />
            </div>
            <div class="field">
                <label>Parent Status</label>
                <select v-model="filters.order_status">
                    <option value="">All</option>
                    <option value="placed">placed</option>
                    <option value="preparing">preparing</option>
                    <option value="ready">ready</option>
                    <option value="completed">completed</option>
                    <option value="cancelled">cancelled</option>
                </select>
            </div>
            <div class="field">
                <label>Seller Status</label>
                <select v-model="filters.seller_status">
                    <option value="">All</option>
                    <option value="new">new</option>
                    <option value="accepted">accepted</option>
                    <option value="preparing">preparing</option>
                    <option value="ready">ready</option>
                    <option value="delivered">delivered</option>
                    <option value="cancelled">cancelled</option>
                </select>
            </div>
            <div class="field">
                <label>Payment Status</label>
                <select v-model="filters.payment_status">
                    <option value="">All</option>
                    <option value="unpaid">unpaid</option>
                    <option value="paid">paid</option>
                    <option value="failed">failed</option>
                </select>
            </div>
            <div class="filters-actions">
                <button class="pill" @click="fetchOrders" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Apply' }}
                </button>
            </div>
        </div>

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
                            <span>Total: BDT {{ money(order.grand_total) }}</span>
                        </div>
                    </div>
                    <div class="actions-cell">
                        <select v-model="order._status_change">
                            <option disabled value="">Parent status</option>
                            <option value="placed">placed</option>
                            <option value="preparing">preparing</option>
                            <option value="ready">ready</option>
                            <option value="completed">completed</option>
                            <option value="cancelled">cancelled</option>
                        </select>
                        <button class="pill outline" @click="updateOrderStatus(order)" :disabled="savingOrderId === order.id">
                            Update
                        </button>
                        <select v-model="order._payment_change">
                            <option disabled value="">Payment</option>
                            <option value="unpaid">unpaid</option>
                            <option value="paid">paid</option>
                            <option value="failed">failed</option>
                        </select>
                        <button class="pill outline" @click="updatePaymentStatus(order)" :disabled="savingOrderId === order.id">
                            Save
                        </button>
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Seller</th>
                            <th>Pickup Token</th>
                            <th>Subtotal</th>
                            <th>Discount</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Cancel Reason</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sellerOrder in order.seller_orders" :key="sellerOrder.id">
                            <td>{{ sellerOrder.seller?.name || 'Unknown' }} ({{ sellerOrder.seller?.type || 'N/A' }})</td>
                            <td>{{ sellerOrder.pickup_token }}</td>
                            <td>BDT {{ money(sellerOrder.subtotal) }}</td>
                            <td>BDT {{ money(sellerOrder.discount_amount) }}</td>
                            <td>BDT {{ money(sellerOrder.total_after_discount) }}</td>
                            <td>{{ sellerOrder.seller_status }}</td>
                            <td>{{ sellerOrder.cancel_reason || '-' }}</td>
                            <td class="actions-cell">
                                <select v-model="sellerOrder._status_change">
                                    <option value="new">new</option>
                                    <option value="accepted">accepted</option>
                                    <option value="preparing">preparing</option>
                                    <option value="ready">ready</option>
                                    <option value="delivered">delivered</option>
                                    <option value="cancelled">cancelled</option>
                                </select>
                                <button
                                    class="pill outline"
                                    @click="updateSellerOrderStatus(order, sellerOrder)"
                                    :disabled="savingSellerOrderId === sellerOrder.id"
                                >
                                    Save
                                </button>
                            </td>
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

const loading = ref(false);
const error = ref('');
const orders = ref([]);
const savingOrderId = ref(null);
const savingSellerOrderId = ref(null);

const filters = ref({
    search: '',
    from: '',
    to: '',
    order_status: '',
    seller_status: '',
    payment_status: '',
});

const money = (value) => Number(value || 0).toFixed(2);

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

const normalizeOrder = (order) => ({
    ...order,
    _status_change: '',
    _payment_change: '',
    seller_orders: (order.seller_orders || []).map((sellerOrder) => ({
        ...sellerOrder,
        _status_change: sellerOrder.seller_status || 'new',
    })),
});

const fetchOrders = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/orders', {
            params: {
                search: filters.value.search || undefined,
                from: filters.value.from || undefined,
                to: filters.value.to || undefined,
                order_status: filters.value.order_status || undefined,
                seller_status: filters.value.seller_status || undefined,
                payment_status: filters.value.payment_status || undefined,
                per_page: 20,
            },
        });
        orders.value = (response.data?.data || []).map(normalizeOrder);
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load orders.');
    } finally {
        loading.value = false;
    }
};

const updateOrderStatus = async (order) => {
    if (!order._status_change) {
        return;
    }

    savingOrderId.value = order.id;
    error.value = '';
    try {
        await api.patch(`/admin/orders/${order.id}/status`, {
            order_status: order._status_change,
        });
        await fetchOrders();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to update order status.');
    } finally {
        savingOrderId.value = null;
    }
};

const updatePaymentStatus = async (order) => {
    if (!order._payment_change) {
        return;
    }

    savingOrderId.value = order.id;
    error.value = '';
    try {
        await api.patch(`/admin/orders/${order.id}/payment-status`, {
            payment_status: order._payment_change,
        });
        await fetchOrders();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to update payment status.');
    } finally {
        savingOrderId.value = null;
    }
};

const updateSellerOrderStatus = async (order, sellerOrder) => {
    savingSellerOrderId.value = sellerOrder.id;
    error.value = '';

    try {
        const payload = {
            seller_status: sellerOrder._status_change,
        };

        if (sellerOrder._status_change === 'cancelled') {
            const reason = window.prompt('Cancel reason (optional):', sellerOrder.cancel_reason || '');
            if (reason !== null && reason !== '') {
                payload.cancel_reason = reason;
            }
        }

        await api.patch(`/admin/seller-orders/${sellerOrder.id}/status`, payload);
        await fetchOrders();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to update seller order status.');
    } finally {
        savingSellerOrderId.value = null;
    }
};

onMounted(fetchOrders);
</script>
