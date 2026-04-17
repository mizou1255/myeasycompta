<template>
  <div
    :class="[
      'ecwp-card w-full p-6',
      topMargin || 'mt-8',
    ]"
  >
    <div class="flex gap-2 justify-end mb-4">
      <label class="swap swap-rotate ecwp-btn ecwp-btn-circle ecwp-btn-ghost">
        <input type="checkbox" class="theme-controller" @change="toggleTheme" />
        <i class="swap-off fas fa-sun text-xl text-yellow-500"></i>
        <i class="swap-on fas fa-moon text-xl text-blue-500"></i>
      </label>
    </div>
    
    <div>
      <slot></slot>
    </div>
  </div>
</template>
  
<script>
export default {
  name: "Card",
  props: {
    topMargin: {
      type: String,
      default: "mt-8",
    },
  },
  methods: {
    setTheme(theme) {
      document.documentElement.setAttribute("data-theme", theme);
      localStorage.setItem("theme", theme);
    },
    toggleTheme(e) {
      if (e.target.checked) {
        this.setTheme("dark");
      } else {
        this.setTheme("winter");
      }
    },
  },
  mounted() {
    const savedTheme = localStorage.getItem("theme");
    const checkbox = this.$el.querySelector('.theme-controller');
    
    if (savedTheme) {
      this.setTheme(savedTheme);
      if (savedTheme === 'dark' && checkbox) {
        checkbox.checked = true;
      }
    } else if (
      window.matchMedia &&
      window.matchMedia("(prefers-color-scheme: dark)").matches
    ) {
      this.setTheme("dark");
      if (checkbox) {
        checkbox.checked = true;
      }
    }
  },
};
</script>