<template>
  <dialog :open="showModal" :class="['fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 w-full h-full border-none m-0 max-w-none max-h-none', showModal ? 'block' : 'hidden']">
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] w-full max-w-5xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col" style="height: 85vh;">

      <!-- Header -->
      <div class="flex items-center justify-between px-8 py-6 border-b border-slate-100 dark:border-slate-800 shrink-0">
          <h3 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-3">
              <div class="w-9 h-9 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                  <Map class="w-5 h-5 text-purple-600" />
              </div>
              {{ translations.clients_map || 'Carte des clients' }}
              <span class="text-sm font-bold text-slate-400 bg-slate-50 dark:bg-slate-800 px-3 py-1 rounded-xl">
                  {{ locatedCount }} / {{ clients.length }}
              </span>
          </h3>
          <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl">
              <X class="w-6 h-6" />
          </button>
      </div>

      <!-- No located clients -->
      <div v-if="locatedClients.length === 0" class="flex-1 flex items-center justify-center">
          <div class="text-center space-y-3 p-8">
              <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto">
                  <MapPin class="w-8 h-8 text-slate-300" />
              </div>
              <h4 class="text-lg font-black text-slate-900 dark:text-white">{{ translations.no_located_clients || 'Aucun client localisé' }}</h4>
              <p class="text-slate-500 text-sm max-w-xs">{{ translations.no_located_clients_hint || 'Ajoutez une adresse complète à vos clients pour les voir apparaître sur la carte.' }}</p>
          </div>
      </div>

      <!-- Map container (always in DOM when clients have addresses so ref is available) -->
      <div v-else class="flex-1 relative">
          <div v-if="loading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm gap-3">
              <div class="w-10 h-10 border-4 border-purple-500/30 border-t-purple-500 rounded-full animate-spin"></div>
              <p class="text-sm font-bold text-slate-400">{{ translations.loading || 'Chargement...' }}</p>
          </div>
          <div ref="mapContainer" class="w-full h-full"></div>
      </div>

    </div>
  </dialog>
</template>

<script setup>
import { ref, computed, watch, onUnmounted, nextTick } from 'vue';
import { X, Map, MapPin } from 'lucide-vue-next';

const props = defineProps({
    showModal: Boolean,
    clients: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const mapContainer = ref(null);
const loading = ref(false);
let mapInstance = null;
let leafletLoaded = false;
let L = null;

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

const locatedClients = computed(() =>
    props.clients.filter(c => c.address || c.city || c.postal_code)
);

const locatedCount = ref(0);

const initMap = async () => {
    loading.value = true;

    try {
        if (!leafletLoaded) {
            L = (await import('leaflet')).default;

            // Fix default marker icon paths broken by bundlers
            delete L.Icon.Default.prototype._getIconUrl;
            L.Icon.Default.mergeOptions({
                iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            });
            leafletLoaded = true;
        }

        if (mapInstance) {
            mapInstance.remove();
            mapInstance = null;
        }

        loading.value = false;
        await nextTick();

        if (!mapContainer.value) return;
        mapInstance = L.map(mapContainer.value).setView([46.603354, 1.888334], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 18,
        }).addTo(mapInstance);

        const geocodeAndPlot = async () => {
            const toGeocode = locatedClients.value;
            let count = 0;
            const bounds = [];

            for (const client of toGeocode) {
                const q = [client.address, client.postal_code, client.city, client.country].filter(Boolean).join(' ');
                if (!q) continue;

                try {
                    const res = await fetch(
                        `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=1`,
                        { headers: { 'Accept-Language': 'fr' } }
                    );
                    const results = await res.json();
                    if (results.length > 0) {
                        const { lat, lon } = results[0];
                        const latlng = [parseFloat(lat), parseFloat(lon)];
                        bounds.push(latlng);

                        const popup = `
                            <div style="font-family:system-ui;min-width:160px;padding:4px">
                                <p style="font-weight:900;font-size:14px;margin:0 0 4px">${client.company_name || client.manager_name || '—'}</p>
                                <p style="font-size:12px;color:#888;margin:0">${[client.address, client.city].filter(Boolean).join(', ')}</p>
                                ${client.email ? `<p style="font-size:12px;color:#7c3aed;margin:4px 0 0">${client.email}</p>` : ''}
                            </div>`;

                        L.marker(latlng).addTo(mapInstance).bindPopup(popup);
                        count++;
                    }
                } catch {
                    // skip client if geocoding fails
                }

                // Nominatim rate limit: 1 req/s
                await new Promise(r => setTimeout(r, 1100));
            }

            locatedCount.value = count;

            if (bounds.length > 1) {
                mapInstance.fitBounds(bounds, { padding: [40, 40] });
            } else if (bounds.length === 1) {
                mapInstance.setView(bounds[0], 13);
            }
        };

        await geocodeAndPlot();
    } catch (e) {
        loading.value = false;
    }
};

watch(() => props.showModal, async (val) => {
    if (val) {
        locatedCount.value = 0;
        await nextTick();
        await initMap();
    } else {
        if (mapInstance) {
            mapInstance.remove();
            mapInstance = null;
        }
    }
});

onUnmounted(() => {
    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
    }
});
</script>

<style>
/* Leaflet CSS must be loaded globally — imported via dynamic import ensures it */
@import 'leaflet/dist/leaflet.css';
</style>
