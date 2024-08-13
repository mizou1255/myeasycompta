<template>
    <router-view></router-view>
</template>

<script>
  
  export default {
    name: 'Quotes',

    mounted() {
      this.$router.push('/quotes');
    }
  }
</script>
