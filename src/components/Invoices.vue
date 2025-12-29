<template>
  <router-view></router-view>
</template>

<script>

export default {
  name: 'Invoices',

  created() {
    // Ne rediriger que si on est sur la route racine ou si aucune route n'est définie
    // Vérifier le hash directement car le router peut ne pas être encore initialisé
    const hash = window.location.hash;
    
    // Si le hash est vide ou ne contient pas de route d'invoice valide, rediriger vers la liste
    if (!hash || hash === '#' || hash === '#/' || (!hash.includes('/invoice') && !hash.includes('/invoices'))) {
      // Utiliser setTimeout pour s'assurer que le router est initialisé
      this.$nextTick(() => {
        if (this.$router) {
          this.$router.push('/invoices');
        }
      });
    }
  }
}
</script>
