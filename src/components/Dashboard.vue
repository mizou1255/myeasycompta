<template>
  <div class="pt-2 pr-4">
    <label class="ecwp-swap swap swap-rotate bg-base-100 p-2 mt-10">
      <input type="checkbox" class="theme-controller" @change="toggleTheme" />
      <i class="swap-off far fa-sun text-xl"></i>
      <i class="swap-on far fa-moon text-xl"></i>
    </label>
    <Stats />

    <div class="grid lg:grid-cols-3 mt-4 grid-cols-1 gap-6">
      <div class="card w-full p-6 bg-base-100 shadow-xl mt-6 col-span-2">
        <BarChart />
      </div>

      <div class="card w-full p-6 bg-base-100 shadow-xl mt-6">
        <InvoicesHistory />
      </div>
    </div>
  </div>
</template>
  
<script>
import Stats from "@/components/dashboard/Stats.vue";
import BarChart from "@/components/dashboard/BarChart.vue";
import InvoicesHistory from "@/components/dashboard/InvoicesHistory.vue";

export default {
  name: "Dashboard",
  components: {
    Stats,
    BarChart,
    InvoicesHistory,
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
};
</script>

  