<template>
  <MainLayout title="Contrats" subtitle="Créez et gérez vos modèles de contrats">

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

      <!-- Header actions -->
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
          <input
            v-model="search"
            @input="debouncedLoad"
            type="text"
            placeholder="Rechercher un contrat..."
            class="px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm text-slate-700 dark:text-slate-300 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-500/30 w-64"
          />
          <select v-model="filterStatus" @change="load" class="px-4 py-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-700 dark:text-slate-300 focus:outline-none">
            <option value="">Tous les statuts</option>
            <option value="draft">Brouillon</option>
            <option value="sent">Envoyé</option>
            <option value="signed">Signé</option>
            <option value="archived">Archivé</option>
          </select>
        </div>
        <button
          @click="openNew"
          class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-lg shadow-purple-500/30 transition-all hover:-translate-y-0.5 active:translate-y-0"
        >
          <Plus class="w-4 h-4" />
          Nouveau contrat
        </button>
      </div>

      <!-- Contracts list -->
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
        <div v-if="loading" class="flex items-center justify-center py-16 text-slate-400">
          <Loader2 class="w-6 h-6 animate-spin mr-2" /> Chargement…
        </div>
        <div v-else-if="!contracts.length" class="flex flex-col items-center justify-center py-16 text-slate-400 gap-3">
          <FileText class="w-10 h-10 opacity-30" />
          <p class="text-sm font-medium">Aucun contrat trouvé</p>
          <button @click="openNew" class="text-purple-600 text-sm font-bold hover:underline">Créer le premier contrat</button>
        </div>
        <table v-else class="w-full">
          <thead class="border-b border-slate-100 dark:border-slate-800">
            <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400">
              <th class="text-left px-8 py-5">Titre</th>
              <th class="text-left px-4 py-5 hidden md:table-cell">Client</th>
              <th class="text-left px-4 py-5 hidden lg:table-cell">Statut</th>
              <th class="text-left px-4 py-5 hidden lg:table-cell">Date</th>
              <th class="px-8 py-5"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
            <tr
              v-for="c in contracts"
              :key="c.id"
              class="hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors group"
            >
              <td class="px-8 py-4">
                <span class="font-bold text-sm text-slate-900 dark:text-white">{{ c.title }}</span>
              </td>
              <td class="px-4 py-4 hidden md:table-cell text-sm text-slate-500">{{ c.client_name || '—' }}</td>
              <td class="px-4 py-4 hidden lg:table-cell">
                <span :class="statusClass(c.status)" class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-xl">
                  {{ statusLabel(c.status) }}
                </span>
              </td>
              <td class="px-4 py-4 hidden lg:table-cell text-sm text-slate-400">{{ formatDate(c.created_at) }}</td>
              <td class="px-8 py-4">
                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity justify-end">
                  <button @click="openEdit(c)" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-purple-600 transition-colors" title="Modifier">
                    <Pencil class="w-4 h-4" />
                  </button>
                  <button @click="downloadPdf(c)" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-rose-600 transition-colors" title="Télécharger PDF">
                    <Download class="w-4 h-4" />
                  </button>
                  <button @click="confirmDelete(c)" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 hover:text-rose-600 transition-colors" title="Supprimer">
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="lastPage > 1" class="flex items-center justify-between px-8 py-4 border-t border-slate-100 dark:border-slate-800">
          <span class="text-xs text-slate-400">Page {{ page }} / {{ lastPage }}</span>
          <div class="flex items-center gap-2">
            <button @click="changePage(page - 1)" :disabled="page === 1" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold disabled:opacity-40">←</button>
            <button @click="changePage(page + 1)" :disabled="page === lastPage" class="px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold disabled:opacity-40">→</button>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal: Create / Edit -->
    <template #modals>
      <div v-if="showModal" class="fixed inset-0 z-[200] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
          <!-- Modal Header -->
          <div class="sticky top-0 bg-white dark:bg-slate-900 px-8 py-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between rounded-t-[2rem] z-10">
            <h3 class="font-black text-slate-900 dark:text-white text-lg">{{ form.id ? 'Modifier le contrat' : 'Nouveau contrat' }}</h3>
            <div class="flex items-center gap-2">
              <button
                v-if="!form.id"
                type="button"
                @click="loadTemplate"
                :disabled="loadingTemplate"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-xs font-bold hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors disabled:opacity-50"
                title="Charger le modèle défini dans les Réglages"
              >
                <Loader2 v-if="loadingTemplate" class="w-3.5 h-3.5 animate-spin" />
                <ClipboardList v-else class="w-3.5 h-3.5" />
                Charger le modèle
              </button>
              <button @click="showModal = false" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 transition-colors"><X class="w-5 h-5" /></button>
            </div>
          </div>

          <form @submit.prevent="saveContract" class="p-8 space-y-6">

            <!-- Title + Status -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="md:col-span-2">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500 mb-2">Titre *</label>
                <input v-model="form.title" required type="text" class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500/30" placeholder="Ex: Contrat de prestation de services" />
              </div>
              <div>
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500 mb-2">Statut</label>
                <select v-model="form.status" class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-800 dark:text-white focus:outline-none">
                  <option value="draft">Brouillon</option>
                  <option value="sent">Envoyé</option>
                  <option value="signed">Signé</option>
                  <option value="archived">Archivé</option>
                </select>
              </div>
            </div>

            <!-- Client -->
            <div>
              <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500 mb-2">Client</label>
              <select v-model="form.client_id" class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-800 dark:text-white focus:outline-none">
                <option :value="null">— Aucun client —</option>
                <option v-for="cl in clients" :key="cl.id" :value="cl.id">{{ cl.company_name }}</option>
              </select>
            </div>

            <!-- Body -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500">Corps du contrat *</label>
                <button type="button" @click="showVarsPanel = !showVarsPanel" class="text-[10px] font-black text-purple-600 hover:underline flex items-center gap-1">
                  <Braces class="w-3 h-3" /> Variables disponibles
                </button>
              </div>

              <!-- Variables panel -->
              <div v-if="showVarsPanel" class="mb-3 p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800">
                <p class="text-[10px] font-black uppercase tracking-wider text-indigo-500 mb-3">Cliquez pour insérer</p>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="(desc, varName) in variables"
                    :key="varName"
                    type="button"
                    @click="insertVariable(varName)"
                    class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-700 text-[11px] font-black text-indigo-600 dark:text-indigo-400 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 transition-all"
                    :title="desc"
                  >{{ varName }}</button>
                </div>
              </div>

              <div class="rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700">
                <VueEditor v-model="form.body" :editor-toolbar="contractEditorToolbar" placeholder="Rédigez votre contrat ici. Utilisez les variables comme {CLIENT_NAME}, {AMOUNT}, etc." />
              </div>
            </div>

            <!-- Amount variable shortcut -->
            <div>
              <label class="block text-[11px] font-black uppercase tracking-wider text-slate-500 mb-2">Montant (variable {AMOUNT})</label>
              <input v-model="form.variables.amount" type="text" class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500/30" placeholder="Ex: 1 500 € HT" />
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-2">
              <button type="button" @click="showModal = false" class="px-6 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">Annuler</button>
              <button
                type="submit"
                :disabled="saving"
                class="inline-flex items-center gap-2 px-8 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-2xl font-black text-sm shadow-lg shadow-purple-500/30 transition-all disabled:opacity-50"
              >
                <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                {{ saving ? 'Sauvegarde…' : (form.id ? 'Mettre à jour' : 'Créer') }}
              </button>
            </div>

          </form>
        </div>
      </div>

      <!-- Confirm Delete Dialog -->
      <div v-if="deleteTarget" class="fixed inset-0 z-[300] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="deleteTarget = null"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl p-8 w-full max-w-sm">
          <h4 class="font-black text-slate-900 dark:text-white text-base mb-2">Supprimer ce contrat ?</h4>
          <p class="text-slate-500 text-sm mb-6">« {{ deleteTarget.title }} » sera supprimé définitivement.</p>
          <div class="flex items-center gap-3 justify-end">
            <button @click="deleteTarget = null" class="px-5 py-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-600 font-bold text-sm hover:bg-slate-200 transition-colors">Annuler</button>
            <button @click="doDelete" :disabled="deleting" class="px-5 py-2.5 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm transition-colors disabled:opacity-50">
              <Loader2 v-if="deleting" class="w-4 h-4 animate-spin inline mr-1" />
              Supprimer
            </button>
          </div>
        </div>
      </div>
    </template>

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Plus, FileText, Pencil, Trash2, Download, X, Loader2, Braces, ClipboardList } from 'lucide-vue-next';
import MainLayout from '@/components/layout/MainLayout.vue';
import { VueEditor } from 'vue3-editor';

