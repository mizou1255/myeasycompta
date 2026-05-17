<template>
  <div class="sticky top-0 z-40 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm rounded-3xl p-3 px-6 shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800 mb-8 flex flex-col xl:flex-row items-center justify-between gap-4">
      
      <!-- Left Actions -->
       <div class="flex flex-wrap gap-2 items-center justify-center xl:justify-start w-full xl:w-auto">
           <!-- Back -->
           <router-link :to="{ name: 'Invoices' }" class="kloxy-btn-secondary btn-expandable"><ArrowLeft class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.back || 'Retour' }}</span></router-link>

           <div class="h-8 w-px bg-slate-100 dark:bg-slate-800 mx-2 hidden xl:block"></div>

           <!-- Edit Draft -->
           <router-link
              v-if="invoiceInfo.status == 'draft'"
              :to="{ name: 'InvoiceEdit', params: { id: invoiceInfo.id } }"
              class="kloxy-btn-primary btn-expandable"
           ><Pencil class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.edit_invoice || 'Modifier' }}</span></router-link>

           <!-- Validate -->
           <button
              v-if="invoiceInfo.status === 'draft' && !noItems"
              @click="changeInvoiceStatus('unpaid')"
              class="kloxy-btn-success btn-expandable"
           ><Check class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.validate || 'Valider' }}</span></button>
            <button v-else-if="invoiceInfo.status === 'draft' && noItems" disabled class="kloxy-btn-disabled btn-expandable" :title="translations.min_article"><Check class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.validate || 'Valider' }}</span></button>

           <!-- Mark Paid (unpaid ou partial) -->
           <button
              v-if="(invoiceInfo.status === 'unpaid' || invoiceInfo.status === 'partial') && !noItems"
              @click="showConfirmPaidModal = true"
              class="kloxy-btn-success hover:bg-emerald-600 btn-expandable"
           ><CheckCheck class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.mark_as_paid || 'Marquer Payée' }}</span></button>

           <!-- Credit Invoice -->
           <button
              v-if="invoiceInfo.status == 'paid' && invoiceInfo.credit != 1"
              @click="confirmCreditInvoice"
              class="kloxy-btn-warning btn-expandable"
           ><Undo class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.credit_invoice || 'Avoir' }}</span></button>

           <!-- Recurring -->
           <button
              v-if="recurringActive == 1"
              @click="createRecurringInvoice"
              class="kloxy-btn-primary bg-blue-600 hover:bg-blue-700 shadow-blue-600/20 btn-expandable"
           ><RefreshCw class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.create_recurring_invoice || 'Récurrente' }}</span></button>
           <button v-else disabled class="kloxy-btn-disabled opacity-50 cursor-not-allowed btn-expandable" title="Activez le module Factures Récurrentes pour automatiser vos factures"><RefreshCw class="w-4 h-4 icon-no-margin" /><span class="btn-label">Récurrente</span></button>

           <!-- Fiscal Validation -->
           <button
              v-if="invoiceInfo.fiscal_status === 'draft' && invoiceInfo.status !== 'draft'"
              @click="validateFiscal"
              class="kloxy-btn-success bg-cyan-600 hover:bg-cyan-700 shadow-cyan-600/20 btn-expandable"
              :disabled="loadingFiscal"
           >
              <ShieldCheck v-if="!loadingFiscal" class="w-4 h-4 icon-no-margin" />
              <Loader2 v-else class="w-4 h-4 animate-spin icon-no-margin" />
              <span class="btn-label">{{ translations.validate_fiscally || 'Validate fiscally' }}</span>
           </button>

           <!-- Transmit PDP -->
           <template v-if="invoiceInfo.fiscal_status === 'validated'">
             <button
               v-if="pdpAddonActive"
               @click="transmitToPDP"
               class="kloxy-btn-primary bg-fuchsia-600 hover:bg-fuchsia-700 shadow-fuchsia-600/20 btn-expandable"
               :disabled="loadingFiscal"
             >
               <Upload v-if="!loadingFiscal" class="w-4 h-4 icon-no-margin" />
               <Loader2 v-else class="w-4 h-4 animate-spin icon-no-margin" />
               <span class="btn-label">{{ translations.transmit_pdp || 'Transmettre PDP' }}</span>
             </button>
             <button
               v-else
               disabled
               class="kloxy-btn-disabled opacity-50 cursor-not-allowed btn-expandable"
               title="Configurez un PDP dans Réglages › Facturation électronique"
             >
               <Upload class="w-4 h-4 icon-no-margin" />
               <span class="btn-label">{{ translations.transmit_pdp || 'Transmettre PDP' }}</span>
             </button>
           </template>
       </div>

       <!-- Right Actions -->
       <div class="flex flex-wrap gap-2 items-center justify-center xl:justify-end w-full xl:w-auto">
           <!-- Send -->
           <div v-if="emailActive == 1" class="flex gap-2">
                <button
                   v-if="invoiceInfo.status != 'draft'"
                   @click="sendInvoice"
                   class="kloxy-btn-primary bg-indigo-600 hover:bg-indigo-700 shadow-indigo-600/20 btn-expandable"
                ><Send class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ invoiceInfo.sent == 1 ? (translations.resend_invoice || 'Renvoyer') : (translations.send_invoice || 'Envoyer') }}</span></button>
                <button v-else disabled class="kloxy-btn-disabled btn-expandable"><Send class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.send_invoice || 'Envoyer' }}</span></button>

                <!-- Relancer -->
                <button
                   v-if="(invoiceInfo.status === 'unpaid' || invoiceInfo.status === 'partial') && invoiceInfo.sent == 1"
                   @click="sendRemind"
                   class="kloxy-btn-primary bg-amber-500 hover:bg-amber-600 shadow-amber-500/20 btn-expandable"
                ><Bell class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.remind_invoice || 'Relancer' }}</span></button>
           </div>
           <button v-else disabled class="kloxy-btn-disabled opacity-50 cursor-not-allowed btn-expandable" :title="translations.activate_email_addon || 'Activez le module Email pour envoyer vos documents'"><Send class="w-4 h-4 icon-no-margin" /><span class="btn-label">{{ translations.send_invoice || 'Envoyer' }}</span></button>

           <!-- SMS / WhatsApp -->
           <button
             v-if="smsActive == 1"
             @click="showSmsModal = true"
             class="kloxy-btn-secondary bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 btn-expandable"
           ><MessageSquare class="w-4 h-4 icon-no-margin" /><span class="btn-label">SMS / WA</span></button>
           <button v-else disabled class="kloxy-btn-disabled opacity-50 cursor-not-allowed btn-expandable" title="Activez le module SMS pour envoyer des SMS et WhatsApp"><MessageSquare class="w-4 h-4 icon-no-margin" /><span class="btn-label">SMS / WA</span></button>

           <!-- PDF -->
           <button @click="downloadPdf" class="kloxy-btn-secondary btn-expandable"><FileText class="w-4 h-4 icon-no-margin" /><span class="btn-label">PDF</span></button>

           <!-- QR Code -->
           <button v-if="qrCodeActive == 1" @click="showQrCode" class="kloxy-btn-secondary btn-expandable"><QrCode class="w-4 h-4 icon-no-margin" /><span class="btn-label">QR</span></button>
           <button v-else disabled class="kloxy-btn-disabled opacity-50 cursor-not-allowed btn-expandable" title="Activez le module QR Code Stripe pour afficher un QR code de paiement"><QrCode class="w-4 h-4 icon-no-margin" /><span class="btn-label">QR</span></button>
       </div>
    
  </div>  
    <!-- Modals -->
    <remind-modal
        v-if="sendRemindModal"
        :show-modal="sendRemindModal"
        :client="client_detail"
        :invoice-id="invoiceInfo.id"
        :subject="processedRemindSubject"
        :content="processedRemindContent"
        @close="sendRemindModal = false"
    />

    <send-invoice-modal
        v-if="sendInvoiceModal"
        :loading="loadingModal"
        :show-modal="sendInvoiceModal"
        modal-id="modal_send_invoice"
        :client="client_detail"
        :invoice-id="invoiceInfo.id"
        :subject="processedSubject"
        :content="processedContent"
        @close="sendInvoiceModal = false"
    />
    
    <ConfirmAlertPaid
      v-if="showConfirmPaidModal"
      :isVisible="showConfirmPaidModal"
      :showModal="showConfirmPaidModal"
      :title="translations.are_you_sure || 'Êtes-vous sûr ?'"
      :message="translations.mark_as_paid_message || 'Cette facture sera marquée comme payée.'"
      :confirmText="translations.yes_confirm_it || 'Confirmer'"
      :cancelText="translations.cancel || 'Annuler'"
      status="paid"
      @confirm="(method) => { changeInvoiceStatus('paid', method); showConfirmPaidModal = false; }"
      @cancel="showConfirmPaidModal = false"
    />

    <confirm-modal
      :show-modal="showConfirmCreditModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_confirm_it"
      :cancelText="translations.cancel"
      @confirm="addCreditInvoice"
      @cancel="showConfirmCreditModal = false"
    />
    
     <!-- SMS Modal -->
    <SmsModal
      v-if="showSmsModal"
      doc-type="invoice"
      :doc-id="invoiceInfo.id"
      :doc-number="invoiceInfo.invoice_number"
      :client-phone="client_detail?.phone || ''"
      :default-message="smsDefaultMessage"
      @close="showSmsModal = false"
      @sent="showSmsModal = false"
    />

    <!-- QR Code Modal -->
    <div v-if="showQrCodeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
         <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-8 w-full max-w-sm shadow-2xl relative text-center">
             <button @click="showQrCodeModal = false" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><X class="w-6 h-6" /></button>
             <h3 class="text-xl font-black text-slate-900 dark:text-white mb-6">{{ translations.download_qr_code }}</h3>
             <div class="bg-white p-4 rounded-xl shadow-inner mb-6 inline-block">
                 <img :src="qrCodeSrc" class="max-w-full h-auto" />
             </div>
             <button @click="downloadQRCode" class="kloxy-btn-primary w-full justify-center">
                 <Download class="w-4 h-4 mr-2" /> {{ translations.download_qr_code }}
             </button>
         </div>
    </div>
    
