<template>
  <MainLayout title="Bons de livraison" subtitle="Gérez vos bons de livraison et téléchargez-les en PDF">

    <!-- Toast -->
    <div v-if="toast.visible" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] toast-animate-in">
      <div :class="['flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md', toast.type === 'success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
        <span class="font-bold text-sm">{{ toast.message }}</span>
        <button @click="toast.visible = false" class="ml-2 hover:bg-white/20 p-1 rounded-full transition-colors"><X class="w-4 h-4" /></button>
      </div>
    </div>

    <div class="flex flex-col gap-6">

      <!-- Toolbar -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3 flex-wrap">
          <div class="flex gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-2xl">
            <button v-for="f in filters" :key="f.value" @click="activeFilter = f.value; page = 1; loadNotes()"
              :class="['px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all', activeFilter === f.value ? 'bg-white dark:bg-slate-700 shadow text-purple-600' : 'text-slate-500']">
              {{ f.label }}
            </button>
          </div>
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input v-model="search" @input="debouncedSearch" type="text" placeholder="Rechercher..."
              class="pl-9 pr-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/30 w-56" />
          </div>
        </div>
        <button @click="openModal()"
          class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-purple-500/30 transition-all hover:-translate-y-0.5">
          <Plus class="w-4 h-4" /> Nouveau bon
        </button>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
        <div v-if="loading" class="flex items-center justify-center py-16 text-slate-400">
          <Loader2 class="w-6 h-6 animate-spin mr-2" /> Chargement…
        </div>
        <div v-else-if="notes.length === 0" class="flex flex-col items-center justify-center py-16 text-slate-400 gap-3">
          <Truck class="w-10 h-10 opacity-30" />
          <p class="text-sm font-medium">Aucun bon de livraison</p>
          <button @click="openModal()" class="text-purple-600 text-sm font-bold hover:underline">Créer le premier bon</button>
        </div>
        <table v-else class="w-full">
          <thead class="border-b border-slate-100 dark:border-slate-800">
            <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400">
              <th class="text-left px-8 py-5">N° BL</th>
              <th class="text-left px-4 py-5 hidden md:table-cell">Client</th>
              <th class="text-left px-4 py-5 hidden md:table-cell">Date</th>
              <th class="text-left px-4 py-5 hidden lg:table-cell">Facture liée</th>
              <th class="text-left px-4 py-5">Statut</th>
              <th class="px-8 py-5"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
            <tr v-for="note in notes" :key="note.id"
              class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors group">
              <td class="px-8 py-4 font-black text-sm text-slate-900 dark:text-white">{{ note.delivery_number }}</td>
              <td class="px-4 py-4 text-sm text-slate-500 hidden md:table-cell">{{ note.client_name || '—' }}</td>
              <td class="px-4 py-4 text-sm text-slate-500 hidden md:table-cell">{{ formatDate(note.delivery_date) }}</td>
              <td class="px-4 py-4 text-sm text-slate-400 hidden lg:table-cell">{{ note.invoice_id ? '#' + note.invoice_id : '—' }}</td>
              <td class="px-4 py-4">
                <span :class="['px-2.5 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider', statusClass(note.status)]">
                  {{ statusLabel(note.status) }}
                </span>
              </td>
              <td class="px-8 py-4">
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity justify-end">
                  <button @click="downloadPdf(note.id)" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-purple-600 transition-colors" title="Télécharger PDF">
                    <Download class="w-4 h-4" />
                  </button>
                  <button @click="openModal(note)" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-purple-600 transition-colors">
                    <Pencil class="w-4 h-4" />
                  </button>
                  <button @click="deleteNote(note.id)" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-rose-600 transition-colors">
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
            <button @click="page--; loadNotes()" :disabled="page <= 1" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 text-xs font-bold disabled:opacity-40">←</button>
            <button @click="page++; loadNotes()" :disabled="page >= totalPages" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 text-xs font-bold disabled:opacity-40">→</button>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal -->
    <template #modals>
      <div v-if="showModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-lg p-8 border border-slate-100 dark:border-slate-800 max-h-[90vh] overflow-y-auto">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ form.id ? 'Modifier' : 'Nouveau' }} bon de livraison</h3>
            <button @click="showModal = false" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400"><X class="w-5 h-5" /></button>
          </div>
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
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Date de livraison</label>
                <input v-model="form.delivery_date" type="date" class="kloxy-input" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">N° BL (auto si vide)</label>
                <input v-model="form.delivery_number" type="text" placeholder="BL-0001" class="kloxy-input" />
              </div>
              <div class="space-y-2">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Statut</label>
                <select v-model="form.status" class="kloxy-input">
                  <option value="draft">Brouillon</option>
                  <option value="sent">Envoyé</option>
                  <option value="delivered">Livré</option>
                </select>
              </div>
            </div>
            <div class="space-y-2">
              <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Adresse de livraison</label>
              <textarea v-model="form.delivery_address" rows="2" class="kloxy-input resize-none"></textarea>
            </div>
            <div class="space-y-2">
              <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Articles / Notes</label>
              <textarea v-model="form.notes" rows="4" placeholder="Liste des articles livrés..." class="kloxy-input resize-none"></textarea>
            </div>
          </div>
          <div class="flex gap-3 mt-6">
            <button @click="showModal = false" class="flex-1 px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">Annuler</button>
            <button @click="saveNote()" :disabled="saving" class="flex-1 px-4 py-3 rounded-2xl bg-purple-600 hover:bg-purple-700 text-white text-sm font-bold shadow transition-all disabled:opacity-50">
              {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>
    </template>

  </MainLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { Plus, Search, Download, Pencil, Trash2, Loader2, Truck, X } from 'lucide-vue-next';
import MainLayout from '@/components/layout/MainLayout.vue';

const notes        = ref([]);
const clients      = ref([]);
const loading      = ref(false);
const saving       = ref(false);
const page         = ref(1);
const totalPages   = ref(1);
const search       = ref('');
const activeFilter = ref('all');
const showModal    = ref(false);

const toast = reactive({ visible: false, message: '', type: 'success' });

const filters = [
  { value: 'all',       label: 'Tous' },
  { value: 'draft',     label: 'Brouillon' },
  { value: 'sent',      label: 'Envoyé' },
  { value: 'delivered', label: 'Livré' },
];

const form = ref({ id: null, client_id: '', invoice_id: '', delivery_number: '', delivery_date: '', notes: '', delivery_address: '', status: 'draft' });

const nonce = () => window.myEasyComptaAdmin?.nonce || '';
const api   = (path, opts = {}) => fetch(`/wp-json/my-easy-compta/v1${path}`, {
  headers: { 'X-WP-Nonce': nonce(), 'Content-Type': 'application/json' }, ...opts,
});

function showToast(message, type = 'success') {
  toast.message = message; toast.type = type; toast.visible = true;
  setTimeout(() => (toast.visible = false), 3000);
}

let searchTimeout = null;
function debouncedSearch() { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => { page.value = 1; loadNotes(); }, 300); }

