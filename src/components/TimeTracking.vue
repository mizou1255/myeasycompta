<template>
  <MainLayout title="Temps & Facturation" subtitle="Chronomètrez vos tâches et générez des factures à partir de vos heures">

    <!-- Toast -->
    <Teleport to="body">
      <div v-if="toast.visible" class="fixed bottom-8 right-8 z-[9999] animate-in fade-in slide-in-from-bottom-8 duration-300">
        <div :class="['flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md', toast.type === 'success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
          <span class="font-bold text-sm">{{ toast.message }}</span>
          <button @click="toast.visible = false" class="ml-2 hover:bg-white/20 p-1 rounded-full transition-colors"><X class="w-4 h-4" /></button>
        </div>
      </div>
    </Teleport>

    <div class="flex flex-col gap-6">

      <!-- Live Timer Card -->
      <div class="bg-gradient-to-br from-purple-600 to-indigo-600 rounded-[2.5rem] p-8 text-white shadow-xl shadow-purple-500/30">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-purple-200 text-xs font-black uppercase tracking-widest mb-2">Chronomètre en cours</p>
            <div class="text-6xl font-black tracking-tighter tabular-nums">{{ timerDisplay }}</div>
            <p v-if="activeTimer" class="text-purple-200 text-sm mt-3 font-medium">
              {{ activeTimer.project_name || 'Sans projet' }} — {{ activeTimer.client_name || 'Sans client' }}
            </p>
            <p v-else class="text-purple-300 text-sm mt-3">Aucun chronomètre actif</p>
          </div>
          <div class="flex flex-col gap-3">
            <button v-if="!activeTimer" @click="openTimerModal()"
              class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-lg hover:scale-105 transition-transform">
              <Play class="w-7 h-7 text-purple-600 fill-purple-600" />
            </button>
            <button v-else @click="stopTimer()"
              class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-lg hover:scale-105 transition-transform">
              <Square class="w-7 h-7 text-red-500 fill-red-500" />
            </button>
          </div>
        </div>
      </div>

      <!-- Stats Bar -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-sm">
          <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Ce mois</p>
          <p class="text-3xl font-black text-slate-900 dark:text-white">{{ monthlyHours }}<span class="text-lg ml-1">h</span></p>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-sm">
          <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">À facturer</p>
          <p class="text-3xl font-black text-purple-600">{{ pendingAmount }} €</p>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-sm">
          <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Facturé</p>
          <p class="text-3xl font-black text-emerald-600">{{ invoicedAmount }} €</p>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="flex items-center gap-3 flex-wrap">
        <div class="flex gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl">
          <button v-for="f in filters" :key="f.value" @click="activeFilter = f.value; loadEntries()"
            :class="['px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all', activeFilter === f.value ? 'bg-white dark:bg-slate-700 shadow text-purple-600' : 'text-slate-500']">
            {{ f.label }}
          </button>
        </div>
        <div class="flex-1"></div>
        <button @click="generateInvoice()"
          class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 text-white text-sm font-bold rounded-2xl shadow hover:bg-emerald-700 transition-all">
          <FileText class="w-4 h-4" />
          <span v-if="selected.length > 0">Facturer {{ selected.length }} entrée{{ selected.length > 1 ? 's' : '' }}</span>
          <span v-else>Générer une facture</span>
        </button>
        <button @click="openModal()"
          class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-purple-500/30 transition-all hover:-translate-y-0.5">
          <Plus class="w-4 h-4" /> Nouvelle entrée
        </button>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
        <div v-if="loading" class="flex items-center justify-center py-16 text-slate-400">
          <Loader2 class="w-6 h-6 animate-spin mr-2" /> Chargement…
        </div>
        <div v-else-if="entries.length === 0" class="flex flex-col items-center justify-center py-16 text-slate-400 gap-3">
          <Clock class="w-10 h-10 opacity-30" />
          <p class="text-sm font-medium">Aucune entrée de temps</p>
          <p class="text-slate-300 text-xs">Démarrez le chronomètre ou ajoutez une entrée manuelle</p>
        </div>
        <table v-else class="w-full">
          <thead class="border-b border-slate-100 dark:border-slate-800">
            <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400">
              <th class="text-left px-8 py-5"><input type="checkbox" @change="toggleAll($event)" class="rounded" /></th>
              <th class="text-left px-4 py-5">Projet / Client</th>
              <th class="text-left px-4 py-5 hidden md:table-cell">Date</th>
              <th class="text-left px-4 py-5">Durée</th>
              <th class="text-left px-4 py-5 hidden lg:table-cell">Taux/h</th>
              <th class="text-left px-4 py-5 hidden lg:table-cell">Montant</th>
              <th class="text-left px-4 py-5">Statut</th>
              <th class="px-8 py-5"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
            <tr v-for="entry in entries" :key="entry.id"
              class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors group">
              <td class="px-8 py-4">
                <input v-if="entry.status === 'pending'" type="checkbox" :value="entry.id" v-model="selected" class="rounded" />
              </td>
              <td class="px-4 py-4">
                <p class="font-bold text-sm text-slate-900 dark:text-white">{{ entry.project_name || '—' }}</p>
                <p class="text-xs text-slate-400">{{ entry.client_name || 'Sans client' }}</p>
                <p v-if="entry.description" class="text-xs text-slate-400 mt-0.5 truncate max-w-xs">{{ entry.description }}</p>
              </td>
              <td class="px-4 py-4 text-sm text-slate-500 hidden md:table-cell">{{ formatDate(entry.start_time) }}</td>
              <td class="px-4 py-4">
                <span class="font-mono font-black text-sm text-slate-900 dark:text-white">{{ entry.duration_formatted }}</span>
              </td>
              <td class="px-4 py-4 text-sm text-slate-500 hidden lg:table-cell">{{ entry.hourly_rate }} €</td>
              <td class="px-4 py-4 font-bold text-sm text-slate-900 dark:text-white hidden lg:table-cell">{{ entry.amount }} €</td>
              <td class="px-4 py-4">
                <span :class="['px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider',
                  entry.status === 'invoiced' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600']">
                  {{ entry.status === 'invoiced' ? 'Facturé' : 'En attente' }}
                </span>
              </td>
              <td class="px-8 py-4">
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity justify-end">
                  <button @click="openModal(entry)" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-purple-600 transition-colors">
                    <Pencil class="w-4 h-4" />
                  </button>
                  <button @click="deleteEntry(entry.id)" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-rose-600 transition-colors">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="totalPages > 1" class="flex items-center justify-between px-8 py-4 border-t border-slate-100 dark:border-slate-800">
          <span class="text-xs text-slate-400">Page {{ page }} / {{ totalPages }}</span>
          <div class="flex items-center gap-2">
            <button @click="page--; loadEntries()" :disabled="page <= 1" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 text-xs font-bold disabled:opacity-40">←</button>
            <button @click="page++; loadEntries()" :disabled="page >= totalPages" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 text-xs font-bold disabled:opacity-40">→</button>
          </div>
        </div>
      </div>

    </div>

    <!-- Modals -->
    <template #modals>

      <!-- Add/Edit Entry Modal -->
      <div v-if="showModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-lg p-8 border border-slate-100 dark:border-slate-800 max-h-[90vh] overflow-y-auto">
          <h3 class="text-lg font-black text-slate-900 dark:text-white mb-6">{{ form.id ? 'Modifier' : 'Nouvelle' }} entrée</h3>
          <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Client</label>
                <select v-model="form.client_id" class="kloxy-input">
                  <option value="">Sans client</option>
                  <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company_name }}</option>
                </select>
              </div>
              <div class="space-y-2">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Taux horaire (€) *</label>
                <input v-model="form.hourly_rate" type="number" min="0.01" step="0.5" class="kloxy-input" :class="form.hourly_rate <= 0 ? 'border-rose-400 focus:ring-rose-500' : ''" />
              </div>
            </div>
            <div class="space-y-2">
              <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Projet</label>
              <input v-model="form.project_name" type="text" placeholder="Nom du projet" class="kloxy-input" />
            </div>
            <div class="space-y-2">
              <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Description</label>
              <textarea v-model="form.description" rows="2" class="kloxy-input resize-none"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Début</label>
                <input v-model="form.start_time" type="datetime-local" class="kloxy-input" />
              </div>
              <div class="space-y-2">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Fin</label>
                <input v-model="form.end_time" type="datetime-local" class="kloxy-input" />
              </div>
            </div>
          </div>
          <div class="flex gap-3 mt-6">
            <button @click="showModal = false" class="flex-1 px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">Annuler</button>
            <button @click="saveEntry()" :disabled="saving" class="flex-1 px-4 py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold shadow transition-all disabled:opacity-50">
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Start Timer Modal -->
      <div v-if="showTimerModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showTimerModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-md p-8 border border-slate-100 dark:border-slate-800">
          <h3 class="text-lg font-black text-slate-900 dark:text-white mb-6">Démarrer le chronomètre</h3>
          <div class="space-y-4">
            <div class="space-y-2">
              <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Client</label>
              <select v-model="timerForm.client_id" class="kloxy-input">
                <option value="">Sans client</option>
                <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company_name }}</option>
              </select>
            </div>
            <div class="space-y-2">
              <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Projet</label>
              <input v-model="timerForm.project_name" type="text" placeholder="Nom du projet" class="kloxy-input" />
            </div>
            <div class="space-y-2">
              <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Taux horaire (€) *</label>
              <input v-model="timerForm.hourly_rate" type="number" min="0.01" step="0.5" class="kloxy-input" :class="timerForm.hourly_rate <= 0 ? 'border-rose-400 focus:ring-rose-500' : ''" />
            </div>
          </div>
          <div class="flex gap-3 mt-6">
            <button @click="showTimerModal = false" class="flex-1 px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-600">Annuler</button>
            <button @click="startTimer()" class="flex-1 px-4 py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold shadow flex items-center justify-center gap-2">
              <Play class="w-4 h-4 fill-white" /> Démarrer
            </button>
          </div>
        </div>
      </div>

      <!-- Generate Invoice Modal -->
      <div v-if="showInvoiceModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showInvoiceModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-md p-8 border border-slate-100 dark:border-slate-800">
          <h3 class="text-lg font-black text-slate-900 dark:text-white mb-2">Générer une facture</h3>
          <p class="text-sm text-slate-500 mb-6">{{ selected.length }} entrée{{ selected.length > 1 ? 's' : '' }} — {{ selectedTotal }} € HT</p>
          <div class="space-y-2">
            <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Client *</label>
            <div v-if="inferredClientId" class="flex items-center gap-2 px-3 py-2.5 mb-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800">
              <CheckCircle2 class="w-4 h-4 text-emerald-500 flex-shrink-0" />
              <span class="text-sm font-bold text-emerald-700 dark:text-emerald-400 flex-1">{{ inferredClientName }}</span>
              <span class="text-[10px] text-emerald-500 font-black uppercase tracking-wider">Auto</span>
            </div>
            <select v-model="invoiceClientId" class="kloxy-input">
              <option value="">Sélectionner un client</option>
              <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.company_name }}</option>
            </select>
            <p v-if="!inferredClientId" class="text-[10px] text-amber-500">Les entrées sélectionnées appartiennent à des clients différents.</p>
          </div>
          <div class="flex gap-3 mt-6">
            <button @click="showInvoiceModal = false" class="flex-1 px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-600">Annuler</button>
            <button @click="confirmGenerateInvoice()" :disabled="!invoiceClientId || saving"
              class="flex-1 px-4 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold shadow transition-all disabled:opacity-50">
              {{ saving ? 'Création...' : 'Créer la facture' }}
            </button>
          </div>
        </div>
      </div>

    </template>
  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { Plus, Play, Square, FileText, Pencil, Trash2, Loader2, Clock, X, CheckCircle2 } from 'lucide-vue-next';
