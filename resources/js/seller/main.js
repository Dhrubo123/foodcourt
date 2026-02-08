import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from '../admin/App.vue';
import router from './router';
import '../admin/admin.css';

const app = createApp(App);

app.use(createPinia());
app.use(router);

app.mount('#app');

