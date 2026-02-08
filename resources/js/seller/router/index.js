import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../../admin/stores/auth';
import Login from '../../admin/pages/Login.vue';
import SellerDashboard from '../../admin/pages/SellerDashboard.vue';
import SellerOrders from '../../admin/pages/SellerOrders.vue';
import SellerMenu from '../../admin/pages/SellerMenu.vue';
import SellerAccounts from '../../admin/pages/SellerAccounts.vue';
import SellerReviews from '../../admin/pages/SellerReviews.vue';
import SellerSubscription from '../../admin/pages/SellerSubscription.vue';
import SellerProfile from '../../admin/pages/SellerProfile.vue';

const router = createRouter({
    history: createWebHistory('/seller'),
    routes: [
        { path: '/login', name: 'login', component: Login, meta: { guest: true } },
        { path: '/', redirect: '/dashboard' },
        {
            path: '/dashboard',
            name: 'seller.dashboard',
            component: SellerDashboard,
            meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] },
        },
        {
            path: '/orders',
            name: 'seller.orders',
            component: SellerOrders,
            meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] },
        },
        {
            path: '/menu',
            name: 'seller.menu',
            component: SellerMenu,
            meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] },
        },
        {
            path: '/accounts',
            name: 'seller.accounts',
            component: SellerAccounts,
            meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] },
        },
        {
            path: '/reviews',
            name: 'seller.reviews',
            component: SellerReviews,
            meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] },
        },
        {
            path: '/subscription',
            name: 'seller.subscription',
            component: SellerSubscription,
            meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] },
        },
        {
            path: '/profile',
            name: 'seller.profile',
            component: SellerProfile,
            meta: { requiresAuth: true, roles: ['seller_owner', 'vendor'] },
        },
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
        if (auth.hasAnyRole(['seller_owner', 'vendor'])) {
            return { name: 'seller.dashboard' };
        }

        auth.logout();
    }

    return true;
});

export default router;
