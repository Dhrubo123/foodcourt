<template>
    <div class="site-shell">
        <header class="topbar">
            <a class="brand" href="/">
                <span class="brand-mark">R</span>
                <span>restaurent</span>
            </a>
            <nav class="menu">
                <a href="#categories">Categories</a>
                <a href="#sellers">Sellers</a>
                <a href="#menu">Menu</a>
                <a :href="links.customerLogin">Customer Login</a>
                <a :href="links.sellerRegister">Become a Seller</a>
            </nav>
            <a class="btn solid" :href="links.adminPanel">Admin</a>
        </header>

        <section class="hero">
            <div class="hero-copy">
                <p class="kicker">Bangladesh Street Food Network</p>
                <h1>One app for every food cart and food court near you.</h1>
                <p>
                    Browse local favorites, compare nearby food courts, and place multi-seller pickup orders
                    in one checkout.
                </p>

                <div class="search-row">
                    <input
                        v-model="sellerSearch"
                        type="text"
                        placeholder="Search by seller name or area"
                        aria-label="Search sellers"
                    />
                    <button type="button" class="btn solid">Find Food</button>
                </div>

                <div class="hero-tags">
                    <span>Live Carts</span>
                    <span>Food Courts</span>
                    <span>Cash on Pickup</span>
                </div>
            </div>

            <div class="hero-panel">
                <div class="metric-card">
                    <p>Active Sellers</p>
                    <strong>{{ featuredSellers.length }}</strong>
                </div>
                <div class="metric-card">
                    <p>Trending Dishes</p>
                    <strong>{{ trendingProducts.length }}</strong>
                </div>
                <div class="metric-card">
                    <p>Food Courts</p>
                    <strong>{{ foodCourtCount }}</strong>
                </div>
            </div>
        </section>

        <section id="categories" class="category-strip">
            <article v-for="category in categories" :key="category.name" class="category-item">
                <div class="category-icon">{{ category.icon }}</div>
                <div>
                    <h3>{{ category.name }}</h3>
                    <p>{{ category.caption }}</p>
                </div>
            </article>
        </section>

        <section class="campaign" :class="activeCampaign.theme" :style="campaignStyle">
            <div class="campaign-copy">
                <p class="campaign-label">Campaign</p>
                <h2>{{ activeCampaign.title }}</h2>
                <p>{{ activeCampaign.description }}</p>
                <a class="btn solid" :href="activeCampaign.link">{{ activeCampaign.cta }}</a>
            </div>
            <div class="campaign-controls">
                <button type="button" class="ctrl" @click="prevCampaign">Prev</button>
                <button type="button" class="ctrl" @click="nextCampaign">Next</button>
            </div>
            <div class="campaign-dots">
                <button
                    v-for="(item, index) in campaigns"
                    :key="item.title"
                    type="button"
                    class="dot"
                    :class="{ active: index === currentCampaignIndex }"
                    @click="setCampaign(index)"
                    :aria-label="`Switch to campaign ${index + 1}`"
                />
            </div>
        </section>

        <main class="content-wrap">
            <section id="sellers" class="section-card">
                <div class="section-head">
                    <div>
                        <h2>Popular nearby sellers</h2>
                        <p>Approved and subscribed sellers ready for pickup orders.</p>
                    </div>
                    <div class="filter-group">
                        <button
                            type="button"
                            class="chip"
                            :class="{ active: sellerTypeFilter === 'all' }"
                            @click="sellerTypeFilter = 'all'"
                        >
                            All
                        </button>
                        <button
                            type="button"
                            class="chip"
                            :class="{ active: sellerTypeFilter === 'cart' }"
                            @click="sellerTypeFilter = 'cart'"
                        >
                            Carts
                        </button>
                        <button
                            type="button"
                            class="chip"
                            :class="{ active: sellerTypeFilter === 'food_court' }"
                            @click="sellerTypeFilter = 'food_court'"
                        >
                            Food Courts
                        </button>
                    </div>
                </div>

                <div v-if="loading" class="state-box">Loading sellers...</div>
                <div v-else-if="filteredSellers.length === 0" class="state-box">
                    No sellers match your search right now.
                </div>
                <div v-else class="seller-grid">
                    <article v-for="seller in filteredSellers" :key="seller.id" class="seller-card">
                        <div class="seller-meta">
                            <span class="pill">
                                {{ seller.type === 'food_court' ? 'Food Court' : 'Food Cart' }}
                            </span>
                            <span class="area">{{ seller.area?.name || 'Local area' }}</span>
                        </div>
                        <h3>{{ seller.name }}</h3>
                        <p class="address">{{ seller.address || 'Pickup from seller location' }}</p>
                        <p class="timing">
                            {{ seller.open_time || '--:--' }} to {{ seller.close_time || '--:--' }}
                        </p>
                        <p v-if="seller.offers?.length" class="offer-text">Offer: {{ seller.offers[0].title }}</p>
                    </article>
                </div>
            </section>

            <section id="menu" class="section-card">
                <div class="section-head">
                    <div>
                        <h2>Trending menu</h2>
                        <p>Fresh picks from currently active carts and food courts.</p>
                    </div>
                </div>

                <div v-if="loading" class="state-box">Loading menu...</div>
                <div v-else-if="trendingProducts.length === 0" class="state-box">
                    Menu items will appear after sellers add products.
                </div>
                <div v-else class="menu-grid">
                    <article v-for="product in menuPreview" :key="product.id" class="menu-card">
                        <h3>{{ product.name }}</h3>
                        <p class="seller-name">{{ product.seller?.name || 'Seller' }}</p>
                        <p class="menu-desc">{{ product.description || 'Freshly prepared and ready for pickup.' }}</p>
                        <p class="price">BDT {{ formatMoney(product.price) }}</p>
                    </article>
                </div>
            </section>

            <section class="cta-strip">
                <div>
                    <h2>Run a food cart or food court?</h2>
                    <p>Register in minutes and get listed after admin approval.</p>
                </div>
                <a class="btn solid" :href="links.sellerRegister">Register as Seller</a>
            </section>

            <section class="section-card">
                <div class="section-head">
                    <div>
                        <h2>Customer Reviews</h2>
                        <p>Latest feedback shared by customers.</p>
                    </div>
                </div>
                <div v-if="reviews.length === 0" class="state-box">Customer reviews will appear here.</div>
                <div v-else class="review-grid">
                    <article v-for="review in reviews.slice(0, 6)" :key="review.id" class="review-card">
                        <p class="review-rating">{{ review.rating }}/5</p>
                        <p class="review-text">{{ review.comment || 'Great service and fresh food.' }}</p>
                        <p class="review-meta">
                            {{ review.customer?.name || 'Customer' }} on {{ review.seller?.name || 'Seller' }}
                        </p>
                    </article>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="footer-brand">
                <h3>restaurent marketplace</h3>
                <p>Unified ordering for food carts, food courts, and street food shops.</p>
            </div>

            <div class="footer-links">
                <a href="#categories">Categories</a>
                <a href="#sellers">Sellers</a>
                <a href="#menu">Menu</a>
                <a :href="links.customerRegister">Customer Register</a>
                <a :href="links.sellerRegister">Seller Registration</a>
            </div>

            <div class="footer-apps">
                <p>Get the app</p>
                <div class="app-buttons">
                    <button type="button" class="store-btn">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M3.6 2.2c.5 0 1 .2 1.3.5l10 9.3-3.4 3.2L3 3.6c.2-.9.8-1.4 1.6-1.4Zm-1 2.8 7.4 7-7.4 7c-.1-.3-.2-.6-.2-.9V5.9c0-.3 0-.6.2-.9Zm10.3 8.4 2.2 2.1c-.2.3-.5.5-.8.6l-9 4.9c-.7.4-1.4.3-1.9-.2l9.5-7.4Zm2.8 1.5-2.1-2 2.1-2 3.1 1.7c1.4.8 1.4 1.9 0 2.7l-3.1 1.6Zm-2.8-4.3L3.4 3.2c.5-.5 1.2-.6 1.9-.2l9 4.9c.3.2.6.4.8.7l-2.2 2Z"
                            />
                        </svg>
                        <span>Google Play</span>
                    </button>
                    <button type="button" class="store-btn">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M15.4 2.5c0 1-.4 1.9-1 2.6-.7.9-1.8 1.5-2.9 1.4-.2-1 .2-2 1-2.8.7-.8 1.9-1.3 2.9-1.2Zm3.8 16.3c-.5 1.2-.8 1.7-1.4 2.8-.9 1.5-2.2 3.4-3.8 3.4-1.4 0-1.8-.9-3.6-.9s-2.2.9-3.6.9c-1.6 0-2.8-1.7-3.7-3.2-2.5-4.2-2.8-9.2-1.2-11.7 1.2-1.8 3-2.9 4.8-2.9 1.9 0 3.1 1 4.7 1 1.5 0 2.4-1 4.7-1 .7 0 2.7.2 4 2.1-3.5 1.9-2.9 6.9.1 8.5Z"
                            />
                        </svg>
                        <span>App Store</span>
                    </button>
                </div>
                <div class="socials">
                    <a href="#" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M13.5 21v-7h2.3l.3-2.7h-2.6V9.6c0-.8.2-1.3 1.4-1.3H16V6a14 14 0 0 0-1.8-.1c-1.8 0-3.1 1.1-3.1 3.2v2.1H9v2.7h2.1v7h2.4Z"
                            />
                        </svg>
                    </a>
                    <a href="#" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm0 8.2a3.2 3.2 0 1 1 0-6.4 3.2 3.2 0 0 1 0 6.4ZM18.4 6.8a1.2 1.2 0 1 1-2.4 0 1.2 1.2 0 0 1 2.4 0Z"
                            />
                            <path
                                d="M12 3.8h3.8c1 0 1.6 0 2.1.2.6.2 1 .4 1.4.8.4.4.6.8.8 1.4.2.5.2 1.1.2 2.1V12c0 1 0 1.6-.2 2.1a3.6 3.6 0 0 1-.8 1.4c-.4.4-.8.6-1.4.8-.5.2-1.1.2-2.1.2H12c-1 0-1.6 0-2.1-.2a3.6 3.6 0 0 1-1.4-.8 3.6 3.6 0 0 1-.8-1.4c-.2-.5-.2-1.1-.2-2.1V8.3c0-1 0-1.6.2-2.1.2-.6.4-1 .8-1.4.4-.4.8-.6 1.4-.8.5-.2 1.1-.2 2.1-.2Zm0-1.8h-3.9c-1 0-1.8 0-2.6.3-.8.3-1.5.7-2.1 1.3-.6.6-1 1.3-1.3 2.1-.3.8-.3 1.6-.3 2.6V12c0 1 0 1.8.3 2.6.3.8.7 1.5 1.3 2.1.6.6 1.3 1 2.1 1.3.8.3 1.6.3 2.6.3H12c1 0 1.8 0 2.6-.3.8-.3 1.5-.7 2.1-1.3.6-.6 1-1.3 1.3-2.1.3-.8.3-1.6.3-2.6V8.2c0-1 0-1.8-.3-2.6-.3-.8-.7-1.5-1.3-2.1-.6-.6-1.3-1-2.1-1.3-.8-.3-1.6-.3-2.6-.3H12Z"
                            />
                        </svg>
                    </a>
                    <a href="#" aria-label="YouTube">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M21.6 8.6a2.9 2.9 0 0 0-2-2c-1.8-.5-7.6-.5-7.6-.5s-5.8 0-7.6.5a2.9 2.9 0 0 0-2 2A30 30 0 0 0 2 12a30 30 0 0 0 .4 3.4 2.9 2.9 0 0 0 2 2c1.8.5 7.6.5 7.6.5s5.8 0 7.6-.5a2.9 2.9 0 0 0 2-2A30 30 0 0 0 22 12a30 30 0 0 0-.4-3.4ZM10 14.8V9.2L15 12l-5 2.8Z"
                            />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="footer-credit">
                <h4>Developed by Aparup Barua</h4>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const backendBase = (import.meta.env.VITE_APP_URL || 'http://127.0.0.1:8000').replace(/\/+$/, '');
