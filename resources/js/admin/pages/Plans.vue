<template>
    <div class="card-grid">
        <div class="card">
            <div class="card-title">Total Plans</div>
            <div class="card-value">{{ plans.length }}</div>
            <div class="login-subtitle">All subscription plans in system.</div>
        </div>
        <div class="card">
            <div class="card-title">Active Plans</div>
            <div class="card-value">{{ activePlans }}</div>
            <div class="login-subtitle">Plans available for seller purchase.</div>
        </div>
        <div class="card">
            <div class="card-title">Active Subscribers</div>
            <div class="card-value">{{ totalActiveSubscribers }}</div>
            <div class="login-subtitle">Sellers currently subscribed to active plans.</div>
        </div>
    </div>

    <div class="card">
        <div class="actions-row">
            <button class="pill" @click="openCreate">Add Plan</button>
        </div>

        <div v-if="loading" class="login-subtitle">Loading plans...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="plans.length === 0" class="login-subtitle">No plans found.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Price</th>
                    <th>Duration</th>
                    <th>Status</th>
                    <th>Usage</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="plan in plans" :key="plan.id">
                    <td>{{ plan.name }}</td>
                    <td>BDT {{ Number(plan.price || 0).toFixed(2) }}</td>
                    <td>{{ plan.duration_days }} days</td>
                    <td>{{ plan.is_active ? 'Active' : 'Inactive' }}</td>
                    <td>
                        {{ plan.active_subscriptions_count || 0 }} active /
                        {{ plan.total_subscriptions_count || 0 }} total
                    </td>
                    <td class="actions-cell">
                        <button class="pill outline" @click="openEdit(plan)">Edit</button>
                        <button class="pill danger" @click="deletePlan(plan)" :disabled="saving">
                            Delete
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="showModal" class="modal">
        <div class="modal-card">
            <div class="section-title">{{ editingId ? 'Edit Plan' : 'Add Plan' }}</div>
            <form @submit.prevent="submit">
                <div class="grid">
                    <div class="field">
                        <label>Name</label>
                        <input v-model="form.name" type="text" required />
                    </div>
                    <div class="field">
                        <label>Price (BDT)</label>
                        <input v-model.number="form.price" type="number" min="0" step="0.01" required />
                    </div>
                    <div class="field">
                        <label>Duration (days)</label>
                        <input v-model.number="form.duration_days" type="number" min="1" required />
                    </div>
                    <div class="field">
                        <label>Active</label>
                        <select v-model="form.is_active">
                            <option :value="true">Yes</option>
                            <option :value="false">No</option>
                        </select>
                    </div>
                </div>

                <div v-if="formError" class="login-subtitle">{{ formError }}</div>

                <div class="modal-actions">
                    <button type="button" class="pill outline" @click="close">Cancel</button>
                    <button type="submit" class="pill" :disabled="saving">
                        {{ saving ? 'Saving...' : editingId ? 'Update Plan' : 'Create Plan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../lib/api';

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const formError = ref('');
const plans = ref([]);
const showModal = ref(false);
const editingId = ref(null);

const form = ref({
    name: '',
    price: 1000,
    duration_days: 30,
    is_active: true,
});

const activePlans = computed(() => plans.value.filter((plan) => plan.is_active).length);
const totalActiveSubscribers = computed(() =>
    plans.value.reduce((sum, plan) => sum + Number(plan.active_subscriptions_count || 0), 0)
);

const resetForm = () => {
    form.value = {
        name: '',
        price: 1000,
        duration_days: 30,
        is_active: true,
    };
    formError.value = '';
};

const fetchPlans = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/subscription-plans');
        plans.value = response.data || [];
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load plans.');
    } finally {
        loading.value = false;
    }
};

const openCreate = () => {
    editingId.value = null;
    resetForm();
    showModal.value = true;
};

const openEdit = (plan) => {
    editingId.value = plan.id;
    form.value = {
        name: plan.name || '',
        price: Number(plan.price || 0),
        duration_days: Number(plan.duration_days || 30),
        is_active: Boolean(plan.is_active),
    };
    formError.value = '';
    showModal.value = true;
};

const close = () => {
    showModal.value = false;
    editingId.value = null;
    resetForm();
};

const submit = async () => {
    saving.value = true;
    formError.value = '';
    try {
        if (editingId.value) {
            await api.patch(`/admin/subscription-plans/${editingId.value}`, form.value);
        } else {
            await api.post('/admin/subscription-plans', form.value);
        }
        close();
        await fetchPlans();
    } catch (err) {
        formError.value = extractErrorMessage(err, 'Failed to save plan.');
    } finally {
        saving.value = false;
    }
};

const deletePlan = async (plan) => {
    if (!window.confirm(`Delete plan "${plan.name}"?`)) {
        return;
    }

    saving.value = true;
    error.value = '';
    try {
        await api.delete(`/admin/subscription-plans/${plan.id}`);
        await fetchPlans();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to delete plan.');
    } finally {
        saving.value = false;
    }
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

onMounted(fetchPlans);
</script>
