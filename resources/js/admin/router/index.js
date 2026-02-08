import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Login from '../pages/Login.vue';
import Dashboard from '../pages/Dashboard.vue';
import Sellers from '../pages/Sellers.vue';
import Orders from '../pages/Orders.vue';
import MenuView from '../pages/MenuView.vue';
import Plans from '../pages/Plans.vue';
import Subscriptions from '../pages/Subscriptions.vue';
import Offers from '../pages/Offers.vue';
import Reports from '../pages/Reports.vue';
import FoodCourtReports from '../pages/FoodCourtReports.vue';
import Banners from '../pages/Banners.vue';
import Reviews from '../pages/Reviews.vue';
import Customers from '../pages/Customers.vue';
import Settlements from '../pages/Settlements.vue';
import Settings from '../pages/Settings.vue';

const router = createRouter({
    history: createWebHistory('/admin'),
    routes: [
        { path: '/login', name: 'login', component: Login, meta: { guest: true } },
        { path: '/', redirect: '/dashboard' },
        { path: '/dashboard', name: 'dashboard', component: Dashboard, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/sellers', name: 'sellers', component: Sellers, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/orders', name: 'orders', component: Orders, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/menu-view', name: 'menu.view', component: MenuView, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/plans', name: 'plans', component: Plans, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/subscriptions', name: 'subscriptions', component: Subscriptions, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/offers', name: 'offers', component: Offers, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/reports', name: 'reports', component: Reports, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/reports/food-courts', name: 'reports.food-courts', component: FoodCourtReports, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/banners', name: 'banners', component: Banners, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/reviews', name: 'reviews', component: Reviews, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/customers', name: 'customers', component: Customers, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/settlements', name: 'settlements', component: Settlements, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/settings', name: 'settings', component: Settings, meta: { requiresAuth: true, roles: ['super_admin'] } },
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
            window.location.href = '/seller/dashboard';
            return false;
        }
        return { name: 'dashboard' };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        if (auth.hasRole('super_admin')) {
            return { name: 'dashboard' };
        }

        auth.logout();
        return true;
    }
    return true;
});

export default router;