const withBackendBase = (path) => `${backendBase}${path.startsWith('/') ? path : `/${path}`}`;

const defaultLinks = {
    customerLogin: withBackendBase('/customer/login'),
    customerRegister: withBackendBase('/customer/register'),
    sellerRegister: withBackendBase('/register-seller'),
    adminPanel: withBackendBase('/admin'),
};
const links = { ...defaultLinks, ...(window.__RESTAURENT_LINKS || {}) };

const loading = ref(true);
const featuredSellers = ref([]);
const trendingProducts = ref([]);
const banners = ref([]);
const reviews = ref([]);
const sellerSearch = ref('');
const sellerTypeFilter = ref('all');

const categories = [
    { name: 'Fuchka', icon: 'FU', caption: 'Tangy street classics' },
    { name: 'Chotpoti', icon: 'CH', caption: 'Spicy local favorites' },
    { name: 'Burgers', icon: 'BG', caption: 'Quick grill bites' },
    { name: 'Tea & Snacks', icon: 'TS', caption: 'Evening adda menu' },
    { name: 'Food Courts', icon: 'FC', caption: 'Multi-vendor hubs' },
];

const fallbackCampaigns = [
    {
        title: 'Weekend Cart Festival',
        description: 'Up to 15% off from selected evening food carts across popular areas.',
        cta: 'See Festival Sellers',
        link: '#sellers',
        theme: 'theme-mint',
    },
    {
        title: 'Food Court Combo Week',
        description: 'Order from multiple stalls in one checkout and pick up with one trip.',
        cta: 'Browse Food Courts',
        link: '#menu',
        theme: 'theme-leaf',
    },
    {
        title: 'New Seller Onboarding',
        description: 'Launch your cart online and start receiving pre-confirmed pickup orders.',
        cta: 'Register Your Cart',
        link: links.sellerRegister,
        theme: 'theme-emerald',
    },
];

