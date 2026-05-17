<template>
  <div class="sticky top-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm rounded-3xl p-3 px-6 shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800 mb-8 flex flex-col xl:flex-row items-center justify-between gap-4">
      
      <!-- Left Actions -->
      <div class="flex flex-wrap gap-2 justify-center xl:justify-start w-full xl:w-auto">
           <!-- Edit -->
          <router-link
             :to="{ name: 'QuoteEdit', params: { id: quoteInfo.id } }"
             class="kloxy-btn-primary btn-expandable"
          ><Pencil class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.edit_quote || 'Modifier'  }}</span></router-link>

          <!-- Validate -->
          <button
             v-if="quoteInfo.status == 'draft' && !noItems"
             @click="changeQuoteStatus('pending')"
             class="kloxy-btn-success btn-expandable"
          ><Check class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.validate_quote || 'Valider' }}</span></button>
           <button v-else-if="quoteInfo.status == 'draft' && noItems" disabled class="kloxy-btn-disabled btn-expandable" :title="translations.min_article"><Check class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.validate_quote || 'Valider' }}</span></button>

          <!-- Accept -->
          <button
             v-if="(quoteInfo.status == 'pending' || quoteInfo.status == 'rejected') && !noItems"
             @click="changeQuoteStatus('approved')"
             class="kloxy-btn-success btn-expandable"
          ><CheckCheck class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.mark_as_accepted || 'Accepter' }}</span></button>

          <!-- Reject -->
          <button
             v-if="(quoteInfo.status == 'pending' || quoteInfo.status == 'approved') && !noItems"
             @click="changeQuoteStatus('rejected')"
             class="kloxy-btn-warning bg-rose-500 hover:bg-rose-600 shadow-rose-500/20 btn-expandable"
          ><XCircle class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.mark_as_rejected || 'Refuser' }}</span></button>

          <!-- Convert -->
          <div v-if="advanceActive == 1 && quoteInfo.converted != 1 && quoteInfo.status == 'approved' && !noItems" class="relative" v-click-outside="() => showConvertDropdown = false">
               <button @click="showConvertDropdown = !showConvertDropdown" class="kloxy-btn-primary bg-indigo-600 hover:bg-indigo-700 shadow-indigo-600/20 btn-expandable">
                   <ArrowRightLeft class="w-4 h-4 icon-no-margin" />
                   <span class="btn-label flex items-center gap-2">
                       {{ translations.convertToInvoice || 'Convertir' }}
                       <ChevronDown class="w-4 h-4" />
                   </span>
               </button>
               <!-- Dropdown -->
               <div v-if="showConvertDropdown" class="absolute left-0 top-full mt-1 w-56 bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-800 p-2 z-20">
                    <button v-if="quoteInfo.advance != 1" @click="showConvertDropdown = false; confirmConvertQuote()" class="w-full text-left px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 text-sm font-bold text-slate-700 dark:text-gray-200 transition-colors">
                        Facture globale
                    </button>
                    <button @click="showConvertDropdown = false; ConvertAdvanceQuote('no_sold')" class="w-full text-left px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 text-sm font-bold text-slate-700 dark:text-gray-200 transition-colors">
                        Facture d'acompte
                    </button>
                    <button v-if="quoteInfo.advance != 0" @click="showConvertDropdown = false; ConvertAdvanceQuote('sold')" class="w-full text-left px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 text-sm font-bold text-slate-700 dark:text-gray-200 transition-colors">
                        Facture du solde
                    </button>
               </div>
          </div>
          
           <!-- Convert Simple -->
          <button
             v-if="advanceActive != 1 && quoteInfo.converted != 1 && quoteInfo.status == 'approved' && !noItems"
             @click="confirmConvertQuote"
             class="kloxy-btn-primary bg-indigo-600 hover:bg-indigo-700 shadow-indigo-600/20 btn-expandable"
          ><ArrowRightLeft class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.convertToInvoice || 'Convertir' }}</span></button>

      </div>

      <!-- Right Actions -->
      <div class="flex flex-wrap gap-2 justify-center xl:justify-end w-full xl:w-auto">
          <!-- Send -->
          <div v-if="emailActive == 1">
               <button
                  v-if="quoteInfo.status != 'draft'"
                  @click="sendQuote"
                  class="kloxy-btn-primary bg-sky-600 hover:bg-sky-700 shadow-sky-600/20 btn-expandable"
               ><Send class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ quoteInfo.sent == 1 ? (translations.resend_quote || 'Renvoyer') : (translations.send_quote || 'Envoyer') }}</span></button>
                <button v-else disabled class="kloxy-btn-disabled btn-expandable" :title="translations.quote_draft_cannot_send"><Send class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.send_quote || 'Envoyer' }}</span></button>
          </div>
          <button v-else disabled class="kloxy-btn-disabled opacity-50 cursor-not-allowed group relative btn-expandable" :title="translations.activate_email_addon || 'Activez le module Email pour envoyer vos documents'"><Send class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.send_quote || 'Envoyer' }}</span></button>

          <!-- SMS / WhatsApp -->
          <button
            v-if="smsActive == 1"
            @click="showSmsModal = true"
            class="kloxy-btn-secondary bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 btn-expandable"
          ><MessageSquare class="w-4 h-4 icon-no-margin" /><span class="btn-label">SMS / WA</span></button>
          <button v-else disabled class="kloxy-btn-disabled opacity-50 cursor-not-allowed btn-expandable" title="Activez le module SMS pour envoyer des SMS et WhatsApp"><MessageSquare class="w-4 h-4 icon-no-margin" /><span class="btn-label">SMS / WA</span></button>

          <!-- PDF -->
          <button @click="exportToPDF" :disabled="loadingPdf" class="kloxy-btn-secondary btn-expandable"><span v-if="loadingPdf" class="w-4 h-4 border-2 border-slate-400 border-t-slate-600 rounded-full animate-spin"></span><FileText v-else class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.exportToPDF || 'PDF' }}</span></button>
      </div>

  </div>
    <!-- Modals -->
    <send-quote-modal
      v-if="sendQuoteModal"
      :loading="loadingModal"
      :show-modal="sendQuoteModal"
      modal-id="modal_send_quote"
      :client="client_detail"
      :quote-id="quoteInfo.id"
      :subject="processedSubject"
      :content="processedContent"
      @close="sendQuoteModal = false"
    />

    <confirm-modal
      :show-modal="showConfirmModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_confirm_it"
      :cancelText="translations.cancel"
      @confirm="changeQuoteStatus(pendingStatusChange)"
      @cancel="showConfirmModal = false"
    />
    
    <!-- Separate modal for conversion confirmation if needed, or reuse confirm modal -->
    <!-- The legacy logic had confirmConvertQuote usage which just set selectedQuote. -->
    
    <confirm-modal
      :show-modal="showConvertModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_confirm_it"
      :cancelText="translations.cancel"
      @confirm="executeConvert"
      @cancel="showConvertModal = false"
    />
    
    <!-- SMS Modal -->
    <SmsModal
      v-if="showSmsModal"
      doc-type="quote"
      :doc-id="quoteInfo.id"
      :doc-number="quoteInfo.quote_number"
      :client-phone="client_detail?.phone || ''"
      :default-message="smsDefaultMessage"
      @close="showSmsModal = false"
      @sent="showSmsModal = false"
    />

    <div v-if="advanceActive == 1">
      <advance-modal
        :is-visible="showAdvanceModal"
        :title="translations.are_you_sure"
        :message="translations.no_turning_back"
        :confirmText="translations.yes_confirm_it"
        :cancelText="translations.cancel"
        :total-amount="quoteInfo.total_amount"
        :currency="currency"
        :quoteId="quoteInfo.id"
        :advance-sold="advanceSold"
        @confirm="handleAdvanceInvoiceConfirm"
        @cancel="showAdvanceModal = false"
      />
    </div>