// ── State ─────────────────────────────────────────────────────────────────────

const contracts    = ref([]);
const clients      = ref([]);
const variables    = ref({});
const loading         = ref(false);
const saving          = ref(false);
const deleting        = ref(false);
const loadingTemplate = ref(false);
const showModal    = ref(false);
const showVarsPanel = ref(false);
const deleteTarget = ref(null);
const search       = ref('');
const filterStatus = ref('');
const page         = ref(1);
const lastPage     = ref(1);
const contractEditorToolbar = [
  [{ header: [1, 2, 3, false] }],
  ['bold', 'italic', 'underline'],
  [{ list: 'ordered' }, { list: 'bullet' }],
  [{ align: [] }],
  ['clean'],
];

const toast = reactive({ visible: false, message: '', type: 'success' });

const form = reactive({
  id: null,
  title: '',
  body: '',
  status: 'draft',
  client_id: null,
  variables: { amount: '' },
});

// ── Helpers ───────────────────────────────────────────────────────────────────

const nonce = () => window.myEasyComptaAdmin?.nonce;

async function apiFetch(path, opts = {}) {
  const res = await fetch(`/wp-json/my-easy-compta/v1${path}`, {
    ...opts,
    headers: { 'X-WP-Nonce': nonce(), 'Content-Type': 'application/json', ...(opts.headers || {}) },
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({ message: 'Erreur serveur' }));
    throw new Error(err.message || 'Erreur serveur');
  }
  return res.json();
}