import MainLayout from '@/components/layout/MainLayout.vue';

const entries          = ref([]);
const clients          = ref([]);
const loading          = ref(false);
const saving           = ref(false);
const page             = ref(1);
const totalPages       = ref(1);
const selected         = ref([]);
const activeFilter     = ref('all');
const showModal        = ref(false);
const showTimerModal   = ref(false);
const showInvoiceModal = ref(false);
const invoiceClientId  = ref('');
const activeTimer      = ref(null);
const timerSeconds     = ref(0);
let   timerInterval    = null;

const toast = reactive({ visible: false, message: '', type: 'success' });

const filters = [
  { value: 'all',      label: 'Tout' },
  { value: 'pending',  label: 'En attente' },
  { value: 'invoiced', label: 'Facturé' },
];

const defaultRate = ref(0);
const form      = ref({ id: null, client_id: '', project_name: '', description: '', start_time: '', end_time: '', hourly_rate: 0 });
const timerForm = ref({ client_id: '', project_name: '', hourly_rate: 0 });

const nonce = () => window.myEasyComptaAdmin?.nonce || '';
const api   = (path, opts = {}) => fetch(`/wp-json/my-easy-compta/v1${path}`, {
  headers: { 'X-WP-Nonce': nonce(), 'Content-Type': 'application/json' }, ...opts,
});

