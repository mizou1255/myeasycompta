<template>
  <dialog :id="modalId" class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200">
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-[90vw] max-w-2xl shadow-2xl border border-slate-100 dark:border-slate-800 text-left relative overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ modalTitle }}</h3>
            <div class="flex items-center gap-2">
                <button @click="openLibrary" type="button" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 font-bold text-xs uppercase tracking-widest hover:bg-purple-600 hover:text-white transition-all">
                    <Package class="w-4 h-4" />
                    {{ translations.library || 'Bibliothèque' }}
                </button>
                <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl">
                    <X class="w-6 h-6" />
                </button>
            </div>
        </div>

        <form @submit.prevent="submitForm" class="space-y-6">
            <div class="space-y-6">
                <!-- Grid 1: Ref & Category -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2 relative search-container">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                            {{ translations.item_ref }}
                        </label>
                        <input 
                            type="text" 
                            v-model="newItem.item_ref" 
                            @input="searchArticles(newItem.item_ref, 'ref')"
                            class="kloxy-input" 
                            :placeholder="translations.item_ref" 
                        />
                        <!-- Suggestions -->
                        <div v-if="showSuggestions && activeSearchField === 'ref'" class="absolute left-0 right-0 top-full mt-2 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800 z-[100] max-h-60 overflow-y-auto p-2">
                             <button 
                                v-for="s in suggestions" 
                                :key="s.ref" 
                                @click="selectSuggestion(s)"
                                type="button"
                                class="w-full text-left p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
                             >
                                 <div class="flex justify-between items-center mb-1">
                                     <span class="font-black text-slate-700 dark:text-slate-200 text-xs">{{ s.ref }}</span>
                                     <span class="text-xs font-mono text-purple-600 font-bold">{{ s.unit_price }}</span>
                                 </div>
                                 <div class="text-slate-900 dark:text-white font-bold text-sm">{{ s.name }}</div>
                             </button>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                            Catégorie
                        </label>
                        <div class="flex gap-2">
                            <select v-model="newItem.item_category" class="kloxy-input cursor-pointer flex-1">
                                <option value="Type" disabled>Choisir un type</option>
                                <option v-for="cat in localCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <button type="button" @click="openAddCategory" class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-2xl bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 hover:bg-purple-600 hover:text-white transition-all" :title="'Ajouter une catégorie'">
                                <Plus class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Item Name -->
                <div class="space-y-2 relative search-container">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.item_name }}
                    </label>
                    <input 
                        type="text" 
                        v-model="newItem.item_name" 
                        @input="searchArticles(newItem.item_name, 'name')"
                        class="kloxy-input" 
                        :placeholder="translations.item_name" 
                    />
                    <!-- Suggestions -->
                    <div v-if="showSuggestions && activeSearchField === 'name'" class="absolute left-0 right-0 top-full mt-2 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800 z-[100] max-h-60 overflow-y-auto p-2">
                            <button 
                                v-for="s in suggestions" 
                                :key="s.ref" 
                                @click="selectSuggestion(s)"
                                type="button"
                                class="w-full text-left p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group"
                            >
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-black text-slate-700 dark:text-slate-200 text-xs">{{ s.ref }}</span>
                                    <span class="text-xs font-mono text-purple-600 font-bold">{{ s.unit_price }}</span>
                                </div>
                                <div class="text-slate-900 dark:text-white font-bold text-sm">{{ s.name }}</div>
                            </button>
                    </div>
                </div>

                <!-- Description (Wysiwyg) -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.description }}
                    </label>
                    <div class="kloxy-editor-wrapper">
                         <vue-editor v-model="newItem.item_description" :editorToolbar="toolbarOptions" />
                    </div>
                </div>

                <!-- Grid Inputs -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                     <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.quantity }}</label>
                        <input type="number" step="any" v-model="newItem.quantity" class="kloxy-input" />
                     </div>
                     <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.unit_price }}</label>
                        <input type="number" step="any" v-model="newItem.unit_price" class="kloxy-input" />
                     </div>
                     <div class="space-y-2">
                         <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.tax }} (%)</label>
                         <input type="number" step="any" v-model="newItem.vat_rate" class="kloxy-input" />
                     </div>
                      <div class="space-y-2">
                         <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.discount }} (%)</label>
                         <input type="number" step="any" v-model="newItem.discount" class="kloxy-input" />
                     </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
               <button type="button" @click="closeModal" class="px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                   {{ translations.cancel || 'Annuler' }}
               </button>
               <button type="submit" :disabled="loadingBtn" class="bg-purple-600 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                   <span v-if="loadingBtn" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                   <Plus class="w-4 h-4" />
                   {{ translations.add || 'Ajouter' }}
               </button>
            </div>
        </form>
      </div>

      <!-- Full Library Modal -->
      <ArticleModal 
        :show-modal="showArticlesLibrary"
        modal-id="modal_articles_lib_addition_invoice"
        :modal-title="translations.articles_list || 'Bibliothèque d\'articles'"
        @close="showArticlesLibrary = false"
        @select-article="onArticleFromLibrary"
      />

      <!-- Sub-modal: Add Category -->
      <dialog ref="categoryDialogRef" class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/70 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200">
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 w-[90vw] max-w-sm shadow-2xl border border-slate-100 dark:border-slate-800 text-left">
          <div class="flex items-center justify-between mb-5">
            <h4 class="text-lg font-black text-slate-900 dark:text-white">Nouvelle catégorie</h4>
            <button type="button" @click="closeAddCategory" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-xl transition-colors">
              <X class="w-5 h-5" />
            </button>
          </div>
          <div class="space-y-4">
            <input
              v-model="newCategoryName"
              type="text"
              class="kloxy-input"
              placeholder="Nom de la catégorie"
              @keydown.enter.prevent="submitCategory"
            />
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100 dark:border-slate-800">
              <button type="button" @click="closeAddCategory" class="px-5 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                Annuler
              </button>
              <button type="button" @click="submitCategory" :disabled="loadingCategory || !newCategoryName.trim()" class="bg-purple-600 text-white px-6 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                <span v-if="loadingCategory" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                <Plus class="w-3.5 h-3.5" v-else />
                Créer
              </button>
            </div>
          </div>
        </div>
      </dialog>

      <form method="dialog" class="fixed inset-0 z-[-1] cursor-default bg-transparent w-full h-full outline-none" @click="closeModal"></form>
    </dialog>