function showToast(message, type = 'success') {
  toast.message  = message;
  toast.type     = type;
  toast.visible  = true;
  setTimeout(() => (toast.visible = false), 3000);
}

function statusLabel(s) {
  return { draft: 'Brouillon', sent: 'Envoyé', signed: 'Signé', archived: 'Archivé' }[s] ?? s;
}

function statusClass(s) {
  return {
    draft:    'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
    sent:     'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    signed:   'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
    archived: 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
  }[s] ?? 'bg-slate-100 text-slate-500';
}

function formatDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
}

let debounceTimer;
function debouncedLoad() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => { page.value = 1; load(); }, 350);
}

function changePage(p) {
  if (p < 1 || p > lastPage.value) return;
  page.value = p;
  load();
}

// ── Load data ─────────────────────────────────────────────────────────────────

async function load() {
  loading.value = true;
  try {
    const params = new URLSearchParams({
      page:     page.value,
      per_page: 20,
      ...(filterStatus.value ? { status: filterStatus.value } : {}),
      ...(search.value        ? { search: search.value }       : {}),
    });
    const res    = await apiFetch(`/contracts?${params}`);
    contracts.value = res.data  || [];
    lastPage.value  = res.last_page || 1;
  } catch (e) {
    showToast(e.message, 'error');
  } finally {
    loading.value = false;
  }
}

