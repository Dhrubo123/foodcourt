import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import Login from '../pages/Login.vue';
import Dashboard from '../pages/Dashboard.vue';
import Sellers from '../pages/Sellers.vue';
import Orders from '../pages/Orders.vue';
import Plans from '../pages/Plans.vue';
import Reports from '../pages/Reports.vue';
import FoodCourtReports from '../pages/FoodCourtReports.vue';

const router = createRouter({
    history: createWebHistory('/admin'),
    routes: [
        { path: '/login', name: 'login', component: Login, meta: { guest: true } },
        { path: '/', redirect: '/dashboard' },
        { path: '/dashboard', name: 'dashboard', component: Dashboard, meta: { requiresAuth: true } },
        { path: '/sellers', name: 'sellers', component: Sellers, meta: { requiresAuth: true } },
        { path: '/orders', name: 'orders', component: Orders, meta: { requiresAuth: true } },
        { path: '/plans', name: 'plans', component: Plans, meta: { requiresAuth: true } },
        { path: '/reports', name: 'reports', component: Reports, meta: { requiresAuth: true } },
        { path: '/reports/food-courts', name: 'reports.food-courts', component: FoodCourtReports, meta: { requiresAuth: true } },
    ],
});

router.beforeEach((to) => {
    const auth = useAuthStore();
    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login' };
    }
    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }
    return true;
});

export default router;
