// assets/app.js
import { createApp } from 'vue';
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import * as ElementPlusIconsVue from '@element-plus/icons-vue';
import App from './vue/App.vue';
import router from './vue/router';
import axios from 'axios';

// Configuración global de axios
axios.defaults.baseURL = '/api';
axios.interceptors.request.use(config => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Crear la aplicación Vue
const app = createApp(App);

// Registrar todos los iconos de Element Plus
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
    app.component(key, component);
}

// Usar Element Plus
app.use(ElementPlus);
app.use(router);

// Montar la aplicación
app.mount('#app');
