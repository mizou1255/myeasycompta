<template>
  <div class="mb-8 overflow-hidden rounded-2xl border transition-all duration-300 shadow-sm" :class="containerClass">
    <div class="flex items-center gap-4 p-5">
      <!-- Icone -->
      <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white shadow-sm" :class="iconClass">
        <span class="text-2xl">{{ icon }}</span>
      </div>
      
      <!-- Contenu -->
      <div class="flex-1 pr-8">
        <h3 class="text-xs font-black uppercase tracking-widest" :class="titleClass">
          {{ title }}
        </h3>
        <p class="mt-1 text-sm font-medium text-gray-700 leading-relaxed">
          {{ message }} <span class="font-bold cursor-pointer hover:underline" :class="linkClass" @click="handleUpgrade">{{ linkText }}</span>
        </p>
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-3">
        <button 
          class="whitespace-nowrap rounded-xl px-5 py-2.5 text-sm font-bold text-white transition-all hover:scale-105 active:scale-95 shadow-md"
          :class="buttonClass"
          @click="handleUpgrade"
        >
          {{ buttonText }}
        </button>
        
        <!-- Close Button -->
        <button 
          class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 transition-colors"
          @click="handleClose"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LicenseNotice',
  props: {
    status: {
      type: String,
      default: 'free', // 'free', 'expired', 'grace'
      validator: (value) => ['free', 'expired', 'grace'].includes(value)
    }
  },
  computed: {
    containerClass() {
      const classes = {
        free: 'bg-purple-50/50 border-purple-100 dark:bg-purple-900/10 dark:border-purple-800/50',
        expired: 'bg-red-50 border-red-100 dark:bg-red-900/10 dark:border-red-800/50',
        grace: 'bg-amber-50 border-amber-100 dark:bg-amber-900/10 dark:border-amber-800/50'
      };
      return classes[this.status];
    },
    iconClass() {
      const classes = {
        free: 'text-purple-600 shadow-purple-100',
        expired: 'text-red-600 shadow-red-100',
        grace: 'text-amber-600 shadow-amber-100'
      };
      return classes[this.status];
    },
    titleClass() {
      const classes = {
        free: 'text-purple-600',
        expired: 'text-red-600',
        grace: 'text-amber-600'
      };
      return classes[this.status];
    },
    linkClass() {
      const classes = {
        free: 'text-purple-600',
        expired: 'text-red-600',
        grace: 'text-amber-600'
      };
      return classes[this.status];
    },
    buttonClass() {
      const classes = {
        free: 'bg-gradient-to-r from-purple-500 to-pink-500 shadow-purple-500/20',
        expired: 'bg-gradient-to-r from-red-500 to-red-600 shadow-red-500/20',
        grace: 'bg-gradient-to-r from-amber-500 to-orange-500 shadow-amber-500/20'
      };
      return classes[this.status];
    },
    icon() {
      const icons = {
        free: '🚀',
        expired: '⚠️',
        grace: '⏳'
      };
      return icons[this.status];
    },
    title() {
      const titles = {
        free: 'Version Gratuite',
        expired: 'Licence Expirée',
        grace: 'Période de Grâce'
      };
      return titles[this.status];
    },
    message() {
      const messages = {
        free: 'Vous utilisez la version gratuite.',
        expired: 'Votre licence a expiré.',
        grace: 'Votre licence expire bientôt.'
      };
      return messages[this.status];
    },
    linkText() {
      const texts = {
        free: 'Passez Pro',
        expired: 'Renouveler maintenant',
        grace: 'Prolonger maintenant'
      };
      return texts[this.status];
    },
    buttonText() {
      const texts = {
        free: 'Débloquer PRO',
        expired: 'Renouveler',
        grace: 'Prolonger'
      };
      return texts[this.status];
    }
  },
  methods: {
    handleUpgrade() {
      // Émettre un événement pour gérer l'upgrade
      this.$emit('upgrade', this.status);
      // Rediriger vers la page de licence
      if (window.myEasyComptaAdmin && window.myEasyComptaAdmin.licenseUrl) {
        window.open(window.myEasyComptaAdmin.licenseUrl, '_blank');
      }
    },
    handleClose() {
      // Émettre un événement pour fermer la notice
      this.$emit('close', this.status);
      // Sauvegarder dans le localStorage
      const dismissedNotices = JSON.parse(localStorage.getItem('licenseNoticesDismissed') || '{}');
      dismissedNotices[this.status] = true;
      localStorage.setItem('licenseNoticesDismissed', JSON.stringify(dismissedNotices));
    }
  }
};
</script>