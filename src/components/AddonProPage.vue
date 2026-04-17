<template>
  <MainLayout :title="addon.label" :subtitle="addon.description">

    <div class="max-w-2xl mx-auto py-8 space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">

      <!-- Hero -->
      <div class="relative overflow-hidden rounded-[2rem] p-10 text-white text-center" :class="addon.gradient">
        <div class="absolute inset-0 opacity-10">
          <div class="absolute top-4 right-8 w-32 h-32 rounded-full border-4 border-white/30"></div>
          <div class="absolute -bottom-6 -left-6 w-40 h-40 rounded-full border-4 border-white/20"></div>
        </div>
        <div class="relative z-10">
          <div class="w-20 h-20 bg-white/20 rounded-3xl flex items-center justify-center mx-auto mb-5 shadow-xl backdrop-blur-sm">
            <component :is="addon.icon" class="w-10 h-10 text-white" />
          </div>
          <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm px-3 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-4">
            <Zap class="w-3 h-3" /> Addon PRO
          </div>
          <h1 class="text-3xl font-black leading-tight mb-3">{{ addon.label }}</h1>
          <p class="text-white/80 font-medium text-base leading-relaxed max-w-lg mx-auto">{{ addon.description }}</p>
        </div>
      </div>

      <!-- Features -->
      <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 p-8 shadow-sm">
        <h2 class="text-sm font-black uppercase tracking-widest text-slate-400 mb-6">Fonctionnalités incluses</h2>
        <ul class="space-y-4">
          <li v-for="(feat, i) in addon.features" :key="i" class="flex items-start gap-4">
            <div class="w-7 h-7 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5" :class="addon.badgeBg">
              <CheckCircle2 class="w-4 h-4" :class="addon.badgeText" />
            </div>
            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300 leading-relaxed">{{ feat }}</span>
          </li>
        </ul>
      </div>

      <!-- CTA -->
      <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 p-8 shadow-sm text-center space-y-4">
        <p class="text-sm text-slate-500 dark:text-slate-400 font-medium">
          Cet addon fait partie des modules complémentaires myEasyCompta.
          Activez-le depuis vos réglages si vous disposez d'une licence, ou découvrez nos offres.
        </p>
        <div class="flex items-center justify-center gap-3 flex-wrap">
          <button
            @click="goToSettings"
            class="flex items-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold text-sm shadow-lg shadow-purple-500/30 hover:shadow-purple-500/50 transition-all hover:scale-105"
          >
            <Settings class="w-4 h-4" />
            Activer depuis les réglages
          </button>
          <a
            href="https://myeasycompta.com/#pricing"
            target="_blank"
            rel="noopener"
            class="flex items-center gap-2 px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
          >
            <ExternalLink class="w-4 h-4" />
            Voir les offres
          </a>
        </div>
      </div>

    </div>
  </MainLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  ScrollText, Clock, Truck, Calendar, BarChart2, Globe, RefreshCcw,
  Mail, Shield, Download, FileSpreadsheet, QrCode, Users, ShoppingCart,
  MessageSquare, Zap, CheckCircle2, Settings, ExternalLink,
} from 'lucide-vue-next';
import MainLayout from '@/components/layout/MainLayout.vue';

const route  = useRoute();
const router = useRouter();

