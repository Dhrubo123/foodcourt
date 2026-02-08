<template>
    <div class="card">
        <div class="section-title">Profile & Settings</div>

        <form @submit.prevent="save">
            <div class="grid">
                <div class="field">
                    <label>Owner Name</label>
                    <input v-model="form.owner_name" type="text" />
                </div>
                <div class="field">
                    <label>Owner Email</label>
                    <input v-model="form.owner_email" type="email" />
                </div>
                <div class="field">
                    <label>Owner Phone</label>
                    <input v-model="form.owner_phone" type="text" />
                </div>
                <div class="field">
                    <label>Stall/Food Court Name</label>
                    <input v-model="form.name" type="text" />
                </div>
                <div class="field">
                    <label>Business Phone</label>
                    <input v-model="form.phone" type="text" />
                </div>
                <div class="field">
                    <label>Address</label>
                    <input v-model="form.address" type="text" />
                </div>
                <div class="field">
                    <label>Open Time</label>
                    <input v-model="form.open_time" type="text" placeholder="09:00" />
                </div>
                <div class="field">
                    <label>Close Time</label>
                    <input v-model="form.close_time" type="text" placeholder="22:00" />
                </div>
                <div class="field">
                    <label>Open / Close Toggle</label>
                    <select v-model="form.is_open">
                        <option :value="true">Open</option>
                        <option :value="false">Closed</option>
                    </select>
                </div>
                <div class="field">
                    <label>Default Preparation Time (minutes)</label>
                    <input v-model.number="form.default_prep_time_minutes" type="number" min="1" max="180" />
                </div>
                <div class="field">
                    <label>Logo</label>
                    <input type="file" accept="image/png,image/jpeg,image/webp" @change="onLogoChange" />
                </div>
                <div v-if="form.logo_path" class="field">
                    <label>Current Logo</label>
                    <img :src="form.logo_path" class="banner-preview" alt="Seller logo" />
                </div>
            </div>

            <div v-if="error" class="login-subtitle">{{ error }}</div>

            <div class="modal-actions">
                <button type="button" class="pill outline" @click="fetchProfile" :disabled="loading">Reload</button>
                <button type="submit" class="pill" :disabled="loading">
                    {{ loading ? 'Saving...' : 'Save Profile' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../lib/api';

const loading = ref(false);
const error = ref('');
const logoFile = ref(null);

const form = ref({
    owner_name: '',
    owner_email: '',
    owner_phone: '',
    name: '',
    phone: '',
    address: '',
    open_time: '',
    close_time: '',
    is_open: true,
    default_prep_time_minutes: null,
    logo_path: '',
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

const onLogoChange = (event) => {
    const files = event?.target?.files;
    logoFile.value = files && files.length ? files[0] : null;
};

const fetchProfile = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/seller/profile');
        const seller = response.data?.seller;
        form.value = {
            owner_name: seller?.owner?.name || '',
            owner_email: seller?.owner?.email || '',
            owner_phone: seller?.owner?.phone || '',
            name: seller?.name || '',
            phone: seller?.phone || '',
            address: seller?.address || '',
            open_time: seller?.open_time || '',
            close_time: seller?.close_time || '',
            is_open: Boolean(seller?.is_open),
            default_prep_time_minutes: seller?.default_prep_time_minutes || null,
            logo_path: seller?.logo_path || '',
        };
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to load profile.');
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    loading.value = true;
    error.value = '';

    try {
        const payload = new FormData();
        payload.append('owner_name', form.value.owner_name || '');
        payload.append('owner_email', form.value.owner_email || '');
        payload.append('owner_phone', form.value.owner_phone || '');
        payload.append('name', form.value.name || '');
        payload.append('phone', form.value.phone || '');
        payload.append('address', form.value.address || '');
        payload.append('open_time', form.value.open_time || '');
        payload.append('close_time', form.value.close_time || '');
        payload.append('is_open', form.value.is_open ? '1' : '0');

        if (form.value.default_prep_time_minutes) {
            payload.append('default_prep_time_minutes', String(form.value.default_prep_time_minutes));
        }

        if (logoFile.value) {
            payload.append('logo', logoFile.value);
        }

        await api.post('/seller/profile', payload, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        logoFile.value = null;
        await fetchProfile();
    } catch (err) {
        error.value = extractErrorMessage(err, 'Failed to save profile.');
    } finally {
        loading.value = false;
    }
};

onMounted(fetchProfile);
</script>
