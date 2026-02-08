<template>
    <div class="card">
        <div class="section-title">Order Management</div>

        <div class="filters">
            <div class="field">
                <label>Search</label>
                <input v-model="filters.search" type="text" placeholder="Order ID / token / customer" />
            </div>
            <div class="field">
                <label>Status</label>
                <select v-model="filters.status">
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
                <label>From</label>
                <input v-model="filters.from" type="date" />
            </div>
            <div class="field">
                <label>To</label>
                <input v-model="filters.to" type="date" />
            </div>
            <div class="filters-actions">
                <button class="pill" @click="fetchOrders" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Apply' }}
                </button>
            </div>
        </div>

        <div v-if="error" class="login-subtitle">{{ error }}</div>
        <div v-if="loading" class="login-subtitle">Loading orders...</div>
        <div v-else-if="orders.length === 0" class="login-subtitle">No orders found.</div>

        <div v-else class="order-list">
            <div v-for="row in orders" :key="row.id" class="order-card">
                <div class="order-header">
                    <div>
                        <div class="order-title">Order #{{ row.order_id }} ({{ row.pickup_token }})</div>
                        <div class="order-meta">
                            <span>Customer: {{ row.order?.customer?.name || row.order?.customer?.phone || 'N/A' }}</span>
                            <span>Status: {{ row.seller_status }}</span>
                            <span>Payment: {{ row.order?.payment_status || '-' }}</span>
                        </div>
                        <div v-if="row.cancel_reason" class="login-subtitle">Cancel Reason: {{ row.cancel_reason }}</div>
                    </div>
                    <div class="actions-cell">
                        <select v-model="row._next_status">
                            <option value="new">new</option>
                            <option value="accepted">accepted</option>
                            <option value="preparing">preparing</option>
                            <option value="ready">ready</option>
                            <option value="delivered">delivered</option>
                            <option value="cancelled">cancelled</option>
                        </select>
                        <input
                            v-if="row._next_status === 'accepted' || row._next_status === 'preparing'"
                            v-model.number="row._prep_time"
                            type="number"
                            min="1"
                            placeholder="Prep min"
                        />
                        <button class="pill outline" @click="updateStatus(row)" :disabled="savingId === row.id">
                            Save
                        </button>
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in row.items" :key="item.id">
                            <td>{{ item.product_name_snapshot }}</td>
                            <td>{{ item.qty }}</td>
                            <td>BDT {{ money(item.unit_price_snapshot) }}</td>
                            <td>BDT {{ money(item.line_total) }}</td>
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
const savingId = ref(null);
const orders = ref([]);

const filters = ref({
    search: '',
    status: '',
    from: '',
    to: '',
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

const normalize = (row) => ({
    ...row,
    _next_status: row.seller_status,
    _prep_time: row.prep_time_minutes || '',
});

const fetchOrders = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/seller/orders', {
            params: {
                search: filters.value.search || undefined,
                status: filters.value.status || undefined,
                from: filters.value.from || undefined,
                to: filters.value.to || undefined,
            },
        });

        orders.value = (response.data?.data || []).map(normalize);
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load seller orders.');
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (row) => {
    savingId.value = row.id;
    error.value = '';

    try {
        const payload = {
            seller_status: row._next_status,
        };

        if (row._prep_time) {
            payload.prep_time_minutes = row._prep_time;
        }

        if (row._next_status === 'cancelled') {
            const reason = window.prompt('Cancel reason (optional):', row.cancel_reason || '');
            if (reason !== null && reason !== '') {
                payload.cancel_reason = reason;
            }
        }

        await api.patch(`/seller/orders/${row.id}/status`, payload);
        await fetchOrders();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to update order status.');
    } finally {
        savingId.value = null;
    }
};

onMounted(fetchOrders);
</script>
