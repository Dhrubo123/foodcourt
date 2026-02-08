import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Login from '../pages/Login.vue';
import Dashboard from '../pages/Dashboard.vue';
import Sellers from '../pages/Sellers.vue';
import Orders from '../pages/Orders.vue';
import Plans from '../pages/Plans.vue';
import Reports from '../pages/Reports.vue';
import FoodCourtReports from '../pages/FoodCourtReports.vue';
import Banners from '../pages/Banners.vue';
import Reviews from '../pages/Reviews.vue';
import SellerMenu from '../pages/SellerMenu.vue';
import SellerAccounts from '../pages/SellerAccounts.vue';

const router = createRouter({
    history: createWebHistory('/admin'),
    routes: [
        { path: '/login', name: 'login', component: Login, meta: { guest: true } },
        { path: '/', redirect: '/dashboard' },
        { path: '/dashboard', name: 'dashboard', component: Dashboard, meta: { requiresAuth: true } },
        { path: '/sellers', name: 'sellers', component: Sellers, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/orders', name: 'orders', component: Orders, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/plans', name: 'plans', component: Plans, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/reports', name: 'reports', component: Reports, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/reports/food-courts', name: 'reports.food-courts', component: FoodCourtReports, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/banners', name: 'banners', component: Banners, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/reviews', name: 'reviews', component: Reviews, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/seller/menu', name: 'seller.menu', component: SellerMenu, meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] } },
        { path: '/seller/accounts', name: 'seller.accounts', component: SellerAccounts, meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] } },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (auth.token && !auth.user) {
        await auth.hydrateUser();
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login' };
    }

    if (to.meta.roles && !auth.hasAnyRole(to.meta.roles)) {
        if (auth.hasAnyRole(['seller_owner', 'vendor'])) {
            return { name: 'seller.menu' };
        }
        return { name: 'dashboard' };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        if (auth.hasAnyRole(['seller_owner', 'vendor'])) {
            return { name: 'seller.menu' };
        }
        return { name: 'dashboard' };
    }
    return true;
});

export default router;
