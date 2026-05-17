<template>
  <MainLayout :title="quote.quote_number || 'Chargement...'" :subtitle="quote.created_at ? `${translations.created_at}: ${quote.created_at}` : ''">
    
    <!-- Toast -->
    <div v-if="toast.visible" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] toast-animate-in">
      <div :class="['flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md', toast.type === 'success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
        <component :is="toast.type === 'success' ? 'CheckCircle2' : 'AlertCircle'" class="w-6 h-6" />
        <span class="font-bold text-sm">{{ toast.message }}</span>
        <button @click="toast.visible = false" class="ml-2 hover:bg-white/20 p-1 rounded-full transition-colors"><X class="w-4 h-4" /></button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="fixed inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center">
       <div class="w-10 h-10 border-4 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Remove Item Modal -->
    <remove-modal
      modal-id="modal_item_remove"
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_delete_it"
      :cancelText="translations.cancel"
      @confirm="removeItem"
      @cancel="showRemoveModal = false"
    />

    <!-- Article Modal -->
    <article-modal
      :show-modal="showArticlesModal"
      modal-id="modal_articles"
      :modal-title="translations.select"
      @select-article="applySelectedArticle"
      @close="showArticlesModal = false"
    />

    <div class="space-y-8">
       <!-- NavBar -->
       <QuoteNavBar
          :quoteInfo="quote"
          :emailActive="isEmailActive"
          :emailSubject="settings.quote_email_subject"
          :emailContent="settings.quote_email_content"
          :advanceActive="settings.easy_compta_advance_addon_active"
          :smsActive="isSmsActive"
          :currency="default_currency_symbol"
          :noItems="no_items"
          @show-toast="showToast"
          @refresh="fetchQuote"
       />

       <!-- Expired Alert -->
       <div v-if="isQuoteExpired && quote.status == 'pending'" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 rounded-2xl p-6 flex items-center gap-4 text-amber-700 dark:text-amber-400">
          <AlertTriangle class="w-6 h-6 stroke-2" />
          <h3 class="font-bold">{{ translations.quote_expired }}</h3>
      </div>

      <!-- Main Content -->
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800 relative overflow-hidden" id="quote-content">
          
          <!-- Watermark -->
          <div v-if="settings.easy_compta_signature_addon_active && quote.signed == 1" class="absolute inset-0 flex items-center justify-center opacity-10 pointer-events-none z-0 overflow-hidden">
             <span class="transform -rotate-45 text-9xl font-black text-emerald-500 border-8 border-emerald-500 p-8 rounded-3xl uppercase tracking-widest">{{ translations.signed }}</span>
          </div>

          <!-- Header -->
          <div class="flex flex-col md:flex-row justify-between gap-10 mb-12 border-b border-slate-100 dark:border-slate-800 pb-12 relative z-10">
               <div class="flex-1">
                   <img v-if="settings.logo_url" :src="settings.logo_url" :style="{ width: settings.logo_width + 'px' }" class="max-w-full h-auto object-contain rounded-xl" alt="Logo" />
               </div>
               <div class="text-right space-y-3">
                   <h2 class="text-4xl font-black text-purple-600">{{ quote.quote_number }}</h2>
                   <div class="space-y-1">
                      <p class="text-slate-500 font-bold text-sm"><span class="text-slate-900 dark:text-white">{{ translations.created_at }}:</span> {{ quote.created_at }}</p>
                      <p class="text-slate-500 font-bold text-sm"><span class="text-slate-900 dark:text-white">{{ translations.due_date }}:</span> {{ quote.due_date }}</p>
                      <p class="text-slate-500 font-bold text-sm"><span class="text-slate-900 dark:text-white">{{ translations.provisional_date }}:</span> {{ quote.provisional_start_date }}</p>
                   </div>
                   <div class="pt-2 flex items-center justify-end gap-3">
                       <span class="text-slate-900 dark:text-white font-black text-sm uppercase tracking-widest">{{ translations.status }}:</span>
                        <span :class="[
                           'px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest',
                           quote.status == 'draft' ? 'bg-slate-100 text-slate-500' : 
                           (quote.status == 'pending' ? 'bg-amber-100 text-amber-600' : 
                           (quote.status == 'approved' ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'))
                       ]">
                           {{ translations[quote.status] || quote.status }}
                       </span>
                   </div>
               </div>
          </div>

          <!-- Addresses -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-12 relative z-10">
              <div class="bg-slate-50 dark:bg-slate-950/50 rounded-3xl p-8 border border-slate-100 dark:border-slate-800">
                  <div class="flex items-center gap-3 mb-6">
                      <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center">
                          <User class="w-5 h-5" />
                      </div>
                      <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ translations.bill_to }}</h3>
                  </div>
                  <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-4">{{ client_detail.company_name }}</h4>
                  <div class="text-slate-500 space-y-1 text-sm font-medium">
                      <p>{{ client_detail.address }}</p>
                      <p>{{ client_detail.postal_code }}, {{ client_detail.city }}</p>
                      <p>{{ client_detail.country }}</p>
                      <a v-if="client_detail.phone" :href="'tel:' + client_detail.phone" class="text-purple-600 hover:underline mt-2 block">{{ client_detail.phone }}</a>
                  </div>
              </div>

              <div class="bg-slate-50 dark:bg-slate-950/50 rounded-3xl p-8 border border-slate-100 dark:border-slate-800">
                   <div class="flex items-center gap-3 mb-6">
                      <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center">
                          <Building class="w-5 h-5" />
                      </div>
                      <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest">{{ translations.received_from }}</h3>
                  </div>
                  <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-4">{{ settings.company_name }}</h4>
                  <div class="text-slate-500 space-y-1 text-sm font-medium">
                      <p>{{ settings.company_address }}</p>
                      <p>{{ settings.postal_code }}, {{ settings.city }}</p>
                      <p>{{ settings.country }}</p>
                      <a v-if="settings.company_phone" :href="'tel:' + settings.company_phone" class="text-indigo-600 hover:underline mt-2 block">{{ settings.company_phone }}</a>
                  </div>
              </div>
          </div>

          <!-- Items Table -->
          <div class="relative z-10 mb-10 overflow-x-auto rounded-3xl border border-slate-200 dark:border-slate-800">
             <table class="w-full min-w-[700px] text-left border-collapse">
                 <thead>
                     <tr class="bg-slate-50 dark:bg-slate-950 text-xs font-black text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800">
                         <th class="p-4 w-12"></th>
                         <th class="p-4">{{ translations.item_ref }}</th>
                         <th class="p-4">{{ translations.item_name }}</th>
                         <th class="p-4">{{ translations.description }}</th>
                         <th class="p-4 text-center">{{ translations.quantity }}</th>
                         <th class="p-4 text-right">{{ translations.unit_price }}</th>
                         <th v-if="settings.vat_active == 1" class="p-4 text-center">{{ translations.vat }}</th>
                         <th class="p-4 text-center">{{ translations.discount }}</th>
                         <th class="p-4 text-right">{{ translations.total }}</th>
                         <th class="p-4 text-center w-24"></th>
                     </tr>
                 </thead>
                 <tbody id="items-body">
                      <tr v-for="item in quoteItems" :key="item.id" :data-id="item.id"
                          :class="['group border-b border-slate-100 dark:border-slate-800 last:border-0 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors',
                                   isItemDeclined(item) ? 'opacity-40' : '']">
                           <td class="p-4 text-center text-slate-300 cursor-move drag-handle group-hover:text-slate-500"><GripVertical class="w-4 h-4" /></td>
                           <td class="p-4 text-sm font-bold text-slate-600 dark:text-slate-300">{{ item.item_ref }}</td>
                           <td class="p-4 text-sm font-bold text-slate-900 dark:text-white">
                               <div class="flex items-center gap-1 flex-wrap mb-1">
                                   <div v-if="item.category_name" class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[10px] font-bold">{{ item.category_name }}</div>
                                   <div v-if="item.is_optional == 1 && !isItemDeclined(item)" class="inline-block px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[10px] font-bold">Optionnel</div>
                               <div v-if="isItemDeclined(item)" class="inline-block px-2 py-0.5 rounded-md bg-rose-100 dark:bg-rose-900/30 text-rose-500 dark:text-rose-400 text-[10px] font-bold">Non retenu</div>
                               </div>
                               <div>{{ item.item_name }}</div>
                           </td>
                           <td class="p-4 text-sm text-slate-500 dark:text-slate-400 max-w-[200px] truncate" :title="stripHtml(item.item_description)">{{ stripHtml(item.item_description) }}</td>
                           <td class="p-4 text-sm font-bold text-center text-slate-900 dark:text-white">{{ item.quantity }}</td>
                           <td class="p-4 text-sm font-mono text-right text-slate-600 dark:text-slate-300">
                               {{ item.unit_price }}
                               <span v-if="default_currency_symbol == client_currency">{{ default_currency_symbol }}</span>
                               <span v-else>{{ client_currency }}</span>
                           </td>
                           <td v-if="settings.vat_active == 1" class="p-4 text-sm text-center text-slate-500">{{ item.vat_rate }}%</td>
                           <td class="p-4 text-sm text-center">
                               <span v-if="item.discount > 0" class="text-rose-500 font-bold">-{{ item.discount }}%</span>
                               <span v-else class="text-slate-300">-</span>
                           </td>
                           <td class="p-4 text-sm font-bold font-mono text-right text-slate-900 dark:text-white">
                               <span :class="isItemDeclined(item) ? 'line-through text-slate-400' : ''">
                                   {{ item.total_amount }}
                                   <span v-if="default_currency_symbol == client_currency">{{ default_currency_symbol }}</span>
                                   <span v-else>{{ client_currency }}</span>
                               </span>
                           </td>
                           <td class="p-4 text-center">
                               <div v-if="quote.status == 'draft' || quote.status == 'pending'" class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                   <button @click="editItem(item)" class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"><Pencil class="w-4 h-4" /></button>
                                   <button @click="confirmRemoveItem(item)" class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors"><Trash2 class="w-4 h-4" /></button>
                               </div>
                           </td>
                      </tr>
                      
                       <!-- Empty State button inside table or below -->
                       <tr v-if="quote.status == 'draft' || quote.status == 'pending'">
                           <td colspan="10" class="p-6 text-center bg-slate-50/50 dark:bg-slate-950/30">
                               <button 
                                  @click="showAddItemModal = true" 
                                  class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-black text-xs uppercase tracking-widest hover:border-purple-500 hover:text-purple-600 transition-all shadow-sm"
                               >
                                   <Plus class="w-4 h-4" />
                                   {{ translations.add_item || 'Ajouter une ligne' }}
                               </button>
                           </td>
                       </tr>
                  </tbody>
              </table>
          </div>

          <!-- Totals -->
          <div class="flex flex-col items-end relative z-10">
             <div class="w-full md:w-1/3 space-y-4 bg-slate-50 dark:bg-slate-950/50 rounded-3xl p-8 border border-slate-100 dark:border-slate-800">
                 <template v-if="settings.vat_active == 1">
                     <div class="flex justify-between items-center text-slate-500 font-bold text-sm">
                         <span>{{ translations.subtotal }}:</span>
                         <span class="font-mono text-slate-900 dark:text-white">
                             <span v-if="totalAmount !== totalAmountWithoutDiscount" class="line-through text-xs text-slate-300 mr-2">{{ totalAmountWithoutDiscount }}</span>
                             {{ totalAmount }}
                         </span>
                     </div>
                     <div v-for="rate in getUniqueVATRates()" :key="rate" class="flex justify-between items-center text-slate-500 font-bold text-sm">
                         <span>{{ translations.tax }} ({{ rate }}%):</span>
                         <span class="font-mono text-slate-900 dark:text-white">{{ calculateVATForRate(rate) }}</span>
                     </div>
                     <div class="h-px bg-slate-200 dark:bg-slate-800 my-4"></div>
                 </template>

                 <div class="flex justify-between items-center text-lg font-black text-slate-900 dark:text-white">
                     <span>{{ translations.total }}:</span>
                     <div class="text-right">
                         <span v-if="acceptedTotalWithVAT !== null" class="block line-through text-sm font-mono text-slate-300">{{ calculateTotalAmountWithVAT() }} {{ client_currency }}</span>
                         <span class="font-mono" :class="acceptedTotalWithVAT !== null ? 'text-emerald-600' : 'text-purple-600'">
                             {{ acceptedTotalWithVAT !== null ? acceptedTotalWithVAT : calculateTotalAmountWithVAT() }} {{ client_currency }}
                         </span>
                     </div>
                 </div>
                 <div v-if="acceptedTotalWithVAT !== null" class="text-[10px] text-emerald-500 font-bold text-right uppercase tracking-widest">Montant accepté par le client</div>

                 <div v-if="client_currency != default_currency_symbol" class="pt-4 border-t border-slate-200 dark:border-slate-800">
                      <div class="flex justify-between items-center text-xs text-slate-400 mb-2">
                           <span>{{ translations.exchange_rate }}:</span>
                           <span class="font-mono">{{ quote.exchange_rate }}</span>
                      </div>
                      <div class="flex justify-between items-center text-base font-bold text-slate-700 dark:text-slate-300">
                          <span>{{ translations.total }} {{ default_currency_symbol }}:</span>
                          <span class="font-mono">{{ totalAmountDefaultCurrency }}{{ default_currency_symbol }}</span>
                      </div>
                 </div>
             </div>
          </div>

          <!-- Signature Image -->
          <div v-if="settings.easy_compta_signature_addon_active && quote.signed == 1 && quote.file_sign" class="mt-8 flex justify-end relative z-10">
               <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 rotate-3 transform hover:rotate-0 transition-transform duration-300">
                   <img :src="signatureImageUrl" class="max-h-24 opacity-80" alt="Signature" />
                   <p class="text-[10px] text-center text-slate-400 font-bold uppercase tracking-widest mt-2">Signature</p>
               </div>
          </div>

      </div>

      <!-- Online Quote Panel — locked (addon inactive) -->
      <div v-if="!onlineQuoteAddonActive" class="relative rounded-[2.5rem] overflow-hidden select-none">
        <!-- Blur overlay -->
        <div class="absolute inset-0 z-10 bg-white/60 dark:bg-slate-900/70 backdrop-blur-[3px] flex flex-col items-center justify-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-white dark:bg-slate-800 shadow-xl flex items-center justify-center">
            <Lock class="w-7 h-7 text-slate-400" />
          </div>
          <div class="text-center px-6">
            <p class="font-black text-slate-700 dark:text-slate-300 text-sm">Addon « Devis en ligne » requis</p>
            <p class="text-xs text-slate-400 mt-1">Activez ce module pour partager vos devis et recevoir les acceptations en ligne.</p>
          </div>
          <router-link to="/addon/online_quote" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold text-sm shadow-lg hover:shadow-purple-500/30 transition-all">
            <Zap class="w-4 h-4" /> Découvrir l'addon
          </router-link>
        </div>
        <!-- Faded content (non-interactive) -->
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden pointer-events-none opacity-40">
          <div class="px-8 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
              <Globe class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
              <h3 class="font-black text-slate-900 dark:text-white text-sm">Devis interactif en ligne</h3>
              <p class="text-[11px] text-slate-400">Partagez ce devis — le client peut accepter ou refuser sans compte</p>
            </div>
          </div>
          <div class="p-8 flex flex-col items-center gap-4 py-6">
            <p class="text-sm text-slate-500 text-center">Aucun lien de partage actif.<br>Générez un lien sécurisé à envoyer à votre client.</p>
            <div class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 text-white rounded-2xl font-bold text-sm">
              <Link class="w-4 h-4" /> Générer le lien
            </div>
          </div>
        </div>
      </div>

      <!-- Online Quote Panel (addon active) -->
      <div v-if="onlineQuoteAddonActive" class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
        <div class="px-8 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
              <Globe class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
              <h3 class="font-black text-slate-900 dark:text-white text-sm">Devis interactif en ligne</h3>
              <p class="text-[11px] text-slate-400">Partagez ce devis — le client peut accepter ou refuser sans compte</p>
            </div>
          </div>
          <span v-if="oqStatus" :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider',
            oqStatus.action === 'accepted' ? 'bg-emerald-100 text-emerald-600' :
            oqStatus.action === 'rejected' ? 'bg-rose-100 text-rose-600' :
            oqStatus.viewed_at             ? 'bg-blue-100 text-blue-600' :
                                             'bg-amber-100 text-amber-600']">
            {{ oqStatus.action === 'accepted' ? '✓ Accepté' : oqStatus.action === 'rejected' ? '✗ Refusé' : oqStatus.viewed_at ? 'Vu' : 'En attente' }}
          </span>
        </div>

        <div class="p-8">
          <!-- No link yet -->
          <div v-if="!oqStatus && !oqLoading" class="flex flex-col items-center gap-4 py-4">
            <p class="text-sm text-slate-500 text-center">Aucun lien de partage actif.<br>Générez un lien sécurisé à envoyer à votre client.</p>
            <button @click="oqGenerate" :disabled="oqGenerating"
              class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold text-sm shadow transition-all disabled:opacity-50">
              <Loader2 v-if="oqGenerating" class="w-4 h-4 animate-spin" />
              <Link v-else class="w-4 h-4" />
              {{ oqGenerating ? 'Génération...' : 'Générer le lien' }}
            </button>
          </div>

          <!-- Loading -->
          <div v-if="oqLoading" class="flex justify-center py-4"><Loader2 class="w-6 h-6 text-indigo-600 animate-spin" /></div>

          <!-- Link active -->
          <div v-if="oqStatus && !oqLoading" class="space-y-4">
            <!-- URL field -->
            <div class="flex items-center gap-2">
              <input :value="oqStatus.public_url" readonly
                class="flex-1 px-4 py-2.5 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm font-mono text-slate-700 dark:text-slate-300 focus:outline-none" />
              <button @click="oqCopy" class="p-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 hover:text-indigo-600 transition-colors" title="Copier">
                <Copy class="w-4 h-4" />
              </button>
              <a :href="oqStatus.public_url" target="_blank" class="p-2.5 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 hover:text-indigo-600 transition-colors" title="Ouvrir">
                <ExternalLink class="w-4 h-4" />
              </a>
            </div>

            <!-- Info row -->
            <div class="flex flex-wrap gap-4 text-xs text-slate-400">
              <span v-if="oqStatus.expires_at">Expire le {{ formatOqDate(oqStatus.expires_at) }}</span>
              <span v-if="oqStatus.viewed_at">Vu le {{ formatOqDate(oqStatus.viewed_at) }}</span>
              <span v-if="oqStatus.action_at">{{ oqStatus.action === 'accepted' ? 'Accepté' : 'Refusé' }} le {{ formatOqDate(oqStatus.action_at) }}</span>
            </div>

            <!-- Comment -->
            <div v-if="oqStatus.client_comment" class="rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 px-4 py-3">
              <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Commentaire client</p>
              <p class="text-sm text-slate-700 dark:text-slate-200 italic">"{{ oqStatus.client_comment }}"</p>
            </div>

            <!-- Signature -->
            <div v-if="oqStatus.action === 'accepted' && oqStatus.signature_data" class="rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 px-4 py-3">
              <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Signature électronique</p>
              <img :src="oqStatus.signature_data" alt="Signature client" class="max-h-24 bg-white rounded-xl p-2 border border-slate-100" />
            </div>
            <div v-else-if="oqStatus.action === 'accepted' && oqStatus.has_signature" class="rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 px-4 py-3">
              <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Signature électronique</p>
              <button @click="oqLoadSignature" :disabled="oqLoadingSig"
                class="text-xs text-indigo-600 font-bold hover:underline flex items-center gap-1 disabled:opacity-50">
                <Loader2 v-if="oqLoadingSig" class="w-3 h-3 animate-spin" />
                <span v-else>Afficher la signature</span>
              </button>
            </div>

            <!-- Reset / Revoke -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
              <button v-if="oqStatus.action" @click="oqReset" :disabled="oqResetting"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-colors disabled:opacity-50">
                <Loader2 v-if="oqResetting" class="w-3.5 h-3.5 animate-spin" />
                <RotateCcw v-else class="w-3.5 h-3.5" />
                Demander une nouvelle réponse
              </button>
              <div v-else></div>
              <button @click="oqRevoke" :disabled="oqRevoking"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors disabled:opacity-50">
                <Loader2 v-if="oqRevoking" class="w-3.5 h-3.5 animate-spin" />
                <Trash2 v-else class="w-3.5 h-3.5" />
                Révoquer le lien
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <EditItemModal
      v-if="editItemsModal"
      :show-modal="editItemsModal"
      modal-id="modal_edit_item"
      :modal-title="translations.edit_item"
      :item="selectedItem"
      @close="editItemsModal = false"
      @itemEdited="fetchItems"
    />

    <AddItemModal
      :show-modal="showAddItemModal"
      modal-id="modal_add_item"
      :modal-title="translations.add_item || 'Ajouter un article'"
      :categories="categories"
      :quoteId="quote.id"
      @close="showAddItemModal = false"
      @itemAdded="fetchItems"
    />

    <!-- Notes internes -->
    <div v-if="quote.internal_notes" class="mt-6 bg-amber-50 dark:bg-amber-900/10 rounded-[2.5rem] p-8 border border-amber-100 dark:border-amber-800/30">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
          <StickyNote class="w-5 h-5" />
        </div>
        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">{{ translations.internal_notes || 'Notes internes' }}</h3>
        <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 rounded-lg">Non imprimé</span>
      </div>
      <p class="text-sm text-slate-600 dark:text-slate-300 font-medium whitespace-pre-wrap">{{ quote.internal_notes }}</p>
    </div>
  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick, onUpdated } from 'vue';
