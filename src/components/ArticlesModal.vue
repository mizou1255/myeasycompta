<template>
  <dialog :id="modalId" class="modal" :open="showModal">
    <div class="modal-box">
      <h3 class="font-bold text-lg">{{ modalTitle }}</h3>
      <button
        class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
        @click="closeModal()"
      >
        ✕
      </button>
      <div v-if="articles">
        <ul v-if="articles.length" class="">
          <li
            v-for="item in articles"
            :key="item.name"
            @click="selectItem(item)"
            class="flex justify-between autocomplete-item hover:bg-base-200"
          >
            <span
              ><strong>{{ item.ref }}</strong> - {{ item.name }}</span
            >
            <span
              ><strong>{{ item.unit_price }}</strong></span
            >
          </li>
        </ul>
      </div>
      <div v-else>
        <!-- Skeleton -->
        <div class="grid grid-cols-2 gap-4">
          <div v-for="n in skeletonItems" :key="n" class="py-2">
            <div class="skeleton h-4 w-full mb-2"></div>
            <div class="skeleton h-4 w-full"></div>
          </div>
        </div>
      </div>
    </div>
  </dialog>
</template>
  
  
  <script>
export default {
  props: {
    showModal: Boolean,
    modalId: String,
    modalTitle: String,
  },
  data() {
    const translations = window.myEasyComptaAdmin.easyComptaTranslations;
    return {
      articles: [],
      translations: translations,
    };
  },
  computed: {
    skeletonItems() {
      return Array.from({ length: 7 }, (_, index) => index);
    },
  },
  mounted() {
    this.fetchArticlesList();
  },
  methods: {
    closeModal() {
      const modal = document.getElementById(this.modalId);
      if (modal) {
        modal.close();
        this.$emit("close");
      }
    },
    selectItem(item) {
      this.$emit("select-article", item);
      this.closeModal();
    },
    fetchArticlesList() {
      this.loading = true;
      fetch("/wp-json/my-easy-compta/v1/articles", {
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          this.articles = data;
          this.loading = false;
        })
        .catch((error) => {
          console.error("Erreur lors du chargement des articles :", error);
          this.loading = false;
        });
    },
  },
};
</script>
  