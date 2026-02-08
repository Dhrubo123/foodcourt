<template>
    <div class="card">
        <div class="section-title">Seller Subscriptions</div>

        <div class="filters">
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
                    <option value="active">active</option>
                    <option value="pending">pending</option>
                    <option value="expired">expired</option>
                    <option value="cancelled">cancelled</option>
                </select>
            </div>
            <div class="filters-actions">
                <button class="pill" @click="fetchSubscriptions" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Apply' }}
                </button>
                <button class="pill outline" @click="openCreate">Add Subscription</button>
            </div>
        </div>

        <div v-if="loading" class="login-subtitle">Loading subscriptions...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="subscriptions.length === 0" class="login-subtitle">No subscriptions found.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in subscriptions" :key="row.id">
                    <td>{{ row.seller?.name || 'N/A' }}</td>
                    <td>{{ row.plan?.name || 'N/A' }}</td>
                    <td>{{ row.status }}</td>
                    <td>{{ shortDate(row.start_at) }}</td>
                    <td>{{ shortDate(row.end_at) }}</td>
                    <td class="actions-cell">
                        <button class="pill outline" @click="openEdit(row)">Edit</button>
                        <button class="pill danger" @click="deleteSubscription(row)" :disabled="saving">
                            Delete
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="showModal" class="modal">
        <div class="modal-card">
            <div class="section-title">{{ editingId ? 'Edit Subscription' : 'Add Subscription' }}</div>
            <form @submit.prevent="submit">
                <div class="grid">
                    <div class="field">
                        <label>Seller</label>
                        <select v-model="form.seller_id" :disabled="Boolean(editingId)">
                            <option v-for="seller in sellers" :key="seller.id" :value="seller.id">
                                {{ seller.name }} ({{ seller.type }})
                            </option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Plan</label>
                        <select v-model="form.plan_id">
                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                {{ plan.name }} - BDT {{ money(plan.price) }} / {{ plan.duration_days }}d
                            </option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Start At</label>
                        <input v-model="form.start_at" type="datetime-local" />
                    </div>
                    <div class="field">
                        <label>End At (optional)</label>
                        <input v-model="form.end_at" type="datetime-local" />
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select v-model="form.status">
                            <option value="active">active</option>
                            <option value="pending">pending</option>
                            <option value="expired">expired</option>
                            <option value="cancelled">cancelled</option>
                        </select>
                    </div>
                </div>

                <div v-if="formError" class="login-subtitle">{{ formError }}</div>

                <div class="modal-actions">
                    <button type="button" class="pill outline" @click="close">Cancel</button>
                    <button type="submit" class="pill" :disabled="saving">
                        {{ saving ? 'Saving...' : editingId ? 'Update' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../lib/api';

const loading = ref(false);
const saving = ref(false);
const error = ref('');
const formError = ref('');
const subscriptions = ref([]);
const sellers = ref([]);
const plans = ref([]);
const showModal = ref(false);
const editingId = ref(null);

const filters = ref({
    seller_id: '',
    status: '',
});

const form = ref({
    seller_id: null,
    plan_id: null,
    start_at: '',
    end_at: '',
    status: 'active',
});

const money = (value) => Number(value || 0).toFixed(2);
const shortDate = (value) => String(value || '').replace('T', ' ').slice(0, 16);

const normalizeDateValue = (value) => {
    if (!value) {
        return '';
    }
    return String(value).slice(0, 16);
};

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

const resetForm = () => {
    form.value = {
        seller_id: sellers.value[0]?.id || null,
        plan_id: plans.value[0]?.id || null,
        start_at: '',
        end_at: '',
        status: 'active',
    };
    formError.value = '';
};

const close = () => {
    showModal.value = false;
    editingId.value = null;
    resetForm();
};

const openCreate = () => {
    editingId.value = null;
    resetForm();
    showModal.value = true;
};

const openEdit = (row) => {
    editingId.value = row.id;
    form.value = {
        seller_id: row.seller_id,
        plan_id: row.plan_id,
        start_at: normalizeDateValue(row.start_at),
        end_at: normalizeDateValue(row.end_at),
        status: row.status || 'active',
    };
    formError.value = '';
    showModal.value = true;
};

const loadMeta = async () => {
    const [sellersRes, plansRes] = await Promise.all([
        api.get('/admin/reports/meta'),
        api.get('/admin/subscription-plans'),
    ]);

    sellers.value = sellersRes.data?.sellers || [];
    plans.value = plansRes.data || [];
};

const fetchSubscriptions = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/subscriptions', {
            params: {
                seller_id: filters.value.seller_id || undefined,
                status: filters.value.status || undefined,
            },
        });
        subscriptions.value = response.data?.data || [];
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load subscriptions.');
    } finally {
        loading.value = false;
    }
};

const submit = async () => {
    saving.value = true;
    formError.value = '';

    try {
        const payload = {
            seller_id: form.value.seller_id,
            plan_id: form.value.plan_id,
            status: form.value.status,
            start_at: form.value.start_at || undefined,
            end_at: form.value.end_at || undefined,
        };

        if (editingId.value) {
            await api.patch(`/admin/subscriptions/${editingId.value}`, payload);
        } else {
            await api.post('/admin/subscriptions', payload);
        }

        close();
        await fetchSubscriptions();
    } catch (err) {
        formError.value = extractErrorMessage(err, 'Failed to save subscription.');
    } finally {
        saving.value = false;
    }
};

const deleteSubscription = async (row) => {
    if (!window.confirm(`Delete subscription #${row.id}?`)) {
        return;
    }

    saving.value = true;
    error.value = '';
    try {
        await api.delete(`/admin/subscriptions/${row.id}`);
        await fetchSubscriptions();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to delete subscription.');
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await loadMeta();
    resetForm();
    await fetchSubscriptions();
});
</script>
