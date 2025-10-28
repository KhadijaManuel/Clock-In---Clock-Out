import { createRouter, createWebHistory } from 'vue-router';
import Home from '../components/Home.vue';
import ClockIn from '../components/ClockIn.vue';
import BreakManagement from '../components/BreakManagement.vue';
import HomeView from '../views/HomeView.vue'
import QrScanner from '@/components/QrScanner.vue'
import Clock from '../views/Clock.vue'
import UpdatedQrCode from '@/components/updatedQrcode.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView
  },
  //Bheka
  { path: '/', component: Home },
  { path: '/break', component: BreakManagement },
  { path: '/clockin', component: ClockIn },
  {
    path: '/about',
    name: 'about',
    // route level code-splitting
    // this generates a separate chunk (about.[hash].js) for this route
    // which is lazy-loaded when the route is visited.
    component: () => import(/* webpackChunkName: "about" */ '../views/AboutView.vue')
  },
  {
    path: '/qrscanner',
    name: 'qrscanner',
    component: QrScanner
  },
  {
    path: '/clockin-out',
    name: 'clock',
    component: Clock
  },
  {
    path: '/updatedQrcode',
    name: 'updatedQrcode',
    component: UpdatedQrCode
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
