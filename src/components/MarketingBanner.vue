<template>
  <!-- Utilisateur avec licence → rien -->
  <template v-if="licenseValid"></template>

  <!-- Utilisateur gratuit → bannière -->
  <div v-else class="flex flex-col h-full font-sans overflow-hidden">
    <div class="flex-1 m-4 rounded-[2rem] overflow-hidden relative shadow-2xl">

      <!-- ── Fond animé ─────────────────────────────── -->
      <div class="absolute inset-0 bg-[#08061a]">
        <div class="absolute inset-0 opacity-50">
          <div class="absolute top-[-30%] left-[-20%] w-[160%] h-[160%] bg-[radial-gradient(ellipse_at_35%_40%,#7c3aed,transparent_52%)] animate-drift-1"></div>
          <div class="absolute top-[-10%] right-[-30%] w-[150%] h-[150%] bg-[radial-gradient(ellipse_at_68%_28%,#4338ca,transparent_52%)] animate-drift-2"></div>
          <div class="absolute bottom-[-25%] left-[5%] w-[130%] h-[130%] bg-[radial-gradient(ellipse_at_42%_88%,#be185d,transparent_48%)] animate-drift-3"></div>
        </div>
        <!-- Grid de points subtil -->
        <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 28px 28px;"></div>
        <!-- Vignette bord -->
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_50%_50%,transparent_40%,#08061a_100%)]"></div>
      </div>

      <!-- ── Contenu ─────────────────────────────────── -->
      <div class="relative z-10 flex flex-col h-full p-5">

        <!-- En-tête -->
        <div class="flex items-center justify-between mb-5">
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 border border-white/10 backdrop-blur-md">
            <Sparkles class="w-3 h-3 text-amber-400" />
            <span class="text-[10px] font-black uppercase tracking-widest text-white/90">Passez Pro</span>
          </div>
          <!-- Compteur -->
          <div class="flex items-center gap-1 text-white/30 text-[11px] font-black tabular-nums">
            <span class="text-white/60">{{ currentIndex + 1 }}</span>
            <span>/</span>
            <span>{{ slides.length }}</span>
          </div>
        </div>

        <!-- Accroche -->
        <div class="mb-5">
          <h2 class="text-[1.55rem] font-black text-white leading-[1.15] mb-2">
            Allez plus loin<br>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-pink-300 to-violet-300">
              avec myEasyCompta
            </span>
          </h2>
          <p class="text-white/45 text-[11px] font-semibold">{{ slides.length }} modules pour automatiser votre activité</p>
        </div>

        <!-- Slide addon -->
        <div class="flex-1 mb-5">
          <Transition
            mode="out-in"
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 scale-[0.97] translate-y-2"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-[0.97] -translate-y-2"
          >
            <div :key="currentIndex" class="space-y-3">

              <!-- Carte addon -->
              <div class="relative rounded-2xl p-4 overflow-hidden border border-white/10 backdrop-blur-sm bg-white/[0.05]">
                <!-- Glow couleur de l'addon -->
                <div class="absolute -top-8 -left-8 w-32 h-32 rounded-full blur-2xl opacity-25 transition-colors duration-700"
                     :style="{ background: currentAddon.color }"></div>

                <div class="relative flex items-start gap-3">
                  <!-- Icône avec glow -->
                  <div class="relative shrink-0">
                    <div class="absolute inset-0 rounded-xl blur-md opacity-50"
                         :style="{ background: currentAddon.color }"></div>
                    <div class="relative w-11 h-11 rounded-xl flex items-center justify-center border border-white/10"
                         :style="{ background: `linear-gradient(135deg, ${currentAddon.color}44 0%, ${currentAddon.color}11 100%)` }">
                      <component :is="currentAddon.icon" class="w-5 h-5 text-white" />
                    </div>
                  </div>

                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                      <span class="text-white font-black text-[15px] leading-none">{{ currentAddon.title }}</span>
                      <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full border"
                            :style="{ color: currentAddon.color, borderColor: currentAddon.color + '40', background: currentAddon.color + '18' }">
                        {{ currentAddon.tag }}
                      </span>
                    </div>
                    <p class="text-white/55 text-[11px] font-medium leading-relaxed">{{ currentAddon.description }}</p>
                  </div>
                </div>
              </div>

              <!-- Features en pills -->
              <div class="flex flex-wrap gap-1.5">
                <span
                  v-for="f in currentAddon.features"
                  :key="f"
                  class="flex items-center gap-1.5 text-[10px] font-bold text-white/65 bg-white/[0.06] border border-white/10 rounded-full px-2.5 py-1 backdrop-blur-sm"
                >
                  <span class="w-1.5 h-1.5 rounded-full shrink-0" :style="{ background: currentAddon.color }"></span>
                  {{ f }}
                </span>
              </div>

            </div>
          </Transition>
        </div>

        <!-- Navigation : barre + prev/next -->
        <div class="mb-4 space-y-2.5">
          <!-- Barre de progression -->
          <div class="h-[2px] bg-white/10 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-500 ease-out"
              :style="{ width: `${((currentIndex + 1) / slides.length) * 100}%`, background: `linear-gradient(90deg, ${currentAddon.color}, #a78bfa)` }"
            ></div>
          </div>
          <!-- Prev / Next -->
          <div class="flex items-center justify-between">
            <button @click="prev" class="w-7 h-7 rounded-lg flex items-center justify-center bg-white/5 border border-white/10 text-white/40 hover:text-white/80 hover:bg-white/10 transition-all duration-200">
              <ChevronLeft class="w-4 h-4" />
            </button>
            <!-- 5 mini-dots autour du curseur -->
            <div class="flex items-center gap-1">
              <button
                v-for="i in dotRange"
                :key="i"
                @click="goTo(i)"
                class="rounded-full transition-all duration-300"
                :class="i === currentIndex
                  ? 'w-5 h-1.5 bg-white'
                  : 'w-1.5 h-1.5 bg-white/20 hover:bg-white/40'"
              ></button>
            </div>
            <button @click="next" class="w-7 h-7 rounded-lg flex items-center justify-center bg-white/5 border border-white/10 text-white/40 hover:text-white/80 hover:bg-white/10 transition-all duration-200">
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- CTA -->
        <a
          href="https://myeasycompta.com/"
          target="_blank"
          rel="noreferrer noopener"
          class="group relative block w-full py-3.5 rounded-2xl text-center text-[12px] font-black uppercase tracking-widest text-slate-900 overflow-hidden shadow-xl hover:shadow-2xl hover:scale-[1.02] active:scale-[0.98] transition-all duration-300"
          style="background: linear-gradient(135deg, #fcd34d 0%, #fbbf24 40%, #f59e0b 100%)"
        >
          <!-- Shimmer effect -->
          <span class="absolute inset-0 -skew-x-12 translate-x-[-150%] group-hover:translate-x-[200%] transition-transform duration-700 ease-in-out"
                style="background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent); width: 60%;"></span>
          <span class="relative flex items-center justify-center gap-2">
            Découvrir les offres
            <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-200" />
          </span>
        </a>

        <!-- Social proof -->
        <p class="text-center text-white/25 text-[10px] font-bold mt-3">
          +500 freelances & PME font confiance à myEasyCompta
        </p>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import {
  ArrowRight, Sparkles, ChevronLeft, ChevronRight,
  Calendar, Mail, CreditCard, BarChart2, PenTool,
  RefreshCw, Shield, UserCheck, ShoppingCart, ShoppingBag,
  Download, QrCode, Building2, DollarSign,
  ScrollText, Clock, Truck, Globe, MessageSquare,
} from 'lucide-vue-next';

