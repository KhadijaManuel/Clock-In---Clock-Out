import { createRouter, createWebHistory } from 'vue-router';
import BreakManagement from '../components/BreakManagement.vue';
import QrScanner from '@/components/QrScanner.vue';
import Clock from '../components/Clock.vue';

const routes = [
  //Redirect the root URL "/" to the Clock page
  { path: '/', redirect: '/clockin-out' },

  { path: '/clockin-out', name: 'clock', component: Clock },
  { path: '/break', name: 'break', component: BreakManagement },
  { path: '/qrscanner', name: 'qrscanner', component: QrScanner },

  //Catch-all route for 404 Not Found pages
  { path: '/:pathMatch(.*)*', redirect: '/' }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
