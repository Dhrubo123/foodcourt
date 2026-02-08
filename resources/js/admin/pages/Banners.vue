<template>
    <div class="card">
        <div class="actions-row">
            <button class="pill" @click="openCreate">Add Banner</button>
        </div>

        <div v-if="loading" class="login-subtitle">Loading banners...</div>
        <div v-else-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="banners.length === 0" class="login-subtitle">No banners found.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Image</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th>Schedule</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="banner in banners" :key="banner.id">
                    <td>{{ banner.title }}</td>
                    <td>
                        <img v-if="banner.image_path" :src="banner.image_path" class="thumb" alt="Banner" />
                        <span v-else>-</span>
                    </td>
                    <td>{{ banner.is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ banner.sort_order }}</td>
                    <td>{{ formatSchedule(banner) }}</td>
                    <td>
                        <button class="pill outline" @click="openEdit(banner)">Edit</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="showModal" class="modal">
        <div class="modal-card">
            <div class="section-title">{{ editingId ? 'Edit Banner' : 'Add Banner' }}</div>
            <form @submit.prevent="submit">
                <div class="grid">
                    <div class="field">
                        <label>Title</label>
                        <input v-model="form.title" type="text" required />
                    </div>
                    <div class="field">
                        <label>Subtitle</label>
                        <input v-model="form.subtitle" type="text" />
                    </div>
                    <div class="field">
                        <label>Image URL (optional)</label>
                        <input v-model="form.image_path" type="text" />
                    </div>
                    <div class="field">
                        <label>Upload Banner (1920x800)</label>
                        <input type="file" accept="image/png,image/jpeg,image/webp" @change="onImageChange" />
                    </div>
                    <div v-if="form.current_image_path" class="field">
                        <label>Current Image</label>
                        <img :src="form.current_image_path" class="banner-preview" alt="Current banner" />
                    </div>
                    <div class="field">
                        <label>CTA Label</label>
                        <input v-model="form.cta_label" type="text" />
                    </div>
                    <div class="field">
                        <label>CTA Link</label>
                        <input v-model="form.cta_link" type="text" />
                    </div>
                    <div class="field">
                        <label>Sort Order</label>
                        <input v-model.number="form.sort_order" type="number" min="0" />
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

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const formError = ref('');
const banners = ref([]);
const showModal = ref(false);
const editingId = ref(null);
const imageFile = ref(null);

const form = ref({
    title: '',
    subtitle: '',
    image_path: '',
    current_image_path: '',
    cta_label: '',
    cta_link: '',
    sort_order: 0,
    start_at: '',
    end_at: '',
    is_active: true,
});

const resetForm = () => {
    form.value = {
        title: '',
        subtitle: '',
        image_path: '',
        current_image_path: '',
        cta_label: '',
        cta_link: '',
        sort_order: 0,
        start_at: '',
        end_at: '',
        is_active: true,
    };
    imageFile.value = null;
    formError.value = '';
};

const normalizeDateValue = (value) => {
    if (!value) {
        return '';
    }
    return String(value).slice(0, 16);
};

const formatSchedule = (banner) => {
    const from = banner.start_at ? String(banner.start_at).replace('T', ' ').slice(0, 16) : 'Always';
    const to = banner.end_at ? String(banner.end_at).replace('T', ' ').slice(0, 16) : 'No end';
    return `${from} -> ${to}`;
};

const fetchBanners = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/banners');
        banners.value = response.data || [];
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load banners.';
    } finally {
        loading.value = false;
    }
};

const openCreate = () => {
    editingId.value = null;
    resetForm();
    showModal.value = true;
};

const openEdit = (banner) => {
    editingId.value = banner.id;
    form.value = {
        title: banner.title || '',
        subtitle: banner.subtitle || '',
        image_path: banner.image_path || '',
        current_image_path: banner.image_path || '',
        cta_label: banner.cta_label || '',
        cta_link: banner.cta_link || '',
        sort_order: banner.sort_order ?? 0,
        start_at: normalizeDateValue(banner.start_at),
        end_at: normalizeDateValue(banner.end_at),
        is_active: Boolean(banner.is_active),
    };
    showModal.value = true;
};

const onImageChange = (event) => {
    const files = event?.target?.files;
    imageFile.value = files && files.length ? files[0] : null;
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
        const payload = new FormData();
        payload.append('title', form.value.title || '');
        payload.append('subtitle', form.value.subtitle || '');
        payload.append('image_path', form.value.image_path || '');
        payload.append('cta_label', form.value.cta_label || '');
        payload.append('cta_link', form.value.cta_link || '');
        payload.append('sort_order', String(form.value.sort_order ?? 0));
        payload.append('is_active', form.value.is_active ? '1' : '0');
        if (form.value.start_at) {
            payload.append('start_at', form.value.start_at);
        }
        if (form.value.end_at) {
            payload.append('end_at', form.value.end_at);
        }
        if (imageFile.value) {
            payload.append('image', imageFile.value);
        }

        if (editingId.value) {
            payload.append('_method', 'PATCH');
            await api.post(`/admin/banners/${editingId.value}`, payload, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
        } else {
            await api.post('/admin/banners', payload, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
        }
        close();
        await fetchBanners();
    } catch (err) {
        formError.value = extractErrorMessage(err, 'Failed to save banner.');
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

onMounted(fetchBanners);
</script>
