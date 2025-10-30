import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '@/views/HomeView.vue';
import BreakView from '@/views/BreakView.vue';
import ClockView from '@/views/ClockView.vue';

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView
  },
  {
    path: '/clock',
    name: 'clock',
    component: ClockView
  },
  {
    path: '/break',
    name: 'break',
    component: BreakView
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
