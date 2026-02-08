<template>
    <div class="card-grid">
        <div class="card">
            <div class="card-title">Average Rating</div>
            <div class="card-value">{{ summary.average_rating || 0 }}</div>
        </div>
        <div class="card">
            <div class="card-title">Total Reviews</div>
            <div class="card-value">{{ summary.total_reviews || 0 }}</div>
        </div>
    </div>

    <div class="card">
        <div class="section-title">Ratings Overview</div>

        <div class="filters">
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
                <label>Search</label>
                <input v-model="filters.search" type="text" placeholder="Customer/comment" />
            </div>
            <div class="filters-actions">
                <button class="pill" @click="fetchReviews" :disabled="loading">
                    {{ loading ? 'Loading...' : 'Apply' }}
                </button>
            </div>
        </div>

        <div v-if="error" class="login-subtitle">{{ error }}</div>

        <table class="table">
            <thead>
                <tr>
                    <th>Rating</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in summary.breakdown || []" :key="row.rating">
                    <td>{{ row.rating }}</td>
                    <td>{{ row.total }}</td>
                </tr>
                <tr v-if="!(summary.breakdown || []).length">
                    <td colspan="2">No breakdown data</td>
                </tr>
            </tbody>
        </table>

        <table class="table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in reviews" :key="row.id">
                    <td>{{ row.customer?.name || row.customer?.phone || 'N/A' }}</td>
                    <td>{{ row.rating }}/5</td>
                    <td>{{ row.comment || '-' }}</td>
                    <td>{{ shortDate(row.created_at) }}</td>
                </tr>
                <tr v-if="reviews.length === 0">
                    <td colspan="4">No reviews found.</td>
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
const summary = ref({});
const reviews = ref([]);

const filters = ref({
    rating: '',
    search: '',
});

const shortDate = (value) => String(value || '').replace('T', ' ').slice(0, 16);

const fetchReviews = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.get('/seller/reviews', {
            params: {
                rating: filters.value.rating || undefined,
                search: filters.value.search || undefined,
            },
        });

        summary.value = response.data?.summary || {};
        reviews.value = response.data?.reviews?.data || [];
    } catch (err) {
        error.value = err?.response?.data?.message || 'Failed to load reviews.';
    } finally {
        loading.value = false;
    }
};

onMounted(fetchReviews);
</script>
