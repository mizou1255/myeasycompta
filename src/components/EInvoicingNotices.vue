<template>
  <div class="space-y-4 mb-8">
    <!-- Notice factures non transmises -->
    <div 
      v-if="untransmittedCount > 0 && !dismissedNotices.untransmitted"
      class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 rounded-3xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative overflow-hidden"
    >
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-800/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                <AlertTriangle class="w-6 h-6" />
            </div>
            <div>
               <h3 class="font-black text-amber-900 dark:text-amber-100 mb-1">Facturation Électronique</h3>
               <p class="text-sm font-medium text-amber-700 dark:text-amber-300">
                  {{ untransmittedCount }} {{ untransmittedCount > 1 ? 'factures non transmises' : 'facture non transmise' }} 
                  fiscalement depuis plus de 7 jours
               </p>
            </div>
        </div>

        <div class="flex items-center gap-3 relative z-10">
            <router-link :to="{ name: 'InvoiceList' }" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-black text-xs uppercase tracking-widest transition-colors shadow-lg shadow-amber-500/20 flex items-center gap-2">
                <Eye class="w-3 h-3" />
                Voir les factures
            </router-link>
            <button @click="dismissNotice('untransmitted')" class="p-2 text-amber-600 hover:bg-amber-100 dark:hover:bg-amber-800/30 rounded-full transition-colors">
                <X class="w-5 h-5" />
            </button>
        </div>
    </div>

    <!-- Notice positionnement juridique -->
    <div 
      v-if="showLegalNotice && !dismissedNotices.legal"
      class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700/50 rounded-3xl p-6 relative overflow-hidden"
    >
        <div class="flex items-start gap-4 reltive z-10">
            <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-800/40 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                <Info class="w-6 h-6" />
            </div>
            <div class="flex-1">
                 <div class="flex justify-between items-start">
                     <h3 class="font-black text-blue-900 dark:text-blue-100 mb-2">Positionnement Juridique - Facturation Électronique</h3>
                     <button @click="dismissNotice('legal')" class="p-2 text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-800/30 rounded-full transition-colors -mt-2 -mr-2">
                        <X class="w-5 h-5" />
                    </button>
                 </div>
                 <p class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-4">
                    MyEasyCompta est un <strong>Opérateur de Dématérialisation (OD)</strong> conforme à la réglementation française sur la facturation électronique.
                 </p>
                 <div class="bg-white/50 dark:bg-black/20 p-4 rounded-2xl mb-4 border border-blue-100 dark:border-blue-800/30">
                     <p class="text-sm text-blue-900 dark:text-blue-100">
                        <strong class="text-orange-500">Important :</strong>
                         MyEasyCompta n'est <strong>PAS</strong> une Plateforme Agréée (PDP). La transmission fiscale vers la DGFiP se fait via une PDP externe agréée que vous configurez. La conformité légale est portée par la PDP choisie, pas par MyEasyCompta.
                     </p>
                 </div>
                 <a href="https://www.impots.gouv.fr/facturation-electronique" target="_blank" class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                     <ExternalLink class="w-3 h-3" /> En savoir plus
                 </a>
            </div>
        </div>
    </div>

    <!-- Notice configuration PDP incomplète -->
    <div 
      v-if="pdpConfigIncomplete && !dismissedNotices.pdpConfig"
      class="bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-700/50 rounded-3xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative overflow-hidden"
    >
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-rose-100 dark:bg-rose-800/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <AlertCircle class="w-6 h-6" />
            </div>
            <div>
               <h3 class="font-black text-rose-900 dark:text-rose-100 mb-1">Facturation Électronique</h3>
               <p class="text-sm font-medium text-rose-700 dark:text-rose-300">
                  Configuration PDP incomplète. Veuillez configurer une Plateforme Agréée dans les réglages.
               </p>
            </div>
        </div>

        <div class="flex items-center gap-3 relative z-10">
            <a href="/wp-admin/admin.php?page=my-easy-compta#/settings?tab=17" class="px-5 py-2.5 bg-rose-500 hover:bg-rose-600 text-white rounded-xl font-black text-xs uppercase tracking-widest transition-colors shadow-lg shadow-rose-500/20 flex items-center gap-2">
                <Settings class="w-3 h-3" />
                Configurer
            </a>
            <button @click="dismissNotice('pdpConfig')" class="p-2 text-rose-600 hover:bg-rose-100 dark:hover:bg-rose-800/30 rounded-full transition-colors">
                <X class="w-5 h-5" />
            </button>
        </div>
    </div>

    <!-- Notice données entreprise manquantes -->
    <div 
      v-if="companyDataIncomplete && !dismissedNotices.companyData"
      class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700/50 rounded-3xl p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative overflow-hidden"
    >
        <div class="flex items-center gap-4 relative z-10">
            <div class="w-12 h-12 rounded-2xl bg-orange-100 dark:bg-orange-800/40 text-orange-600 dark:text-orange-400 flex items-center justify-center shrink-0">
                <Building class="w-6 h-6" />
            </div>
            <div>
               <h3 class="font-black text-orange-900 dark:text-orange-100 mb-1">Facturation Électronique</h3>
               <p class="text-sm font-medium text-orange-700 dark:text-orange-300">
                  Données entreprise incomplètes (SIREN ou adresse légale manquants).
               </p>
            </div>
        </div>

        <div class="flex items-center gap-3 relative z-10">
            <a href="/wp-admin/admin.php?page=my-easy-compta#/settings?tab=17" class="px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-xl font-black text-xs uppercase tracking-widest transition-colors shadow-lg shadow-orange-500/20 flex items-center gap-2">
                <Pencil class="w-3 h-3" />
                Compléter
            </a>
            <button @click="dismissNotice('companyData')" class="p-2 text-orange-600 hover:bg-orange-100 dark:hover:bg-orange-800/30 rounded-full transition-colors">
                <X class="w-5 h-5" />
            </button>
        </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import { AlertTriangle, AlertCircle, Info, Building, Eye, X, ExternalLink, Settings, Pencil } from 'lucide-vue-next';

const props = defineProps({
    untransmittedCount: { type: Number, default: 0 },
    showLegalNotice: { type: Boolean, default: false },
    pdpConfigIncomplete: { type: Boolean, default: false },
    companyDataIncomplete: { type: Boolean, default: false }
});

const dismissedNotices = reactive({
    untransmitted: false,
    legal: false,
    pdpConfig: false,
    companyData: false
});

onMounted(() => {
    const saved = localStorage.getItem('ecwp_dismissed_notices');
    if(saved) {
        try {
            Object.assign(dismissedNotices, JSON.parse(saved));
    } catch (e) {}
    }
});

const dismissNotice = (key) => {
    dismissedNotices[key] = true;
    localStorage.setItem('ecwp_dismissed_notices', JSON.stringify(dismissedNotices));
};
</script>
