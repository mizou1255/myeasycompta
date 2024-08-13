<template>
  <router-view></router-view>
</template>

<script>

export default {
  name: 'Invoices',

  mounted() {
      this.$router.push('/invoices');
    }
}
</script>