function showToast(message, type = 'success') {
  toast.message = message; toast.type = type; toast.visible = true;
  setTimeout(() => (toast.visible = false), 3000);
}

const monthlyHours   = computed(() => Math.round(entries.value.filter(e => e.status === 'pending').reduce((s, e) => s + (parseInt(e.duration_minutes) || 0), 0) / 60 * 10) / 10);
const pendingAmount  = computed(() => entries.value.filter(e => e.status === 'pending').reduce((s, e) => s + parseFloat(e.amount || 0), 0).toFixed(2));
const invoicedAmount = computed(() => entries.value.filter(e => e.status === 'invoiced').reduce((s, e) => s + parseFloat(e.amount || 0), 0).toFixed(2));
const selectedTotal  = computed(() => entries.value.filter(e => selected.value.includes(e.id)).reduce((s, e) => s + parseFloat(e.amount || 0), 0).toFixed(2));

const inferredClientId = computed(() => {
  const sel = entries.value.filter(e => selected.value.includes(e.id));
  if (!sel.length) return null;
  const ids = [...new Set(sel.map(e => e.client_id).filter(Boolean))];
  return ids.length === 1 ? String(ids[0]) : null;
});
const inferredClientName = computed(() => {
  if (!inferredClientId.value) return null;
  return entries.value.find(e => selected.value.includes(e.id) && e.client_id)?.client_name || null;
});