const ADDONS = {
  contracts: {
    label: 'Contrats',
    description: 'Créez et gérez des contrats professionnels avec variables dynamiques, génération PDF et suivi de statut.',
    icon: ScrollText,
    gradient: 'bg-gradient-to-br from-purple-600 to-indigo-700',
    badgeBg: 'bg-purple-100 dark:bg-purple-900/30',
    badgeText: 'text-purple-600 dark:text-purple-400',
    features: [
      'Modèles de contrats réutilisables',
      'Variables dynamiques : client, montant, dates, SIRET…',
      'Éditeur WYSIWYG intégré',
      'Génération PDF avec logo et couleurs de l\'entreprise',
      'Suivi du statut : brouillon, envoyé, signé, archivé',
    ],
  },
  timetracking: {
    label: 'Temps & Facturation',
    description: 'Suivez votre temps de travail par projet et générez des factures automatiquement depuis vos entrées.',
    icon: Clock,
    gradient: 'bg-gradient-to-br from-cyan-500 to-blue-600',
    badgeBg: 'bg-cyan-100 dark:bg-cyan-900/30',
    badgeText: 'text-cyan-600 dark:text-cyan-400',
    features: [
      'Chronomètre en temps réel',
      'Saisie manuelle d\'entrées de temps',
      'Taux horaire configurable par projet',
      'Génération de facture en 1 clic depuis les entrées sélectionnées',
      'Filtrage par client et statut (en attente / facturé)',
    ],
  },
  delivery: {
    label: 'Bons de livraison',
    description: 'Créez des bons de livraison professionnels liés à vos factures, avec génération PDF.',
    icon: Truck,
    gradient: 'bg-gradient-to-br from-emerald-500 to-teal-600',
    badgeBg: 'bg-emerald-100 dark:bg-emerald-900/30',
    badgeText: 'text-emerald-600 dark:text-emerald-400',
    features: [
      'Génération de bons de livraison PDF',
      'Lien automatique avec les factures',
      'Numérotation et préfixe personnalisables',
      'Statuts : brouillon, expédié, livré',
      'Template PDF avec logo et couleurs',
    ],
  },
  planning: {
    label: 'Planning & Agenda',
    description: 'Gérez votre agenda professionnel avec rappels automatiques et vues hebdomadaires ou mensuelles.',
    icon: Calendar,
    gradient: 'bg-gradient-to-br from-orange-500 to-amber-500',
    badgeBg: 'bg-orange-100 dark:bg-orange-900/30',
    badgeText: 'text-orange-600 dark:text-orange-400',
    features: [
      'Vue agenda semaine et mois',
      'Création d\'événements liés à un client',
      'Rappels et notifications automatiques',
      'Catégories d\'événements personnalisées',
      'Lien avec devis et factures',
    ],
  },
  stats: {
    label: 'Statistiques',
    description: 'Analysez votre activité avec des graphiques interactifs, des rapports et des indicateurs clés.',
    icon: BarChart2,
    gradient: 'bg-gradient-to-br from-rose-500 to-pink-600',
    badgeBg: 'bg-rose-100 dark:bg-rose-900/30',
    badgeText: 'text-rose-600 dark:text-rose-400',
    features: [
      'Chiffre d\'affaires mensuel et annuel',
      'Graphiques interactifs (barres, courbes)',
      'Analyse des marges et des charges',
      'Top clients et catégories de dépenses',
      'Comparaison avec l\'année précédente',
    ],
  },
  online_quote: {
    label: 'Devis en ligne',
    description: 'Partagez vos devis par lien public et recevez les acceptations de vos clients directement en ligne.',
    icon: Globe,
    gradient: 'bg-gradient-to-br from-violet-500 to-purple-600',
    badgeBg: 'bg-violet-100 dark:bg-violet-900/30',
    badgeText: 'text-violet-600 dark:text-violet-400',
    features: [
      'Lien de partage sécurisé par devis',
      'Page publique avec détail des lignes',
      'Acceptation ou refus en ligne par le client',
      'Signature électronique intégrée',
      'Notification automatique à l\'acceptation',
    ],
  },
  recurring: {
    label: 'Factures récurrentes',
    description: 'Automatisez la génération de vos factures récurrentes mensuelles, trimestrielles ou annuelles.',
    icon: RefreshCcw,
    gradient: 'bg-gradient-to-br from-teal-500 to-cyan-600',
    badgeBg: 'bg-teal-100 dark:bg-teal-900/30',
    badgeText: 'text-teal-600 dark:text-teal-400',
    features: [
      'Génération automatique selon la fréquence choisie',
      'Fréquences : mensuelle, trimestrielle, annuelle',
      'Basée sur un modèle de facture existant',
      'Envoi automatique par email au client',
      'Historique et gestion des cycles',
    ],
  },
  email: {
    label: 'Notifications Email',
    description: 'Envoyez des emails personnalisés à vos clients pour chaque événement (devis, factures, relances).',
    icon: Mail,
    gradient: 'bg-gradient-to-br from-sky-500 to-blue-600',
    badgeBg: 'bg-sky-100 dark:bg-sky-900/30',
    badgeText: 'text-sky-600 dark:text-sky-400',
    features: [
      'Templates d\'emails personnalisables',
      'Envoi automatique à la création de facture',
      'Relances automatiques pour impayés',
      'Variables dynamiques dans les emails',
      'Historique des envois',
    ],
  },
  signature: {
    label: 'Signature électronique',
    description: 'Faites signer vos documents directement depuis le portail client avec une signature électronique.',
    icon: Shield,
    gradient: 'bg-gradient-to-br from-indigo-500 to-violet-600',
    badgeBg: 'bg-indigo-100 dark:bg-indigo-900/30',
    badgeText: 'text-indigo-600 dark:text-indigo-400',
    features: [
      'Signature électronique sur devis et contrats',
      'Interface de signature tactile ou souris',
      'Horodatage et archivage sécurisé',
      'Notification à la signature',
      'Intégration dans le PDF final',
    ],
  },
  backup: {
    label: 'Sauvegarde',
    description: 'Sauvegardez toutes vos données comptables et restaurez-les en cas de besoin.',
    icon: Download,
    gradient: 'bg-gradient-to-br from-slate-600 to-slate-800',
    badgeBg: 'bg-slate-100 dark:bg-slate-800',
    badgeText: 'text-slate-600 dark:text-slate-400',
    features: [
      'Sauvegarde complète de toutes les données',
      'Export en fichier ZIP',
      'Restauration en 1 clic',
      'Historique des sauvegardes',
      'Planification automatique',
    ],
  },
  export: {
    label: 'Export CSV / Excel',
    description: 'Exportez vos factures, devis et dépenses en CSV ou Excel pour votre comptable.',
    icon: FileSpreadsheet,
    gradient: 'bg-gradient-to-br from-green-600 to-emerald-700',
    badgeBg: 'bg-green-100 dark:bg-green-900/30',
    badgeText: 'text-green-600 dark:text-green-400',
    features: [
      'Export factures, devis, dépenses, paiements',
      'Format CSV et Excel (.xlsx)',
      'Filtrage par période et statut',
      'Compatible FEC et logiciels comptables',
      'Export automatisé par email',
    ],
  },
};

const addon = computed(() => {
  const slug = route.params.slug;
  return ADDONS[slug] || {
    label: 'Addon indisponible',
    description: 'Cet addon n\'existe pas.',
    icon: Zap,
    gradient: 'bg-gradient-to-br from-slate-500 to-slate-700',
    badgeBg: 'bg-slate-100',
    badgeText: 'text-slate-600',
    features: [],
  };
});

function goToSettings() {
  router.push('/settings');
}
</script>