async function loadClients() {
  try {
    const res = await apiFetch('/clients?per_page=200');
    clients.value = res.clients || res.data || [];
  } catch (_) {}
}

async function loadVariables() {
  try {
    variables.value = await apiFetch('/contracts/variables');
  } catch (_) {}
}

// ── Modal ─────────────────────────────────────────────────────────────────────

function resetForm() {
  form.id        = null;
  form.title     = '';
  form.body      = '';
  form.status    = 'draft';
  form.client_id = null;
  form.variables = { amount: '' };
}

function openNew() {
  resetForm();
  showVarsPanel.value = false;
  showModal.value     = true;
}

async function loadTemplate() {
  loadingTemplate.value = true;
  try {
    const res = await apiFetch('/settings/get');
    const title = res.contract_default_title || '';
    const body  = res.contract_default_body  || '';
    if (!title && !body) {
      showToast('Aucun modèle configuré dans les Réglages → Contrats', 'error');
      return;
    }
    if (title) form.title = title;
    if (body)  form.body  = body;
    showToast('Modèle chargé');
  } catch (e) {
    showToast('Impossible de charger le modèle', 'error');
  } finally {
    loadingTemplate.value = false;
  }
}

function openEdit(c) {
  form.id        = c.id;
  form.title     = c.title;
  form.body      = c.body;
  form.status    = c.status;
  form.client_id = c.client_id ? Number(c.client_id) : null;
  form.variables = { amount: c.variables?.amount ?? '', ...(c.variables || {}) };
  showVarsPanel.value = false;
  showModal.value     = true;
}

function insertVariable(varName) {
  // Append variable token before the closing </p> of the last paragraph,
  // or just append if the body ends with a block tag.
  if (form.body.endsWith('</p>')) {
    form.body = form.body.slice(0, -4) + varName + '</p>';
  } else {
    form.body += varName;
  }
}

// ── Save ──────────────────────────────────────────────────────────────────────

async function saveContract() {
  saving.value = true;
  try {
    const payload = {
      title:      form.title,
      body:       form.body,
      status:     form.status,
      client_id:  form.client_id,
      variables:  form.variables,
    };

    if (form.id) {
      await apiFetch(`/contracts/${form.id}`, { method: 'PUT', body: JSON.stringify(payload) });
      showToast('Contrat mis à jour');
    } else {
      await apiFetch('/contracts', { method: 'POST', body: JSON.stringify(payload) });
      showToast('Contrat créé');
    }
    showModal.value = false;
    load();
  } catch (e) {
    showToast(e.message, 'error');
  } finally {
    saving.value = false;
  }
}

// ── Delete ────────────────────────────────────────────────────────────────────

function confirmDelete(c) {
  deleteTarget.value = c;
}

async function doDelete() {
  if (!deleteTarget.value) return;
  deleting.value = true;
  try {
    await apiFetch(`/contracts/${deleteTarget.value.id}`, { method: 'DELETE' });
    showToast('Contrat supprimé');
    deleteTarget.value = null;
    load();
  } catch (e) {
    showToast(e.message, 'error');
  } finally {
    deleting.value = false;
  }
}

// ── PDF ───────────────────────────────────────────────────────────────────────

async function downloadPdf(c) {
  try {
    const url  = `/wp-json/my-easy-compta/v1/contracts/${c.id}/pdf`;
    const res  = await fetch(url, { headers: { 'X-WP-Nonce': nonce() } });
    if (!res.ok) throw new Error('Erreur génération PDF');
    const blob = await res.blob();
    const link = document.createElement('a');
    link.href  = URL.createObjectURL(blob);
    link.setAttribute('download', `contrat_${c.id}.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  } catch (e) {
    showToast(e.message, 'error');
  }
}

// ── Init ──────────────────────────────────────────────────────────────────────

onMounted(() => {
  load();
  loadClients();
  loadVariables();
});
</script>
