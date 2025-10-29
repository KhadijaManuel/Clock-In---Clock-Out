import { createRouter, createWebHistory } from 'vue-router';
import BreakManagement from '../components/BreakManagement.vue';
import QrScanner from '@/components/QrScanner.vue';
import Clock from '../components/Clock.vue';

const routes = [
  {
    path: '/',
    name: 'Clock',
    component: Clock
  },
  {
    path: '/break',
    name: 'break',
    component: BreakManagement
  },
  {
    path: '/qrscanner',
    name: 'qrscanner',
    component: QrScanner
  },
    // { path: '/clockin', component: ClockIn },
  {
    path: '/clockin-out',
    name: 'clock',
    component: Clock
  },
  // Catch-all -> redirect to home
  { path: '/:pathMatch(.*)*', redirect: '/' }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
