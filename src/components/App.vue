<template>
  <div class="ecwp-app-root">

    <!-- Blocker: permalinks not configured -->
    <div v-if="!permalinksOk" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4">
      <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl max-w-lg w-full p-8 text-center">
        <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-5">
          <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
          </svg>
        </div>
        <h2 class="text-xl font-black text-slate-900 dark:text-white mb-2">Permaliens non configurés</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
          myEasyCompta utilise l'API REST de WordPress qui nécessite des <strong>permaliens "jolis"</strong>.
          Veuillez aller dans <strong>Réglages → Permaliens</strong>, choisir n'importe quelle structure sauf <em>Plain</em>, puis revenir ici.
        </p>
        <a :href="permalinksUrl" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm transition-colors">
          ⚙️ Configurer les permaliens
        </a>
      </div>
    </div>

    <!-- Blocker: setup not complete -->
    <div v-else-if="!setupComplete" class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm p-4">
      <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl max-w-lg w-full p-8 text-center">
        <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-5">
          <svg class="w-8 h-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
          </svg>
        </div>
        <h2 class="text-xl font-black text-slate-900 dark:text-white mb-2">Installation requise</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
          myEasyCompta n'est pas encore configuré.<br>
          Veuillez lancer l'assistant d'installation pour créer les tables et paramétrer votre compte.
        </p>
        <a :href="setupUrl" class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold text-sm transition-all shadow-lg shadow-purple-500/30">
          🚀 Lancer l'assistant de configuration
        </a>
      </div>
    </div>

    <router-view v-else></router-view>
  </div>
</template>

<script setup>
const permalinksOk  = window.myEasyComptaAdmin?.permalinksOk  ?? true;
const permalinksUrl = window.myEasyComptaAdmin?.permalinksUrl ?? '/wp-admin/options-permalink.php';
const setupComplete = window.myEasyComptaAdmin?.setupComplete ?? true;
const setupUrl      = window.myEasyComptaAdmin?.setupUrl      ?? '/wp-admin/index.php?page=my-easy-compta-setup';
</script>
