import { createRouter, createWebHistory } from 'vue-router';
import Home from '../components/Home.vue';
import ClockIn from '../components/ClockIn.vue';
import BreakManagement from '../components/BreakManagement.vue';

const routes = [
  { path: '/', component: Home },
  { path: '/break', component: BreakManagement },
  { path: '/clockin', component: ClockIn },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