</template>

<script setup>
import { ref, reactive, computed, toRefs, watch } from 'vue';
import { useRouter } from 'vue-router';
import SendQuoteModal from "@/components/quotes/SendModal.vue";
import ConfirmModal from "@/components/ConfirmAlert.vue";
import AdvanceModal from "@/components/AdvanceAlert.vue";
import SmsModal from "@/components/SmsModal.vue";
import { Pencil, Check, CheckCheck, XCircle, ArrowRightLeft, ChevronDown, Send, FileText, MessageSquare } from 'lucide-vue-next';

const vClickOutside = {
    mounted(el, binding) {
        el._clickOutside = (e) => { if (!el.contains(e.target)) binding.value(e); };
        document.addEventListener('click', el._clickOutside);
    },
    unmounted(el) {
        document.removeEventListener('click', el._clickOutside);
    }
};

const props = defineProps({
    quoteInfo: Object,
    emailActive: Number,
    advanceActive: Number,
    smsActive: Number,
    currency: String,
    noItems: Boolean,
    emailSubject: String,
    emailContent: String,
});

const emit = defineEmits(['show-toast', 'refresh']);
const router = useRouter();

const { quoteInfo } = toRefs(props);
const client_detail = ref({});
const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});
const showSmsModal = ref(false);
const smsDefaultMessage = computed(() => {
    const client = client_detail.value?.company_name || '';
    const num    = quoteInfo.value?.quote_number || '';
    const amount = (quoteInfo.value?.total_amount || '0.00') + ' ' + (props.currency || '€');
    return `Bonjour ${client}, votre devis ${num} d'un montant de ${amount} est disponible.`;
});

