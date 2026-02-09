<template>
    <div class="card">
        <div class="section-title">Sellers</div>

        <div class="filters">
            <div class="field">
                <label>Search</label>
                <input v-model="filters.search" type="text" placeholder="Seller, owner, phone" />
            </div>
            <div class="field">
                <label>Type</label>
                <select v-model="filters.type">
                    <option value="">All</option>
                    <option value="cart">Cart</option>
                    <option value="food_court">Food Court</option>
                </select>
            </div>
            <div class="field">
                <label>Approved</label>
                <select v-model="filters.approved">
                    <option value="">All</option>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="field">
                <label>Blocked</label>
                <select v-model="filters.blocked">
                    <option value="">All</option>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="field">
                <label>Subscription</label>
                <select v-model="filters.subscription_status">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="filters-actions">
                <button class="pill" @click="fetchSellers" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Apply' }}
                </button>
                <button class="pill outline" @click="openCreate">Add Seller</button>
            </div>
        </div>

        <div v-if="loading" class="login-subtitle">Loading sellers...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="sellers.length === 0" class="login-subtitle">No sellers yet.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Owner</th>
                    <th>Type</th>
                    <th>Subscription</th>
                    <th>Orders / Revenue</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="seller in sellers" :key="seller.id">
                    <td>
                        <strong>{{ seller.name }}</strong>
                        <div class="login-subtitle">{{ seller.phone || 'N/A' }}</div>
                    </td>
                    <td>
                        {{ seller.owner?.name || 'N/A' }}
                        <div class="login-subtitle">{{ seller.owner?.email || seller.owner?.phone || '' }}</div>
                    </td>
                    <td>{{ seller.type }}</td>
                    <td>{{ subscriptionLabel(seller) }}</td>
                    <td>
                        {{ seller.delivered_orders_count || 0 }} orders
                        <div class="login-subtitle">BDT {{ money(seller.delivered_revenue) }}</div>
                    </td>
                    <td>{{ sellerStatus(seller) }}</td>
                    <td class="actions-cell">
                        <button class="pill outline" @click="openEdit(seller)">Edit</button>
                        <button
                            class="pill outline"
                            @click="toggleApprove(seller)"
                            :disabled="savingId === seller.id"
                        >
                            {{ seller.is_approved ? 'Unapprove' : 'Approve' }}
                        </button>
                        <button class="pill danger" @click="toggleBlock(seller)" :disabled="savingId === seller.id">
                            {{ seller.is_blocked ? 'Unblock' : 'Block' }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="showModal" class="modal">
        <div class="modal-card">
            <div class="section-title">{{ editingId ? 'Edit Seller' : 'Create Seller' }}</div>
            <form @submit.prevent="submit">
                <div class="grid">
                    <div class="field">
                        <label>Owner name</label>
                        <input v-model="form.owner_name" type="text" :required="!editingId" />
                    </div>
                    <div class="field">
                        <label>Owner email</label>
                        <input v-model="form.owner_email" type="email" />
                    </div>
                    <div class="field">
                        <label>Owner phone</label>
                        <input v-model="form.owner_phone" type="text" />
                    </div>
                    <div v-if="!editingId" class="field">
                        <label>Password</label>
                        <input v-model="form.password" type="password" required />
                    </div>
                    <div class="field">
                        <label>Seller name</label>
                        <input v-model="form.name" type="text" required />
                    </div>
                    <div class="field">
                        <label>Type</label>
                        <select v-model="form.type">
                            <option value="cart">Cart</option>
                            <option value="food_court">Food Court</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Phone</label>
                        <input v-model="form.phone" type="text" />
                    </div>
                    <div class="field">
                        <label>Address</label>
                        <input v-model="form.address" type="text" />
                    </div>
                    <div class="field">
                        <label>Area Name</label>
                        <input v-model="form.area_name" type="text" placeholder="e.g. Dhanmondi" />
                    </div>
                    <div class="field">
                        <label>Latitude</label>
                        <input v-model="form.lat" type="number" step="0.000001" />
                    </div>
                    <div class="field">
                        <label>Longitude</label>
                        <input v-model="form.lng" type="number" step="0.000001" />
                    </div>
                    <div class="field">
                        <label>Open time (HH:mm)</label>
                        <input v-model="form.open_time" type="text" placeholder="09:00" />
                    </div>
                    <div class="field">
                        <label>Close time (HH:mm)</label>
                        <input v-model="form.close_time" type="text" placeholder="22:00" />
                    </div>
                    <div class="field">
                        <label>Approved</label>
                        <select v-model="form.is_approved">
                            <option :value="true">Yes</option>
                            <option :value="false">No</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Blocked</label>
                        <select v-model="form.is_blocked">
                            <option :value="false">No</option>
                            <option :value="true">Yes</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Open</label>
                        <select v-model="form.is_open">
                            <option :value="true">Yes</option>
                            <option :value="false">No</option>
                        </select>
                    </div>
                </div>

                <div v-if="formError" class="login-subtitle">{{ formError }}</div>

                <div class="modal-actions">
                    <button type="button" class="pill outline" @click="close">Cancel</button>
                    <button type="submit" class="pill" :disabled="savingForm">
                        {{ savingForm ? 'Saving...' : editingId ? 'Update Seller' : 'Create Seller' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import api from '../lib/api';

const sellers = ref([]);
const loading = ref(false);
const error = ref('');
const showModal = ref(false);
const savingForm = ref(false);
const savingId = ref(null);
const formError = ref('');
const editingId = ref(null);

const filters = ref({
    search: '',
    type: '',
    approved: '',
    blocked: '',
    subscription_status: '',
});

const form = ref({
    owner_name: '',
    owner_email: '',
    owner_phone: '',
    password: '',
    name: '',
    type: 'cart',
    phone: '',
    address: '',
    area_name: '',
    lat: '',
    lng: '',
    open_time: '',
    close_time: '',
    is_approved: false,
    is_blocked: false,
    is_open: true,
});

const money = (value) => Number(value || 0).toFixed(2);

const extractErrorMessage = (err, fallback) => {
    const responseData = err?.response?.data;
    if (responseData?.message) {
        return responseData.message;
    }

    if (typeof responseData === 'string' && responseData.trim() !== '') {
        return responseData.slice(0, 220);
    }

    const validation = responseData?.errors;
    if (validation && typeof validation === 'object') {
        const firstGroup = Object.values(validation)[0];
        if (Array.isArray(firstGroup) && firstGroup[0]) {
            return firstGroup[0];
        }
    }

    if (err?.message) {
        return err.message;
    }

    return fallback;
};

const sellerStatus = (seller) => {
    if (seller.is_blocked) {
        return 'Blocked';
    }
    return seller.is_approved ? 'Approved' : 'Pending';
};

const subscriptionLabel = (seller) => {
    const active = seller.active_subscription;
    if (active) {
        const planName = active.plan?.name ? `${active.plan.name} ` : '';
        const endAt = active.end_at ? String(active.end_at).slice(0, 10) : '-';
        return `${planName}(active until ${endAt})`;
    }

    const latest = seller.latest_subscription;
    if (latest) {
        const endAt = latest.end_at ? String(latest.end_at).slice(0, 10) : '-';
        return `${latest.status || 'inactive'} (${endAt})`;
    }

    return 'No subscription';
};

const resetForm = () => {
    form.value = {
        owner_name: '',
        owner_email: '',
        owner_phone: '',
        password: '',
        name: '',
        type: 'cart',
        phone: '',
        address: '',
        area_name: '',
        lat: '',
        lng: '',
        open_time: '',
        close_time: '',
        is_approved: false,
        is_blocked: false,
        is_open: true,
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

const openEdit = (seller) => {
    editingId.value = seller.id;
    form.value = {
        owner_name: seller.owner?.name || '',
        owner_email: seller.owner?.email || '',
        owner_phone: seller.owner?.phone || '',
        password: '',
        name: seller.name || '',
        type: seller.type || 'cart',
        phone: seller.phone || '',
        address: seller.address || '',
        area_name: seller.area?.name || '',
        lat: seller.lat ?? '',
        lng: seller.lng ?? '',
        open_time: seller.open_time || '',
        close_time: seller.close_time || '',
        is_approved: Boolean(seller.is_approved),
        is_blocked: Boolean(seller.is_blocked),
        is_open: Boolean(seller.is_open),
    };
    formError.value = '';
    showModal.value = true;
};

const fetchSellers = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/sellers', {
            params: {
                search: filters.value.search || undefined,
                type: filters.value.type || undefined,
                approved: filters.value.approved || undefined,
                blocked: filters.value.blocked || undefined,
                subscription_status: filters.value.subscription_status || undefined,
            },
        });
        sellers.value = response.data?.data || [];
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load sellers.');
    } finally {
        loading.value = false;
    }
};

const submit = async () => {
    savingForm.value = true;
    formError.value = '';
    try {
        if (editingId.value) {
            await api.patch(`/admin/sellers/${editingId.value}`, {
                type: form.value.type,
                name: form.value.name,
                phone: form.value.phone || null,
                address: form.value.address || null,
                area_name: form.value.area_name || null,
                lat: form.value.lat === '' ? null : form.value.lat,
                lng: form.value.lng === '' ? null : form.value.lng,
                open_time: form.value.open_time || null,
                close_time: form.value.close_time || null,
                is_open: form.value.is_open,
                is_approved: form.value.is_approved,
                is_blocked: form.value.is_blocked,
            });
        } else {
            await api.post('/admin/sellers', form.value);
        }

        close();
        await fetchSellers();
    } catch (err) {
        formError.value = extractErrorMessage(err, 'Failed to save seller.');
    } finally {
        savingForm.value = false;
    }
};

const toggleApprove = async (seller) => {
    savingId.value = seller.id;
    error.value = '';
    try {
        await api.patch(`/admin/sellers/${seller.id}/approve`, {
            is_approved: !seller.is_approved,
        });
        await fetchSellers();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to change approval.');
    } finally {
        savingId.value = null;
    }
};

const toggleBlock = async (seller) => {
    savingId.value = seller.id;
    error.value = '';
    try {
        await api.patch(`/admin/sellers/${seller.id}/block`, {
            is_blocked: !seller.is_blocked,
        });
        await fetchSellers();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to change block status.');
    } finally {
        savingId.value = null;
    }
};

const onOpenModal = () => {
    openCreate();
};

onMounted(() => {
    fetchSellers();
    window.addEventListener('open-seller-modal', onOpenModal);
});

onUnmounted(() => {
    window.removeEventListener('open-seller-modal', onOpenModal);
});
</script>
