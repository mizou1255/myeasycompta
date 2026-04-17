<template>
  <div class="space-y-6">

    <!-- Header + New button -->
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h2 class="text-xl font-black text-slate-900 dark:text-white">Mes entreprises</h2>
        <p class="text-sm text-slate-500 mt-1">Gérez plusieurs entités légales depuis la même interface.</p>
      </div>
      <button @click="openNew" class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold text-sm transition-all shadow-lg shadow-purple-500/20">
        <Plus class="w-4 h-4" /> Nouvelle entreprise
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <Loader2 class="w-8 h-8 animate-spin text-purple-500" />
    </div>

    <!-- Entity cards -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="entity in entities" :key="entity.id"
        :class="['rounded-2xl border p-5 flex flex-col gap-4 transition-all', entity.is_active ? 'border-purple-500/60 bg-purple-500/5' : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800']"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-black text-base"
                 :style="{ background: entity.pdf_color || '#7c3aed' }">
              {{ entity.name.charAt(0).toUpperCase() }}
            </div>
            <div>
              <div class="font-black text-slate-900 dark:text-white text-sm">{{ entity.name }}</div>
              <div v-if="entity.is_active" class="text-[10px] font-bold text-purple-500 uppercase tracking-wider">Active</div>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <button @click="switchEntity(entity.id)" :disabled="entity.is_active"
              class="p-2 rounded-lg text-slate-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/30 disabled:opacity-30 transition-all" title="Activer">
              <CheckCircle class="w-4 h-4" />
            </button>
            <button @click="openEdit(entity)" class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-all" title="Modifier">
              <Pencil class="w-4 h-4" />
            </button>
            <button @click="confirmDelete(entity)" :disabled="entity.is_active || entities.length <= 1"
              class="p-2 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 disabled:opacity-30 transition-all" title="Supprimer">
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
        <div class="text-xs text-slate-500 space-y-0.5">
          <div v-if="entity.siret">SIRET : {{ entity.siret }}</div>
          <div v-if="entity.email">{{ entity.email }}</div>
        </div>
      </div>
    </div>

    <!-- Entity form modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
      <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-8">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-black text-slate-900 dark:text-white">{{ editingId ? 'Modifier l\'entreprise' : 'Nouvelle entreprise' }}</h3>
          <button @click="closeModal" class="p-2 rounded-xl text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2">
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Nom de l'entreprise *</label>
            <input v-model="form.name" type="text" class="ecwp-input w-full" placeholder="Ma Société SAS" required />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Email</label>
            <input v-model="form.email" type="email" class="ecwp-input w-full" placeholder="contact@masociete.fr" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Téléphone</label>
            <input v-model="form.phone" type="text" class="ecwp-input w-full" />
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Adresse</label>
            <input v-model="form.address" type="text" class="ecwp-input w-full" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Ville</label>
            <input v-model="form.city" type="text" class="ecwp-input w-full" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Code postal</label>
            <input v-model="form.postal_code" type="text" class="ecwp-input w-full" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Pays</label>
            <input v-model="form.country" type="text" class="ecwp-input w-full" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">SIRET</label>
            <input v-model="form.siret" type="text" class="ecwp-input w-full" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">N° TVA</label>
            <input v-model="form.vat_number" type="text" class="ecwp-input w-full" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">IBAN</label>
            <input v-model="form.iban" type="text" class="ecwp-input w-full" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">BIC</label>
            <input v-model="form.bic" type="text" class="ecwp-input w-full" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Site web</label>
            <input v-model="form.website" type="url" class="ecwp-input w-full" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">Couleur PDF</label>
            <div class="flex items-center gap-3">
              <input v-model="form.pdf_color" type="color" class="w-10 h-10 rounded-lg cursor-pointer border border-slate-200 dark:border-slate-700" />
              <span class="text-sm text-slate-500 font-mono">{{ form.pdf_color }}</span>
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-600 dark:text-slate-400 mb-1.5 uppercase tracking-wider">URL Logo</label>
            <input v-model="form.logo_url" type="url" class="ecwp-input w-full" placeholder="https://..." />
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
          <button @click="closeModal" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">Annuler</button>
          <button @click="saveEntity" :disabled="saving" class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-bold text-sm transition-all disabled:opacity-50">
            <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
            {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <div v-if="toast.visible" class="fixed bottom-8 right-8 z-[9999] animate-in fade-in slide-in-from-bottom-8 duration-300">
      <div :class="['flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-2xl border', toast.type === 'success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
        <span class="font-bold text-sm">{{ toast.message }}</span>
      </div>
    </div>

  </div>
</template>

<script>
import { Plus, Loader2, Pencil, Trash2, CheckCircle, X } from 'lucide-vue-next';

const BLANK_FORM = () => ({
  name: '', email: '', phone: '', address: '', city: '',
  postal_code: '', country: '', siret: '', vat_number: '',
  iban: '', bic: '', website: '', logo_url: '', pdf_color: '#7c3aed',
});

export default {
  name: 'Entities',
  components: { Plus, Loader2, Pencil, Trash2, CheckCircle, X },

  data() {
    return {
      loading: true,
      saving: false,
      entities: [],
      showModal: false,
      editingId: null,
      form: BLANK_FORM(),
      toast: { visible: false, message: '', type: 'success' },
    };
  },

  mounted() {
    this.fetchEntities();
  },

  methods: {
    api(path, opts = {}) {
      return fetch(`/wp-json/my-easy-compta/v1${path}`, {
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin?.nonce },
        ...opts,
      }).then(r => r.json());
    },

    async fetchEntities() {
      this.loading = true;
      const data = await this.api('/entities');
      this.entities = Array.isArray(data) ? data : [];
      this.loading = false;
    },

    openNew() {
      this.editingId = null;
      this.form = BLANK_FORM();
      this.showModal = true;
    },

    openEdit(entity) {
      this.editingId = entity.id;
      this.form = { ...BLANK_FORM(), ...entity };
      this.showModal = true;
    },

    closeModal() {
      this.showModal = false;
      this.editingId = null;
    },

    async saveEntity() {
      if (!this.form.name.trim()) return;
      this.saving = true;
      const method = this.editingId ? 'PUT' : 'POST';
      const path   = this.editingId ? `/entities/${this.editingId}` : '/entities';
      const res = await this.api(path, { method, body: JSON.stringify(this.form) });
      this.saving = false;
      if (res.code) {
        this.showToast(res.message || 'Erreur', 'error');
      } else {
        this.showToast(res.message || 'Enregistré', 'success');
        this.closeModal();
        await this.fetchEntities();
      }
    },

    async switchEntity(id) {
      const res = await this.api('/entities/switch', { method: 'POST', body: JSON.stringify({ id }) });
      if (!res.code) {
        this.showToast('Entreprise active changée', 'success');
        // Update JS global so other components react without reload
        if (window.myEasyComptaAdmin) window.myEasyComptaAdmin.currentEntityId = id;
        await this.fetchEntities();
        // Reload to refresh all cached data
        setTimeout(() => window.location.reload(), 800);
      }
    },

    async confirmDelete(entity) {
      if (!confirm(`Supprimer l'entreprise "${entity.name}" ? Cette action est irréversible.`)) return;
      const res = await this.api(`/entities/${entity.id}`, { method: 'DELETE' });
      if (res.code) {
        this.showToast(res.message || 'Erreur', 'error');
      } else {
        this.showToast('Entreprise supprimée', 'success');
        await this.fetchEntities();
      }
    },

    showToast(message, type = 'success') {
      this.toast = { visible: true, message, type };
      setTimeout(() => { this.toast.visible = false; }, 3000);
    },
  },
};
</script>
