<template>
  <div id="app">
    <el-config-provider>
      <router-view></router-view>
    </el-config-provider>
  </div>
</template>

<script>
export default {
  name: 'App'
}
</script>

<style>
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: #2c3e50;
  height: 100vh;
}

html, body {
  margin: 0;
  padding: 0;
  height: 100%;
}
</style>
