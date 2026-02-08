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
        hasRole(role) {
            if (!this.user?.roles) {
                return false;
            }
            return this.user.roles.includes(role);
        },
        hasAnyRole(roles) {
            return roles.some((role) => this.hasRole(role));
        },
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
        async hydrateUser() {
            if (!this.token) {
                return null;
            }
            if (this.user) {
                return this.user;
            }

            this.loading = true;
            try {
                const response = await fetch('/api/auth/me', {
                    headers: {
                        Accept: 'application/json',
                        Authorization: `Bearer ${this.token}`,
                    },
                });

                if (!response.ok) {
                    if (response.status === 401) {
                        this.logout();
                    }
                    return null;
                }

                const payload = await response.json();
                this.user = payload.user;
                return this.user;
            } catch (error) {
                return null;
            } finally {
                this.loading = false;
            }
        },
        logout() {
            this.setToken(null);
            this.setUser(null);
        },
    },
});
