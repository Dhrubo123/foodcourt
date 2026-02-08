<template>
    <div class="card">
        <div class="section-title">Customers</div>

        <div class="filters">
            <div class="field">
                <label>Search</label>
                <input v-model="filters.search" type="text" placeholder="Name/email/phone" />
            </div>
            <div class="field">
                <label>Status</label>
                <select v-model="filters.status">
                    <option value="">All</option>
                    <option value="active">active</option>
                    <option value="blocked">blocked</option>
                </select>
            </div>
            <div class="filters-actions">
                <button class="pill" @click="fetchCustomers" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Apply' }}
                </button>
            </div>
        </div>

        <div v-if="loading" class="login-subtitle">Loading customers...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="customers.length === 0" class="login-subtitle">No customers found.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Reviews</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="customer in customers" :key="customer.id">
                    <td>{{ customer.name }}</td>
                    <td>{{ customer.email || customer.phone || 'N/A' }}</td>
                    <td>{{ customer.status }}</td>
                    <td>{{ customer.orders_count || 0 }}</td>
                    <td>BDT {{ money(customer.total_spent) }}</td>
                    <td>{{ customer.reviews_count || 0 }}</td>
                    <td>
                        <button class="pill outline" @click="toggleStatus(customer)" :disabled="savingId === customer.id">
                            {{ customer.status === 'blocked' ? 'Unblock' : 'Block' }}
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

const loading = ref(false);
const error = ref('');
const savingId = ref(null);
const customers = ref([]);

const filters = ref({
    search: '',
    status: '',
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

const fetchCustomers = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/customers', {
            params: {
                search: filters.value.search || undefined,
                status: filters.value.status || undefined,
            },
        });

        customers.value = response.data?.data || [];
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load customers.');
    } finally {
        loading.value = false;
    }
};

const toggleStatus = async (customer) => {
    savingId.value = customer.id;
    error.value = '';
    try {
        const nextStatus = customer.status === 'blocked' ? 'active' : 'blocked';
        await api.patch(`/admin/customers/${customer.id}/status`, { status: nextStatus });
        await fetchCustomers();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to update customer status.');
    } finally {
        savingId.value = null;
    }
};

onMounted(fetchCustomers);
</script>