</template>

<script setup>
import { ref, reactive, computed, watch, toRefs } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import SendInvoiceModal from "@/components/invoices/Send.vue";
import RemindModal from "@/components/invoices/Remind.vue";
import ConfirmModal from "@/components/ConfirmAlert.vue";
import ConfirmAlertPaid from "@/components/ConfirmAlertPaid.vue";
import SmsModal from "@/components/SmsModal.vue";
import { ArrowLeft, Pencil, Check, CheckCheck, Undo, RefreshCw, Send, Bell, FileText, QrCode, X, Download, ShieldCheck, Loader2, Upload, MessageSquare } from 'lucide-vue-next';

const props = defineProps({
    invoiceInfo: Object,
    currencyDefault: String,
    currencyClient: String,
    emailActive: [Number, Boolean],
    qrCodeActive: [Number, Boolean],
    recurringActive: [Number, Boolean],
    smsActive: [Number, Boolean],
    noItems: Boolean,
    emailSubject: String,
    emailContent: String,
    remindSubject: String,
    remindContent: String,
});

const emit = defineEmits(['refresh', 'show-toast']);
const router = useRouter();

const { invoiceInfo } = toRefs(props);
const client_detail = ref({});
const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});
const pdpAddonActive = computed(() => !!window.myEasyComptaAdmin?.pdpConfigured);
const sendInvoiceModal = ref(false);
const sendRemindModal = ref(false);
const showConfirmPaidModal = ref(false);
const processedRemindSubject = ref('');
const processedRemindContent = ref('');
const showConfirmCreditModal = ref(false);
const showQrCodeModal = ref(false);
const qrCodeSrc = ref("");
const loadingModal = ref(false);
const processedSubject = ref("");
const processedContent = ref("");

