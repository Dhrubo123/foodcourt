<template>
    <div class="card">
        <div class="section-title">Category Management</div>

        <div class="actions-row">
            <button class="pill" @click="openCategoryCreate">Add Category</button>
        </div>

        <div v-if="loadingCategories" class="login-subtitle">Loading categories...</div>
        <div v-else-if="categories.length === 0" class="login-subtitle">No categories found.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="category in categories" :key="category.id">
                    <td>{{ category.name }}</td>
                    <td>{{ category.is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ category.seller_id ? 'My category' : 'System' }}</td>
                    <td>
                        <div v-if="category.seller_id" class="actions-cell">
                            <button class="pill outline" @click="openCategoryEdit(category)">Edit</button>
                            <button class="pill danger" @click="deleteCategory(category)" :disabled="saving">
                                Delete
                            </button>
                        </div>
                        <span v-else>-</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="section-title">Menu Management</div>

        <div class="actions-row">
            <button class="pill" @click="openCreate">Add Food Item</button>
        </div>

        <div v-if="loadingProducts" class="login-subtitle">Loading menu...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="products.length === 0" class="login-subtitle">No products yet.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Cost</th>
                    <th>In Stock</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="product in products" :key="product.id">
                    <td>{{ product.name }}</td>
                    <td>{{ product.category?.name || '-' }}</td>
                    <td>BDT {{ Number(product.price || 0).toFixed(2) }}</td>
                    <td>BDT {{ Number(product.cost_price || 0).toFixed(2) }}</td>
                    <td>{{ product.is_available ? 'Yes' : 'No' }}</td>
                    <td class="actions-cell">
                        <button class="pill outline" @click="openEdit(product)">Edit</button>
                        <button class="pill danger" @click="deleteProduct(product)" :disabled="saving">
                            Delete
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="showProductModal" class="modal">
        <div class="modal-card">
            <div class="section-title">{{ editingProductId ? 'Edit Product' : 'Add Product' }}</div>
            <form @submit.prevent="submitProduct">
                <div class="grid">
                    <div class="field">
                        <label>Name</label>
                        <input v-model="productForm.name" type="text" required />
                    </div>
                    <div class="field">
                        <label>Category</label>
                        <select v-model="productForm.category_id">
                            <option :value="null">None</option>
                            <option v-for="category in activeCategories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Description</label>
                        <input v-model="productForm.description" type="text" />
                    </div>
                    <div class="field">
                        <label>Price</label>
                        <input v-model.number="productForm.price" type="number" min="0" step="0.01" required />
                    </div>
                    <div class="field">
                        <label>Cost Price</label>
                        <input v-model.number="productForm.cost_price" type="number" min="0" step="0.01" />
                    </div>
                    <div class="field">
                        <label>In Stock</label>
                        <select v-model="productForm.is_available">
                            <option :value="true">Yes</option>
                            <option :value="false">No</option>
                        </select>
                    </div>
                </div>

                <div v-if="formError" class="login-subtitle">{{ formError }}</div>
                <div class="modal-actions">
                    <button type="button" class="pill outline" @click="closeProductModal">Cancel</button>
                    <button type="submit" class="pill" :disabled="saving">
                        {{ saving ? 'Saving...' : editingProductId ? 'Update' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="showCategoryModal" class="modal">
        <div class="modal-card">
            <div class="section-title">{{ editingCategoryId ? 'Edit Category' : 'Add Category' }}</div>
            <form @submit.prevent="submitCategory">
                <div class="grid">
                    <div class="field">
                        <label>Name</label>
                        <input v-model="categoryForm.name" type="text" required />
                    </div>
                    <div class="field">
                        <label>Active</label>
                        <select v-model="categoryForm.is_active">
                            <option :value="true">Yes</option>
                            <option :value="false">No</option>
                        </select>
                    </div>
                </div>
                <div v-if="formError" class="login-subtitle">{{ formError }}</div>
                <div class="modal-actions">
                    <button type="button" class="pill outline" @click="closeCategoryModal">Cancel</button>
                    <button type="submit" class="pill" :disabled="saving">
                        {{ saving ? 'Saving...' : editingCategoryId ? 'Update' : 'Create' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../lib/api';

const loadingProducts = ref(true);
const loadingCategories = ref(true);
const saving = ref(false);
const error = ref('');
const formError = ref('');

const products = ref([]);
const categories = ref([]);

const showProductModal = ref(false);
const showCategoryModal = ref(false);
const editingProductId = ref(null);
const editingCategoryId = ref(null);

const productForm = ref({
    category_id: null,
    name: '',
    description: '',
    price: 0,
    cost_price: 0,
    is_available: true,
});

const categoryForm = ref({
    name: '',
    is_active: true,
});

const activeCategories = computed(() => categories.value.filter((category) => category.is_active));

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

const resetProductForm = () => {
    productForm.value = {
        category_id: null,
        name: '',
        description: '',
        price: 0,
        cost_price: 0,
        is_available: true,
    };
    formError.value = '';
};

const resetCategoryForm = () => {
    categoryForm.value = {
        name: '',
        is_active: true,
    };
    formError.value = '';
};

const fetchProducts = async () => {
    loadingProducts.value = true;
    error.value = '';
    try {
        const response = await api.get('/seller/products');
        products.value = response.data?.data || [];
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load menu.');
    } finally {
        loadingProducts.value = false;
    }
};

const fetchCategories = async () => {
    loadingCategories.value = true;
    try {
        const response = await api.get('/seller/categories');
        categories.value = response.data || [];
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load categories.');
    } finally {
        loadingCategories.value = false;
    }
};

const openCreate = () => {
    editingProductId.value = null;
    resetProductForm();
    showProductModal.value = true;
};

const openEdit = (product) => {
    editingProductId.value = product.id;
    productForm.value = {
        category_id: product.category_id || null,
        name: product.name || '',
        description: product.description || '',
        price: Number(product.price || 0),
        cost_price: Number(product.cost_price || 0),
        is_available: Boolean(product.is_available),
    };
    formError.value = '';
    showProductModal.value = true;
};

const closeProductModal = () => {
    showProductModal.value = false;
    editingProductId.value = null;
    resetProductForm();
};

const submitProduct = async () => {
    saving.value = true;
    formError.value = '';
    try {
        if (editingProductId.value) {
            await api.patch(`/seller/products/${editingProductId.value}`, productForm.value);
        } else {
            await api.post('/seller/products', productForm.value);
        }

        closeProductModal();
        await fetchProducts();
    } catch (err) {
        formError.value = extractErrorMessage(err, 'Failed to save product.');
    } finally {
        saving.value = false;
    }
};

const deleteProduct = async (product) => {
    if (!window.confirm(`Delete product "${product.name}"?`)) {
        return;
    }

    saving.value = true;
    error.value = '';
    try {
        await api.delete(`/seller/products/${product.id}`);
        await fetchProducts();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to delete product.');
    } finally {
        saving.value = false;
    }
};

const openCategoryCreate = () => {
    editingCategoryId.value = null;
    resetCategoryForm();
    showCategoryModal.value = true;
};

const openCategoryEdit = (category) => {
    editingCategoryId.value = category.id;
    categoryForm.value = {
        name: category.name || '',
        is_active: Boolean(category.is_active),
    };
    formError.value = '';
    showCategoryModal.value = true;
};

const closeCategoryModal = () => {
    showCategoryModal.value = false;
    editingCategoryId.value = null;
    resetCategoryForm();
};

const submitCategory = async () => {
    saving.value = true;
    formError.value = '';
    try {
        if (editingCategoryId.value) {
            await api.patch(`/seller/categories/${editingCategoryId.value}`, categoryForm.value);
        } else {
            await api.post('/seller/categories', categoryForm.value);
        }

        closeCategoryModal();
        await fetchCategories();
    } catch (err) {
        formError.value = extractErrorMessage(err, 'Failed to save category.');
    } finally {
        saving.value = false;
    }
};

const deleteCategory = async (category) => {
    if (!window.confirm(`Delete category "${category.name}"?`)) {
        return;
    }

    saving.value = true;
    error.value = '';
    try {
        await api.delete(`/seller/categories/${category.id}`);
        await fetchCategories();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to delete category.');
    } finally {
        saving.value = false;
    }
};

onMounted(async () => {
    await Promise.all([fetchCategories(), fetchProducts()]);
});
</script>