import { useRoute } from 'vue-router';
import MainLayout from '@/components/layout/MainLayout.vue';
import QuoteNavBar from "@/components/quotes/NavBar.vue";
import EditItemModal from "@/components/quotes/Modal_Edit_Item.vue";
import AddItemModal from "@/components/quotes/Modal_Add_Item.vue";
import RemoveModal from "@/components/RemoveAlert.vue";
import ArticleModal from "@/components/ArticlesModal.vue";
import Sortable from "sortablejs";
import axios from 'axios';
import { CheckCircle2, AlertCircle, AlertTriangle, User, Building, GripVertical, Pencil, Trash2, List, Plus, X, Globe, Link, Copy, ExternalLink, Loader2, Lock, Zap, StickyNote, RotateCcw } from 'lucide-vue-next';

const route = useRoute();
const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});
const quote = ref({});
const quoteItems = ref([]);
const client_detail = ref({});
const settings = ref({});
const loading = ref(true);
const toast = reactive({ visible: false, message: "", type: "success" });
const no_items = ref(true);
const licenseData = ref(null);

const list_vats = ref([]);
const categories = ref([]);
const default_currency_symbol = ref("");
const client_currency = ref("");

// Modals
const showRemoveModal = ref(false);
const showArticlesModal = ref(false);
const editItemsModal = ref(false);
const selectedItem = ref(null);
const itemToRemove = ref(null);
const showAddItemModal = ref(false);

