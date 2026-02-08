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
                <div class="card-title">Revenue (Range)</div>
                <div class="card-value">BDT {{ format(summary.revenue) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Today's Revenue</div>
                <div class="card-value">BDT {{ format(summary.today_revenue) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Weekly Revenue</div>
                <div class="card-value">BDT {{ format(summary.weekly_revenue) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Monthly Revenue</div>
                <div class="card-value">BDT {{ format(summary.monthly_revenue) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Commission ({{ summary.commission_percent || 0 }}%)</div>
                <div class="card-value">BDT {{ format(summary.commission_amount) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Net Payout</div>
                <div class="card-value">BDT {{ format(summary.net_payout) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Profit</div>
                <div class="card-value">BDT {{ format(summary.profit) }}</div>
            </div>
            <div class="card">
                <div class="card-title">Cancelled Orders</div>
                <div class="card-value">{{ summary.cancelled_orders || 0 }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="section-title">Daily Sales</div>
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

    <div class="card">
        <div class="section-title">Weekly & Monthly Sales</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Week Start</th>
                    <th>Week Code</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in weekly" :key="row.year_week">
                    <td>{{ row.week_start }}</td>
                    <td>{{ row.year_week }}</td>
                    <td>BDT {{ format(row.revenue) }}</td>
                </tr>
                <tr v-if="weekly.length === 0">
                    <td colspan="3">No weekly data</td>
                </tr>
            </tbody>
        </table>

        <table class="table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in monthly" :key="row.month">
                    <td>{{ row.month }}</td>
                    <td>BDT {{ format(row.revenue) }}</td>
                </tr>
                <tr v-if="monthly.length === 0">
                    <td colspan="2">No monthly data</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="section-title">Top Selling Items</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in topItems" :key="`${row.product_id}-${row.product_name_snapshot}`">
                    <td>{{ row.product_name_snapshot }}</td>
                    <td>{{ row.total_qty }}</td>
                    <td>BDT {{ format(row.total_amount) }}</td>
                </tr>
                <tr v-if="topItems.length === 0">
                    <td colspan="3">No top item data</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="section-title">Peak Order Time & Cancelled Summary</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Hour</th>
                    <th>Total Orders</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in peakOrderTime" :key="row.hour">
                    <td>{{ String(row.hour).padStart(2, '0') }}:00</td>
                    <td>{{ row.total_orders }}</td>
                </tr>
                <tr v-if="peakOrderTime.length === 0">
                    <td colspan="2">No peak time data</td>
                </tr>
            </tbody>
        </table>

        <table class="table">
            <thead>
                <tr>
                    <th>Cancel Reason</th>
                    <th>Orders</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in cancelledByReason" :key="row.reason">
                    <td>{{ row.reason }}</td>
                    <td>{{ row.total_orders }}</td>
                    <td>BDT {{ format(row.total_value) }}</td>
                </tr>
                <tr v-if="cancelledByReason.length === 0">
                    <td colspan="3">No cancelled order data</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="section-title">Payment History</div>
        <div v-if="paymentHistory.length === 0" class="login-subtitle">No payout history yet.</div>
        <table v-else class="table">
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Gross</th>
                    <th>Commission</th>
                    <th>Net</th>
                    <th>Status</th>
                    <th>Method</th>
                    <th>Transaction</th>
                    <th>Paid At</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in paymentHistory" :key="row.id">
                    <td>{{ row.period_from }} to {{ row.period_to }}</td>
                    <td>BDT {{ format(row.gross_amount) }}</td>
                    <td>BDT {{ format(row.commission_amount) }}</td>
                    <td>BDT {{ format(row.net_amount) }}</td>
                    <td>{{ row.status }}</td>
                    <td>{{ row.payment_method || '-' }}</td>
                    <td>{{ row.trx_id || '-' }}</td>
                    <td>{{ shortDate(row.paid_at) }}</td>
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
const weekly = ref([]);
const monthly = ref([]);
const topItems = ref([]);
const peakOrderTime = ref([]);
const cancelledByReason = ref([]);
const paymentHistory = ref([]);

const filters = ref({
    from: '',
    to: '',
});

const format = (value) => Number(value || 0).toFixed(2);
const shortDate = (value) => String(value || '').replace('T', ' ').slice(0, 16);

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
        weekly.value = response.data?.weekly || [];
        monthly.value = response.data?.monthly || [];
        topItems.value = response.data?.top_items || [];
        peakOrderTime.value = response.data?.peak_order_time || [];
        cancelledByReason.value = response.data?.cancelled_by_reason || [];
        paymentHistory.value = response.data?.payment_history || [];
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load account report.';
    } finally {
        loading.value = false;
    }
};

onMounted(fetchReport);
</script>