const processEmailTemplate = (subject, content) => {
    let sub = subject || "";
    let cont = content || "";
    
    const replacements = {
        '{nom_client}': client_detail.value?.company_name || "",
        '{numero_document}': invoiceInfo.value?.invoice_number || "",
        '{montant_total}': (invoiceInfo.value?.total_amount || "0.00") + " " + (props.currencyClient || "€")
    };

    Object.keys(replacements).forEach(key => {
        sub = sub.split(key).join(replacements[key]);
        cont = cont.split(key).join(replacements[key]);
    });

    processedSubject.value = sub;
    processedContent.value = cont;
};

watch(invoiceInfo, (newVal) => {
    if(newVal && newVal.client_detail) client_detail.value = newVal.client_detail;
}, { immediate: true, deep: true });

const changeInvoiceStatus = async (status, method = null) => {
    try {
        const payload = { status };
        if (method) payload.method = method;
        const res = await axios.post(`/wp-json/my-easy-compta/v1/invoices/${invoiceInfo.value.id}/status`, payload, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        if(res.data.success) emit('refresh');
    } catch (e) {}
};

const confirmCreditInvoice = () => showConfirmCreditModal.value = true;

const addCreditInvoice = async () => {
    showConfirmCreditModal.value = false;
    try {
        const res = await axios.post(`/wp-json/my-easy-compta/v1/invoices/${invoiceInfo.value.id}/credit`, {}, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        if(res.data.success) {
             emit('refresh');
             if(res.data.data?.id) router.push({ name: 'InvoiceViewDetail', params: { id: res.data.data.id } });
        }
} catch (e) {}
};

const createRecurringInvoice = () => {
    // Recurring invoices is a separate WP admin page (not in the core SPA router)
    const adminBase = window.location.href.split('#')[0].split('?')[0];
    window.location.href = adminBase + '?page=my-easy-compta-recurring-invoices&from_invoice_id=' + invoiceInfo.value.id;
};

const sendInvoice = () => {
    processEmailTemplate(props.emailSubject, props.emailContent);
    sendInvoiceModal.value = true;
};

const sendRemind = () => {
    const sub = props.remindSubject || '';
    const cont = props.remindContent || '';
    const replacements = {
        '{nom_client}': client_detail.value?.company_name || '',
        '{numero_document}': invoiceInfo.value?.invoice_number || '',
        '{montant_total}': (invoiceInfo.value?.total_amount || '0.00') + ' ' + (props.currencyClient || '€'),
    };
    const apply = (str) => Object.keys(replacements).reduce((s, k) => s.split(k).join(replacements[k]), str);
    processedRemindSubject.value = apply(sub);
    processedRemindContent.value = apply(cont);
    sendRemindModal.value = true;
};

const downloadPdf = () => {
    // Standardize PDF route to match Quotes (/invoices/pdf/ID)
    window.open(`/wp-json/my-easy-compta/v1/invoices/pdf/${invoiceInfo.value.id}?_wpnonce=${window.myEasyComptaAdmin.nonce}`, "_blank");
};

const showQrCode = async () => {
    try {
        const res = await axios.get(`/wp-json/my-easy-compta/v1/invoices/${invoiceInfo.value.id}/qrcode`, { 
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } 
        });
        if(res.data.success) {
            qrCodeSrc.value = res.data.data.url;
            showQrCodeModal.value = true;
        } else {
            emit('show-toast', res.data.message || "Erreur lors de la génération du QR Code", "error");
        }
    } catch(e) { 
        emit('show-toast', e.response?.data?.message || "Erreur lors de la récupération du QR Code", "error");
    }
};

const showSmsModal = ref(false);
const smsDefaultMessage = computed(() => {
    const client = client_detail.value?.company_name || '';
    const num    = invoiceInfo.value?.invoice_number  || '';
    const amount = (invoiceInfo.value?.total_amount   || '0.00') + ' ' + (props.currencyClient || '€');
    return `Bonjour ${client}, votre facture ${num} d'un montant de ${amount} est disponible.`;
});

const loadingFiscal = ref(false);

const validateFiscal = async () => {
    if (loadingFiscal.value) return;
    loadingFiscal.value = true;
    try {
        const res = await axios.post(`/wp-json/my-easy-compta/v1/invoices/${invoiceInfo.value.id}/validate`, {}, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        if(res.data.success) emit('refresh');
    } catch(e) { 
        emit('show-toast', e.response?.data?.message || "Erreur lors de la validation fiscale", 'error');
    } finally { loadingFiscal.value = false; }
};

const transmitToPDP = async () => {
    if (loadingFiscal.value) return;
    loadingFiscal.value = true;
    try {
        const res = await axios.post(`/wp-json/my-easy-compta/v1/invoices/${invoiceInfo.value.id}/transmit`, {}, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        if(res.data.success) emit('refresh');
    } catch(e) { 
        emit('show-toast', e.response?.data?.message || "Erreur lors de la transmission", 'error');
    } finally { loadingFiscal.value = false; }
};

const downloadQRCode = () => {
    const link = document.createElement("a");
    link.href = qrCodeSrc.value;
    link.download = `qrcode-${invoiceInfo.value.invoice_number}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

defineExpose({ sendRemind });
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