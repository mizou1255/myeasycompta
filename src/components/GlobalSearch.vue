<template>
  <div class="global-search-container">
    <!-- Bouton de recherche (si pas contrôlé par le parent) -->
    <label
      v-if="shouldShowButton"
      class="ecwp-swap bg-base-100 p-2"
      style="position: absolute; top: 6px; left: 40px; right: auto; border-width: 1px 1px 0 1px; border-color: #c3c4c7; border-radius: 10px 10px 0px 0; cursor: pointer; transition: all 0.3s ease;"
      @click="openSearch"
      @mouseenter="$event.target.style.backgroundColor = 'oklch(var(--p) / 0.1)'"
      @mouseleave="$event.target.style.backgroundColor = ''"
    >
      <i class="fas fa-search text-xl"></i>
    </label>

    <!-- Modal de recherche -->
    <div
      v-if="isOpenComputed"
      class="modal modal-open search-modal"
      style="z-index: 9999;"
      @click.self="closeSearch"
    >
      <div class="modal-box w-11/12 max-w-3xl search-modal-box" @click.stop>
        <!-- En-tête avec gradient -->
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-base-300">
          <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary/10 text-primary">
              <i class="fas fa-search text-lg"></i>
            </div>
            <div>
              <h3 class="font-bold text-xl text-base-content">
                {{ translations.global_search || 'Recherche globale' }}
              </h3>
              <p class="text-xs text-base-content/60 mt-0.5">
                Recherchez rapidement dans tous vos documents
              </p>
            </div>
          </div>
          <button
            class="btn btn-sm btn-circle btn-ghost hover:bg-error/10 hover:text-error transition-colors"
            @click="closeSearch"
            aria-label="Fermer"
          >
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Barre de recherche améliorée -->
        <div class="form-control mb-5">
          <div class="relative">
            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
              <i class="fas fa-search text-base-content/40"></i>
            </div>
            <input
              v-model="searchQuery"
              @input="performSearch"
              @keydown.enter="handleEnter"
              @keydown.escape="closeSearch"
              @keydown.arrow-down.prevent="navigateResults(1)"
              @keydown.arrow-up.prevent="navigateResults(-1)"
              type="text"
              placeholder="Tapez pour rechercher..."
              class="input input-lg input-bordered w-full pl-12 pr-12 focus:input-primary transition-all"
              ref="searchInput"
              autofocus
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-4">
              <span v-if="searchQuery && !loading" class="text-xs text-base-content/40">
                {{ results.length }} résultat{{ results.length > 1 ? 's' : '' }}
              </span>
              <span v-if="loading" class="loading loading-spinner loading-sm"></span>
            </div>
          </div>
        </div>

        <!-- Filtres rapides améliorés -->
        <div class="flex flex-wrap gap-2 mb-5">
          <button
            v-for="filter in quickFilters"
            :key="filter.type"
            @click="applyQuickFilter(filter.type)"
            :class="[
              'btn btn-sm transition-all duration-200',
              activeFilter === filter.type 
                ? 'btn-primary shadow-lg scale-105' 
                : 'btn-outline hover:btn-primary hover:scale-105'
            ]"
          >
            <i :class="[filter.icon, activeFilter === filter.type ? 'text-white' : '']"></i>
            <span :class="activeFilter === filter.type ? 'text-white font-semibold' : ''">
              {{ filter.label }}
            </span>
          </button>
        </div>

        <!-- Résultats améliorés -->
        <div v-if="searchQuery || activeFilter" class="search-results">
          <div v-if="loading" class="flex flex-col items-center justify-center py-12">
            <span class="loading loading-spinner loading-lg text-primary mb-4"></span>
            <p class="text-sm text-base-content/60">Recherche en cours...</p>
          </div>

          <div v-else-if="results.length > 0" class="space-y-2 max-h-[500px] overflow-y-auto custom-scrollbar">
            <div
              v-for="(result, index) in results"
              :key="`${result.type}-${result.id}`"
              @click="navigateToResult(result)"
              @mouseenter="selectedIndex = index"
              :class="[
                'result-item group cursor-pointer transition-all duration-200 rounded-lg border',
                selectedIndex === index 
                  ? 'bg-primary/10 border-primary shadow-md scale-[1.02]' 
                  : 'bg-base-100 border-base-300 hover:bg-base-200 hover:border-primary/50 hover:shadow-sm'
              ]"
            >
              <div class="p-4">
                <div class="flex items-start gap-4">
                  <!-- Icône du type -->
                  <div :class="[
                    'flex items-center justify-center w-12 h-12 rounded-lg flex-shrink-0 transition-all',
                    getTypeIconClass(result.type)
                  ]">
                    <i :class="[getTypeIcon(result.type), 'text-lg']"></i>
                  </div>
                  
                  <!-- Contenu principal -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-3 mb-2">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                          <span :class="getTypeBadgeClass(result.type)">
                            {{ result.typeLabel }}
                          </span>
                          <h4 class="font-semibold text-base text-base-content truncate group-hover:text-primary transition-colors">
                            {{ result.title }}
                          </h4>
                        </div>
                        <p class="text-sm text-base-content/70 line-clamp-2 mb-2">
                          {{ result.description }}
                        </p>
                      </div>
                      <div class="flex items-center gap-2 flex-shrink-0">
                        <span v-if="result.amount" class="text-lg font-bold text-primary whitespace-nowrap">
                          {{ formatAmount(result.amount) }}
                        </span>
                        <i class="fas fa-chevron-right text-base-content/30 group-hover:text-primary group-hover:translate-x-1 transition-all"></i>
                      </div>
                    </div>
                    
                    <!-- Métadonnées -->
                    <div class="flex items-center gap-4 flex-wrap text-xs text-base-content/60">
                      <span v-if="result.date" class="flex items-center gap-1.5">
                        <i class="far fa-calendar text-xs"></i>
                        <span>{{ formatDate(result.date) }}</span>
                      </span>
                      <span v-if="result.status" class="flex items-center gap-1.5">
                        <span :class="getStatusClass(result.status)">{{ result.statusLabel }}</span>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="searchQuery && !loading" class="flex flex-col items-center justify-center py-12">
            <div class="w-20 h-20 rounded-full bg-base-200 flex items-center justify-center mb-4">
              <i class="fas fa-search text-3xl text-base-content/40"></i>
            </div>
            <p class="text-base font-medium text-base-content mb-1">Aucun résultat trouvé</p>
            <p class="text-sm text-base-content/60">Essayez avec d'autres mots-clés</p>
          </div>
        </div>

        <!-- Raccourcis clavier améliorés -->
        <div v-else class="flex flex-col items-center justify-center py-12">
          <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary/20 to-primary/10 flex items-center justify-center mb-6">
            <i class="fas fa-keyboard text-3xl text-primary"></i>
          </div>
          <p class="text-base font-semibold text-base-content mb-6">Raccourcis clavier</p>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full max-w-2xl">
            <div class="flex flex-col items-center p-4 rounded-lg bg-base-200/50 hover:bg-base-200 transition-colors">
              <kbd class="kbd kbd-lg mb-2">Ctrl+K</kbd>
              <span class="text-xs text-base-content/70 text-center">Ouvrir/Fermer</span>
            </div>
            <div class="flex flex-col items-center p-4 rounded-lg bg-base-200/50 hover:bg-base-200 transition-colors">
              <kbd class="kbd kbd-lg mb-2">Esc</kbd>
              <span class="text-xs text-base-content/70 text-center">Fermer</span>
            </div>
            <div class="flex flex-col items-center p-4 rounded-lg bg-base-200/50 hover:bg-base-200 transition-colors">
              <kbd class="kbd kbd-lg mb-2">Enter</kbd>
              <span class="text-xs text-base-content/70 text-center">Premier résultat</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'GlobalSearch',
  props: {
    isOpen: {
      type: Boolean,
      required: false,
      default: null
    }
  },
  emits: ['close', 'open'],
  data() {
    return {
      internalIsOpen: false,
      searchQuery: '',
      loading: false,
      results: [],
      activeFilter: null,
      selectedIndex: -1,
      quickFilters: [
        { type: 'invoices', label: 'Factures', icon: 'fas fa-file-invoice' },
        { type: 'quotes', label: 'Devis', icon: 'fas fa-file-alt' },
        { type: 'clients', label: 'Clients', icon: 'fas fa-users' },
        { type: 'payments', label: 'Paiements', icon: 'fas fa-money-check-alt' },
        { type: 'expenses', label: 'Dépenses', icon: 'fas fa-receipt' },
        { type: 'credits', label: 'Avoirs', icon: 'fas fa-undo' },
        { type: 'all', label: 'Tout', icon: 'fas fa-th' }
      ]
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin?.easyComptaTranslations || {};
    },
    isOpenComputed() {
      // Si isOpen est défini et n'est pas undefined (mode contrôlé), utiliser cette valeur
      // Sinon, utiliser l'état interne
      if (this.isOpen !== undefined && this.isOpen !== null) {
        return this.isOpen;
      }
      return this.internalIsOpen;
    },
    shouldShowButton() {
      // Afficher le bouton si la prop isOpen n'a pas été passée (mode autonome)
      // En Vue 3, si une prop n'est pas passée, elle sera null avec default: null
      // Mais on peut aussi vérifier via $attrs pour être sûr
      const propPassed = 'isOpen' in this.$attrs || (this.isOpen !== null && this.isOpen !== undefined);
      return !propPassed;
    }
  },
  watch: {
    isOpen(newVal) {
      if (newVal !== undefined) {
        this.internalIsOpen = newVal;
      }
    }
  },
  mounted() {
    // Raccourci clavier Ctrl+K
    document.addEventListener('keydown', this.handleKeyboard);
    if (this.isOpen !== undefined && this.isOpen !== null) {
      this.internalIsOpen = this.isOpen;
    }
  },
  beforeUnmount() {
    document.removeEventListener('keydown', this.handleKeyboard);
  },
  methods: {
    handleKeyboard(e) {
      // Ctrl+K ou Cmd+K pour ouvrir/fermer
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const currentState = this.isOpenComputed;
        if (currentState) {
          this.closeSearch();
        } else {
          this.openSearch();
        }
      }
    },
    openSearch() {
      if (this.isOpen === undefined || this.isOpen === null) {
        // Mode autonome : gérer l'état interne
        this.internalIsOpen = true;
      } else {
        // Mode contrôlé : émettre l'événement
        this.$emit('open');
        return; // Ne pas continuer si mode contrôlé
      }
      this.$nextTick(() => {
        if (this.$refs.searchInput) {
          this.$refs.searchInput.focus();
        }
      });
    },
    closeSearch() {
      if (this.isOpen === undefined || this.isOpen === null) {
        // Mode autonome : gérer l'état interne
        this.internalIsOpen = false;
        this.searchQuery = '';
        this.results = [];
        this.activeFilter = null;
        this.selectedIndex = -1;
      }
      this.$emit('close');
    },
    async performSearch() {
      if (!this.searchQuery && !this.activeFilter) {
        this.results = [];
        return;
      }

      this.loading = true;
      try {
        const response = await fetch(
          `/wp-json/my-easy-compta/v1/search?q=${encodeURIComponent(this.searchQuery)}&type=${this.activeFilter || 'all'}`,
          {
            headers: {
              'X-WP-Nonce': window.myEasyComptaAdmin?.nonce || ''
            }
          }
        );

        const data = await response.json();
        this.results = data.results || [];
        this.selectedIndex = -1; // Réinitialiser la sélection
      } catch (error) {
        console.error('Erreur de recherche:', error);
        this.results = [];
        this.selectedIndex = -1;
      } finally {
        this.loading = false;
      }
    },
    applyQuickFilter(type) {
      this.activeFilter = this.activeFilter === type ? null : type;
      this.performSearch();
    },
    handleEnter() {
      if (this.results.length > 0) {
        const index = this.selectedIndex >= 0 ? this.selectedIndex : 0;
        this.navigateToResult(this.results[index]);
      }
    },
    navigateToResult(result) {
      this.closeSearch();
      
      // Navigation selon le type
      if (result.type === 'invoice') {
        // Navigation vers le détail de la facture
        // Utiliser le hash pour Vue Router (fonctionne même sans $router injecté)
        window.location.href = `/wp-admin/admin.php?page=my-easy-compta-invoices#/invoice/detail/${result.id}`;
      } else if (result.type === 'quote') {
        // Navigation vers le détail du devis
        // Utiliser le hash pour Vue Router (fonctionne même sans $router injecté)
        window.location.href = `/wp-admin/admin.php?page=my-easy-compta-quotes#/quote/detail/${result.id}`;
      } else if (result.type === 'client') {
        // Émettre un événement pour ouvrir le modal de détail du client
        window.dispatchEvent(new CustomEvent('ecwp:open-client-modal', { 
          detail: { clientId: result.id } 
        }));
        // Si on n'est pas sur la page clients, naviguer vers cette page avec le paramètre
        if (!window.location.href.includes('my-easy-compta-clients')) {
          window.location.href = `/wp-admin/admin.php?page=my-easy-compta-clients&openClient=${result.id}`;
        }
      } else if (result.type === 'payment') {
        // Navigation vers la liste des paiements avec highlight
        window.location.href = `/wp-admin/admin.php?page=my-easy-compta-payments&highlight=${result.id}`;
      } else if (result.type === 'expense') {
        // Navigation vers la liste des dépenses avec highlight
        window.location.href = `/wp-admin/admin.php?page=my-easy-compta-expenses&highlight=${result.id}`;
      } else if (result.type === 'credit') {
        // Navigation vers la liste des avoirs avec highlight
        window.location.href = `/wp-admin/admin.php?page=my-easy-compta-credits&highlight=${result.id}`;
      }
    },
    formatDate(date) {
      if (!date) return '';
      const d = new Date(date);
      return d.toLocaleDateString('fr-FR');
    },
    formatAmount(amount) {
      if (!amount) return '0,00 €';
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
      }).format(amount);
    },
    getStatusClass(status) {
      const classes = {
        'paid': 'badge badge-success badge-sm',
        'unpaid': 'badge badge-warning badge-sm',
        'sent': 'badge badge-info badge-sm',
        'draft': 'badge badge-ghost badge-sm',
        'accepted': 'badge badge-success badge-sm',
        'pending': 'badge badge-warning badge-sm',
        'rejected': 'badge badge-error badge-sm'
      };
      return classes[status] || 'badge badge-ghost badge-sm';
    },
    getTypeIcon(type) {
      const icons = {
        'invoice': 'fas fa-file-invoice',
        'quote': 'fas fa-file-alt',
        'client': 'fas fa-user',
        'payment': 'fas fa-money-check-alt',
        'expense': 'fas fa-receipt',
        'credit': 'fas fa-undo'
      };
      return icons[type] || 'fas fa-file';
    },
    getTypeIconClass(type) {
      const classes = {
        'invoice': 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
        'quote': 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400',
        'client': 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
        'payment': 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-400',
        'expense': 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
        'credit': 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400'
      };
      return classes[type] || 'bg-base-200 text-base-content';
    },
    getTypeBadgeClass(type) {
      const classes = {
        'invoice': 'badge badge-sm badge-info',
        'quote': 'badge badge-sm badge-success',
        'client': 'badge badge-sm badge-secondary',
        'payment': 'badge badge-sm badge-warning',
        'expense': 'badge badge-sm badge-error',
        'credit': 'badge badge-sm'
      };
      return classes[type] || 'badge badge-sm badge-ghost';
    },
    navigateResults(direction) {
      if (this.results.length === 0) return;
      
      this.selectedIndex += direction;
      
      if (this.selectedIndex < 0) {
        this.selectedIndex = this.results.length - 1;
      } else if (this.selectedIndex >= this.results.length) {
        this.selectedIndex = 0;
      }
      
      // Scroll vers l'élément sélectionné
      this.$nextTick(() => {
        const selectedElement = document.querySelector(`.result-item:nth-child(${this.selectedIndex + 1})`);
        if (selectedElement) {
          selectedElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      });
    }
  },
  watch: {
    searchQuery() {
      // Debounce de la recherche
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.performSearch();
      }, 300);
    }
  }
};
</script>

<style scoped>
.global-search-container {
  position: relative;
  display: inline-block;
  z-index: 100;
}

.ecwp-search-swap {
  position: absolute;
  top: -37px;
  left: 40px;
  border-width: 1px 1px 0 1px;
  border-color: #c3c4c7;
  border-radius: 10px 10px 0px 0;
  cursor: pointer;
  transition: all 0.3s ease;
}

.ecwp-search-swap:hover {
  background-color: oklch(var(--p) / 0.1);
  border-color: oklch(var(--p));
}

.search-modal {
  animation: fadeIn 0.2s ease-out;
}

.search-modal-box {
  animation: slideUp 0.3s ease-out;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.search-results {
  max-height: 500px;
  overflow-y: auto;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 8px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
  border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: oklch(var(--bc) / 0.2);
  border-radius: 4px;
  transition: background 0.2s;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: oklch(var(--bc) / 0.4);
}

.result-item {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.result-item:hover {
  transform: translateX(4px);
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>