const licenseValid  = computed(() => {
  if (localStorage.getItem('ecwp_preview_free') === '1') return false;
  return !!window.myEasyComptaAdmin?.licenseValid;
});

const slides = [
  {
    title: 'Paiement en ligne',
    description: 'Proposez le paiement par carte bancaire sur chaque facture et encaissez plus vite.',
    icon: CreditCard, tag: 'Stripe', color: '#10b981',
    features: ['Lien de paiement sécurisé', 'Mise à jour auto du statut', 'Zéro installation'],
  },
  {
    title: 'Email',
    description: 'Envoyez vos devis et factures par email avec des modèles personnalisables.',
    icon: Mail, tag: 'Communication', color: '#6366f1',
    features: ['Modèles personnalisables', 'Envoi direct depuis l\'app', 'Logs d\'emails'],
  },
  {
    title: 'Signature Électronique',
    description: 'Faites signer vos devis en ligne, avec valeur légale, sans impression ni scan.',
    icon: PenTool, tag: 'Légal', color: '#f59e0b',
    features: ['Valeur légale en France', 'Signature à distance', 'Archivage sécurisé'],
  },
  {
    title: 'Statistiques',
    description: 'Analysez votre chiffre d\'affaires, vos marges et vos charges en un coup d\'œil.',
    icon: BarChart2, tag: 'Analytics', color: '#8b5cf6',
    features: ['CA, bénéfices, charges', 'Graphiques interactifs', 'Rapports détaillés'],
  },
  {
    title: 'Factures récurrentes',
    description: 'Automatisez la création de vos factures répétitives (abonnements, loyers…).',
    icon: RefreshCw, tag: 'Automatisation', color: '#06b6d4',
    features: ['Mensuel, trimestriel…', 'Génération automatique', 'Pause & reprise'],
  },
  {
    title: 'Acomptes',
    description: 'Générez des factures d\'acompte et de solde depuis vos devis validés.',
    icon: DollarSign, tag: 'Facturation', color: '#f97316',
    features: ['% ou montant fixe', 'Calcul auto du solde', 'Lié au devis'],
  },
  {
    title: 'Temps & Facturation',
    description: 'Suivez le temps passé sur vos projets et facturez automatiquement à l\'heure.',
    icon: Clock, tag: 'Productivité', color: '#ec4899',
    features: ['Chronomètre intégré', 'Conversion en facture', 'Rapports par projet'],
  },
  {
    title: 'Planning',
    description: 'Gérez votre agenda et vos rendez-vous clients directement dans myEasyCompta.',
    icon: Calendar, tag: 'Organisation', color: '#3b82f6',
    features: ['Vue agenda & liste', 'Rappels automatiques', 'Catégories personnalisées'],
  },
  {
    title: 'Devis en ligne',
    description: 'Partagez vos devis en ligne et laissez vos clients les accepter ou refuser.',
    icon: Globe, tag: 'Client', color: '#14b8a6',
    features: ['Lien de partage sécurisé', 'Acceptation / refus en ligne', 'Notification instantanée'],
  },
  {
    title: 'SMS & WhatsApp',
    description: 'Envoyez vos factures et relances par SMS ou WhatsApp directement depuis l\'app.',
    icon: MessageSquare, tag: 'Communication', color: '#a3e635',
    features: ['SMS & WhatsApp', 'Relances automatiques', 'Compatible Twilio & OVH'],
  },
  {
    title: 'Contrats',
    description: 'Créez et gérez vos contrats clients directement depuis myEasyCompta.',
    icon: ScrollText, tag: 'Juridique', color: '#c084fc',
    features: ['Modèles de contrats', 'Signature électronique', 'Archivage sécurisé'],
  },
  {
    title: 'Compte Client',
    description: 'Offrez à vos clients un espace personnel pour consulter leurs factures et devis.',
    icon: UserCheck, tag: 'Portail client', color: '#fb923c',
    features: ['Espace client dédié', 'Consultation devis & factures', 'Paiement en ligne'],
  },
  {
    title: 'Export',
    description: 'Exportez vos données en CSV ou Excel pour votre comptable ou vos archivages.',
    icon: Download, tag: 'Productivité', color: '#34d399',
    features: ['CSV & Excel', 'Clients, factures, devis', 'Statistiques exportables'],
  },
  {
    title: 'Sauvegarde',
    description: 'Protégez toutes vos données comptables avec des sauvegardes complètes.',
    icon: Shield, tag: 'Sécurité', color: '#60a5fa',
    features: ['Sauvegarde complète', 'Restauration en 1 clic', 'Téléchargement local'],
  },
  {
    title: 'Bons de livraison',
    description: 'Générez des bons de livraison depuis vos factures en quelques secondes.',
    icon: Truck, tag: 'Logistique', color: '#fbbf24',
    features: ['Généré depuis la facture', 'PDF personnalisable', 'Suivi des livraisons'],
  },
  {
    title: 'QR Code Stripe',
    description: 'Ajoutez un QR code de paiement Stripe sur vos factures PDF.',
    icon: QrCode, tag: 'Stripe', color: '#4ade80',
    features: ['QR code dynamique', 'Paiement instantané', 'Intégré au PDF'],
  },
  {
    title: 'SIRET / SIREN',
    description: 'Retrouvez les informations d\'un client via son SIRET en un clic.',
    icon: Building2, tag: 'France', color: '#f472b6',
    features: ['API gouvernementale', 'Remplissage auto', 'Données officielles'],
  },
  {
    title: 'WooCommerce',
    description: 'Générez automatiquement des factures pour chaque commande WooCommerce.',
    icon: ShoppingCart, tag: 'E-commerce', color: '#a78bfa',
    features: ['Facturation automatique', 'Sync des commandes', 'Gestion clients WC'],
  },
  {
    title: 'SureCart',
    description: 'Intégrez myEasyCompta à SureCart pour facturer automatiquement vos ventes.',
    icon: ShoppingBag, tag: 'E-commerce', color: '#38bdf8',
    features: ['Factures automatiques', 'Synchronisation des ordres', 'Conformité comptable'],
  },
];

