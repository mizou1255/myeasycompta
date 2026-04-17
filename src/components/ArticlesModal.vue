<template>
  <dialog :id="modalId" class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200">
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 w-[90vw] max-w-lg shadow-2xl border border-slate-100 dark:border-slate-800 relative overflow-hidden flex flex-col max-h-[85vh]">
          
          <!-- Header -->
          <div class="flex items-center justify-between mb-6 shrink-0">
              <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-3">
                  <Package class="w-6 h-6 text-purple-600" />
                  {{ modalTitle }}
              </h3>
              <button @click="closeModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl">
                  <X class="w-5 h-5" />
              </button>
          </div>

          <!-- List -->
          <div class="overflow-y-auto flex-1 pr-2 -mr-2">
              <div v-if="loading" class="space-y-3">
                  <div v-for="n in 5" :key="n" class="h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl animate-pulse"></div>
              </div>

              <div v-else-if="articles.length === 0" class="text-center py-10">
                  <p class="text-slate-400 font-bold">{{ translations.no_articles_found || 'No article found.' }}</p>
              </div>
              
              <ul v-else class="space-y-3">
                  <li 
                    v-for="item in articles" 
                    :key="item.id"
                    @click="selectItem(item)"
                    class="bg-slate-50 dark:bg-slate-950/50 border border-slate-100 dark:border-slate-800 rounded-2xl p-4 hover:border-purple-500/50 hover:shadow-lg hover:shadow-purple-500/10 cursor-pointer transition-all group"
                  >
                      <div class="flex justify-between items-start mb-1">
                          <span class="font-black text-slate-700 dark:text-slate-200 text-sm group-hover:text-purple-600 transition-colors">{{ item.item_ref || item.ref }}</span>
                          <span class="font-mono text-purple-600 font-bold text-sm bg-purple-100 dark:bg-purple-900/20 px-2 py-1 rounded-lg">
                              {{ item.unit_price || item.price }}
                          </span>
                      </div>
                      <div class="text-slate-900 dark:text-white font-bold text-sm mb-1">{{ item.item_name || item.name }}</div>
                      <div class="text-slate-400 text-xs line-clamp-2">{{ item.item_description || item.description }}</div>
                  </li>
              </ul>
          </div>
      </div>
      <form method="dialog" class="fixed inset-0 z-[-1] cursor-default bg-transparent w-full h-full outline-none" @click="closeModal"></form>
  </dialog>
</template>

<script setup>
import { ref, toRefs, watch, onMounted, computed } from 'vue';
import { X, Package } from 'lucide-vue-next';

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

const props = defineProps({
    showModal: Boolean,
    modalId: String,
    modalTitle: String,
});

const emit = defineEmits(['close', 'select-article']);
const { showModal, modalId } = toRefs(props);
const articles = ref([]);
const loading = ref(false);

watch(showModal, (val) => {
    const el = document.getElementById(modalId.value);
    if(el) {
        if(val && !el.open) {
            el.showModal();
            if(articles.value.length === 0) fetchArticles();
        }
        if(!val && el.open) el.close();
    }
});

const closeModal = () => {
    emit('close');
};

const selectItem = (item) => {
    emit('select-article', item);
    closeModal();
};

const fetchArticles = async () => {
    loading.value = true;
    try {
        // Legacy used /wp-json/my-easy-compta/v1/articles? Or /items?
        // In legacy code it was /wp-json/my-easy-compta/v1/articles
        // QuoteViewDetail passes 'item' with ref, name, price.
        // Assuming the endpoint returns array of objects with these keys.
        const res = await fetch("/wp-json/my-easy-compta/v1/articles", {
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        articles.value = data;
    } catch (e) {} finally { loading.value = false; }
};

onMounted(() => {
    // Optional pre-fetch? No, fetch on open.
});
</script>