const sendQuoteModal = ref(false);
const showConfirmModal = ref(false);
const showConvertModal = ref(false);
const showAdvanceModal = ref(false);
const showConvertDropdown = ref(false);
const loadingModal = ref(false);
const loadingPdf = ref(false);
const pendingStatusChange = ref('');
const advanceSold = ref('no_sold');
const processedSubject = ref("");
const processedContent = ref("");

const processEmailTemplate = (subject, content) => {
    let sub = subject || "";
    let cont = content || "";
    
    const replacements = {
        '{nom_client}': client_detail.value?.company_name || "",
        '{numero_document}': quoteInfo.value?.quote_number || "",
        '{montant_total}': (quoteInfo.value?.total_amount || "0.00") + " " + (props.currency || "€")
    };

    Object.keys(replacements).forEach(key => {
        sub = sub.split(key).join(replacements[key]);
        cont = cont.split(key).join(replacements[key]);
    });

    processedSubject.value = sub;
    processedContent.value = cont;
};

watch(quoteInfo, (newVal) => {
    if (newVal?.client_detail) client_detail.value = newVal.client_detail;
}, { immediate: true, deep: true });

const changeQuoteStatus = async (status) => {
    // Legacy logic didn't use confirm for status change except maybe rejection?
    // Actually the legacy code used simple click.
    // I will keep it simple.
    try {
        const res = await fetch("/wp-json/my-easy-compta/v1/quotes/update-status", {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({ id: quoteInfo.value.id, status }),
        });
        const data = await res.json();
        if(data.success) {
            emit('show-toast', data.message, "success");
            // Mutating prop is bad practice? In Vue 3 we better emit refresh.
             emit('refresh'); // Assuming ViewDetail will re-fetch
        } else {
             emit('show-toast', data.message, "error");
        }
    } catch(e) { emit('show-toast', "Error", "error"); }
};

const confirmConvertQuote = () => {
    showConvertModal.value = true;
};

const executeConvert = () => {
    showConvertModal.value = false;
    convertToInvoice(quoteInfo.value.id);
};

const convertToInvoice = async (quoteId) => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/quotes/convert-quote/${quoteId}`, {
             method: "POST",
             headers: { "Content-Type": "application/json", "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        if(data.success) {
             emit('show-toast', data.message, "success");
             router.push({ name: "InvoiceViewDetail", params: { id: data.id } });
        } else {
             emit('show-toast', data.message, "error");
        }
    } catch(e) { emit('show-toast', "Error", "error"); }
};

const ConvertAdvanceQuote = (solde) => {
    advanceSold.value = solde; // 'sold' ou 'no_sold'
    showAdvanceModal.value = true;
};

const handleAdvanceInvoiceConfirm = (details) => {
    showAdvanceModal.value = false;
    const { type, value, date } = details;
    
    fetch(`/wp-json/my-easy-compta/v1/quotes/convert-advance/${quoteInfo.value.id}`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
        body: JSON.stringify({ advance_type: type, advance_value: value, advance_date: date }),
    })
    .then(r => r.json())
    .then(data => {
         if(data.success) {
             emit('show-toast', data.message, "success");
             router.push({ name: "InvoiceViewDetail", params: { id: data.id } });
         } else {
             emit('show-toast', data.message, "error");
         }
    });
};

const exportToPDF = async () => {
    loadingPdf.value = true;
    try {
        // WordPress REST API validation for this route needs _wpnonce in query params
        const url = `/wp-json/my-easy-compta/v1/quotes/pdf/${quoteInfo.value.id}?_wpnonce=${window.myEasyComptaAdmin.nonce}`;
        window.open(url, '_blank');
    } catch(e) { 
        emit('show-toast', "Erreur lors de la génération du PDF", "error");
    } finally { loadingPdf.value = false; }
};

const sendQuote = () => {
    loadingModal.value = true;
    if(quoteInfo.value.client_id) {
         fetch(`/wp-json/my-easy-compta/v1/clients/details/${quoteInfo.value.client_id}`, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }})
         .then(r => r.json())
         .then(data => {
              client_detail.value = data;
              processEmailTemplate(props.emailSubject, props.emailContent);
              sendQuoteModal.value = true; 
         })
         .finally(() => loadingModal.value = false);
    }
};

</script>

<style scoped>
.btn-expandable {
    @apply flex items-center justify-center h-11 transition-all duration-300 ease-in-out relative overflow-hidden !important;
    min-width: 44px;
    max-width: 44px;
    padding-left: 0 !important;
    padding-right: 0 !important;
    gap: 0 !important;
}

.btn-expandable:hover {
    @apply px-5 !important;
    max-width: 320px;
    gap: 8px !important;
}

.btn-label {
    @apply opacity-0 transition-opacity duration-200 whitespace-nowrap overflow-hidden inline-block;
    max-width: 0;
}

.btn-expandable:hover .btn-label {
    @apply opacity-100;
    max-width: 250px;
}

.icon-no-margin {
    @apply m-0 transition-all duration-300 flex-shrink-0;
}
</style>
