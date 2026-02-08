<template>
    <div class="card">
        <div class="section-title">Food Court Sales Summary</div>

        <div class="filters">
            <div class="field">
                <label>From</label>
                <input v-model="filters.from" type="date" />
            </div>
            <div class="field">
                <label>To</label>
                <input v-model="filters.to" type="date" />
            </div>
            <div class="field">
                <label>Status (Queue)</label>
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

    <div class="card">
        <div class="section-title">Food Court Pickup Queue</div>
        <div v-if="queue.length === 0" class="login-subtitle">No queue data.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Order</th>
                    <th>Pickup Token</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="item in queue" :key="item.id">
                    <td>{{ item.seller_name }}</td>
                    <td>#{{ item.order_id }}</td>
                    <td>{{ item.pickup_token }}</td>
                    <td>{{ item.seller_status }}</td>
                    <td>{{ item.payment_status }}</td>
                    <td>BDT {{ Number(item.total_after_discount || 0).toFixed(2) }}</td>
                    <td class="actions-cell">
                        <select v-model="item._next_status">
                            <option value="new">new</option>
                            <option value="accepted">accepted</option>
                            <option value="preparing">preparing</option>
                            <option value="ready">ready</option>
                            <option value="delivered">delivered</option>
                            <option value="cancelled">cancelled</option>
                        </select>
                        <button class="pill outline" @click="updateQueueStatus(item)" :disabled="savingId === item.id">
                            Save
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../lib/api';

const rows = ref([]);
const queue = ref([]);
const loading = ref(false);
const savingId = ref(null);
const error = ref('');

const filters = ref({
    from: '',
    to: '',
    status: '',
});

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

const fetchReport = async () => {
    loading.value = true;
    error.value = '';
    try {
        const params = {
            from: filters.value.from || undefined,
            to: filters.value.to || undefined,
            status: filters.value.status || undefined,
        };

        const [summaryRes, queueRes] = await Promise.all([
            api.get('/admin/reports/food-courts', { params }),
            api.get('/admin/reports/food-court-queue', { params: { ...params, per_page: 50 } }),
        ]);

        rows.value = summaryRes.data?.data || [];
        queue.value = (queueRes.data?.data || []).map((item) => ({
            ...item,
            _next_status: item.seller_status,
        }));
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load food court report.');
    } finally {
        loading.value = false;
    }
};

const updateQueueStatus = async (item) => {
    savingId.value = item.id;
    error.value = '';
    try {
        const payload = {
            seller_status: item._next_status,
        };

        if (item._next_status === 'cancelled') {
            const reason = window.prompt('Cancel reason (optional):', item.cancel_reason || '');
            if (reason !== null && reason !== '') {
                payload.cancel_reason = reason;
            }
        }

        await api.patch(`/admin/seller-orders/${item.id}/status`, payload);
        await fetchReport();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to update queue status.');
    } finally {
        savingId.value = null;
    }
};

const resetFilters = () => {
    filters.value = { from: '', to: '', status: '' };
    fetchReport();
};

onMounted(fetchReport);
</script>
