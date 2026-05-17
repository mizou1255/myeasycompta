<template>
  <div class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-base font-black text-slate-800 dark:text-slate-100">Zaps & Zapier</h3>
        <p class="text-xs text-slate-400 mt-0.5">Notifications HTTP temps réel vers n'importe quelle URL — compatible Zapier, Make, n8n.</p>
      </div>
      <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-black tracking-wide transition-colors">
        <Plus class="w-3.5 h-3.5" /> Nouveau webhook
      </button>
    </div>

    <!-- Stats row -->
    <div class="grid grid-cols-3 gap-3">
      <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Total</p>
        <p class="text-2xl font-black text-slate-800 dark:text-white">{{ webhooks.length }}</p>
      </div>
      <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Actifs</p>
        <p class="text-2xl font-black text-emerald-600">{{ webhooks.filter(w => w.active).length }}</p>
      </div>
      <div class="bg-slate-50 dark:bg-slate-800/60 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Inactifs</p>
        <p class="text-2xl font-black text-slate-400">{{ webhooks.filter(w => !w.active).length }}</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <Loader2 class="w-6 h-6 animate-spin text-purple-500" />
    </div>

    <!-- Empty state -->
    <div v-else-if="!webhooks.length" class="flex flex-col items-center justify-center py-16 bg-slate-50 dark:bg-slate-900/30 rounded-[2rem] border border-dashed border-slate-200 dark:border-slate-800 text-center">
      <div class="w-14 h-14 bg-purple-50 dark:bg-purple-900/20 rounded-2xl flex items-center justify-center mb-4">
        <Zap class="w-7 h-7 text-purple-400" />
      </div>
      <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Aucun webhook configuré</p>
      <p class="text-xs text-slate-400 mt-1 mb-4">Créez votre premier webhook pour recevoir des notifications en temps réel.</p>
      <button @click="openCreate" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-black tracking-wide transition-colors">
        <Plus class="w-3.5 h-3.5" /> Créer un webhook
      </button>
    </div>

    <!-- Zaps list -->
    <div v-else class="space-y-3">
      <div v-for="wh in webhooks" :key="wh.id"
        class="group bg-white dark:bg-slate-900 rounded-[1.5rem] border border-slate-100 dark:border-slate-800 overflow-hidden hover:border-purple-300 dark:hover:border-purple-600/40 transition-all duration-200">

        <!-- Top accent line -->
        <div :class="['h-0.5', wh.active ? 'bg-emerald-400' : 'bg-slate-200 dark:bg-slate-700']"></div>

        <div class="p-5">
          <div class="flex items-start justify-between gap-4">
            <!-- Info -->
            <div class="flex items-start gap-3 min-w-0">
              <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0 mt-0.5', wh.active ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-400']">
                <Zap class="w-5 h-5" />
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 class="font-black text-sm text-slate-800 dark:text-slate-100">{{ wh.name }}</h4>
                  <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide', wh.active ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500']">
                    <span :class="['w-1.5 h-1.5 rounded-full', wh.active ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                    {{ wh.active ? 'Actif' : 'Inactif' }}
                  </span>
                  <span v-if="wh.has_secret" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400">
                    <Lock class="w-2.5 h-2.5" /> Signé
                  </span>
                </div>
                <p class="text-xs text-slate-400 font-mono mt-0.5 truncate max-w-xs">{{ wh.url }}</p>
                <!-- Events badges -->
                <div class="flex flex-wrap gap-1 mt-2">
                  <span v-for="evt in wh.events" :key="evt"
                    class="inline-block px-2 py-0.5 rounded-lg bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 text-[10px] font-mono font-bold">
                    {{ evt }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2 shrink-0">
              <button @click="openLogs(wh)" title="Voir les logs" class="p-2 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <ScrollText class="w-4 h-4" />
              </button>
              <button @click="testZap(wh)" :disabled="testing === wh.id" title="Tester" class="p-2 rounded-lg text-slate-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors disabled:opacity-50">
                <Loader2 v-if="testing === wh.id" class="w-4 h-4 animate-spin" />
                <Zap v-else class="w-4 h-4" />
              </button>
              <button @click="toggleActive(wh)" :title="wh.active ? 'Désactiver' : 'Activer'" class="p-2 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors">
                <ToggleLeft v-if="!wh.active" class="w-4 h-4" />
                <ToggleRight v-else class="w-4 h-4 text-emerald-600" />
              </button>
              <button @click="openEdit(wh)" title="Modifier" class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                <Pencil class="w-4 h-4" />
              </button>
              <button @click="confirmDelete(wh)" title="Supprimer" class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Test result inline -->
          <div v-if="testResults[wh.id]" :class="['mt-3 flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold', testResults[wh.id].success ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300']">
            <CheckCircle2 v-if="testResults[wh.id].success" class="w-3.5 h-3.5 shrink-0" />
            <AlertCircle v-else class="w-3.5 h-3.5 shrink-0" />
            <span v-if="testResults[wh.id].success">Livré avec succès — HTTP {{ testResults[wh.id].status_code }}</span>
            <span v-else>Échec — {{ testResults[wh.id].response || 'Erreur de connexion' }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Create/Edit Modal ──────────────────────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <!-- Header -->
          <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-800">
            <h3 class="font-black text-slate-800 dark:text-white">{{ editingId ? 'Modifier le webhook' : 'Nouveau webhook' }}</h3>
            <button @click="closeModal" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 transition-colors">
              <X class="w-4 h-4" />
            </button>
          </div>

          <div class="p-6 space-y-5">
            <!-- Name -->
            <div class="space-y-1.5">
              <label class="kloxy-label">Nom <span class="text-red-500">*</span></label>
              <input v-model="form.name" type="text" class="kloxy-input" placeholder="Mon webhook Zapier" />
            </div>

            <!-- URL -->
            <div class="space-y-1.5">
              <label class="kloxy-label">URL de destination <span class="text-red-500">*</span></label>
              <input v-model="form.url" type="url" class="kloxy-input font-mono" placeholder="https://hooks.zapier.com/hooks/catch/…" />
            </div>

            <!-- Events -->
            <div class="space-y-2">
              <label class="kloxy-label">Événements <span class="text-red-500">*</span></label>
              <div v-if="eventsLoading" class="flex justify-center py-4"><Loader2 class="w-4 h-4 animate-spin text-purple-500" /></div>
              <div v-else class="space-y-3">
                <div v-for="(groupEvents, groupName) in groupedEvents" :key="groupName">
                  <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5">{{ groupName }}</p>
                  <div class="grid grid-cols-1 gap-1.5">
                    <label v-for="evt in groupEvents" :key="evt.value"
                      class="flex items-center gap-3 p-2.5 rounded-xl border cursor-pointer transition-colors"
                      :class="form.events.includes(evt.value) ? 'border-purple-400 bg-purple-50 dark:bg-purple-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-purple-300'">
                      <input type="checkbox" :value="evt.value" v-model="form.events" class="w-4 h-4 accent-purple-600" />
                      <div>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ evt.label }}</p>
                        <p class="text-[10px] font-mono text-slate-400">{{ evt.value }}</p>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Secret -->
            <div class="space-y-1.5">
              <label class="kloxy-label">Clé secrète <span class="text-slate-400 font-normal">(optionnel)</span></label>
              <input v-model="form.secret" type="text" class="kloxy-input font-mono" placeholder="Laissez vide pour désactiver la signature HMAC" />
              <p class="text-[11px] text-slate-400">Si renseignée, chaque requête inclura un header <code class="font-mono bg-slate-100 dark:bg-slate-800 px-1 rounded">X-ECWP-Signature: sha256=…</code></p>
            </div>

            <!-- Active toggle -->
            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
              <div>
                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Activer ce webhook</p>
                <p class="text-xs text-slate-400">Les événements ne seront envoyés que si le webhook est actif.</p>
              </div>
              <button type="button"
                :class="['relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200', form.active ? 'bg-purple-600' : 'bg-slate-200 dark:bg-slate-700']"
                @click="form.active = !form.active">
                <span :class="['pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', form.active ? 'translate-x-5' : 'translate-x-0']"></span>
              </button>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-end gap-3 p-6 border-t border-slate-100 dark:border-slate-800">
            <button @click="closeModal" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-500 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Annuler</button>
            <button @click="save" :disabled="saving" class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-sm font-black tracking-wide transition-colors disabled:opacity-60">
              <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
              <Save v-else class="w-3.5 h-3.5" />
              {{ editingId ? 'Enregistrer' : 'Créer' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Logs Modal ─────────────────────────────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showLogs" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showLogs = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden">
          <!-- Header -->
          <div class="flex items-center justify-between p-6 border-b border-slate-100 dark:border-slate-800 shrink-0">
            <div>
              <h3 class="font-black text-slate-800 dark:text-white">Logs — {{ selectedZap?.name }}</h3>
              <p class="text-xs text-slate-400 mt-0.5">{{ logsData.total }} livraison(s) enregistrée(s)</p>
            </div>
            <div class="flex items-center gap-2">
              <button @click="clearLogs" :disabled="!logsData.logs.length" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors disabled:opacity-40">
                <Trash2 class="w-3.5 h-3.5" /> Vider
              </button>
              <button @click="showLogs = false" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 transition-colors">
                <X class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Logs list -->
          <div class="overflow-y-auto flex-1 p-4 space-y-2">
            <div v-if="logsLoading" class="flex justify-center py-8">
              <Loader2 class="w-5 h-5 animate-spin text-purple-500" />
            </div>
            <div v-else-if="!logsData.logs.length" class="flex flex-col items-center py-12 text-slate-400">
              <ScrollText class="w-8 h-8 mb-3 opacity-40" />
              <p class="text-sm font-bold">Aucun log disponible</p>
            </div>
            <div v-else v-for="log in logsData.logs" :key="log.id"
              class="flex items-start gap-3 p-3 rounded-xl border transition-colors"
              :class="log.success == 1 ? 'border-emerald-100 dark:border-emerald-900/30 bg-emerald-50/50 dark:bg-emerald-900/10' : 'border-red-100 dark:border-red-900/30 bg-red-50/50 dark:bg-red-900/10'">
              <div :class="['w-6 h-6 rounded-lg flex items-center justify-center shrink-0 mt-0.5', log.success == 1 ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600' : 'bg-red-100 dark:bg-red-900/40 text-red-500']">
                <CheckCircle2 v-if="log.success == 1" class="w-3.5 h-3.5" />
                <AlertCircle v-else class="w-3.5 h-3.5" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="inline-block px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-mono font-bold text-slate-600 dark:text-slate-300">{{ log.event }}</span>
                  <span v-if="log.status_code" :class="['text-xs font-black', log.success == 1 ? 'text-emerald-600' : 'text-red-500']">HTTP {{ log.status_code }}</span>
                  <span class="text-[10px] text-slate-400">{{ formatDate(log.created_at) }}</span>
                </div>
                <p v-if="log.response" class="text-[11px] text-slate-400 mt-1 truncate font-mono">{{ log.response }}</p>
              </div>
            </div>
          </div>

          <!-- Pagination -->
          <div v-if="logsData.pages > 1" class="flex items-center justify-center gap-2 p-4 border-t border-slate-100 dark:border-slate-800 shrink-0">
            <button @click="logsPage--; fetchLogs()" :disabled="logsPage <= 1" class="px-3 py-1.5 rounded-xl text-xs font-bold disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-300">← Précédent</button>
            <span class="text-xs text-slate-400">{{ logsPage }} / {{ logsData.pages }}</span>
            <button @click="logsPage++; fetchLogs()" :disabled="logsPage >= logsData.pages" class="px-3 py-1.5 rounded-xl text-xs font-bold disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-slate-600 dark:text-slate-300">Suivant →</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── Delete Confirm ─────────────────────────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showDeleteConfirm" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
        <div class="relative bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl w-full max-w-sm p-6 text-center">
          <div class="w-12 h-12 bg-red-50 dark:bg-red-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <Trash2 class="w-6 h-6 text-red-500" />
          </div>
          <h4 class="font-black text-slate-800 dark:text-white mb-2">Supprimer ce webhook ?</h4>
          <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">Cette action supprimera aussi tous les logs associés. Irréversible.</p>
          <div class="flex gap-3">
            <button @click="showDeleteConfirm = false" class="flex-1 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Annuler</button>
            <button @click="deleteZap" :disabled="deleting" class="flex-1 py-2.5 rounded-xl bg-red-500 hover:bg-red-600 text-white text-sm font-black transition-colors disabled:opacity-60">
              <Loader2 v-if="deleting" class="w-4 h-4 animate-spin mx-auto" />
              <span v-else>Supprimer</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Plus, Trash2, Pencil, X, Save, Loader2, Zap, CheckCircle2, AlertCircle, ScrollText, ToggleLeft, ToggleRight, Lock } from 'lucide-vue-next';

const BASE = '/wp-json/my-easy-compta/v1/webhooks';
const nonce = () => window.myEasyComptaAdmin?.nonce || '';

const headers = () => ({ 'Content-Type': 'application/json', 'X-WP-Nonce': nonce() });
const api = async (url, opts = {}) => {
  const r = await fetch(url, { headers: headers(), ...opts });
  return r.json();
};

// ── State ───────────────────────────────────────────────────────────────────
const webhooks     = ref([]);
const loading      = ref(false);
const saving       = ref(false);
const deleting     = ref(false);
const testing      = ref(null);
const showModal    = ref(false);
const showLogs     = ref(false);
const showDeleteConfirm = ref(false);
const editingId    = ref(null);
const deletingId   = ref(null);
const selectedZap = ref(null);
const testResults  = ref({});
const eventsLoading = ref(false);
const availableEvents = ref([]);
const logsData     = ref({ logs: [], total: 0, pages: 1 });
const logsLoading  = ref(false);
const logsPage     = ref(1);

const form = ref({ name: '', url: '', events: [], secret: '', active: true });

// ── Computed ─────────────────────────────────────────────────────────────────
const groupedEvents = computed(() => {
  const groups = {};
  for (const evt of availableEvents.value) {
    if (!groups[evt.group]) groups[evt.group] = [];
    groups[evt.group].push(evt);
  }
  return groups;
});

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(() => {
  fetchZaps();
  fetchEvents();
});

// ── Methods ───────────────────────────────────────────────────────────────────
async function fetchZaps() {
  loading.value = true;
  try {
    const data = await api(BASE);
    webhooks.value = Array.isArray(data) ? data : [];
  } finally {
    loading.value = false;
  }
}

async function fetchEvents() {
  eventsLoading.value = true;
  try {
    const data = await api(`${BASE}/events`);
    availableEvents.value = Array.isArray(data) ? data : [];
  } finally {
    eventsLoading.value = false;
  }
}

function openCreate() {
  editingId.value = null;
  form.value = { name: '', url: '', events: [], secret: '', active: true };
  showModal.value = true;
}

function openEdit(wh) {
  editingId.value = wh.id;
  form.value = { name: wh.name, url: wh.url, events: [...wh.events], secret: '', active: wh.active };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingId.value = null;
}

async function save() {
  if (!form.value.name || !form.value.url || !form.value.events.length) return;
  saving.value = true;
  try {
    const url    = editingId.value ? `${BASE}/${editingId.value}` : BASE;
    const method = editingId.value ? 'PUT' : 'POST';
    const data   = await api(url, { method, body: JSON.stringify({ ...form.value, active: form.value.active ? 1 : 0 }) });
    if (data?.id) {
      if (editingId.value) {
        const idx = webhooks.value.findIndex(w => w.id === editingId.value);
        if (idx !== -1) webhooks.value[idx] = data;
      } else {
        webhooks.value.unshift(data);
      }
      closeModal();
    }
  } finally {
    saving.value = false;
  }
}

function confirmDelete(wh) {
  deletingId.value = wh.id;
  showDeleteConfirm.value = true;
}

async function deleteZap() {
  deleting.value = true;
  try {
    await api(`${BASE}/${deletingId.value}`, { method: 'DELETE' });
    webhooks.value = webhooks.value.filter(w => w.id !== deletingId.value);
    showDeleteConfirm.value = false;
  } finally {
    deleting.value = false;
  }
}

async function toggleActive(wh) {
  const updated = await api(`${BASE}/${wh.id}`, { method: 'PUT', body: JSON.stringify({ active: wh.active ? 0 : 1 }) });
  if (updated?.id) {
    const idx = webhooks.value.findIndex(w => w.id === wh.id);
    if (idx !== -1) webhooks.value[idx] = updated;
  }
}

async function testZap(wh) {
  testing.value = wh.id;
  delete testResults.value[wh.id];
  try {
    const result = await api(`${BASE}/${wh.id}/test`, { method: 'POST' });
    testResults.value = { ...testResults.value, [wh.id]: result };
    setTimeout(() => {
      const r = { ...testResults.value };
      delete r[wh.id];
      testResults.value = r;
    }, 6000);
  } finally {
    testing.value = null;
  }
}

function openLogs(wh) {
  selectedZap.value = wh;
  logsPage.value = 1;
  logsData.value = { logs: [], total: 0, pages: 1 };
  showLogs.value = true;
  fetchLogs();
}

async function fetchLogs() {
  logsLoading.value = true;
  try {
    const data = await api(`${BASE}/${selectedZap.value.id}/logs?page=${logsPage.value}`);
    logsData.value = data;
  } finally {
    logsLoading.value = false;
  }
}

async function clearLogs() {
  await api(`${BASE}/${selectedZap.value.id}/logs`, { method: 'DELETE' });
  logsData.value = { logs: [], total: 0, pages: 1 };
}

function formatDate(dt) {
  if (!dt) return '';
  const d = new Date(dt.replace(' ', 'T') + 'Z');
  return d.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>