const currentIndex = ref(0);
const currentAddon = computed(() => slides[currentIndex.value]);

// 5 dots autour du curseur courant
const dotRange = computed(() => {
  const total = slides.length;
  const cur = currentIndex.value;
  const half = 2;
  let start = Math.max(0, cur - half);
  let end = Math.min(total - 1, start + 4);
  start = Math.max(0, end - 4);
  const result = [];
  for (let i = start; i <= end; i++) result.push(i);
  return result;
});

let timer = null;
const goTo = (i) => { currentIndex.value = i; resetTimer(); };
const next = () => { currentIndex.value = (currentIndex.value + 1) % slides.length; resetTimer(); };
const prev = () => { currentIndex.value = (currentIndex.value - 1 + slides.length) % slides.length; resetTimer(); };
const resetTimer = () => {
  clearInterval(timer);
  timer = setInterval(next, 4500);
};

onMounted(resetTimer);
onUnmounted(() => clearInterval(timer));
</script>

<style scoped>
@keyframes drift-1 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33%       { transform: translate(5%, 7%) scale(1.06); }
  66%       { transform: translate(-4%, 3%) scale(0.96); }
}
@keyframes drift-2 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33%       { transform: translate(-6%, 4%) scale(1.05); }
  66%       { transform: translate(4%, -5%) scale(0.97); }
}
@keyframes drift-3 {
  0%, 100% { transform: translate(0, 0) scale(1); }
  50%       { transform: translate(5%, -6%) scale(1.07); }
}
.animate-drift-1 { animation: drift-1 20s ease-in-out infinite; }
.animate-drift-2 { animation: drift-2 26s ease-in-out infinite; }
.animate-drift-3 { animation: drift-3 30s ease-in-out infinite; }
</style>
