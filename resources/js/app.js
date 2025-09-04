import './bootstrap';
import { createApp } from 'vue';
import router from './router';

const Root = {
    name: 'AppRoot',
    data() {
        return {
            title: 'Bemo',
        };
    },
    template: `
        <main class="app">
            <header class="app__header">
                <h1 class="app__title">{{ title }}</h1>
                <nav class="nav">
                    <router-link class="nav__link" to="/dashboard">Dashboard</router-link>
                    <router-link class="nav__link" to="/tickets">Tickets</router-link>
                </nav>
            </header>
            <section class="app__content">
                <router-view></router-view>
            </section>
        </main>
    `,
};

const app = createApp(Root);
app.use(router);
app.mount('#app');
