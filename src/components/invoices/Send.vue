<template>
  <dialog :id="modalId" class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200">
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-[90vw] max-w-2xl shadow-2xl border border-slate-100 dark:border-slate-800 text-left relative overflow-hidden">
        
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                <Send class="w-6 h-6 text-purple-600" />
                {{ translations.send_invoice }}
            </h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl">
                <X class="w-6 h-6" />
            </button>
        </div>

        <div v-if="loading" class="space-y-4">
             <div v-for="n in 3" :key="n" class="h-12 w-full bg-slate-100 dark:bg-slate-800 rounded-2xl animate-pulse"></div>
        </div>

        <form v-else @submit.prevent="submitForm" class="space-y-6">
            <div class="space-y-6">
                <!-- Client Email -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.client }}
                    </label>
                    <input type="text" :value="client?.email" disabled class="kloxy-input opacity-60 cursor-not-allowed" />
                </div>

                <!-- Subject -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">
                        {{ translations.email_subject }}
                    </label>
                     <input type="text" v-model="formData.subject" class="kloxy-input" />
                </div>

                <!-- Message (Wysiwyg) -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between ml-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-400">
                            {{ translations.email_content }}
                        </label>
                        <button type="button" @click="togglePreview" :disabled="previewLoading" class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-purple-600 transition-colors disabled:opacity-50">
                            <span v-if="previewLoading" class="w-3 h-3 border-2 border-slate-300 border-t-purple-500 rounded-full animate-spin"></span>
                            <Eye v-else-if="!showPreview" class="w-3.5 h-3.5" />
                            <EyeOff v-else class="w-3.5 h-3.5" />
                            {{ showPreview ? 'Masquer' : 'Prévisualiser' }}
                        </button>
                    </div>
                    <div v-if="showPreview" class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 p-4 text-sm text-slate-700 dark:text-slate-300 overflow-y-auto max-h-48" v-html="previewHtml"></div>
                    <div v-else class="kloxy-editor-wrapper">
                         <vue-editor v-model="formData.message" :editorToolbar="toolbarOptions" />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
               <button type="button" @click="closeModal" class="px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                   {{ translations.cancel || 'Annuler' }}
               </button>
               <button type="submit" :disabled="loadingBtn" class="bg-purple-600 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                   <span v-if="loadingBtn" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                   <Send class="w-4 h-4" />
                   {{ translations.send }}
               </button>
            </div>
        </form>
      </div>
      <form method="dialog" class="fixed inset-0 z-[-1] cursor-default bg-transparent w-full h-full outline-none" @click="closeModal"></form>
    </dialog>
</template>

<script setup>
import { ref, reactive, computed, watch, toRefs, onMounted, nextTick } from 'vue';
import { VueEditor } from "vue3-editor";
import { X, Send, Eye, EyeOff } from 'lucide-vue-next';

const props = defineProps({
    loading: Boolean,
    showModal: Boolean,
    modalId: String,
    client: Object,
    invoiceId: [Number, String],
    subject: String,
    content: String,
});

const emit = defineEmits(['close', 'success']);

const { showModal, modalId, client, subject, content } = toRefs(props);
const loadingBtn = ref(false);
const previewHtml = ref('');
const showPreview = ref(false);
const previewLoading = ref(false);
const formData = reactive({
    subject: "",
    message: ""
});

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});
const toolbarOptions = [
    ["bold", "italic", "underline", "strike"],
    [{ list: "ordered" }, { list: "bullet" }],
    ["clean"],
];

const openModal = () => {
    nextTick(() => {
        const el = document.getElementById(modalId.value);
        if(el && !el.open) {
            el.showModal();
            // Sync data
            formData.subject = subject.value || "";
            formData.message = content.value || "";
        }
    });
};

onMounted(() => {
    if(showModal.value) openModal();
});

watch(showModal, (val) => {
    const el = document.getElementById(modalId.value);
    if(el) {
        if(val && !el.open) {
            openModal();
        }
        if(!val && el.open) el.close();
    }
});

watch([subject, content], ([newSub, newCont]) => {
     formData.subject = newSub || "";
     formData.message = newCont || "";
}, { immediate: true });


const closeModal = () => {
    showPreview.value = false;
    previewHtml.value = '';
    emit('close');
};

const togglePreview = async () => {
    if (showPreview.value) {
        showPreview.value = false;
        return;
    }
    previewLoading.value = true;
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/emails/preview', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({ id: props.invoiceId, type: 'invoice', email_message: formData.message }),
        });
        if (res.ok) {
            const data = await res.json();
            previewHtml.value = data.html;
            showPreview.value = true;
        }
    } catch (e) {
    } finally {
        previewLoading.value = false;
    }
};

const submitForm = async () => {
    loadingBtn.value = true;
    try {
        const response = await fetch(`/wp-json/my-easy-compta/v1/emails/send-email`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({
                type: "invoice",
                id: props.invoiceId,
                client_email: client.value?.email,
                email_subject: formData.subject,
                email_message: formData.message,
            }),
        });

        const data = await response.json();
        if (response.ok) {
            emit('success', data.message);
            closeModal();
        } else {
             // Ideally emit error or show toast. Using alert for now or reliance on parent to show toast?
             // Since I can't inject global toast easily without setup, I'll rely on emitting success.
             // But for error? I should emit 'error'.
             emit('error', data.message);
        }
    } catch (e) {
        emit('error', "Error sending email");
    } finally { loadingBtn.value = false; }
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