const checkLicense = async () => {
    try {
        const res = await axios.get('/wp-json/my-easy-compta/v1/license/check-license', {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        if (res.data.success) {
            licenseData.value = res.data.license_data;
        }
} catch (e) {}
};

const checkSlug = (slug) => {
    if (!licenseData.value || !licenseData.value.plugins) return false;
    const plugins = Array.isArray(licenseData.value.plugins) ? licenseData.value.plugins : Object.values(licenseData.value.plugins);
    
    const findMatch = (s) => {
        return plugins.some(p => p.product_slug === s);
    };

    // 1. Exact match
    if (findMatch(slug)) return true;

    // 2. Try variations
    if (slug.startsWith('myeasycompta-')) {
        if (findMatch(slug.replace('myeasycompta-', 'my-easy-compta-'))) return true;
    } else if (slug.startsWith('my-easy-compta-')) {
        if (findMatch(slug.replace('my-easy-compta-', 'myeasycompta-'))) return true;
    }

    // 3. Handle specific known spelling variations
    if (slug.includes('e-mail')) {
        if (findMatch(slug.replace('e-mail', 'email'))) return true;
        if (findMatch(slug.replace('e-mail', 'email').replace('my-easy-compta-', 'myeasycompta-'))) return true;
        if (findMatch(slug.replace('e-mail', 'email').replace('myeasycompta-', 'my-easy-compta-'))) return true;
    }
    if (slug.includes('email') && !slug.includes('e-mail')) {
        if (findMatch(slug.replace('email', 'e-mail'))) return true;
        if (findMatch(slug.replace('email', 'e-mail').replace('my-easy-compta-', 'myeasycompta-'))) return true;
        if (findMatch(slug.replace('email', 'e-mail').replace('myeasycompta-', 'my-easy-compta-'))) return true;
    }

    if (slug.includes('user')) {
        const plural = slug.includes('users') ? slug : slug.replace('user', 'users');
        const singular = slug.includes('users') ? slug.replace('users', 'user') : slug;
        if (findMatch(plural)) return true;
        if (findMatch(singular)) return true;
    }

    return false;
};

const isEmailActive = computed(() => {
    return settings.value.easy_compta_email_addon_active == 1 ? 1 : 0;
});

const isSmsActive = computed(() => {
    return window.myEasyComptaAdmin?.smsAddonActive ? 1 : 0;
});

const isQuoteExpired = computed(() => {
    const today = new Date().getTime();
    const dueDate = quote.value.due_date ? new Date(quote.value.due_date).getTime() : null;
    return dueDate && dueDate < today;
});

const signatureImageUrl = computed(() => {
    return `/wp-json/my-easy-compta/v1/signature-image/${quote.value.file_sign}?_wpnonce=${window.myEasyComptaAdmin.nonce}`;
});

const showToast = (message, type = "success") => {
    toast.message = message;
    toast.type = type;
    toast.visible = true;
    setTimeout(() => toast.visible = false, 3000);
};

// Data Fetching
const fetchSettings = async () => {
    try {
        const res = await fetch("/wp-json/my-easy-compta/v1/settings/get", { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        const data = await res.json();
        settings.value = data;
        
        // Fetch VATs and Categories if settings loaded
        if(data.vat_active) {
            // Assuming we need to fetch VATs list or it's in settings?
            // Legacy code used `list_vats` but didn't show where it came from in the snippet.
            // I'll try to fetch Vats from API.
            fetchVats();
        }
        fetchCategories();
    } catch (e) {}
};

const fetchVats = async () => {
    try {
        const res = await fetch("/wp-json/my-easy-compta/v1/settings/vats", { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        list_vats.value = await res.json();
    } catch (e) {}
};

const fetchCategories = async () => {
    try {
         const res = await fetch("/wp-json/my-easy-compta/v1/categories-articles", { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
         categories.value = await res.json();
    } catch (e) {}
};

const fetchQuote = async () => {
    loading.value = true;
    try {
        const res = await axios.get(`/wp-json/my-easy-compta/v1/quotes/${route.params.id}`, { 
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } 
        });
        const data = res.data;
        if (data.quote) {
            quote.value = data.quote;
            client_detail.value = data.client || {};
        } else {
            quote.value = data; // Fallback if direct object
        }
    } catch(e) { 
        showToast("Erreur lors du chargement des détails", "error");
    } finally {
        loading.value = false;
    }
};

// Client info is now directly included in fetchQuote response
// If currency info is still needed separately:
const maybeFetchCurrency = async () => {
    if (client_detail.value.currency_id) {
        try {
            const res = await axios.get(`/wp-json/my-easy-compta/v1/settings/currency/${client_detail.value.currency_id}`, { 
                headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } 
            });
            client_currency.value = res.data.symbol;
            default_currency_symbol.value = quote.value.currency_symbol || "€";
    } catch (e) {}
    }
};

const fetchItems = async () => {
    try {
        const res = await axios.get(`/wp-json/my-easy-compta/v1/quotes/${route.params.id}/items`, { 
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } 
        });
        const data = res.data;
        if(data.code === 'no_items_found' || !data.length) {
            quoteItems.value = [];
            no_items.value = true;
        } else {
            quoteItems.value = data;
            no_items.value = false;
        }
        loading.value = false;
        nextTick(() => initSortable());
    } catch(e) { 
        quoteItems.value = [];
        no_items.value = true;
        loading.value = false; 
    }
};


// Calculations
const formatCurrency = (val) => {
    return parseFloat(val).toFixed(2);
};

const totalAmountWithoutDiscount = computed(() => {
   const total = quoteItems.value.reduce((acc, item) => acc + (item.quantity * item.unit_price), 0);
   return formatCurrency(total);
});

const totalAmount = computed(() => {
    const total = quoteItems.value.reduce((acc, item) => acc + parseFloat(item.total_amount), 0); // Warning: item.total_amount usually includes tax? Checking legacy code.
    // Legacy computed totalAmount used item.total_price (without vat?) or total_amount?
    // Legacy code: reduce item.total_price.
    // Let's rely on item properties returned by backend or recalculated if needed.
    // Ideally backend gives total_amount = qty * price * discount + vat.
    // Legacy `totalAmount` logic seemed to sum `total_price` which was "After Discount".
    // I will approximate using backend values.
    // Wait, let's recalculate to be safe.
    let sum = 0;
    quoteItems.value.forEach(item => {
        let base = item.quantity * item.unit_price;
        let disc = base * (item.discount / 100);
        sum += (base - disc);
    });
    return formatCurrency(sum);
});

const getUniqueVATRates = () => {
    const rates = new Set();
    quoteItems.value.forEach(item => { if(item.vat_rate) rates.add(item.vat_rate); });
    return Array.from(rates);
};

const calculateVATForRate = (rate) => {
    let vat = 0;
    quoteItems.value.forEach(item => {
        if(item.vat_rate == rate) {
            let base = item.quantity * item.unit_price;
            let disc = base * (item.discount / 100);
            vat += ((base - disc) * rate / 100);
        }
    });
    return formatCurrency(vat);
};

const calculateTotalAmountWithVAT = () => {
    let total = parseFloat(totalAmount.value);
    if(settings.value.vat_active == 1) {
        let vat = 0;
        getUniqueVATRates().forEach(r => vat += parseFloat(calculateVATForRate(r)));
        total += vat;
    }
    return formatCurrency(total);
};

const totalAmountDefaultCurrency = computed(() => {
    const total = parseFloat(calculateTotalAmountWithVAT());
    return formatCurrency(total * (quote.value.exchange_rate || 1));
});


const stripHtml = (html) => {
    if (!html) return '';
    return html.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim();
};

// Actions
const addItem = () => {
    showAddItemModal.value = true;
};

const ShowModalArticles = () => showArticlesModal.value = true;

const applySelectedArticle = (article) => {
    newItem.item_ref = article.reference;
    newItem.item_name = article.name;
    newItem.item_description = article.description;
    newItem.unit_price = article.price; // or ht_price
    newItem.vat_rate = article.tva;
    showArticlesModal.value = false;
};

const editItem = (item) => {
    selectedItem.value = { ...item };
    editItemsModal.value = true;
};

const confirmRemoveItem = (item) => {
    itemToRemove.value = item;
    showRemoveModal.value = true;
};

const removeItem = async () => {
    if(!itemToRemove.value) return;
    showRemoveModal.value = false;
    try {
        await fetch(`/wp-json/my-easy-compta/v1/quotes/element-delete/${itemToRemove.value.id}`, { 
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }, 
            method: 'DELETE' 
        });
        showToast("Supprimé", "success");
        fetchItems();
    } catch (e) {}
};

const updateItemOrder = async (sortedIds) => {
    try {
        const res = await axios.post(`/wp-json/my-easy-compta/v1/quotes/update-quote-items-order`, { order: sortedIds }, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        if (res.data.success) {
            showToast(translations.value.order_updated_successfully || "Ordre mis à jour");
        }
    } catch(e) { 
        showToast("Erreur lors de la mise à jour de l'ordre", "error");
    }
};

const initSortable = () => {
    const el = document.getElementById('items-body');
    if (el) {
        // Destroy existing instance if it exists to avoid duplicates
        const existingInstance = Sortable.get(el);
        if (existingInstance) {
            existingInstance.destroy();
        }

        Sortable.create(el, {
            handle: ".drag-handle",
            animation: 150,
            onEnd: () => {
                const rows = el.querySelectorAll('tr[data-id]');
                const sortedIds = Array.from(rows).map(row => row.getAttribute('data-id'));
                updateItemOrder(sortedIds);
            },
        });
    }
};

// ── Online Quote addon ──────────────────────────────────────────────────────
const onlineQuoteAddonActive = computed(() => !!window.myEasyComptaAdmin?.onlineQuoteAddonActive);
const oqStatus   = ref(null);   // null = no link; object = token data
const oqLoading  = ref(false);
const oqGenerating = ref(false);
const oqRevoking = ref(false);
const oqResetting = ref(false);
const oqLoadingSig = ref(false);

const oqDeclinedIds = computed(() => {
    if (!oqStatus.value?.optional_summary) return new Set();
    return new Set(oqStatus.value.optional_summary.filter(o => !o.selected).map(o => o.id));
});

const isItemDeclined = (item) => item.is_optional == 1 && oqDeclinedIds.value.has(parseInt(item.id));

const acceptedTotalWithVAT = computed(() => {
    if (!oqStatus.value?.action || oqDeclinedIds.value.size === 0) return null;
    let total = 0;
    quoteItems.value.forEach(item => {
        if (!isItemDeclined(item)) {
            let base = item.quantity * item.unit_price;
            let disc = base * (item.discount / 100);
            let lineTotal = base - disc;
            if (settings.value.vat_active == 1) lineTotal += lineTotal * (item.vat_rate / 100);
            total += lineTotal;
        }
    });
    return formatCurrency(total);
});

const oqFetchStatus = async () => {
    if (!window.myEasyComptaAdmin?.onlineQuoteAddonActive) return;
    oqLoading.value = true;
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/quotes/${route.params.id}/share-status`, {
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        oqStatus.value = data.has_link ? data : null;
    } catch (e) {} finally { oqLoading.value = false; }
};

const oqGenerate = async () => {
    oqGenerating.value = true;
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/quotes/${route.params.id}/generate-link`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce, 'Content-Type': 'application/json' },
            body: JSON.stringify({ expires_days: 30 })
        });
        const data = await res.json();
        if (data.public_url) {
            await oqFetchStatus();
            showToast('Lien généré avec succès');
        } else {
            showToast('Erreur lors de la génération', 'error');
        }
    } catch (e) {
        showToast('Erreur lors de la génération', 'error');
    } finally { oqGenerating.value = false; }
};

const oqCopy = async () => {
    if (!oqStatus.value?.public_url) return;
    try {
        await navigator.clipboard.writeText(oqStatus.value.public_url);
        showToast('Lien copié !');
    } catch (e) {
        showToast('Impossible de copier', 'error');
    }
};

const oqRevoke = async () => {
    if (!confirm('Révoquer ce lien ? Le client ne pourra plus y accéder.')) return;
    oqRevoking.value = true;
    try {
        await fetch(`/wp-json/my-easy-compta/v1/quotes/${route.params.id}/revoke-link`, {
            method: 'DELETE',
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce }
        });
        oqStatus.value = null;
        showToast('Lien révoqué');
    } catch (e) {
        showToast('Erreur lors de la révocation', 'error');
    } finally { oqRevoking.value = false; }
};

