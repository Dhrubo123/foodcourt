<template>
    <div class="card">
        <div class="actions-row">
            <button class="pill" @click="openCreate">Add Product</button>
        </div>

        <div v-if="loading" class="login-subtitle">Loading menu...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="products.length === 0" class="login-subtitle">No products yet.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Cost</th>
                    <th>Available</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="product in products" :key="product.id">
                    <td>{{ product.name }}</td>
                    <td>BDT {{ Number(product.price || 0).toFixed(2) }}</td>
                    <td>BDT {{ Number(product.cost_price || 0).toFixed(2) }}</td>
                    <td>{{ product.is_available ? 'Yes' : 'No' }}</td>
                    <td>
                        <button class="pill outline" @click="openEdit(product)">Edit</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="showModal" class="modal">
        <div class="modal-card">
            <div class="section-title">{{ editingId ? 'Edit Product' : 'Add Product' }}</div>
            <form @submit.prevent="submit">
                <div class="grid">
                    <div class="field">
                        <label>Name</label>
                        <input v-model="form.name" type="text" required />
                    </div>
                    <div class="field">
                        <label>Description</label>
                        <input v-model="form.description" type="text" />
                    </div>
                    <div class="field">
                        <label>Price</label>
                        <input v-model.number="form.price" type="number" min="0" step="0.01" required />
                    </div>
                    <div class="field">
                        <label>Cost Price</label>
                        <input v-model.number="form.cost_price" type="number" min="0" step="0.01" />
                    </div>
                    <div class="field">
                        <label>Available</label>
                        <select v-model="form.is_available">
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

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const formError = ref('');
const products = ref([]);
const showModal = ref(false);
const editingId = ref(null);

const form = ref({
    name: '',
    description: '',
    price: 0,
    cost_price: 0,
    is_available: true,
});

const resetForm = () => {
    form.value = {
        name: '',
        description: '',
        price: 0,
        cost_price: 0,
        is_available: true,
    };
    formError.value = '';
};

const fetchProducts = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/seller/products');
        products.value = response.data?.data || [];
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load menu.';
    } finally {
        loading.value = false;
    }
};

const openCreate = () => {
    editingId.value = null;
    resetForm();
    showModal.value = true;
};

const openEdit = (product) => {
    editingId.value = product.id;
    form.value = {
        name: product.name || '',
        description: product.description || '',
        price: Number(product.price || 0),
        cost_price: Number(product.cost_price || 0),
        is_available: Boolean(product.is_available),
    };
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
            await api.patch(`/seller/products/${editingId.value}`, form.value);
        } else {
            await api.post('/seller/products', form.value);
        }
        close();
        await fetchProducts();
    } catch (err) {
        formError.value = err?.response?.data?.message || 'Failed to save product.';
    } finally {
        saving.value = false;
    }
};

onMounted(fetchProducts);
</script>
