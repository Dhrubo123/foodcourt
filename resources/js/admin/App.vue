<template>
    <div class="app-shell" :class="{ 'is-guest': isGuest }">
        <aside v-if="!isGuest" class="sidebar">
            <div class="brand">
                <div class="brand-mark">R</div>
                <div>
                    <div class="brand-title">restaurent</div>
                    <div class="brand-subtitle">Admin Console</div>
                </div>
            </div>
            <nav class="nav">
                <RouterLink to="/dashboard" class="nav-link">Dashboard</RouterLink>
                <template v-if="isSuperAdmin">
                    <RouterLink to="/sellers" class="nav-link">Sellers</RouterLink>
                    <RouterLink to="/orders" class="nav-link">Orders</RouterLink>
                    <RouterLink to="/plans" class="nav-link">Plans</RouterLink>
                    <RouterLink to="/banners" class="nav-link">Banners</RouterLink>
                    <RouterLink to="/reviews" class="nav-link">Reviews</RouterLink>
                    <RouterLink to="/reports" class="nav-link">Reports</RouterLink>
                    <RouterLink to="/reports/food-courts" class="nav-link">Food Court Orders</RouterLink>
                </template>
                <template v-if="isSellerUser">
                    <RouterLink to="/seller/menu" class="nav-link">My Menu</RouterLink>
                    <RouterLink to="/seller/accounts" class="nav-link">Accounts</RouterLink>
                </template>
            </nav>
            <div class="sidebar-footer">
                <button class="ghost" @click="logout">Log out</button>
            </div>
        </aside>

        <div class="main">
            <header v-if="!isGuest" class="topbar">
                <div>
                    <div class="topbar-title">{{ pageTitle }}</div>
                    <div class="topbar-subtitle">Street food & market operations</div>
                </div>
                <div class="topbar-actions">
                    <button v-if="isSuperAdmin" class="pill" @click="openSellerModal">New Seller</button>
                    <button class="pill outline">Export</button>
                </div>
            </header>
            <main class="content">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const isGuest = computed(() => route.meta.guest === true);
const isSuperAdmin = computed(() => auth.hasRole('super_admin'));
const isSellerUser = computed(() => auth.hasAnyRole(['seller_owner', 'vendor']));

const pageTitle = computed(() => {
    if (route.name === 'dashboard') return 'Dashboard Overview';
    if (route.name === 'sellers') return 'Seller Management';
    if (route.name === 'orders') return 'Orders Overview';
    if (route.name === 'plans') return 'Subscription Plans';
    if (route.name === 'banners') return 'Homepage Banners';
    if (route.name === 'reviews') return 'Customer Reviews';
    if (route.name === 'reports') return 'Reports & Insights';
    if (route.name === 'reports.food-courts') return 'Food Court Orders';
    if (route.name === 'seller.menu') return 'Seller Menu Management';
    if (route.name === 'seller.accounts') return 'Seller Accounts & Profit/Loss';
    return 'Admin';
});

const logout = () => {
    auth.logout();
    router.push({ name: 'login' });
};

const openSellerModal = () => {
    window.dispatchEvent(new CustomEvent('open-seller-modal'));
    router.push({ name: 'sellers' });
};

onMounted(() => {
    auth.hydrateUser();
});
</script>
