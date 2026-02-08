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
                <a href="/register-seller">Become a Seller</a>
            </nav>
            <a class="btn solid" href="/admin">Admin</a>
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

        <section class="campaign" :class="activeCampaign.theme">
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
                <a class="btn solid" href="/register-seller">Register as Seller</a>
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
                <a href="/register-seller">Seller Registration</a>
            </div>

            <div class="footer-apps">
                <p>Get the app</p>
                <div class="app-buttons">
                    <button type="button" class="store-btn">Google Play</button>
                    <button type="button" class="store-btn">App Store</button>
                </div>
                <div class="socials">
                    <a href="#" aria-label="Facebook">FB</a>
                    <a href="#" aria-label="Instagram">IG</a>
                    <a href="#" aria-label="YouTube">YT</a>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const loading = ref(true);
const featuredSellers = ref([]);
const trendingProducts = ref([]);
const sellerSearch = ref('');
const sellerTypeFilter = ref('all');

const categories = [
    { name: 'Fuchka', icon: 'FU', caption: 'Tangy street classics' },
    { name: 'Chotpoti', icon: 'CH', caption: 'Spicy local favorites' },
    { name: 'Burgers', icon: 'BG', caption: 'Quick grill bites' },
    { name: 'Tea & Snacks', icon: 'TS', caption: 'Evening adda menu' },
    { name: 'Food Courts', icon: 'FC', caption: 'Multi-vendor hubs' },
];

const campaigns = [
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
        link: '/register-seller',
        theme: 'theme-emerald',
    },
];

const currentCampaignIndex = ref(0);
let campaignTimer = null;

const activeCampaign = computed(() => campaigns[currentCampaignIndex.value]);

const setCampaign = (index) => {
    currentCampaignIndex.value = index;
};

const nextCampaign = () => {
    currentCampaignIndex.value = (currentCampaignIndex.value + 1) % campaigns.length;
};

const prevCampaign = () => {
    currentCampaignIndex.value = (currentCampaignIndex.value - 1 + campaigns.length) % campaigns.length;
};

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
        featuredSellers.value = data.featured_sellers || [];
        trendingProducts.value = data.trending_products || [];
    } catch (error) {
        featuredSellers.value = [];
        trendingProducts.value = [];
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