const currentCampaignIndex = ref(0);
let campaignTimer = null;

const campaigns = computed(() => {
    if (!banners.value.length) {
        return fallbackCampaigns;
    }

    return banners.value.map((banner, index) => ({
        title: banner.title,
        description: banner.subtitle || 'Explore latest food marketplace campaign offers.',
        cta: banner.cta_label || 'Explore Now',
        link: banner.cta_link || '#sellers',
        image: banner.image_path || null,
        theme: index % 3 === 0 ? 'theme-mint' : index % 3 === 1 ? 'theme-leaf' : 'theme-emerald',
    }));
});

const activeCampaign = computed(() => campaigns.value[currentCampaignIndex.value]);

const campaignStyle = computed(() => {
    if (!activeCampaign.value?.image) {
        return {};
    }

    return {
        backgroundImage: `linear-gradient(120deg, rgba(226, 244, 229, 0.93) 0%, rgba(248, 255, 249, 0.88) 100%), url('${activeCampaign.value.image}')`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
    };
});

const setCampaign = (index) => {
    currentCampaignIndex.value = index;
};

const nextCampaign = () => {
    currentCampaignIndex.value = (currentCampaignIndex.value + 1) % campaigns.value.length;
};

const prevCampaign = () => {
    currentCampaignIndex.value = (currentCampaignIndex.value - 1 + campaigns.value.length) % campaigns.value.length;
};

