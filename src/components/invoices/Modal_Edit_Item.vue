<template>
  <dialog :id="modalId" class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200">
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-[90vw] max-w-2xl shadow-2xl border border-slate-100 dark:border-slate-800 text-left relative overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white">{{ modalTitle }}</h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl">
                <X class="w-6 h-6" />
            </button>
        </div>

        <div v-if="loading" class="space-y-4">
             <div v-for="n in 3" :key="n" class="h-12 w-full bg-slate-100 dark:bg-slate-800 rounded-2xl animate-pulse"></div>
        </div>

        <form v-else @submit.prevent="submitForm" class="space-y-6">
            <div class="space-y-6">
                <!-- Item Name & Ref -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-1 space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                            {{ translations.reference || 'Référence' }}
                        </label>
                        <input type="text" v-model="editedItem.item_ref" class="kloxy-input" />
                    </div>
                    <div class="col-span-2 space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                            {{ translations.item_name }}
                        </label>
                        <input type="text" v-model="editedItem.item_name" class="kloxy-input" />
                    </div>
                </div>

                <!-- Description (Wysiwyg) -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.item_description }}
                    </label>
                    <div class="kloxy-editor-wrapper">
                         <vue-editor v-model="editedItem.item_description" :editorToolbar="toolbarOptions" />
                    </div>
                </div>

                <!-- Grid Inputs -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                     <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.quantity }}</label>
                        <input type="number" step="any" v-model="editedItem.quantity" class="kloxy-input" />
                     </div>
                     <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.unit_price }}</label>
                        <input type="number" step="any" v-model="editedItem.unit_price" class="kloxy-input" />
                     </div>
                     <div class="space-y-2">
                         <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.vat }} (%)</label>
                         <input type="number" step="any" v-model="editedItem.vat_rate" class="kloxy-input" />
                     </div>
                      <div class="space-y-2">
                         <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">{{ translations.discount }} (%)</label>
                         <input type="number" step="any" v-model="editedItem.discount" class="kloxy-input" />
                     </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
               <button type="button" @click="closeModal" class="px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                   {{ translations.cancel || 'Annuler' }}
               </button>
               <button type="submit" :disabled="loadingBtn" class="bg-purple-600 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                   <span v-if="loadingBtn" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                   <Save class="w-4 h-4" />
                   {{ translations.save }}
               </button>
            </div>
        </form>
      </div>
      <form method="dialog" class="fixed inset-0 z-[-1] cursor-default bg-transparent w-full h-full outline-none" @click="closeModal"></form>
    </dialog>
</template>

<script setup>
import { ref, reactive, computed, watch, toRefs, onMounted } from 'vue';
import { VueEditor } from "vue3-editor";
import { X, Save } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    loading: Boolean,
    showModal: Boolean,
    modalId: String,
    modalTitle: String,
    item: Object,
});

const emit = defineEmits(['close', 'itemEdited']);

const { item, showModal, modalId } = toRefs(props);
const editedItem = ref({ ...props.item });
const loadingBtn = ref(false);

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});
const toolbarOptions = [
    ["bold", "italic", "underline", "strike"],
    [{ list: "ordered" }, { list: "bullet" }],
    ["clean"],
];

watch(item, (newVal) => {
    editedItem.value = { ...newVal };
}, { deep: true, immediate: true });

watch(showModal, (val) => {
    const el = document.getElementById(modalId.value);
    if(el) {
        if(val && !el.open) el.showModal();
        if(!val && el.open) el.close();
    }
}, { immediate: true });

onMounted(() => {
    if(showModal.value) {
        const el = document.getElementById(modalId.value);
        if(el && !el.open) el.showModal();
    }
});

const closeModal = () => {
    emit('close');
};

const submitForm = async () => {
    loadingBtn.value = true;
    try {
        const response = await axios.put(
            `/wp-json/my-easy-compta/v1/invoices/edit-item/${editedItem.value.id}`, 
            editedItem.value,
            { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } }
        );

        if (response.data.success || response.status === 200) {
            emit('itemEdited');
            closeModal();
        }
    } catch (e) {} finally { loadingBtn.value = false; }
};
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