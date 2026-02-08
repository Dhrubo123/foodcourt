<template>
    <div class="card">
        <div class="filters">
            <div class="field">
                <label>Search</label>
                <input v-model="filters.search" type="text" placeholder="Seller or comment" />
            </div>
            <div class="field">
                <label>Rating</label>
                <select v-model="filters.rating">
                    <option value="">All</option>
                    <option value="5">5</option>
                    <option value="4">4</option>
                    <option value="3">3</option>
                    <option value="2">2</option>
                    <option value="1">1</option>
                </select>
            </div>
            <div class="field">
                <label>Visible</label>
                <select v-model="filters.is_visible">
                    <option value="">All</option>
                    <option value="1">Visible</option>
                    <option value="0">Hidden</option>
                </select>
            </div>
            <div class="filters-actions">
                <button class="pill" @click="fetchReviews" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Apply' }}
                </button>
            </div>
        </div>

        <div v-if="error" class="login-subtitle">{{ error }}</div>
        <div v-else-if="reviews.length === 0" class="login-subtitle">No reviews found.</div>

        <table v-else class="table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Seller</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Visible</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="review in reviews" :key="review.id">
                    <td>{{ review.customer?.name || 'N/A' }}</td>
                    <td>{{ review.seller?.name || 'N/A' }}</td>
                    <td>{{ review.rating }}/5</td>
                    <td>{{ review.comment || '-' }}</td>
                    <td>{{ review.is_visible ? 'Yes' : 'No' }}</td>
                    <td>
                        <button class="pill outline" @click="toggleVisibility(review)">
                            {{ review.is_visible ? 'Hide' : 'Show' }}
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
const reviews = ref([]);
const filters = ref({
    search: '',
    rating: '',
    is_visible: '',
});

const fetchReviews = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/admin/reviews', {
            params: {
                search: filters.value.search || undefined,
                rating: filters.value.rating || undefined,
                is_visible: filters.value.is_visible || undefined,
            },
        });
        reviews.value = response.data?.data || [];
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load reviews.';
    } finally {
        loading.value = false;
    }
};

const toggleVisibility = async (review) => {
    try {
        await api.patch(`/admin/reviews/${review.id}/visibility`, {
            is_visible: !review.is_visible,
        });
        await fetchReviews();
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to update review.';
    }
};

onMounted(fetchReviews);
</script>
