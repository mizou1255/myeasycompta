<template>
  <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest inline-flex items-center gap-1.5', badgeConfig.class]">
    <component :is="badgeConfig.icon" class="w-3 h-3" />
    {{ badgeConfig.label }}
    
    <div v-if="rejectionReason" class="group relative flex items-center">
         <AlertTriangle class="w-3 h-3 text-red-500 ml-1 cursor-help" />
         <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-slate-900 text-white text-xs rounded-xl shadow-xl opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50 pointer-events-none">
            {{ rejectionReason }}
            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
         </div>
    </div>
  </span>
</template>

<script setup>
import { computed } from 'vue';
import { FileEdit, CheckCircle2, Send, CheckCheck, XCircle, AlertTriangle, HelpCircle } from 'lucide-vue-next';

const props = defineProps({
    status: { type: String, default: null },
    fiscalStatus: { type: String, default: null }, // Fallback
    rejectionReason: { type: String, default: null }
});

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

const resolvedStatus = computed(() => props.status || props.fiscalStatus);

const badgeConfig = computed(() => {
    switch(resolvedStatus.value) {
        case 'draft':
            return {
                class: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                icon: FileEdit,
                label: translations.value.draft || 'Draft'
            };
        case 'validated':
            return {
                class: 'bg-cyan-50 text-cyan-600 dark:bg-cyan-900/20 dark:text-cyan-400',
                icon: CheckCircle2,
                label: translations.value.validated || 'Validated'
            };
        case 'sent_pdp':
            return {
                class: 'bg-purple-50 text-purple-600 dark:bg-purple-900/20 dark:text-purple-400',
                icon: Send,
                label: translations.value.sent_pdp || 'Sent PDP'
            };
        case 'transmitted':
            return {
                class: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400',
                icon: CheckCheck,
                label: translations.value.transmitted || 'Transmitted DGFiP'
            };
        case 'accepted':
            return {
                class: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400',
                icon: CheckCircle2, // Or CheckDouble
                label: translations.value.accepted || 'Accepted'
            };
        case 'rejected':
            return {
                class: 'bg-rose-50 text-rose-600 dark:bg-rose-900/20 dark:text-rose-400',
                icon: XCircle,
                label: translations.value.rejected || 'Rejected'
            };
        default:
            return {
                class: 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500',
                icon: HelpCircle,
                label: resolvedStatus.value || 'N/A'
            };
    }
});
</script>