const timerDisplay = computed(() => {
  const s = timerSeconds.value;
  return `${String(Math.floor(s / 3600)).padStart(2, '0')}:${String(Math.floor((s % 3600) / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
});

async function loadEntries() {
  loading.value = true;
  const status = activeFilter.value !== 'all' ? `&status=${activeFilter.value}` : '';
  const res    = await api(`/time-entries?page=${page.value}&per_page=20${status}`);
  const data   = await res.json();
  entries.value    = data.data || [];
  totalPages.value = data.last_page || 1;
  loading.value    = false;
}

async function loadClients() {
  const res  = await api('/clients?per_page=200');
  const data = await res.json();
  clients.value = data.clients || data.data || [];
}

async function loadDefaultRate() {
  try {
    const res  = await api('/settings/get');
    const data = await res.json();
    const rate = parseFloat(data.timetracking_default_rate || 0);
    if (rate > 0) {
      defaultRate.value = rate;
      form.value.hourly_rate      = rate;
      timerForm.value.hourly_rate = rate;
    }
  } catch (e) { /* silently ignore */ }
}

function openModal(entry = null) {
  form.value = entry
    ? { ...entry, start_time: entry.start_time?.slice(0, 16), end_time: entry.end_time?.slice(0, 16) }
    : { id: null, client_id: '', project_name: '', description: '', start_time: new Date().toISOString().slice(0, 16), end_time: '', hourly_rate: defaultRate.value };
  showModal.value = true;
}

function openTimerModal() { timerForm.value = { client_id: '', project_name: '', hourly_rate: defaultRate.value }; showTimerModal.value = true; }

async function saveEntry() {
  if (parseFloat(form.value.hourly_rate) <= 0) {
    showToast('Le taux horaire doit être supérieur à 0', 'error');
    return;
  }
  saving.value = true;
  const method = form.value.id ? 'PUT' : 'POST';
  const url    = form.value.id ? `/time-entries/${form.value.id}` : '/time-entries';
  const res    = await api(url, { method, body: JSON.stringify(form.value) });
  if (res.ok) showToast(form.value.id ? 'Entrée modifiée' : 'Entrée créée');
  else showToast('Erreur lors de l\'enregistrement', 'error');
  saving.value    = false;
  showModal.value = false;
  loadEntries();
}

async function deleteEntry(id) {
  if (!confirm('Supprimer cette entrée ?')) return;
  await api(`/time-entries/${id}`, { method: 'DELETE' });
  showToast('Entrée supprimée');
  loadEntries();
}

async function startTimer() {
  if (parseFloat(timerForm.value.hourly_rate) <= 0) {
    showToast('Le taux horaire doit être supérieur à 0', 'error');
    return;
  }
  const res = await api('/time-entries/timer/start', { method: 'POST', body: JSON.stringify(timerForm.value) });
  activeTimer.value    = await res.json();
  showTimerModal.value = false;
  startTick();
  loadEntries();
}

async function stopTimer() {
  if (!activeTimer.value) return;
  clearInterval(timerInterval);
  await api(`/time-entries/timer/${activeTimer.value.id}/stop`, { method: 'PUT' });
  activeTimer.value  = null;
  timerSeconds.value = 0;
  showToast('Chronomètre arrêté — entrée enregistrée');
  loadEntries();
}

function startTick() {
  timerSeconds.value = 0;
  clearInterval(timerInterval);
  timerInterval = setInterval(() => { timerSeconds.value++; }, 1000);
}

function generateInvoice() {
  if (selected.value.length === 0) {
    selected.value = entries.value.filter(e => e.status === 'pending').map(e => e.id);
  }
  invoiceClientId.value = inferredClientId.value || '';
  showInvoiceModal.value = true;
}

async function confirmGenerateInvoice() {
  if (!invoiceClientId.value) return;
  saving.value = true;
  const res  = await api('/time-entries/generate-invoice', { method: 'POST', body: JSON.stringify({ ids: selected.value, client_id: invoiceClientId.value }) });
  const data = await res.json();
  saving.value           = false;
  showInvoiceModal.value = false;
  selected.value         = [];
  if (data.invoice_id) showToast(`Facture ${data.invoice_number} créée — ${data.total} € HT`);
  else showToast('Erreur lors de la génération', 'error');
  loadEntries();
}

function toggleAll(e) { selected.value = e.target.checked ? entries.value.filter(en => en.status === 'pending').map(en => en.id) : []; }

function formatDate(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
}

onMounted(() => { loadEntries(); loadClients(); loadDefaultRate(); });
onUnmounted(() => clearInterval(timerInterval));
</script>