const oqLoadSignature = async () => {
    oqLoadingSig.value = true;
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/quotes/${route.params.id}/share-status`, {
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        if (data.signature_data) {
            oqStatus.value = { ...oqStatus.value, signature_data: data.signature_data };
        }
    } finally { oqLoadingSig.value = false; }
};

const oqReset = async () => {
    if (!confirm('Réinitialiser la réponse ? Le client pourra à nouveau signer et choisir ses options.')) return;
    oqResetting.value = true;
    try {
        await fetch(`/wp-json/my-easy-compta/v1/quotes/${route.params.id}/reset-token`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce }
        });
        await oqFetchStatus();
        await fetchQuote();
        showToast('Réponse réinitialisée — le client peut re-signer');
    } catch (e) {
        showToast('Erreur lors de la réinitialisation', 'error');
    } finally { oqResetting.value = false; }
};

const formatOqDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
};
// ────────────────────────────────────────────────────────────────────────────

onMounted(async () => {
    await fetchSettings();
    await checkLicense();
    await fetchQuote();
    await fetchItems();
    if (client_detail.value.currency_id) {
        await maybeFetchCurrency();
    }
    oqFetchStatus();
});

onUpdated(() => {
    initSortable();
});
</script>

<style scoped>
.kloxy-input-sm {
    @apply w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-purple-500/20 transition-all outline-none dark:text-white placeholder:text-slate-400;
}
.kloxy-select-sm {
    @apply w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-purple-500/20 transition-all outline-none dark:text-white cursor-pointer;
}
</style>
