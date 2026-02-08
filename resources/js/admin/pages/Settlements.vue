<template>
    <div class="card-grid">
        <div class="card">
            <div class="card-title">Gross Amount</div>
            <div class="card-value">BDT {{ money(summary.gross_amount) }}</div>
        </div>
        <div class="card">
            <div class="card-title">Commission</div>
            <div class="card-value">BDT {{ money(summary.commission_amount) }}</div>
        </div>
        <div class="card">
            <div class="card-title">Paid</div>
            <div class="card-value">BDT {{ money(summary.paid_amount) }}</div>
        </div>
        <div class="card">
            <div class="card-title">Due</div>
            <div class="card-value">BDT {{ money(summary.due_amount) }}</div>
        </div>
    </div>

    <div class="card">
        <div class="section-title">Settlements</div>

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
                <label>Seller</label>
                <select v-model="filters.seller_id">
                    <option value="">All</option>
                    <option v-for="seller in sellers" :key="seller.id" :value="String(seller.id)">
                        {{ seller.name }} ({{ seller.type }})
                    </option>
                </select>
            </div>
            <div class="field">
                <label>Status</label>
                <select v-model="filters.status">
                    <option value="">All</option>
                    <option value="pending">pending</option>
                    <option value="paid">paid</option>
                </select>
            </div>
            <div class="field">
                <label>Commission % (for generate)</label>
                <input v-model.number="filters.commission_percent" type="number" min="0" max="100" />
            </div>
            <div class="filters-actions">
                <button class="pill" @click="fetchAll" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Apply' }}
                </button>
                <button class="pill outline" @click="generateSettlements" :disabled="loading || !canGenerate">
                    Generate
                </button>
            </div>
        </div>

        <div v-if="error" class="login-subtitle">{{ error }}</div>
        <div v-if="loading" class="login-subtitle">Loading settlements...</div>
        <div v-else-if="rows.length === 0" class="login-subtitle">No settlement records.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Period</th>
                    <th>Orders</th>
                    <th>Gross</th>
                    <th>Commission</th>
                    <th>Net</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id">
                    <td>{{ row.seller?.name || 'N/A' }}</td>
                    <td>{{ row.period_from }} to {{ row.period_to }}</td>
                    <td>{{ row.orders_count }}</td>
                    <td>BDT {{ money(row.gross_amount) }}</td>
                    <td>
                        {{ row.commission_percent }}% / BDT {{ money(row.commission_amount) }}
                    </td>
                    <td>BDT {{ money(row.net_amount) }}</td>
                    <td>{{ row.status }}</td>
                    <td>
                        <button
                            v-if="row.status === 'pending'"
                            class="pill outline"
                            @click="markPaid(row)"
                            :disabled="savingId === row.id"
                        >
                            Mark Paid
                        </button>
                        <span v-else>{{ row.payment_method || '-' }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../lib/api';

const loading = ref(false);
const error = ref('');
const savingId = ref(null);
const rows = ref([]);
const sellers = ref([]);

const summary = ref({
    gross_amount: 0,
    commission_amount: 0,
    paid_amount: 0,
    due_amount: 0,
});

const filters = ref({
    from: '',
    to: '',
    seller_id: '',
    status: '',
    commission_percent: 10,
});

const canGenerate = computed(() => Boolean(filters.value.from && filters.value.to));

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

const loadMeta = async () => {
    const response = await api.get('/admin/reports/meta');
    sellers.value = response.data?.sellers || [];
};

const fetchAll = async () => {
    loading.value = true;
    error.value = '';
    try {
        const params = {
            from: filters.value.from || undefined,
            to: filters.value.to || undefined,
            seller_id: filters.value.seller_id || undefined,
            status: filters.value.status || undefined,
        };

        const [rowsRes, summaryRes] = await Promise.all([
            api.get('/admin/settlements', { params }),
            api.get('/admin/settlements/summary'),
        ]);

        rows.value = rowsRes.data?.data || [];
        summary.value = summaryRes.data || summary.value;
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load settlements.');
    } finally {
        loading.value = false;
    }
};

const generateSettlements = async () => {
    if (!canGenerate.value) {
        return;
    }

    loading.value = true;
    error.value = '';
    try {
        await api.post('/admin/settlements/generate', {
            from: filters.value.from,
            to: filters.value.to,
            seller_id: filters.value.seller_id || null,
            commission_percent: filters.value.commission_percent,
        });

        await fetchAll();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to generate settlements.');
    } finally {
        loading.value = false;
    }
};

const markPaid = async (row) => {
    const paymentMethod = window.prompt('Payment method (cash/bank/sslcommerz):', row.payment_method || 'bank');
    if (!paymentMethod) {
        return;
    }

    const trxId = window.prompt('Transaction ID (optional):', row.trx_id || '');

    savingId.value = row.id;
    error.value = '';
    try {
        await api.patch(`/admin/settlements/${row.id}/mark-paid`, {
            payment_method: paymentMethod,
            trx_id: trxId || null,
        });

        await fetchAll();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to mark settlement as paid.');
    } finally {
        savingId.value = null;
    }
};

onMounted(async () => {
    await loadMeta();
    await fetchAll();
});
</script>
