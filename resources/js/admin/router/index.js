import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Login from '../pages/Login.vue';
import Dashboard from '../pages/Dashboard.vue';
import Sellers from '../pages/Sellers.vue';
import Orders from '../pages/Orders.vue';
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
import SellerDashboard from '../pages/SellerDashboard.vue';
import SellerOrders from '../pages/SellerOrders.vue';
import SellerMenu from '../pages/SellerMenu.vue';
import SellerAccounts from '../pages/SellerAccounts.vue';
import SellerReviews from '../pages/SellerReviews.vue';
import SellerSubscription from '../pages/SellerSubscription.vue';
import SellerProfile from '../pages/SellerProfile.vue';

const router = createRouter({
    history: createWebHistory('/admin'),
    routes: [
        { path: '/login', name: 'login', component: Login, meta: { guest: true } },
        { path: '/', redirect: '/dashboard' },
        { path: '/dashboard', name: 'dashboard', component: Dashboard, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/sellers', name: 'sellers', component: Sellers, meta: { requiresAuth: true, roles: ['super_admin'] } },
        { path: '/orders', name: 'orders', component: Orders, meta: { requiresAuth: true, roles: ['super_admin'] } },
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
        { path: '/seller/dashboard', name: 'seller.dashboard', component: SellerDashboard, meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] } },
        { path: '/seller/orders', name: 'seller.orders', component: SellerOrders, meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] } },
        { path: '/seller/menu', name: 'seller.menu', component: SellerMenu, meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] } },
        { path: '/seller/accounts', name: 'seller.accounts', component: SellerAccounts, meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] } },
        { path: '/seller/reviews', name: 'seller.reviews', component: SellerReviews, meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] } },
        { path: '/seller/subscription', name: 'seller.subscription', component: SellerSubscription, meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] } },
        { path: '/seller/profile', name: 'seller.profile', component: SellerProfile, meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] } },
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
        auth.logout();
        return { name: 'login' };
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