watch(campaigns, (items) => {
    if (currentCampaignIndex.value >= items.length) {
        currentCampaignIndex.value = 0;
    }
});

const filteredSellers = computed(() => {
    const term = sellerSearch.value.trim().toLowerCase();

    return featuredSellers.value
        .filter((seller) => {
            if (sellerTypeFilter.value === 'all') {
                return true;
            }
            return seller.type === sellerTypeFilter.value;
        })
        .filter((seller) => {
            if (!term) {
                return true;
            }

            const sellerName = String(seller.name || '').toLowerCase();
            const sellerArea = String(seller.area?.name || '').toLowerCase();
            return sellerName.includes(term) || sellerArea.includes(term);
        })
        .slice(0, 8);
});

const menuPreview = computed(() => trendingProducts.value.slice(0, 8));

const foodCourtCount = computed(() => {
    return featuredSellers.value.filter((seller) => seller.type === 'food_court').length;
});

const formatMoney = (value) => {
    const num = Number(value || 0);
    return num.toFixed(2);
};

const loadHome = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/public/home');
        const data = await response.json();
        banners.value = data.banners || [];
        featuredSellers.value = data.featured_sellers || [];
        trendingProducts.value = data.trending_products || [];
        reviews.value = data.reviews || [];
    } catch (error) {
        banners.value = [];
        featuredSellers.value = [];
        trendingProducts.value = [];
        reviews.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadHome();
    campaignTimer = setInterval(nextCampaign, 5000);
});

onUnmounted(() => {
    if (campaignTimer) {
        clearInterval(campaignTimer);
    }
});
</script>
