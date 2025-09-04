import { createRouter, createWebHistory } from 'vue-router';
import DashboardView from './views/DashboardView.vue';
import TicketsView from './views/TicketsView.vue';
import TicketDetailView from './views/TicketDetailView.vue';

const routes = [
  { path: '/', redirect: '/dashboard' },
  { path: '/dashboard', name: 'dashboard', component: DashboardView },
  { path: '/tickets', name: 'tickets', component: TicketsView },
  { path: '/tickets/:id', name: 'ticket-detail', component: TicketDetailView },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
