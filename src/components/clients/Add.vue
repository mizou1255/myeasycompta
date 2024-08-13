<template>
  <div>
    <div
      v-if="toast.visible"
      :class="['toast', toast.position]"
      :style="{ zIndex: 9999 }"
    >
      <div :class="['alert', toast.type, 'text-white']">
        <span>{{ toast.message }}</span>
      </div>
    </div>
    <dialog id="modal_clients" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg">{{ translations.new_client }}</h3>
        <form @submit.prevent="submitForm">
          <button
            class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
            @click="closeModal()"
          >
            ✕
          </button>
          <div v-if="options.addon_siret_active" class="grid grid-cols-1 gap-4">
            <div class="ecwp-group form-group relative join">
              <label for="siret" class="ecwp-label form-label">{{
                translations.siret
              }}</label>
              <input
                type="number"
                id="siret"
                v-model="formData.siret"
                class="ecwp-input input input-bordered w-full"
              />
              <button
                @click="fetchCompanyInfo"
                class="btn join-item rounded-r-full mt-5"
                :disabled="loadingSiret"
              >
                <span
                  v-if="loadingSiret"
                  class="loading loading-spinner loading-sm"
                ></span>
                <span v-else>
                  {{ translations.search }}
                </span>
              </button>
            </div>
          </div>
          <div class="grid grid-cols-1 gap-4">
            <div class="ecwp-group form-group relative">
              <label for="siren" class="ecwp-label form-label">{{
                translations.siren
              }}</label>
              <input
                type="number"
                id="siren"
                v-model="formData.siren_number"
                class="ecwp-input input input-bordered w-full"
              />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div
              v-for="(field, key) in fields"
              :key="key"
              class="ecwp-group form-group relative"
            >
              <label :for="key" class="ecwp-label form-label">{{
                field.label
              }}</label>
              <template v-if="key === 'currency_id'">
                <select
                  :id="key"
                  v-model="formData[key]"
                  class="ecwp-input input input-bordered w-full peer"
                  :required="field.required"
                >
                  <option :value="options.default_currency">
                    {{ translations.default_currency }}
                  </option>
                  <option
                    v-for="option in getOptions(key)"
                    :key="option.id"
                    :value="option.id"
                  >
                    {{ option.name }} - {{ option.code }} ({{ option.symbol }})
                  </option>
                </select>
              </template>
              <template v-else>
                <input
                  :type="field.type"
                  :id="key"
                  v-model="formData[key]"
                  class="ecwp-input input input-bordered w-full peer"
                  placeholder=" "
                  :required="field.required"
                />
              </template>
            </div>
          </div>
          <div class="ecwp-group form-group mt-4 relative">
            <label :for="key" class="ecwp-label form-label">{{
              translations.note
            }}</label>
            <textarea
              id="note"
              v-model="formData.note"
              class="ecwp-input textarea textarea-bordered w-full peer"
              rows="4"
              placeholder=" "
            ></textarea>
          </div>
          <div
            v-if="options.addon_user_active"
            class="ecwp-group form-group mt-6 w-52 flex justify-between"
          >
            <label>{{ translations.create_user }}</label>
            <input
              class="ecwp-switch"
              type="checkbox"
              v-model="formData.user_create"
            />
          </div>
          <div class="form-group mt-4 flex justify-end">
            <button
              type="submit"
              class="btn btn-primary rounded-full"
              :disabled="loadingBtn"
            >
              {{ translations.add }}
              <span
                v-if="loadingBtn"
                class="loading loading-spinner loading-sm"
              ></span>
            </button>
          </div>
        </form>
      </div>
    </dialog>
  </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    const translations = window.myEasyComptaAdmin.easyComptaTranslations;
    return {
      formData: {
        siret: "",
        company_name: "",
        siren_number: "",
        manager_name: "",
        address: "",
        city: "",
        postal_code: "",
        country: "",
        phone: "",
        mobile_phone: "",
        email: "",
        website: "",
        currency_id: "",
        note: "",
        user_create: "",
      },
      fields: {
        company_name: {
          label: translations.company_name,
          type: "text",
          required: true,
        },
        manager_name: {
          label: translations.manager_name,
          type: "text",
        },
        address: {
          label: translations.address,
          type: "text",
          required: true,
        },
        city: { label: translations.city, type: "text", required: true },
        postal_code: {
          label: translations.postal_code,
          type: "text",
        },
        country: {
          label: translations.country,
          type: "text",
        },
        phone: { label: translations.phone, type: "tel", required: true },
        mobile_phone: {
          label: translations.mobile,
          type: "tel",
        },
        email: {
          label: translations.email,
          type: "email",
          required: true,
        },
        website: {
          label: translations.website,
          type: "url",
        },
        currency_id: {
          label: translations.currency,
          type: "text",
          required: true,
        },
      },
      options: {
        currency_options: [],
        default_currency: "",
        addon_user_active: false,
        addon_siret_active: false,
      },
      toast: {
        visible: false,
        message: "",
        type: "alert-success",
        position: "toast-bottom toast-end",
      },
      loadingBtn: false,
      loadingSiret: false,
    };
  },
  computed: {
    translations() {
      return window.myEasyComptaAdmin.easyComptaTranslations;
    },
  },
  methods: {
    submitForm() {
      this.loadingBtn = true;
      axios
        .post("/wp-json/my-easy-compta/v1/clients/add", this.formData, {
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        })
        .then((response) => {
          if (response.data.success) {
            this.showToast(response.data.message, "alert-success");
            this.loadingBtn = false;
            this.resetForm();
            this.closeModal();
            this.$emit("clientAdded");
          } else {
            this.showToast(response.data.message, "alert-error");
            this.loadingBtn = false;
          }
        })
        .catch((error) => {
          const errorMessage =
            error.response && error.response.data && error.response.data.message
              ? error.response.data.message
              : "Erreur serveur";
          console.error(error);
          this.showToast(errorMessage, "alert-error");
          this.loadingBtn = false;
        });
    },
    closeModal() {
      const modal = document.getElementById("modal_clients");
      modal.close();
    },
    showToast(message, type) {
      this.toast.message = message;
      this.toast.type = type;
      this.toast.visible = true;
      setTimeout(() => {
        this.toast.visible = false;
      }, 3000);
    },
    resetForm() {
      for (let key in this.formData) {
        this.formData[key] = "";
      }
    },
    getOptions(key) {
      if (key === "currency_id") {
        return this.options.currency_options;
      }
      return [];
    },
    fetchOptions() {
      axios
        .get("/wp-json/my-easy-compta/v1/options", {
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        })
        .then((response) => {
          this.options.currency_options = response.data.currency_options;
          this.options.default_currency = response.data.default_currency;
          this.options.addon_user_active = response.data.addon_user_active;
          this.options.addon_siret_active = response.data.addon_siret_active;
        })
        .catch((error) => {
          console.error("Erreur lors de la récupération des options", error);
        });
    },

    fetchCompanyInfo(event) {
      event.preventDefault();
      this.loadingSiret = true;
      const siret = this.formData.siret;

      if (siret) {
        fetch(`/wp-json/my-easy-compta/v1/fetch-company-info/${siret}`, {
          headers: {
            "X-WP-Nonce": myEasyComptaAdmin.nonce,
          },
        })
          .then((response) => {
            if (!response.ok) {
              throw new Error("Network response was not ok");
            }
            return response.json();
          })
          .then((data) => {
            if (data) {
              this.formData.company_name = data.company_name;
              this.formData.manager_name = data.manager_name;
              this.formData.address = data.address;
              this.formData.postal_code = data.postal_code;
              this.formData.city = data.city;
              this.formData.siren_number = data.siren;

              this.showToast(
                "Informations chargées avec succès",
                "alert-success"
              );
              this.loadingSiret = false;
            } else {
              this.showToast(
                "Aucune information trouvée pour ce SIRET",
                "alert-error"
              );
              this.loadingSiret = false;
            }
          })
          .catch((error) => {
            console.error("Error fetching company info:", error);
            this.showToast(
              "Erreur lors du chargement des informations",
              "alert-error"
            );
            this.loadingSiret = false;
          });
      } else {
        this.showToast("Veuillez saisir un numéro SIRET", "alert-error");
        this.loadingSiret = false;
      }
    },
  },
  mounted() {
    this.fetchOptions();
  },
};
</script>
