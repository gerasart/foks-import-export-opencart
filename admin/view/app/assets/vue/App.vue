<template>
  <div class="foks_ie">
    <Settings></Settings>
  </div>
</template>

<script>
  import Settings from "./components/Settings";

  export default {
    name: "app",
    components: {Settings},
  }
</script>

<style lang="scss">
  @import "../../node_modules/ant-design-vue/dist/antd.css";
  @import "../styles/main";
</style>
