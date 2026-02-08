import { defineStore } from 'pinia';

const TOKEN_KEY = 'admin_token';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem(TOKEN_KEY),
        user: null,
        loading: false,
    }),
    getters: {
        isAuthenticated: (state) => Boolean(state.token),
    },
    actions: {
        setToken(token) {
            this.token = token;
            if (token) {
                localStorage.setItem(TOKEN_KEY, token);
            } else {
                localStorage.removeItem(TOKEN_KEY);
            }
        },
        setUser(user) {
            this.user = user;
        },
        logout() {
            this.setToken(null);
            this.setUser(null);
        },
    },
});
