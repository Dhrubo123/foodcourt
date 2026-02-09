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
                <template v-if="isSuperAdmin">
                    <RouterLink :to="{ name: 'dashboard' }" class="nav-link">Dashboard</RouterLink>
                    <RouterLink :to="{ name: 'sellers' }" class="nav-link">Sellers</RouterLink>
                    <RouterLink :to="{ name: 'orders' }" class="nav-link">Orders</RouterLink>
                    <RouterLink :to="{ name: 'menu.view' }" class="nav-link">Menu View</RouterLink>
                    <RouterLink :to="{ name: 'plans' }" class="nav-link">Plans</RouterLink>
                    <RouterLink :to="{ name: 'subscriptions' }" class="nav-link">Subscriptions</RouterLink>
                    <RouterLink :to="{ name: 'offers' }" class="nav-link">Offers</RouterLink>
                    <RouterLink :to="{ name: 'banners' }" class="nav-link">Banners</RouterLink>
                    <RouterLink :to="{ name: 'reviews' }" class="nav-link">Reviews</RouterLink>
                    <RouterLink :to="{ name: 'customers' }" class="nav-link">Customers</RouterLink>
                    <RouterLink :to="{ name: 'reports' }" class="nav-link">Reports</RouterLink>
                    <RouterLink :to="{ name: 'reports.food-courts' }" class="nav-link">Food Court Orders</RouterLink>
                    <RouterLink :to="{ name: 'settlements' }" class="nav-link">Settlements</RouterLink>
                    <RouterLink :to="{ name: 'settings' }" class="nav-link">Settings</RouterLink>
                </template>
                <template v-if="isSellerUser">
                    <RouterLink :to="{ name: 'seller.dashboard' }" class="nav-link">My Dashboard</RouterLink>
                    <RouterLink :to="{ name: 'seller.orders' }" class="nav-link">My Orders</RouterLink>
                    <RouterLink :to="{ name: 'seller.menu' }" class="nav-link">My Menu</RouterLink>
                    <RouterLink :to="{ name: 'seller.accounts' }" class="nav-link">Accounts</RouterLink>
                    <RouterLink :to="{ name: 'seller.reviews' }" class="nav-link">Reviews</RouterLink>
                    <RouterLink :to="{ name: 'seller.subscription' }" class="nav-link">Subscription</RouterLink>
                    <RouterLink :to="{ name: 'seller.profile' }" class="nav-link">Profile</RouterLink>
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
            <footer v-if="!isGuest" class="app-credit">Developed by APARUP BARUA</footer>
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
    if (route.name === 'menu.view') return 'Seller Menu View';
    if (route.name === 'plans') return 'Subscription Plans';
    if (route.name === 'subscriptions') return 'Seller Subscriptions';
    if (route.name === 'offers') return 'Offers & Coupons';
    if (route.name === 'banners') return 'Homepage Banners';
    if (route.name === 'reviews') return 'Customer Reviews';
    if (route.name === 'customers') return 'Customer Management';
    if (route.name === 'reports') return 'Reports & Insights';
    if (route.name === 'reports.food-courts') return 'Food Court Orders';
    if (route.name === 'settlements') return 'Payout Settlements';
    if (route.name === 'settings') return 'System Settings';
    if (route.name === 'seller.dashboard') return 'Seller Dashboard';
    if (route.name === 'seller.orders') return 'Order Management';
    if (route.name === 'seller.menu') return 'Seller Menu Management';
    if (route.name === 'seller.accounts') return 'Seller Accounts & Profit/Loss';
    if (route.name === 'seller.reviews') return 'Customer Reviews';
    if (route.name === 'seller.subscription') return 'Subscription & Renewal';
    if (route.name === 'seller.profile') return 'Profile & Settings';
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
