import { createRouter, createWebHistory } from 'vue-router';
import BreakManagement from '../components/BreakManagement.vue';
import QrScanner from '@/components/QrScanner.vue';
import Clock from '../components/Clock.vue';
import HomeView from '../views/HomeView.vue'
import UpdatedQrCode from '@/components/updatedQrcode.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView
  },
  //Bheka
  //Redirect the root URL "/" to the Clock page
  { path: '/', redirect: '/clockin-out' },
  { path: '/break', name: 'break', component: BreakManagement },

  //Catch-all route for 404 Not Found pages
  { path: '/:pathMatch(.*)*', redirect: '/' },
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
