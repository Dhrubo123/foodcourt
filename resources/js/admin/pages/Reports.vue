<template>
    <div class="card">
        <div class="section-title">Report Filters</div>
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
                <label>Seller/Cart</label>
                <select v-model="filters.seller_id">
                    <option value="">All</option>
                    <option v-for="seller in sellers" :key="seller.id" :value="String(seller.id)">
                        {{ seller.name }} ({{ seller.type }})
                    </option>
                </select>
            </div>
            <div class="field">
                <label>Seller Type</label>
                <select v-model="filters.seller_type">
                    <option value="">All</option>
                    <option value="cart">Cart</option>
                    <option value="food_court">Food Court</option>
                </select>
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
                <label>Payment Status</label>
                <select v-model="filters.payment_status">
                    <option value="">All</option>
                    <option value="unpaid">unpaid</option>
                    <option value="paid">paid</option>
                    <option value="failed">failed</option>
                </select>
            </div>
            <div class="field">
                <label>Payment Method</label>
                <select v-model="filters.payment_method">
                    <option value="">All</option>
                    <option value="cash">cash</option>
                    <option value="sslcommerz">sslcommerz</option>
                </select>
            </div>
            <div class="field">
                <label>Commission %</label>
                <input v-model.number="filters.commission_percent" type="number" min="0" max="100" />
            </div>
            <div class="filters-actions">
                <button class="pill" @click="fetchAll" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Apply' }}
                </button>
            </div>
        </div>
        <div v-if="error" class="login-subtitle">{{ error }}</div>
    </div>

    <div class="card">
        <div class="section-title">Sales Summary</div>
        <div class="card-grid">
            <div class="card">
                <div class="card-title">Parent Orders</div>
                <div class="card-value">{{ salesSummary.parent_orders || 0 }}</div>
            </div>
            <div class="card">
                <div class="card-title">Seller Orders</div>
                <div class="card-value">{{ salesSummary.seller_orders || 0 }}</div>
            </div>
            <div class="card">
                <div class="card-title">Gross Sales</div>
                <div class="card-value">BDT {{ money(salesSummary.gross_sales) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Net Sales</div>
                <div class="card-value">BDT {{ money(salesSummary.net_sales) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Paid Sales</div>
                <div class="card-value">BDT {{ money(salesSummary.paid_sales) }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="section-title">Paid Breakdown</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Payment Status</th>
                    <th>Seller Orders</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in paymentStatusBreakdown" :key="row.payment_status">
                    <td>{{ row.payment_status }}</td>
                    <td>{{ row.seller_orders }}</td>
                    <td>BDT {{ money(row.amount) }}</td>
                </tr>
                <tr v-if="paymentStatusBreakdown.length === 0">
                    <td colspan="3">No data</td>
                </tr>
            </tbody>
        </table>
        <table class="table">
            <thead>
                <tr>
                    <th>Method</th>
                    <th>Payments</th>
                    <th>Paid Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in paymentMethodBreakdown" :key="row.method">
                    <td>{{ row.method }}</td>
                    <td>{{ row.payments_count }}</td>
                    <td>BDT {{ money(row.paid_amount) }}</td>
                </tr>
                <tr v-if="paymentMethodBreakdown.length === 0">
                    <td colspan="3">No paid method data</td>
                </tr>
            </tbody>
        </table>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Parent Orders</th>
                    <th>Seller Orders</th>
                    <th>Total Sales</th>
                    <th>Paid Sales</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in dailySales" :key="row.date">
                    <td>{{ row.date }}</td>
                    <td>{{ row.parent_orders }}</td>
                    <td>{{ row.seller_orders }}</td>
                    <td>BDT {{ money(row.total_sales) }}</td>
                    <td>BDT {{ money(row.paid_sales) }}</td>
                </tr>
                <tr v-if="dailySales.length === 0">
                    <td colspan="5">No daily totals</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="section-title">
            Seller Earnings / Settlement Report (Commission {{ settlementCommission }}%)
        </div>
        <div class="card-grid">
            <div class="card">
                <div class="card-title">Gross</div>
                <div class="card-value">BDT {{ money(settlementTotals.gross_amount) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Commission</div>
                <div class="card-value">BDT {{ money(settlementTotals.commission_amount) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Net</div>
                <div class="card-value">BDT {{ money(settlementTotals.net_amount) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Orders</div>
                <div class="card-value">{{ settlementTotals.orders_count || 0 }}</div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Type</th>
                    <th>Orders</th>
                    <th>Gross</th>
                    <th>Commission</th>
                    <th>Net</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in settlements" :key="row.seller_id">
                    <td>{{ row.seller_name }}</td>
                    <td>{{ row.seller_type }}</td>
                    <td>{{ row.orders_count }}</td>
                    <td>BDT {{ money(row.gross_amount) }}</td>
                    <td>BDT {{ money(row.commission_amount) }}</td>
                    <td>BDT {{ money(row.net_amount) }}</td>
                </tr>
                <tr v-if="settlements.length === 0">
                    <td colspan="6">No settlement data</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="section-title">Orders Report</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Seller</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Method</th>
                    <th>Total</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in orders" :key="row.seller_order_id">
                    <td>#{{ row.order_id }}</td>
                    <td>{{ row.seller_name }} ({{ row.seller_type }})</td>
                    <td>{{ row.seller_status }}</td>
                    <td>{{ row.payment_status }}</td>
                    <td>{{ row.payment_method }}</td>
                    <td>BDT {{ money(row.total_after_discount) }}</td>
                    <td>{{ shortDate(row.order_date) }}</td>
                </tr>
                <tr v-if="orders.length === 0">
                    <td colspan="7">No orders found for this filter</td>
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
const sellers = ref([]);

const filters = ref({
    from: '',
    to: '',
    seller_id: '',
    seller_type: '',
    status: '',
    payment_status: '',
    payment_method: '',
    commission_percent: 10,
});

const orders = ref([]);
const salesSummary = ref({});
const dailySales = ref([]);
const paymentStatusBreakdown = ref([]);
const paymentMethodBreakdown = ref([]);
const settlements = ref([]);
const settlementTotals = ref({});
const settlementCommission = ref(10);

const baseParams = () => ({
    from: filters.value.from || undefined,
    to: filters.value.to || undefined,
    seller_id: filters.value.seller_id || undefined,
    seller_type: filters.value.seller_type || undefined,
    status: filters.value.status || undefined,
    payment_status: filters.value.payment_status || undefined,
    payment_method: filters.value.payment_method || undefined,
});

const money = (value) => Number(value || 0).toFixed(2);
const shortDate = (value) => String(value || '').replace('T', ' ').slice(0, 16);

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

const fetchMeta = async () => {
    const response = await api.get('/admin/reports/meta');
    sellers.value = response.data?.sellers || [];
};

const fetchAll = async () => {
    loading.value = true;
    error.value = '';
    try {
        const params = baseParams();

        const [ordersRes, salesRes, settlementsRes] = await Promise.all([
            api.get('/admin/reports/orders', { params: { ...params, per_page: 20 } }),
            api.get('/admin/reports/sales-summary', { params }),
            api.get('/admin/reports/seller-settlements', {
                params: {
                    ...params,
                    commission_percent: filters.value.commission_percent ?? 10,
                },
            }),
        ]);

        orders.value = ordersRes.data?.data || [];
        salesSummary.value = salesRes.data?.summary || {};
        dailySales.value = salesRes.data?.daily || [];
        paymentStatusBreakdown.value = salesRes.data?.payment_status_breakdown || [];
        paymentMethodBreakdown.value = salesRes.data?.payment_method_breakdown || [];
        settlements.value = settlementsRes.data?.rows || [];
        settlementTotals.value = settlementsRes.data?.totals || {};
        settlementCommission.value = settlementsRes.data?.commission_percent ?? 10;
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load reports.');
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    await fetchMeta();
    await fetchAll();
});
</script>
