<template>
    <div class="card-grid">
        <div class="card">
            <div class="card-title">Current Plan</div>
            <div class="card-value">{{ currentPlanName }}</div>
        </div>
        <div class="card">
            <div class="card-title">Plan Expiry</div>
            <div class="card-value">{{ currentExpiry }}</div>
        </div>
        <div class="card">
            <div class="card-title">Status</div>
            <div class="card-value">{{ currentStatus }}</div>
        </div>
    </div>

    <div class="card">
        <div class="section-title">Renew Subscription</div>

        <div class="grid">
            <div class="field">
                <label>Select Plan</label>
                <select v-model="selectedPlanId">
                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                        {{ plan.name }} - BDT {{ money(plan.price) }} / {{ plan.duration_days }} days
                    </option>
                </select>
            </div>
            <div class="field">
                <label>Action</label>
                <button class="pill" @click="renew" :disabled="loading || !selectedPlanId">
                    {{ loading ? 'Processing...' : 'Renew Plan' }}
                </button>
            </div>
        </div>

        <div v-if="error" class="login-subtitle">{{ error }}</div>
    </div>

    <div class="card">
        <div class="section-title">Subscription History</div>

        <div v-if="history.length === 0" class="login-subtitle">No subscription history.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Start</th>
                    <th>End</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in history" :key="row.id">
                    <td>{{ row.plan?.name || 'N/A' }}</td>
                    <td>{{ row.status }}</td>
                    <td>{{ shortDate(row.start_at) }}</td>
                    <td>{{ shortDate(row.end_at) }}</td>
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
const currentSubscription = ref(null);
const plans = ref([]);
const history = ref([]);
const selectedPlanId = ref(null);

const money = (value) => Number(value || 0).toFixed(2);
const shortDate = (value) => String(value || '').replace('T', ' ').slice(0, 16);

const currentPlanName = computed(() => currentSubscription.value?.plan?.name || 'No active plan');
const currentExpiry = computed(() => shortDate(currentSubscription.value?.end_at) || '-');
const currentStatus = computed(() => currentSubscription.value?.status || 'inactive');

const fetchSubscription = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/seller/subscription');
        currentSubscription.value = response.data?.current_subscription || null;
        plans.value = response.data?.plans || [];
        history.value = response.data?.history || [];

        if (!selectedPlanId.value && plans.value.length > 0) {
            selectedPlanId.value = plans.value[0].id;
        }
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load subscription.';
    } finally {
        loading.value = false;
    }
};

const renew = async () => {
    if (!selectedPlanId.value) {
        return;
    }

    loading.value = true;
    error.value = '';
    try {
        await api.post('/seller/subscription/renew', {
            plan_id: selectedPlanId.value,
        });

        await fetchSubscription();
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to renew plan.';
    } finally {
        loading.value = false;
    }
};

onMounted(fetchSubscription);
</script>
