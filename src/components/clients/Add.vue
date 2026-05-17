<template>
  <div>
    <!-- Toast Notification -->
    <div v-if="toast.visible" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] toast-animate-in">
      <div :class="['flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md', toast.type === 'alert-success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
        <component :is="toast.type === 'alert-success' ? 'CheckCircle2' : 'AlertCircle'" class="w-6 h-6" />
        <span class="font-bold text-sm">{{ toast.message }}</span>
        <button @click="toast.visible = false" class="ml-2 hover:bg-white/20 p-1 rounded-full transition-colors"><X class="w-4 h-4" /></button>
      </div>
    </div>

    <!-- Modal -->
    <dialog :open="showModal" :class="['fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4 w-full h-full border-none m-0 max-w-none max-h-none', showModal ? 'block' : 'hidden']">
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-full max-w-2xl shadow-2xl border border-slate-100 dark:border-slate-800 text-left relative max-h-[90vh] overflow-y-auto">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8 sticky top-0 bg-white dark:bg-slate-900 z-10 pb-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                <UserPlus class="w-6 h-6 text-purple-600" />
                {{ translations.add_client }}
            </h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl">
                <X class="w-6 h-6" />
            </button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                 <div v-for="(field, key) in fields" :key="key" class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ field.label }}
                    </label>
                    <input :type="field.type || 'text'" v-model="newClient[key]" :placeholder="field.label" class="kloxy-input" />
                 </div>

                 <!-- Currency -->
                 <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.currency }}
                    </label>
                    <div class="relative">
                        <select v-model="newClient.currency_id" class="kloxy-select-native">
                            <option v-for="opt in currencyOptions" :key="opt.id" :value="opt.id">
                                {{ opt.name }} - {{ opt.code }} ({{ opt.symbol }})
                            </option>
                        </select>
                         <ChevronDown class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
                    </div>
                 </div>
            </div>

            <!-- Address Block with Autocomplete -->
            <div class="border-t border-slate-100 dark:border-slate-800 pt-6">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 flex items-center gap-2">
                    <MapPin class="w-3.5 h-3.5" /> {{ translations.address || 'Adresse' }}
                </h4>
                <div class="space-y-4">
                    <!-- Address with autocomplete -->
                    <div class="space-y-2 relative">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                            {{ translations.address || 'Adresse' }}
                        </label>
                        <input
                            type="text"
                            v-model="newClient.address"
                            @input="onAddressInput"
                            @blur="hideSuggestionsDelayed"
                            :placeholder="translations.address || 'Adresse'"
                            class="kloxy-input"
                            autocomplete="off"
                        />
                        <!-- Suggestions dropdown -->
                        <ul v-if="suggestions.length > 0" class="absolute z-50 left-0 right-0 mt-1 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 overflow-hidden">
                            <li
                                v-for="(s, i) in suggestions"
                                :key="i"
                                @mousedown.prevent="selectSuggestion(s)"
                                class="flex items-start gap-3 px-5 py-3 cursor-pointer hover:bg-purple-50 dark:hover:bg-slate-700 transition-colors"
                            >
                                <MapPin class="w-4 h-4 text-purple-400 mt-0.5 shrink-0" />
                                <div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">{{ s.label }}</p>
                                    <p class="text-xs text-slate-400">{{ s.context }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                                {{ translations.city || 'Ville' }}
                            </label>
                            <input type="text" v-model="newClient.city" :placeholder="translations.city || 'Ville'" class="kloxy-input" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                                {{ translations.postal_code || 'Code postal' }}
                            </label>
                            <input type="text" v-model="newClient.postal_code" :placeholder="translations.postal_code || 'Code postal'" class="kloxy-input" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                                {{ translations.country || 'Pays' }}
                            </label>
                            <input type="text" v-model="newClient.country" :placeholder="translations.country || 'Pays'" class="kloxy-input" />
                        </div>
                    </div>
                </div>
            </div>

             <div class="space-y-2">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.note }}</label>
                <textarea v-model="newClient.note" class="kloxy-input min-h-[100px]" :placeholder="translations.note"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800 sticky bottom-0 bg-white dark:bg-slate-900 z-10 py-4">
               <button type="button" @click="closeModal" class="px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                   {{ translations.cancel || 'Annuler' }}
               </button>
               <button type="submit" :disabled="loadingBtn" class="bg-purple-600 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                   <span v-if="loadingBtn" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                   <Check v-else class="w-4 h-4" />
                   {{ translations.save }}
               </button>
            </div>
        </form>
      </div>
    </dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { X, CheckCircle2, AlertCircle, ChevronDown, UserPlus, Check, MapPin } from 'lucide-vue-next';

