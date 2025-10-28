<template>
  <!-- from bheka -->
  <div class="app">
    <NavBar />
    <div class="page-content">
      <router-view />
    </div>
  </div>

  <!-- from repo -->
  <nav>
    <router-link to="/">Home</router-link> |
    <router-link to="/about">About</router-link> |
    <router-link to="/qrscanner">QrScanner</router-link> |
    <router-link to="/clockin-out">Clock</router-link>
  </nav>
  <router-view/>

</template>

<script>
import QrScanner from './components/QrScanner.vue'
import Clock from '@/views/Clock.vue'
import NavBar from './components/NavBar.vue';

export default {
  name: 'App',
  components: { QrScanner, Clock }
}
</script>

<style>
body {
  font-family: 'Poppins', sans-serif;
  background: #f8f9fa;
  margin: 0;
  padding: 0;
}
.app {
  text-align: center;
}
.page-content {
  padding: 20px;
}
</style>
