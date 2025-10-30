<template>
  <nav class="navbar">
    <router-link to="/" class="nav-link" exact>Home</router-link>
    <router-link to="/clock" class="nav-link">Clock-in</router-link>
    <router-link to="/break" class="nav-link">Break</router-link> 
  </nav>
</template>

<style scoped>
.navbar {
  display: flex;
  justify-content: center;
  background-color: #0a9f6e;
  padding: 12px;
}
.nav-link {
  color: white;
  text-decoration: none;
  margin: 0 15px;
  font-weight: 600;
}
.nav-link.router-link-active {
  border-bottom: 2px solid white;
}
</style>