</template>

<script setup>
import { ref, reactive, computed, watch, toRefs, onMounted } from 'vue';
import { VueEditor } from "vue3-editor";
import { X, Plus, Package } from 'lucide-vue-next';
import ArticleModal from "@/components/ArticlesModal.vue";
import axios from 'axios';

const props = defineProps({
    showModal: Boolean,
    modalId: String,
    modalTitle: String,
    categories: Array,
    invoiceId: [Number, String],
});

const emit = defineEmits(['close', 'itemAdded']);

const { showModal, modalId } = toRefs(props);
const loadingBtn = ref(false);
const showArticlesLibrary = ref(false);

// Local copy of categories so we can append new ones without mutating the prop
const localCategories = ref([...(props.categories || [])]);
watch(() => props.categories, (val) => { localCategories.value = [...(val || [])]; });

// Sub-modal: add category
const categoryDialogRef = ref(null);
const newCategoryName = ref('');
const loadingCategory = ref(false);

const openAddCategory = () => {
    newCategoryName.value = '';
    categoryDialogRef.value?.showModal();
};
const closeAddCategory = () => {
    categoryDialogRef.value?.close();
};

const submitCategory = async () => {
    if (!newCategoryName.value.trim()) return;
    loadingCategory.value = true;
    try {
        const res = await axios.post('/wp-json/my-easy-compta/v1/settings/categories-articles', { name: newCategoryName.value.trim() }, {
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce }
        });
        if (res.data.success) {
            const created = { id: res.data.id, name: newCategoryName.value.trim() };
            localCategories.value.push(created);
            newItem.item_category = created.id;
            closeAddCategory();
        }
    } catch (e) {
    } finally {
        loadingCategory.value = false;
    }
};
const suggestions = ref([]);
const showSuggestions = ref(false);
const activeSearchField = ref(''); // 'ref' or 'name'

