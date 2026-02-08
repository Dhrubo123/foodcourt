<template>
    <div class="card">
        <div class="section-title">Sellers</div>

        <div class="actions-row">
            <button class="pill" @click="showModal = true">Add Seller</button>
        </div>

        <div v-if="loading" class="login-subtitle">Loading sellers...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="sellers.length === 0" class="login-subtitle">No sellers yet.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Type</th>
                    <th>Owner</th>
                    <th>Phone</th>
                    <th>Approved</th>
                    <th>Blocked</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="seller in sellers" :key="seller.id">
                    <td>{{ seller.name }}</td>
                    <td>{{ seller.type }}</td>
                    <td>{{ seller.owner?.name || 'N/A' }}</td>
                    <td>{{ seller.phone || seller.owner?.phone || 'N/A' }}</td>
                    <td>{{ seller.is_approved ? 'Yes' : 'No' }}</td>
                    <td>{{ seller.is_blocked ? 'Yes' : 'No' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="showModal" class="modal">
        <div class="modal-card">
            <div class="section-title">Create Seller</div>
            <form @submit.prevent="submit">
                <div class="grid">
                    <div class="field">
                        <label>Owner name</label>
                        <input v-model="form.owner_name" type="text" />
                    </div>
                    <div class="field">
                        <label>Owner email</label>
                        <input v-model="form.owner_email" type="email" />
                    </div>
                    <div class="field">
                        <label>Owner phone</label>
                        <input v-model="form.owner_phone" type="text" />
                    </div>
                    <div class="field">
                        <label>Password</label>
                        <input v-model="form.password" type="password" />
                    </div>
                    <div class="field">
                        <label>Seller name</label>
                        <input v-model="form.name" type="text" />
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
                </div>

                <div v-if="formError" class="login-subtitle">{{ formError }}</div>

                <div class="modal-actions">
                    <button type="button" class="pill outline" @click="close">Cancel</button>
                    <button type="submit" class="pill" :disabled="saving">
                        {{ saving ? 'Saving...' : 'Create Seller' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../lib/api';

const sellers = ref([]);
const loading = ref(true);
const error = ref('');
const showModal = ref(false);
const saving = ref(false);
const formError = ref('');

const form = ref({
    owner_name: '',
    owner_email: '',
    owner_phone: '',
    password: '',
    name: '',
    type: 'cart',
    phone: '',
    address: '',
    lat: '',
    lng: '',
    open_time: '',
    close_time: '',
});

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
        lat: '',
        lng: '',
        open_time: '',
        close_time: '',
    };
    formError.value = '';
};

const close = () => {
    showModal.value = false;
    resetForm();
};

const fetchSellers = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/sellers');
        sellers.value = response.data?.data || [];
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load sellers.';
    } finally {
        loading.value = false;
    }
};

const submit = async () => {
    saving.value = true;
    formError.value = '';
    try {
        await api.post('/admin/sellers', form.value);
        close();
        fetchSellers();
    } catch (err) {
        formError.value = err?.response?.data?.message || 'Failed to create seller.';
    } finally {
        saving.value = false;
    }
};

const onOpenModal = () => {
    showModal.value = true;
};

onMounted(() => {
    fetchSellers();
    window.addEventListener('open-seller-modal', onOpenModal);
});
</script>