const props = defineProps({
    showModal: Boolean
});
const emit = defineEmits(['close', 'clientAdded']);

const newClient = reactive({});
const currencyOptions = ref([]);
const loadingBtn = ref(false);
const toast = reactive({ visible: false, message: "", type: "alert-success" });
const suggestions = ref([]);
let suggestTimer = null;

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

const fields = computed(() => ({
    siren_number: { label: translations.value.siren },
    tax_number: { label: translations.value.tax_number },
    company_name: { label: translations.value.company_name },
    manager_name: { label: translations.value.manager_name },
    email: { label: translations.value.email },
    phone: { label: translations.value.phone, type: "tel" },
    mobile_phone: { label: translations.value.mobile, type: "tel" },
    website: { label: translations.value.website, type: "url" },
}));

const showToast = (message, type) => {
    toast.message = message;
    toast.type = type;
    toast.visible = true;
    setTimeout(() => toast.visible = false, 3000);
};

const closeModal = () => {
    emit('close');
    Object.keys(newClient).forEach(k => delete newClient[k]);
    suggestions.value = [];
};

const onAddressInput = () => {
    clearTimeout(suggestTimer);
    const q = newClient.address?.trim();
    if (!q || q.length < 3) { suggestions.value = []; return; }
    suggestTimer = setTimeout(() => fetchSuggestions(q), 300);
};

const fetchSuggestions = async (q) => {
    try {
        const res = await fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(q)}&limit=5`);
        if (!res.ok) return;
        const data = await res.json();
        suggestions.value = (data.features || []).map(f => ({
            label: f.properties.name || f.properties.label,
            city: f.properties.city || '',
            postcode: f.properties.postcode || '',
            country: 'France',
            context: f.properties.context || '',
            fullLabel: f.properties.label,
        }));
    } catch {
        suggestions.value = [];
    }
};

const selectSuggestion = (s) => {
    newClient.address = s.label;
    newClient.city = s.city;
    newClient.postal_code = s.postcode;
    newClient.country = s.country;
    suggestions.value = [];
};

const hideSuggestionsDelayed = () => {
    setTimeout(() => { suggestions.value = []; }, 200);
};

const fetchOptions = async () => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/options`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        if(res.ok) {
            const data = await res.json();
            currencyOptions.value = data.currency_options;
        }
    } catch (e) {}
};

const submitForm = async () => {
    loadingBtn.value = true;
    try {
        const res = await fetch("/wp-json/my-easy-compta/v1/clients/add", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": window.myEasyComptaAdmin.nonce,
            },
            body: JSON.stringify(newClient),
        });
        const data = await res.json();
        if (res.ok) {
             showToast(data.message, "alert-success");
             emit('clientAdded', data.client);
             closeModal();
        } else {
             showToast(data.message || "Error", "alert-error");
        }
    } catch (e) {
        showToast("Server Error", "alert-error");
    } finally { loadingBtn.value = false; }
};

onMounted(fetchOptions);
</script>

<style scoped>
.kloxy-input {
    @apply w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white placeholder:text-slate-400;
}
.kloxy-select-native {
    @apply w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white appearance-none cursor-pointer;
}
</style>