const newItem = reactive({
    item_ref: "",
    item_name: "",
    item_category: "Type",
    item_description: "",
    quantity: 1,
    unit_price: 0,
    vat_rate: 0,
    discount: 0
});

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});
const toolbarOptions = [
    ["bold", "italic", "underline", "strike"],
    [{ list: "ordered" }, { list: "bullet" }],
    ["clean"],
];

watch(showModal, (val) => {
    const el = document.getElementById(modalId.value);
    if(el) {
        if(val && !el.open) {
            // Reset form on open
            Object.assign(newItem, {
                item_ref: "",
                item_name: "",
                item_category: "Type",
                item_description: "",
                quantity: 1,
                unit_price: 0,
                vat_rate: 0,
                discount: 0
            });
            el.showModal();
        }
        if(!val && el.open) el.close();
    }
});

const closeModal = () => {
    emit('close');
};

const searchArticles = async (query, method) => {
    if (query.length < 2) {
        suggestions.value = [];
        showSuggestions.value = false;
        return;
    }
    try {
        const res = await axios.get(`/wp-json/my-easy-compta/v1/articles`, {
            params: { search: query, method: method },
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        suggestions.value = res.data;
        showSuggestions.value = suggestions.value.length > 0;
        activeSearchField.value = method;
    } catch (e) {}
};

const selectSuggestion = (article) => {
    newItem.item_ref = article.ref || article.item_ref;
    newItem.item_name = article.name || article.item_name;
    newItem.item_description = article.description || article.item_description;
    newItem.unit_price = article.unit_price || article.price || 0;
    newItem.item_category = article.category_id || article.item_category || "Type";
    newItem.vat_rate = article.vat_rate || article.tva || 0;
    
    showSuggestions.value = false;
    suggestions.value = [];
};

const openLibrary = () => {
    showArticlesLibrary.value = true;
};

const onArticleFromLibrary = (article) => {
    selectSuggestion(article);
    showArticlesLibrary.value = false;
};

const submitForm = async () => {
    if(!newItem.item_name) return;
    loadingBtn.value = true;
    try {
        // Calculate totals
        const qty = parseFloat(newItem.quantity) || 0;
        const price = parseFloat(newItem.unit_price) || 0;
        const vat = parseFloat(newItem.vat_rate) || 0;
        const discount = parseFloat(newItem.discount) || 0;
        
        // Total HT (Total Price) = Qty * Unit Price * (1 - Discount/100)
        const total_price = qty * price * (1 - discount / 100);
        
        // Total TTC (Total Amount) = Total HT * (1 + VAT/100)
        const total_amount = total_price * (1 + vat / 100);

        const payload = { 
            ...newItem, 
            invoice_id: props.invoiceId,
            total_price: total_price.toFixed(2),
            total_amount: total_amount.toFixed(2)
        };

        const response = await axios.post(`/wp-json/my-easy-compta/v1/invoices/element-add`, payload, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });

        if (response.data.success) {
            emit('itemAdded');
            closeModal();
        }
    } catch (e) { 
    } finally { 
        loadingBtn.value = false; 
    }
};

// Handle click outside suggestions
onMounted(() => {
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-container')) {
            showSuggestions.value = false;
        }
    });
});
</script>

<style>
.kloxy-input {
    @apply w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-purple-500/10 transition-all outline-none dark:text-white placeholder:text-slate-400;
}
.kloxy-editor-wrapper .ql-toolbar {
    @apply border-none bg-slate-50 dark:bg-slate-950 rounded-t-2xl px-4 py-2 border-b border-slate-200 dark:border-slate-800 !important;
}
.kloxy-editor-wrapper .ql-container {
    @apply border-none bg-slate-50 dark:bg-slate-950 rounded-b-2xl font-sans text-sm !important;
}
.kloxy-editor-wrapper .ql-editor {
    @apply p-4 min-h-[100px] text-slate-700 dark:text-slate-200 !important;
}
</style>
