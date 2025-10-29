<template>
  <div class="app">
    <NavBar />
    <div class="page-content">
      <router-view />
    </div>
  </div>


</template>

<script>
import NavBar from './components/NavBar.vue';

export default {
  components: {
    NavBar
  }
};
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
