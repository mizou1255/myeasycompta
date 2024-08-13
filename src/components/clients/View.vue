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
      <div v-if="loading">
        <!-- Skeleton -->
        <div class="grid grid-cols-2 gap-4">
          <div v-for="n in skeletonItems" :key="n" class="py-2">
            <div class="skeleton h-4 w-full mb-2"></div>
            <div class="skeleton h-4 w-full"></div>
          </div>
        </div>
      </div>
      <div v-else-if="client">
        <div class="grid grid-cols-2 gap-4">
          <div v-if="client.siret_number != 0" class="py-2">
            <dt class="text-sm font-medium text-gray-500">
              {{ translations.siret }}
            </dt>
            <dd class="mt-1 text-sm text-gray-900">
              {{ client["siret_number"] }}
            </dd>
            <dl class="divide-y divide-gray-200"></dl>
          </div>
          <div class="py-2" v-if="client.siren_number != 0">
            <dt class="text-sm font-medium text-gray-500">
              {{ translations.siren }}
            </dt>
            <dd class="mt-1 text-sm text-gray-900">
              {{ client["siren_number"] }}
            </dd>
            <dl class="divide-y divide-gray-200"></dl>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div v-for="(field, key) in fields" :key="key" class="py-2">
            <dt class="text-sm font-medium text-gray-500">{{ field.label }}</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ client[key] }}</dd>
            <dl class="divide-y divide-gray-200"></dl>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="py-2">
            <dt class="text-sm font-medium text-gray-500">
              {{ translations.currency }}
            </dt>
            <dd class="mt-1 text-sm text-gray-900">
              {{ getCurrencyOption(client.currency_id) }}
            </dd>
            <dl class="divide-y divide-gray-200"></dl>
          </div>
        </div>
        <div class="py-2">
          <dt class="text-sm font-medium text-gray-500">
            {{ translations.note }}
          </dt>
          <dd class="mt-1 text-sm text-gray-900">{{ client.note }}</dd>
          <dl class="divide-y divide-gray-200"></dl>
        </div>
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
    loading: Boolean,
    showModal: Boolean,
    modalId: String,
    modalTitle: String,
    client: Object,
    currencyOptions: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    const translations = window.myEasyComptaAdmin.easyComptaTranslations;
    return {
      addon_siret_active: "",
      localCurrencyOptions: this.currencyOptions,
      translations: translations,
    };
  },
  computed: {
    skeletonItems() {
      return Array.from({ length: 7 }, (_, index) => index);
    },
    fields() {
      const translations = window.myEasyComptaAdmin.easyComptaTranslations;
      return {
        company_name: { label: translations.company_name },
        manager_name: { label: translations.manager_name },
        email: { label: translations.email },
        phone: { label: translations.phone },
        mobile_phone: { label: translations.mobile },
        website: { label: translations.website },
        address: { label: translations.address },
        city: { label: translations.city },
        postal_code: { label: translations.postal_code },
        country: { label: translations.country },
      };
    },
  },
  mounted() {
    this.fetchOptions();
  },
  methods: {
    closeModal() {
      const modal = document.getElementById(this.modalId);
      if (modal) {
        modal.close();
        this.$emit("close");
      }
    },
    fetchOptions() {
      fetch(`/wp-json/my-easy-compta/v1/options`, {
        headers: {
          "X-WP-Nonce": myEasyComptaAdmin.nonce,
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.currency_options) {
            this.localCurrencyOptions = data.currency_options;
            this.addon_siret_active = data.addon_siret_active;
          }
        })
        .catch((error) => {
          console.error("Erreur lors de la récupération des options", error);
        });
    },
    getCurrencyOption(currencyId) {
      const option = this.localCurrencyOptions.find(
        (opt) => opt.id === currencyId
      );
      return option ? option.name : "N/A";
    },
  },
};
</script>
