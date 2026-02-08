<template>
    <div class="card">
        <div class="section-title">Offers & Coupons</div>

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
                <label>Active</label>
                <select v-model="filters.is_active">
                    <option value="">All</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="field">
                <label>Search</label>
                <input v-model="filters.search" type="text" placeholder="Offer title/code" />
            </div>
            <div class="filters-actions">
                <button class="pill" @click="fetchOffers" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Apply' }}
                </button>
                <button class="pill outline" @click="openCreate">Add Offer</button>
            </div>
        </div>

        <div v-if="loading" class="login-subtitle">Loading offers...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="offers.length === 0" class="login-subtitle">No offers found.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Title</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Min Subtotal</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="offer in offers" :key="offer.id">
                    <td>{{ offer.seller?.name || 'N/A' }}</td>
                    <td>{{ offer.title }}</td>
                    <td>{{ offer.code || '-' }}</td>
                    <td>{{ offer.type }}</td>
                    <td>{{ offer.value }}</td>
                    <td>{{ offer.min_subtotal }}</td>
                    <td>{{ offer.is_active ? 'Active' : 'Inactive' }}</td>
                    <td class="actions-cell">
                        <button class="pill outline" @click="openEdit(offer)">Edit</button>
                        <button class="pill danger" @click="deleteOffer(offer)" :disabled="saving">
                            Delete
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="showModal" class="modal">
        <div class="modal-card">
            <div class="section-title">{{ editingId ? 'Edit Offer' : 'Add Offer' }}</div>
            <form @submit.prevent="submit">
                <div class="grid">
                    <div class="field">
                        <label>Seller</label>
                        <select v-model="form.seller_id" required>
                            <option v-for="seller in sellers" :key="seller.id" :value="seller.id">
                                {{ seller.name }} ({{ seller.type }})
                            </option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Title</label>
                        <input v-model="form.title" type="text" required />
                    </div>
                    <div class="field">
                        <label>Code</label>
                        <input v-model="form.code" type="text" />
                    </div>
                    <div class="field">
                        <label>Type</label>
                        <select v-model="form.type">
                            <option value="percent">percent</option>
                            <option value="fixed">fixed</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Value</label>
                        <input v-model.number="form.value" type="number" min="0" step="0.01" required />
                    </div>
                    <div class="field">
                        <label>Min Subtotal</label>
                        <input v-model.number="form.min_subtotal" type="number" min="0" step="0.01" />
                    </div>
                    <div class="field">
                        <label>Max Discount</label>
                        <input v-model.number="form.max_discount" type="number" min="0" step="0.01" />
                    </div>
                    <div class="field">
                        <label>Start At</label>
                        <input v-model="form.start_at" type="datetime-local" />
                    </div>
                    <div class="field">
                        <label>End At</label>
                        <input v-model="form.end_at" type="datetime-local" />
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
const offers = ref([]);
const sellers = ref([]);
const showModal = ref(false);
const editingId = ref(null);

const filters = ref({
    seller_id: '',
    is_active: '',
    search: '',
});

const form = ref({
    seller_id: null,
    title: '',
    code: '',
    type: 'percent',
    value: 0,
    min_subtotal: 0,
    max_discount: null,
    start_at: '',
    end_at: '',
    is_active: true,
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

const normalizeDateValue = (value) => {
    if (!value) {
        return '';
    }
    return String(value).slice(0, 16);
};

const loadSellers = async () => {
    const response = await api.get('/admin/reports/meta');
    sellers.value = response.data?.sellers || [];
};

const resetForm = () => {
    form.value = {
        seller_id: sellers.value[0]?.id || null,
        title: '',
        code: '',
        type: 'percent',
        value: 0,
        min_subtotal: 0,
        max_discount: null,
        start_at: '',
        end_at: '',
        is_active: true,
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

const openEdit = (offer) => {
    editingId.value = offer.id;
    form.value = {
        seller_id: offer.seller_id,
        title: offer.title || '',
        code: offer.code || '',
        type: offer.type || 'percent',
        value: Number(offer.value || 0),
        min_subtotal: Number(offer.min_subtotal || 0),
        max_discount: offer.max_discount === null ? null : Number(offer.max_discount),
        start_at: normalizeDateValue(offer.start_at),
        end_at: normalizeDateValue(offer.end_at),
        is_active: Boolean(offer.is_active),
    };
    formError.value = '';
    showModal.value = true;
};

const fetchOffers = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/offers', {
            params: {
                seller_id: filters.value.seller_id || undefined,
                is_active: filters.value.is_active || undefined,
                search: filters.value.search || undefined,
            },
        });
        offers.value = response.data?.data || [];
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load offers.');
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
            title: form.value.title,
            code: form.value.code || null,
            type: form.value.type,
            value: form.value.value,
            min_subtotal: form.value.min_subtotal || 0,
            max_discount: form.value.max_discount === '' ? null : form.value.max_discount,
            start_at: form.value.start_at || null,
            end_at: form.value.end_at || null,
            is_active: form.value.is_active,
        };

        if (editingId.value) {
            await api.patch(`/admin/offers/${editingId.value}`, payload);
        } else {
            await api.post('/admin/offers', payload);
        }

        close();
        await fetchOffers();
    } catch (err) {
        formError.value = extractErrorMessage(err, 'Failed to save offer.');
    } finally {
        saving.value = false;
    }
};

const deleteOffer = async (offer) => {
    if (!window.confirm(`Delete offer "${offer.title}"?`)) {
        return;
    }

    saving.value = true;
    error.value = '';
    try {
        await api.delete(`/admin/offers/${offer.id}`);
        await fetchOffers();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to delete offer.');
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await loadSellers();
    resetForm();
    await fetchOffers();
});
</script>
