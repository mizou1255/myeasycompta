<template>
  <div class="relative pt-2 pr-4">
    <!-- Boutons de contrôle en haut à droite -->
    <div class="flex gap-2">
      <!-- Bouton de recherche -->
      <label
        v-if="!searchOpen"
        class="ecwp-swap bg-base-100 p-2"
        style="position: absolute; top: 1px;left: 40px; right: auto; z-index: 10; border-width: 1px 1px 0 1px; border-color: #c3c4c7; border-radius: 10px 10px 0px 0; cursor: pointer; transition: all 0.3s ease;"
        @click="openSearch"
        @mouseenter="$event.target.style.backgroundColor = 'oklch(var(--p) / 0.1)'"
        @mouseleave="$event.target.style.backgroundColor = ''"
      >
        <i class="fas fa-search text-xl"></i>
      </label>
      <!-- Toggle thème -->
      <label class="ecwp-swap swap swap-rotate bg-base-100 p-2" style="top: 2px">
        <input type="checkbox" class="theme-controller" @change="toggleTheme" />
        <i class="swap-off far fa-sun text-xl"></i>
        <i class="swap-on far fa-moon text-xl"></i>
      </label>
    </div>
    
    <!-- Modal de recherche -->
    <GlobalSearch :isOpen="searchOpen" @close="closeSearch" @open="searchOpen = true" />
    
    <Stats />

    <div class="grid lg:grid-cols-3 mt-4 grid-cols-1 gap-6">
      <div class="card w-full p-6 bg-base-100 shadow-xl col-span-2 mt-4">
        <BarChart />
      </div>

      <div class="card w-full p-6 bg-base-100 shadow-xl mt-4">
        <InvoicesHistory />
      </div>
    </div>
  </div>
</template>
  
<script>
import Stats from "@/components/dashboard/Stats.vue";
import BarChart from "@/components/dashboard/BarChart.vue";
import InvoicesHistory from "@/components/dashboard/InvoicesHistory.vue";
import GlobalSearch from "@/components/GlobalSearch.vue";

export default {
  name: "Dashboard",
  components: {
    Stats,
    BarChart,
    InvoicesHistory,
    GlobalSearch,
  },
  data() {
    return {
      searchOpen: false,
    };
  },
  methods: {
    setTheme(theme) {
      document.documentElement.setAttribute("data-theme", theme);
      localStorage.setItem("theme", theme);
    },
    toggleTheme() {
      const currentTheme = document.documentElement.getAttribute("data-theme");
      if (currentTheme === "dark") {
        this.setTheme("winter");
      } else {
        this.setTheme("dark");
      }
    },
    openSearch() {
      this.searchOpen = true;
    },
    closeSearch() {
      this.searchOpen = false;
    },
  },
  mounted() {
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme) {
      this.setTheme(savedTheme);
    } else if (
      window.matchMedia &&
      window.matchMedia("(prefers-color-scheme: dark)").matches
    ) {
      this.setTheme("dark");
    }
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
};
</script>

  