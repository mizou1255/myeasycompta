<template>
  <div class="space-y-8">

    <!-- Header card -->
    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/30 rounded-[2rem] p-8 border border-indigo-100 dark:border-indigo-900/40">
      <div class="flex items-start gap-5">
        <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30 shrink-0">
          <FileDown class="w-7 h-7 text-white" />
        </div>
        <div class="flex-1">
          <h2 class="text-xl font-black text-slate-900 dark:text-white">Export FEC</h2>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
            Fichier des Écritures Comptables conforme à l'article L47 A du LPF (format tab-séparé, encodage UTF-8 BOM).
          </p>
          <div class="flex flex-wrap gap-2 mt-4">
            <span class="text-[10px] font-black uppercase tracking-widest bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 px-3 py-1.5 rounded-xl">Journal VE – Ventes</span>
            <span class="text-[10px] font-black uppercase tracking-widest bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 px-3 py-1.5 rounded-xl">Journal BQ – Banque</span>
            <span class="text-[10px] font-black uppercase tracking-widest bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 px-3 py-1.5 rounded-xl">Journal AC – Achats</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Config panel -->
    <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-8 border border-slate-100 dark:border-slate-800 space-y-6">
      <h3 class="text-sm font-black uppercase tracking-widest text-slate-400">Paramètres d'export</h3>

      <!-- Quick presets -->
      <div class="flex flex-wrap gap-2">
        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 self-center mr-2">Exercice :</span>
        <button
          v-for="y in fiscalYears"
          :key="y"
          @click="setYear(y)"
          :class="selectedYear === y ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-slate-700'"
          class="px-4 py-2 rounded-xl font-black text-sm transition-all hover:scale-105 active:scale-95"
        >{{ y}}</button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">Date de début</label>
          <input type="date" v-model="dateStart" class="kloxy-input" />
        </div>
        <div class="space-y-2">
          <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">Date de fin</label>
          <input type="date" v-model="dateEnd" class="kloxy-input" />
        </div>
      </div>

      <!-- Preview stats -->
      <div v-if="preview" class="grid grid-cols-3 gap-4 pt-2">
        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl p-5 text-center border border-indigo-100/30">
          <p class="text-2xl font-black text-indigo-700 dark:text-indigo-300">{{ preview.invoices }}</p>
          <p class="text-[10px] font-black uppercase tracking-widest text-indigo-500 mt-1">Factures</p>
        </div>
        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-2xl p-5 text-center border border-emerald-100/30">
          <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ preview.payments }}</p>
          <p class="text-[10px] font-black uppercase tracking-widest text-emerald-500 mt-1">Paiements</p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl p-5 text-center border border-amber-100/30">
          <p class="text-2xl font-black text-amber-700 dark:text-amber-300">{{ preview.expenses }}</p>
          <p class="text-[10px] font-black uppercase tracking-widest text-amber-500 mt-1">Dépenses</p>
        </div>
      </div>
      <div v-if="preview" class="text-center text-sm font-bold text-slate-400">
        {{ preview.total_rows }} lignes au total dans le fichier FEC
      </div>

      <!-- Actions -->
      <div class="flex flex-wrap items-center gap-3 pt-2">
        <button
          @click="loadPreview"
          :disabled="!canExport || loadingPreview"
          class="flex items-center gap-2 px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-indigo-50 dark:hover:bg-slate-700 transition-all disabled:opacity-40 disabled:cursor-not-allowed"
        >
          <span v-if="loadingPreview" class="w-4 h-4 border-2 border-slate-400/30 border-t-slate-500 rounded-full animate-spin"></span>
          <Eye v-else class="w-4 h-4" />
          Aperçu
        </button>

        <a
          :href="downloadUrl"
          :class="!canExport ? 'pointer-events-none opacity-40' : ''"
          class="flex items-center gap-2 px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest bg-indigo-600 text-white hover:scale-105 active:scale-95 transition-all shadow-lg shadow-indigo-500/30"
          download
        >
          <FileDown class="w-4 h-4" />
          Télécharger le FEC
        </a>
      </div>

      <p v-if="!canExport" class="text-xs text-rose-500 font-bold ml-2">
        Sélectionnez une date de début et une date de fin valides.
      </p>
    </div>

    <!-- Info card -->
    <div class="bg-slate-50 dark:bg-slate-900/50 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800">
      <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 flex items-center gap-2">
        <Info class="w-3.5 h-3.5" /> Comptes comptables générés
      </h4>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
        <div class="space-y-2">
          <p class="text-[10px] font-black uppercase text-indigo-500">Ventes (VE)</p>
          <div class="space-y-1 font-mono text-xs text-slate-600 dark:text-slate-400">
            <p>411xxxxxx — Clients (débit TTC)</p>
            <p>706000 — Prestations (crédit HT)</p>
            <p>44571 — TVA collectée (crédit)</p>
          </div>
        </div>
        <div class="space-y-2">
          <p class="text-[10px] font-black uppercase text-emerald-500">Banque (BQ)</p>
          <div class="space-y-1 font-mono text-xs text-slate-600 dark:text-slate-400">
            <p>512000 — Banque (débit)</p>
            <p>411xxxxxx — Clients (crédit)</p>
          </div>
        </div>
        <div class="space-y-2">
          <p class="text-[10px] font-black uppercase text-amber-500">Achats (AC)</p>
          <div class="space-y-1 font-mono text-xs text-slate-600 dark:text-slate-400">
            <p>607000 — Achats (débit)</p>
            <p>401000 — Fournisseurs (crédit)</p>
          </div>
        </div>
      </div>
      <p class="text-xs text-slate-400 mt-4">
        Les comptes 706 peuvent nécessiter un ajustement selon votre plan comptable (701 pour marchandises, 706 pour services). Vérifiez le fichier avec votre comptable.
      </p>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { FileDown, Eye, Info } from 'lucide-vue-next';

const currentYear = new Date().getFullYear();
const fiscalYears = [currentYear, currentYear - 1, currentYear - 2];
const selectedYear = ref(null);

const dateStart = ref('');
const dateEnd   = ref('');
const preview   = ref(null);
const loadingPreview = ref(false);

const nonce = computed(() => window.myEasyComptaAdmin?.nonce || '');

const canExport = computed(() => dateStart.value && dateEnd.value && dateStart.value <= dateEnd.value);

const downloadUrl = computed(() => {
    if (!canExport.value) return '#';
    return `/wp-json/my-easy-compta/v1/fec/generate?date_start=${dateStart.value}&date_end=${dateEnd.value}&_wpnonce=${nonce.value}`;
});

const setYear = (y) => {
    selectedYear.value = y;
    dateStart.value = `${y}-01-01`;
    dateEnd.value   = `${y}-12-31`;
    preview.value   = null;
};

const loadPreview = async () => {
    if (!canExport.value) return;
    loadingPreview.value = true;
    preview.value = null;
    try {
        const res = await fetch(
            `/wp-json/my-easy-compta/v1/fec/preview?date_start=${dateStart.value}&date_end=${dateEnd.value}`,
            { headers: { 'X-WP-Nonce': nonce.value } }
        );
        if (res.ok) preview.value = await res.json();
    } catch {}
    loadingPreview.value = false;
};
</script>

<style scoped>
.kloxy-input {
    @apply w-full bg-slate-50 dark:bg-slate-950 border-none rounded-2xl px-6 py-4 font-bold text-sm focus:ring-4 focus:ring-indigo-500/10 transition-all outline-none dark:text-white;
}
</style>
