import { createRouter, createWebHistory } from 'vue-router';
import BreakManagement from '../components/BreakManagement.vue';
import QrScanner from '@/components/QrScanner.vue';
import Clock from '../components/Clock.vue';

const routes = [

  //Bheka
  //Redirect the root URL "/" to the Clock page
  { path: '/', redirect: '/clockin-out' },

  { path: '/break', component: BreakManagement },

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
   //Catch-all route for 404 Not Found pages
  { path: '/:pathMatch(.*)*', redirect: '/' }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