async function loadNotes() {
  loading.value = true;
  const status  = activeFilter.value !== 'all' ? `&status=${activeFilter.value}` : '';
  const q       = search.value ? `&search=${encodeURIComponent(search.value)}` : '';
  const res     = await api(`/delivery-notes?page=${page.value}&per_page=20${status}${q}`);
  const data    = await res.json();
  notes.value      = data.data || [];
  totalPages.value  = data.last_page || 1;
  loading.value     = false;
}

async function loadClients() {
  const res  = await api('/clients?per_page=200');
  const data = await res.json();
  clients.value = data.clients || data.data || [];
}

function openModal(note = null) {
  form.value = note
    ? { ...note }
    : { id: null, client_id: '', invoice_id: '', delivery_number: '', delivery_date: new Date().toISOString().slice(0, 10), notes: '', delivery_address: '', status: 'draft' };
  showModal.value = true;
}

async function saveNote() {
  saving.value = true;
  const method = form.value.id ? 'PUT' : 'POST';
  const url    = form.value.id ? `/delivery-notes/${form.value.id}` : '/delivery-notes';
  const res    = await api(url, { method, body: JSON.stringify(form.value) });
  if (res.ok) showToast(form.value.id ? 'Bon modifié' : 'Bon créé');
  else showToast('Erreur lors de l\'enregistrement', 'error');
  saving.value    = false;
  showModal.value = false;
  loadNotes();
}

async function deleteNote(id) {
  if (!confirm('Supprimer ce bon de livraison ?')) return;
  await api(`/delivery-notes/${id}`, { method: 'DELETE' });
  showToast('Bon supprimé');
  loadNotes();
}

async function downloadPdf(id) {
  const url = `/wp-json/my-easy-compta/v1/delivery-notes/${id}/pdf`;
  window.open(url + `?_wpnonce=${nonce()}`, '_blank');
}

function statusLabel(s) { return { draft: 'Brouillon', sent: 'Envoyé', delivered: 'Livré' }[s] || s; }
function statusClass(s)  {
  return {
    draft:     'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
    sent:      'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    delivered: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
  }[s] || 'bg-slate-100 text-slate-500';
}
function formatDate(d) { return d ? new Date(d).toLocaleDateString('fr-FR') : '—'; }

onMounted(() => { loadNotes(); loadClients(); });
</script>
