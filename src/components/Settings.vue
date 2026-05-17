<template>
  <MainLayout title="Paramètres" subtitle="Gérez les paramètres de votre application">
    
    <!-- Toast -->
    <div v-if="toast.visible" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] toast-animate-in">
      <div :class="['flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md', toast.type === 'success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
        <component :is="toast.type === 'success' ? 'CheckCircle2' : 'AlertCircle'" class="w-6 h-6" />
        <span class="font-bold text-sm">{{ toast.message }}</span>
        <button @click="toast.visible = false" class="ml-2 hover:bg-white/20 p-1 rounded-full transition-colors"><X class="w-4 h-4" /></button>
      </div>
    </div>

    <remove-modal
      modal-id="modal_settings_remove"
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      @confirm="handleDeletion(deleteType, selectedId)"
      @cancel="showRemoveModal = false"
    />

    <div class="flex flex-col xl:flex-row gap-6 items-start">
      <!-- Sidebar -->
      <aside class="w-full xl:w-60 shrink-0">
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-3 shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800 sticky top-6 max-h-[calc(100vh-3rem)] overflow-y-auto settings-sidebar-scroll">
          <template v-for="(group, gi) in tabGroups" :key="gi">
            <!-- Group separator + label -->
            <div :class="['px-2 pt-3 pb-1.5 flex items-center gap-2', gi > 0 ? 'mt-1 border-t border-slate-100 dark:border-slate-800' : '']">
              <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">{{ group.label }}</span>
            </div>
            <!-- Tabs in group -->
            <button
              v-for="tab in group.tabs"
              :key="tab.id"
              @click="selectedTab = tab.id"
              :class="[
                'w-full flex items-center gap-2.5 px-2.5 py-2 rounded-xl text-sm transition-all duration-200 mb-0.5',
                selectedTab === tab.id
                  ? 'bg-purple-600 text-white shadow-md shadow-purple-500/25 font-bold'
                  : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white font-semibold'
              ]"
            >
              <div :class="['w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors', selectedTab === tab.id ? 'bg-white/20' : 'bg-slate-100 dark:bg-slate-800/80']">
                <component :is="tab.icon" class="w-3.5 h-3.5" />
              </div>
              <span class="truncate text-[13px]">{{ tab.label }}</span>
            </button>
          </template>
        </div>
      </aside>

      <!-- Main Content -->
      <div class="flex-1 min-w-0 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800 relative overflow-hidden min-h-[500px]">

         <div v-show="loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center">
             <div class="w-10 h-10 border-4 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
         </div>

         <!-- Tab Header -->
         <div class="sticky top-0 z-10 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 px-8 py-5 flex items-center justify-between rounded-t-[2.5rem]">
             <div class="flex items-center gap-3">
               <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                 <component :is="currentTabIcon" class="w-4 h-4 text-purple-600 dark:text-purple-400" />
               </div>
               <div>
                 <h2 class="text-lg font-black text-slate-900 dark:text-white leading-tight">{{ getCurrentTabTitle }}</h2>
                 <p class="text-[11px] text-slate-400 font-medium leading-tight">{{ getCurrentTabDesc }}</p>
               </div>
             </div>
             <button v-if="isTableTab && selectedTab !== 7" @click="openAddModal" class="kloxy-btn-primary">
                 <Plus class="w-4 h-4 mr-2" /> {{ translations.add || 'Ajouter' }}
             </button>
         </div>

         <!-- Content padding wrapper -->
         <div class="p-8">

         <!-- General / System Forms -->
         <form v-if="[1, 4, 5, 6, 10, 11, 12, 13, 14, 15, 20, 22, 23, 24, 25, 30].includes(selectedTab)" @submit.prevent="handleSubmit" class="space-y-6">

            
            <!-- Tab 1: General -->
            <div v-if="selectedTab === 1" class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                 <div class="space-y-2">
                     <label class="kloxy-label">{{ translations.company_code || 'SIRET' }}</label>
                     <input type="text" v-model="form.company_code" class="kloxy-input" />
                 </div>
                  <div class="space-y-2">
                     <label class="kloxy-label">{{ translations.tax_number || 'Numéro de TVA' }}</label>
                     <input type="text" v-model="form.tax_number" class="kloxy-input" />
                  </div>
                  <div class="space-y-2">
                     <label class="kloxy-label">{{ translations.company_name || 'Nom de l\'entreprise' }}</label>
                     <input type="text" v-model="form.company_name" class="kloxy-input" required />
                  </div>
                  <div class="space-y-2">
                     <label class="kloxy-label">{{ translations.email || 'Email' }}</label>
                     <input type="email" v-model="form.company_email" class="kloxy-input" />
                  </div>
                   <div class="space-y-2 md:col-span-2">
                     <label class="kloxy-label">{{ translations.address || 'Adresse' }}</label>
                     <input type="text" v-model="form.company_address" class="kloxy-input" required />
                  </div>
                  <div class="space-y-2">
                     <label class="kloxy-label">{{ translations.postal_code || 'Code Postal' }}</label>
                     <input type="text" v-model="form.postal_code" class="kloxy-input" />
                  </div>
                   <div class="space-y-2">
                     <label class="kloxy-label">{{ translations.city || 'Ville' }}</label>
                     <input type="text" v-model="form.city" class="kloxy-input" />
                  </div>
                   <div class="space-y-2">
                     <label class="kloxy-label">{{ translations.country || 'Pays' }}</label>
                     <input type="text" v-model="form.country" class="kloxy-input" />
                  </div>
                  <div class="space-y-2">
                     <label class="kloxy-label">{{ translations.phone || 'Téléphone' }}</label>
                     <input type="tel" v-model="form.company_phone" class="kloxy-input" />
                  </div>
                  <div class="space-y-2 md:col-span-2 border-t border-slate-100 dark:border-slate-800 pt-4"></div>
                  <div class="space-y-2">
                     <label class="kloxy-label">{{ translations.default_currency || 'Devise par défaut' }}</label>
                     <select v-model="form.default_currency" class="kloxy-input appearance-none">
                        <option v-for="c in currencies" :key="c.id" :value="c.id">{{ c.name }} ({{ c.symbol }})</option>
                     </select>
                  </div>
                  <div class="space-y-2">
                     <label class="kloxy-label">{{ translations.currency_position || 'Position de la devise' }}</label>
                     <select v-model="form.currency_position" class="kloxy-input appearance-none">
                        <option value="before">{{ translations.before_amount || 'Avant' }}</option>
                        <option value="after">{{ translations.after_amount || 'Après' }}</option>
                     </select>
                  </div>
                  <div class="space-y-2">
                     <label class="kloxy-label">{{ translations.format_date || 'Format Date' }}</label>
                     <select v-model="form.date_format" class="kloxy-input appearance-none">
                        <option value="DD-MM-YYYY">DD-MM-YYYY</option>
                        <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                        <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                     </select>
                  </div>
            </div>

            <!-- Tab 20: Documents PDF (settings communs à tous les documents) -->
            <div v-if="selectedTab === 20" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">

                <!-- Logo -->
                <div class="space-y-3">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Logo</p>
                    <div class="flex items-center gap-4">
                        <div v-if="logoPreviewUrl || form.logo_url" class="w-24 h-24 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center border border-slate-200 dark:border-slate-700 p-2">
                            <img :src="logoPreviewUrl || form.logo_url" class="max-w-full max-h-full object-contain" />
                        </div>
                        <label class="cursor-pointer kloxy-btn-secondary">
                            <Upload class="w-4 h-4 mr-2" /> {{ translations.select || 'Sélectionner' }}
                            <input type="file" @change="handleLogoUpload" accept="image/*" class="hidden" />
                        </label>
                    </div>
                    <input v-if="logoPreviewUrl || form.logo_url" type="range" min="50" max="400" v-model="form.logo_width" class="w-full accent-purple-600 cursor-pointer" />
                </div>

                <!-- Affichage sur les documents PDF -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Affichage sur les documents PDF</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div v-for="opt in pdfDisplayOptions" :key="opt.key"
                            class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
                            :class="form[opt.key] == '1' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 hover:border-purple-400'"
                            @click="form[opt.key] = form[opt.key] == '1' ? '0' : '1'">
                            <span class="w-4 h-4 rounded flex-shrink-0 flex items-center justify-center border-2 transition-colors"
                                :class="form[opt.key] == '1' ? 'bg-purple-600 border-purple-600' : 'border-slate-300 dark:border-slate-600'">
                                <svg v-if="form[opt.key] == '1'" viewBox="0 0 12 12" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1.5 6 4.5 9 10.5 3"/></svg>
                            </span>
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ opt.label }}</span>
                        </div>
                    </div>
                </div>

                <!-- Filigrane -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Filigrane</p>
                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl">
                        <div>
                            <span class="font-bold text-slate-700 dark:text-slate-300">Afficher le filigrane</span>
                            <p class="text-xs text-slate-400 mt-0.5">Affiche le statut en filigrane sur les PDFs</p>
                        </div>
                        <button type="button"
                            :class="['relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200', form.show_watermark == '1' ? 'bg-purple-600' : 'bg-slate-200 dark:bg-slate-700']"
                            @click="form.show_watermark = form.show_watermark == '1' ? '0' : '1'">
                            <span :class="['pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', form.show_watermark == '1' ? 'translate-x-5' : 'translate-x-0']"></span>
                        </button>
                    </div>
                    <div v-if="form.show_watermark == '1'" class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl animate-in fade-in slide-in-from-top-2">
                        <div>
                            <span class="font-bold text-slate-700 dark:text-slate-300">Filigrane uniquement sur les payées</span>
                            <p class="text-xs text-slate-400 mt-0.5">N'affiche le filigrane que sur les factures payées</p>
                        </div>
                        <button type="button"
                            :class="['relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200', form.show_watermark_only_paid == '1' ? 'bg-purple-600' : 'bg-slate-200 dark:bg-slate-700']"
                            @click="form.show_watermark_only_paid = form.show_watermark_only_paid == '1' ? '0' : '1'">
                            <span :class="['pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', form.show_watermark_only_paid == '1' ? 'translate-x-5' : 'translate-x-0']"></span>
                        </button>
                    </div>
                </div>

                <!-- Mentions légales -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-3">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Mentions légales</p>
                    <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl">
                        <div>
                            <span class="font-bold text-slate-700 dark:text-slate-300">Afficher les mentions légales sur les PDF</span>
                            <p class="text-xs text-slate-400 mt-0.5">Statut juridique, exonération TVA, etc.</p>
                        </div>
                        <button type="button"
                            :class="['relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200', form.logo_mentions_active == '1' ? 'bg-purple-600' : 'bg-slate-200 dark:bg-slate-700']"
                            @click="form.logo_mentions_active = form.logo_mentions_active == '1' ? '0' : '1'">
                            <span :class="['pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', form.logo_mentions_active == '1' ? 'translate-x-5' : 'translate-x-0']"></span>
                        </button>
                    </div>
                    <div v-if="form.logo_mentions_active == '1'" class="space-y-2 animate-in fade-in slide-in-from-top-2">
                        <label class="kloxy-label">Texte des mentions</label>
                        <textarea v-model="form.logo_mentions" class="kloxy-input" rows="3" placeholder="EI - Entrepreneur Individuel&#10;TVA non applicable, art. 293 B du CGI"></textarea>
                    </div>
                </div>

                <!-- Paiement par défaut -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Paiement par défaut</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="kloxy-label">Mode de paiement</label>
                            <input type="text" v-model="form.payment_mode" class="kloxy-input" placeholder="Virement bancaire" />
                        </div>
                        <div class="space-y-2">
                            <label class="kloxy-label">Conditions de paiement</label>
                            <input type="text" v-model="form.payment_conditions" class="kloxy-input" placeholder="30 jours" />
                        </div>
                    </div>
                </div>

                <!-- Coordonnées bancaires -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
                    <p class="text-xs font-black uppercase tracking-widest text-slate-400">Coordonnées bancaires</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="kloxy-label">IBAN</label>
                            <input type="text" v-model="form.invoice_iban" class="kloxy-input" placeholder="FR76 3000 6000 0112 3456 7890 189" />
                        </div>
                        <div class="space-y-2">
                            <label class="kloxy-label">BIC / SWIFT</label>
                            <input type="text" v-model="form.invoice_bic" class="kloxy-input" placeholder="BNPAFRPPXXX" />
                        </div>
                    </div>
                </div>

            </div>

            <!-- Tab 4: Invoices Settings -->
            <div v-if="selectedTab === 4" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <!-- Format du numéro de facture -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                    <div class="space-y-2">
                        <label class="kloxy-label">Format de numérotation</label>
                        <select v-model="form.invoice_number_format" class="kloxy-input cursor-pointer">
                            <option v-for="opt in numberFormatOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <p v-if="numberFormatOptions.find(o => o.value === form.invoice_number_format)?.note" class="text-[11px] text-amber-600 dark:text-amber-400">
                            {{ numberFormatOptions.find(o => o.value === form.invoice_number_format).note }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <label class="kloxy-label">Aperçu</label>
                        <div class="kloxy-input bg-slate-50 dark:bg-slate-800 font-mono font-bold text-purple-600 dark:text-purple-400 select-all">{{ invoiceNumberPreview }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="kloxy-label">{{ translations.invoice_prefix || 'Préfixe' }}</label>
                        <input type="text" v-model="form.invoice_prefix" class="kloxy-input" />
                    </div>
                    <div class="space-y-2">
                        <label class="kloxy-label">{{ translations.invoice_next_number || 'Prochain numéro' }}</label>
                        <input type="number" v-model="form.invoice_first" class="kloxy-input" />
                    </div>
                    <div class="space-y-2">
                        <label class="kloxy-label">{{ translations.invoice_color || 'Couleur principale' }}</label>
                        <div class="flex gap-2">
                            <input type="text" v-model="form.invoice_color" class="kloxy-input" />
                            <input type="color" v-model="form.invoice_color" class="h-12 w-12 rounded-xl border-none cursor-pointer bg-transparent" />
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-slate-100 dark:border-slate-800">
                    <div class="space-y-2">
                        <label class="kloxy-label">Modèle de PDF</label>
                        <select v-model="form.invoice_pdf_template" class="kloxy-input cursor-pointer">
                            <option value="modern">Moderne</option>
                            <option value="classic">Classique</option>
                            <option value="minimal">Minimaliste</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="kloxy-label">Police d'écriture</label>
                        <select v-model="form.invoice_pdf_font" class="kloxy-input cursor-pointer">
                            <option value="dejavusanscondensed">DejaVu Sans</option>
                            <option value="helvetica">Helvetica</option>
                            <option value="courier">Courier</option>
                            <option value="times">Times New Roman</option>
                        </select>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="kloxy-label">{{ translations.invoice_footer || 'Pied de page' }}</label>
                    <VueEditor v-model="form.invoice_footer" />
                </div>
                <div class="space-y-2">
                    <label class="kloxy-label">{{ translations.invoice_terms || 'Conditions' }}</label>
                    <VueEditor v-model="form.invoice_terms" />
                </div>
                <!-- Preview area for Invoices -->
                <div class="mt-8 p-6 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white mb-4">Aperçu du style (Facture)</h3>
                    <div class="border rounded-lg shadow-sm bg-white p-8 max-w-2xl mx-auto dark:text-slate-900" :style="{ fontFamily: form.invoice_pdf_font }">
                        <!-- Mock Header -->
                        <div class="flex justify-between items-start border-b pb-4 mb-4" :style="{ borderColor: form.invoice_color }">
                                <div class="font-bold text-xl uppercase" :style="{ color: form.invoice_color }">FACTURE</div>
                                <div class="text-right text-xs text-slate-500">
                                    <div>#INV-2024-001</div>
                                    <div>Date: {{ new Date().toLocaleDateString() }}</div>
                                </div>
                        </div>
                        <!-- Mock Table -->
                            <table class="w-full text-xs">
                                <thead :style="{ backgroundColor: form.invoice_color, color: '#fff' }">
                                    <tr>
                                        <th class="p-2 text-left">Description</th>
                                        <th class="p-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b">
                                        <td class="p-2">Développement Site Web</td>
                                        <td class="p-2 text-right">1500.00 €</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="mt-4 text-right font-bold text-lg" :style="{ color: form.invoice_color }">
                                Total: 1500.00 €
                            </div>
                    </div>
                </div>
            </div>

            <!-- Tab 5: Credits Settings -->
            <div v-if="selectedTab === 5" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <!-- Format du numéro d'avoir -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                    <div class="space-y-2">
                        <label class="kloxy-label">Format de numérotation</label>
                        <select v-model="form.credit_number_format" class="kloxy-input cursor-pointer">
                            <option v-for="opt in numberFormatOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <p v-if="numberFormatOptions.find(o => o.value === form.credit_number_format)?.note" class="text-[11px] text-amber-600 dark:text-amber-400">
                            {{ numberFormatOptions.find(o => o.value === form.credit_number_format).note }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <label class="kloxy-label">Aperçu</label>
                        <div class="kloxy-input bg-slate-50 dark:bg-slate-800 font-mono font-bold text-amber-600 dark:text-amber-400 select-all">{{ creditNumberPreview }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="kloxy-label">{{ translations.credit_prefix || 'Préfixe' }}</label>
                        <input type="text" v-model="form.credit_prefix" class="kloxy-input" />
                    </div>
                    <div class="space-y-2">
                        <label class="kloxy-label">{{ translations.credit_color || 'Couleur principale' }}</label>
                        <div class="flex gap-2">
                            <input type="text" v-model="form.credit_color" class="kloxy-input" />
                            <input type="color" v-model="form.credit_color" class="h-12 w-12 rounded-xl border-none cursor-pointer bg-transparent" />
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-slate-100 dark:border-slate-800">
                    <div class="space-y-2">
                        <label class="kloxy-label">Modèle de PDF</label>
                        <select v-model="form.credit_pdf_template" class="kloxy-input cursor-pointer">
                            <option value="modern">Moderne</option>
                            <option value="classic">Classique</option>
                            <option value="minimal">Minimaliste</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="kloxy-label">Police d'écriture</label>
                        <select v-model="form.credit_pdf_font" class="kloxy-input cursor-pointer">
                            <option value="dejavusanscondensed">DejaVu Sans</option>
                            <option value="helvetica">Helvetica</option>
                            <option value="courier">Courier</option>
                            <option value="times">Times New Roman</option>
                        </select>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="kloxy-label">{{ translations.credit_footer || 'Pied de page' }}</label>
                    <VueEditor v-model="form.credit_footer" />
                </div>
                <div class="space-y-2">
                    <label class="kloxy-label">{{ translations.credit_terms || 'Conditions' }}</label>
                    <VueEditor v-model="form.credit_terms" />
                </div>

                <!-- Preview area for Credits -->
                <div class="mt-8 p-6 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white mb-4">Aperçu du style (Avoir)</h3>
                    <div class="border rounded-lg shadow-sm bg-white p-8 max-w-2xl mx-auto dark:text-slate-900" :style="{ fontFamily: form.credit_pdf_font }">
                        <!-- Mock Header -->
                        <div class="flex justify-between items-start border-b pb-4 mb-4" :style="{ borderColor: form.credit_color }">
                                <div class="font-bold text-xl uppercase" :style="{ color: form.credit_color }">AVOIR</div>
                                <div class="text-right text-xs text-slate-500">
                                    <div>#AV-2024-001</div>
                                    <div>Date: {{ new Date().toLocaleDateString() }}</div>
                                </div>
                        </div>
                        <!-- Mock Table -->
                            <table class="w-full text-xs">
                                <thead :style="{ backgroundColor: form.credit_color, color: '#fff' }">
                                    <tr>
                                        <th class="p-2 text-left">Description</th>
                                        <th class="p-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b">
                                        <td class="p-2">Remboursement Service</td>
                                        <td class="p-2 text-right">-100.00 €</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="mt-4 text-right font-bold text-lg" :style="{ color: form.credit_color }">
                                Total: -100.00 €
                            </div>
                    </div>
                </div>
            </div>

            <!-- Tab 6: Quotes Settings -->
            <div v-if="selectedTab === 6" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <!-- Format du numéro de devis -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                    <div class="space-y-2">
                        <label class="kloxy-label">Format de numérotation</label>
                        <select v-model="form.quote_number_format" class="kloxy-input cursor-pointer">
                            <option v-for="opt in numberFormatOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <p v-if="numberFormatOptions.find(o => o.value === form.quote_number_format)?.note" class="text-[11px] text-amber-600 dark:text-amber-400">
                            {{ numberFormatOptions.find(o => o.value === form.quote_number_format).note }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <label class="kloxy-label">Aperçu</label>
                        <div class="kloxy-input bg-slate-50 dark:bg-slate-800 font-mono font-bold text-emerald-600 dark:text-emerald-400 select-all">{{ quoteNumberPreview }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="kloxy-label">{{ translations.quote_prefix || 'Préfixe' }}</label>
                        <input type="text" v-model="form.quote_prefix" class="kloxy-input" />
                    </div>
                    <div class="space-y-2">
                        <label class="kloxy-label">{{ translations.quote_next_number || 'Prochain numéro' }}</label>
                        <input type="number" v-model="form.quote_first" class="kloxy-input" />
                    </div>
                    <div class="space-y-2">
                        <label class="kloxy-label">{{ translations.quote_color || 'Couleur principale' }}</label>
                        <div class="flex gap-2">
                            <input type="text" v-model="form.quote_color" class="kloxy-input" />
                            <input type="color" v-model="form.quote_color" class="h-12 w-12 rounded-xl border-none cursor-pointer bg-transparent" />
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-6 border-b border-slate-100 dark:border-slate-800">
                    <div class="space-y-2">
                        <label class="kloxy-label">Modèle de PDF</label>
                        <select v-model="form.quote_pdf_template" class="kloxy-input cursor-pointer">
                            <option value="modern">Moderne</option>
                            <option value="classic">Classique</option>
                            <option value="minimal">Minimaliste</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="kloxy-label">Police d'écriture</label>
                        <select v-model="form.quote_pdf_font" class="kloxy-input cursor-pointer">
                            <option value="dejavusanscondensed">DejaVu Sans</option>
                            <option value="helvetica">Helvetica</option>
                            <option value="courier">Courier</option>
                            <option value="times">Times New Roman</option>
                        </select>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="kloxy-label">{{ translations.quote_footer || 'Pied de page' }}</label>
                    <VueEditor v-model="form.quote_footer" />
                </div>
                <div class="space-y-2">
                    <label class="kloxy-label">{{ translations.quote_terms || 'Conditions' }}</label>
                    <VueEditor v-model="form.quote_terms" />
                </div>

                <!-- Preview area for Quotes -->
                <div class="mt-8 p-6 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <h3 class="font-bold text-slate-900 dark:text-white mb-4">Aperçu du style (Devis)</h3>
                    <div class="border rounded-lg shadow-sm bg-white p-8 max-w-2xl mx-auto dark:text-slate-900" :style="{ fontFamily: form.quote_pdf_font }">
                        <!-- Mock Header -->
                        <div class="flex justify-between items-start border-b pb-4 mb-4" :style="{ borderColor: form.quote_color }">
                                <div class="font-bold text-xl uppercase" :style="{ color: form.quote_color }">DEVIS</div>
                                <div class="text-right text-xs text-slate-500">
                                    <div>#DEV-2024-001</div>
                                    <div>Date: {{ new Date().toLocaleDateString() }}</div>
                                </div>
                        </div>
                        <!-- Mock Table -->
                            <table class="w-full text-xs">
                                <thead :style="{ backgroundColor: form.quote_color, color: '#fff' }">
                                    <tr>
                                        <th class="p-2 text-left">Description</th>
                                        <th class="p-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b">
                                        <td class="p-2">Prestation de conseil</td>
                                        <td class="p-2 text-right">800.00 €</td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="mt-4 text-right font-bold text-lg" :style="{ color: form.quote_color }">
                                Total: 800.00 €
                            </div>
                    </div>
                </div>
            </div>
            <!-- Tab 10: Planning Addon -->
            <div v-if="selectedTab === 10" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-[2rem] border border-blue-100 dark:border-blue-800">
                    <div class="flex items-center gap-4 mb-4">
                        <Calendar class="w-8 h-8 text-blue-600" />
                        <h3 class="text-xl font-black text-blue-900 dark:text-blue-100">Add-on Planning</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 font-medium">L'add-on Planning permet de gérer vos rendez-vous et votre emploi du temps.</p>
                </div>
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl">
                    <span class="font-bold text-slate-700 dark:text-slate-300">Activer l'add-on Planning</span>
                    <button 
                        type="button"
                        class="kloxy-toggle"
                        :aria-checked="(form.easy_compta_planning_addon_active == 1).toString()"
                        @click="toggleAddon('myeasycompta-planning', 'easy_compta_planning_addon_active')"
                    >
                        <span class="kloxy-toggle-thumb"></span>
                    </button>
                </div>
            </div>

            <!-- Tab 11: Email Addon -->
            <div v-if="selectedTab === 11" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">

                <!-- Header card -->
                <div class="flex items-center justify-between p-5 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl border border-indigo-100 dark:border-indigo-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center flex-shrink-0">
                            <Mail class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">Add-on Emailing</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">Envoyez vos factures et devis directement depuis myEasyCompta</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="kloxy-toggle flex-shrink-0"
                        :aria-checked="(form.easy_compta_email_addon_active == 1).toString()"
                        @click="toggleAddon('myeasycompta-e-mail', 'easy_compta_email_addon_active')"
                    ><span class="kloxy-toggle-thumb"></span></button>
                </div>

                <!-- Email Notification Settings — always visible -->
                <div class="rounded-[2rem] border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3.5 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                        <div class="w-7 h-7 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                            <Bell class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <span class="font-black text-xs uppercase tracking-widest text-slate-600 dark:text-slate-300">Notifications automatiques</span>
                    </div>
                    <div class="p-5 space-y-5 bg-white dark:bg-slate-900">

                        <!-- Theme picker -->
                        <div class="space-y-2">
                            <label class="kloxy-label">Thème des emails</label>
                            <div class="flex gap-3">
                                <button type="button" @click="form.ecwp_email_theme = 'dark'"
                                    :class="['flex-1 flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all text-left',
                                             form.ecwp_email_theme === 'dark' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-purple-300']">
                                    <div class="w-8 h-8 rounded-lg bg-slate-900 flex-shrink-0 flex items-center justify-center">
                                        <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                    </div>
                                    <div>
                                        <p class="font-black text-xs text-slate-800 dark:text-white">Dark</p>
                                        <p class="text-[10px] text-slate-400">Fond sombre</p>
                                    </div>
                                    <div v-if="form.ecwp_email_theme === 'dark'" class="ml-auto w-4 h-4 rounded-full bg-purple-500 flex items-center justify-center flex-shrink-0">
                                        <svg viewBox="0 0 12 12" class="w-2.5 h-2.5 text-white" fill="currentColor"><path d="M2 6l3 3 5-5"/></svg>
                                    </div>
                                </button>
                                <button type="button" @click="form.ecwp_email_theme = 'light'"
                                    :class="['flex-1 flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all text-left',
                                             form.ecwp_email_theme === 'light' ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'border-slate-200 dark:border-slate-700 hover:border-purple-300']">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 border border-slate-200 flex-shrink-0 flex items-center justify-center">
                                        <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                    </div>
                                    <div>
                                        <p class="font-black text-xs text-slate-800 dark:text-white">Light</p>
                                        <p class="text-[10px] text-slate-400">Fond clair</p>
                                    </div>
                                    <div v-if="form.ecwp_email_theme === 'light'" class="ml-auto w-4 h-4 rounded-full bg-purple-500 flex items-center justify-center flex-shrink-0">
                                        <svg viewBox="0 0 12 12" class="w-2.5 h-2.5 text-white" fill="currentColor"><path d="M2 6l3 3 5-5"/></svg>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- Preview button -->
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="previewEmail('quote_accepted')"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:border-purple-400 hover:text-purple-600 transition-all">
                                <Eye class="w-3.5 h-3.5" /> Aperçu — Devis accepté
                            </button>
                            <button type="button" @click="previewEmail('quote_rejected')"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-600 dark:text-slate-300 hover:border-rose-400 hover:text-rose-600 transition-all">
                                <Eye class="w-3.5 h-3.5" /> Aperçu — Devis refusé
                            </button>
                            <button type="button" @click="sendTestEmail" :disabled="testEmailLoading"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-amber-200 dark:border-amber-800 text-xs font-bold text-amber-600 dark:text-amber-400 hover:border-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition-all disabled:opacity-50 disabled:cursor-wait">
                                <span v-if="testEmailLoading" class="w-3.5 h-3.5 border-2 border-amber-400 border-t-transparent rounded-full animate-spin"></span>
                                <span v-else>🧪</span>
                                Envoyer un email test
                            </button>
                        </div>
                        <!-- Test email result -->
                        <div v-if="testEmailMsg" :class="['text-xs font-medium px-3 py-2 rounded-xl', testEmailMsg.ok ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400']">
                            {{ testEmailMsg.text }}
                        </div>

                        <div class="h-px bg-slate-100 dark:bg-slate-800"></div>

                        <!-- Section 1 : Notifications gratuites -->
                        <div class="space-y-1">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 pb-1">Incluses gratuitement</p>
                            <template v-for="notif in [
                                { key: 'ecwp_notify_invoice_paid',    label: 'Facture payée intégralement',   desc: 'Email quand une facture passe au statut « Payée ».', preview: 'invoice_paid' },
                                { key: 'ecwp_notify_new_client',      label: 'Nouveau client créé',            desc: 'Email quand un client est ajouté à la base.', preview: 'new_client' },
                                { key: 'ecwp_notify_quote_converted', label: 'Devis converti en facture',      desc: 'Email quand un devis est transformé en facture.', preview: 'quote_converted' },
                                { key: 'ecwp_notify_quote_action',    label: 'Devis accepté / refusé en ligne', desc: 'Email quand un client répond à un devis partagé (addon Devis en ligne).', preview: 'quote_accepted' },
                            ]" :key="notif.key">
                                <div class="flex items-center justify-between gap-4 py-2">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-sm text-slate-900 dark:text-white">{{ notif.label }}</p>
                                        <p class="text-xs text-slate-400 mt-0.5">{{ notif.desc }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <button type="button" @click="previewEmail(notif.preview)" class="text-purple-400 hover:text-purple-600 transition-colors"><Eye class="w-3.5 h-3.5" /></button>
                                        <button type="button" @click="form[notif.key] = form[notif.key] == '1' ? '0' : '1'"
                                            :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors cursor-pointer', form[notif.key] == '1' ? 'bg-purple-600' : 'bg-slate-200 dark:bg-slate-700']">
                                            <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow', form[notif.key] == '1' ? 'translate-x-6' : 'translate-x-1']"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Section 2 : Notifications avec addons payants -->
                        <div class="space-y-1 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 pb-1">Avec addons</p>
                            <template v-for="notif in [
                                { key: 'ecwp_notify_partial_payment', label: 'Acompte / paiement partiel reçu', desc: 'Email quand un acompte est enregistré.',                              preview: 'partial_payment', addonSlug: 'advance',  addonLabel: 'Advance'    },
                                { key: 'ecwp_notify_backup_done',     label: 'Sauvegarde effectuée',             desc: 'Email quand une sauvegarde est créée avec succès.',                   preview: 'backup_done',     addonSlug: 'backup',   addonLabel: 'Sauvegarde' },
                                { key: 'ecwp_notify_backup_deleted',  label: 'Sauvegarde supprimée',             desc: 'Email quand une sauvegarde est supprimée manuellement.',              preview: 'backup_deleted',  addonSlug: 'backup',   addonLabel: 'Sauvegarde' },
                                { key: 'ecwp_notify_planning_event',  label: 'Événement ajouté au planning',     desc: 'Email quand un nouvel événement est créé dans le planning.',          preview: 'planning_event',  addonSlug: 'planning', addonLabel: 'Planning'   },
                            ]" :key="notif.key">
                                <div :class="['flex items-center justify-between gap-4 py-2 transition-opacity', !addonActive(notif.addonSlug) ? 'opacity-50' : '']">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="font-semibold text-sm text-slate-900 dark:text-white">{{ notif.label }}</p>
                                            <span v-if="!addonActive(notif.addonSlug)" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[10px] font-black uppercase tracking-wide whitespace-nowrap">
                                                <svg class="w-2.5 h-2.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                                {{ notif.addonLabel }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-0.5">{{ notif.desc }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <button type="button" @click="previewEmail(notif.preview)" class="text-purple-400 hover:text-purple-600 transition-colors"><Eye class="w-3.5 h-3.5" /></button>
                                        <button type="button"
                                            :disabled="!addonActive(notif.addonSlug)"
                                            @click="addonActive(notif.addonSlug) && (form[notif.key] = form[notif.key] == '1' ? '0' : '1')"
                                            :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', !addonActive(notif.addonSlug) ? 'cursor-not-allowed' : 'cursor-pointer', form[notif.key] == '1' && addonActive(notif.addonSlug) ? 'bg-purple-600' : 'bg-slate-200 dark:bg-slate-700']">
                                            <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow', form[notif.key] == '1' && addonActive(notif.addonSlug) ? 'translate-x-6' : 'translate-x-1']"></span>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                    </div>
                </div>

                <!-- Templates — only when active -->
                <div v-if="form.easy_compta_email_addon_active == 1" class="space-y-4 animate-in fade-in slide-in-from-top-2 duration-300">

                    <!-- Variables pill bar -->
                    <div class="flex flex-wrap items-center gap-2 px-4 py-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-200 dark:border-slate-700">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mr-1">Variables :</span>
                        <code class="px-2 py-0.5 bg-white dark:bg-slate-900 rounded-lg text-[11px] font-bold text-indigo-600 dark:text-indigo-400 border border-slate-200 dark:border-slate-700 cursor-pointer select-all">{nom_client}</code>
                        <code class="px-2 py-0.5 bg-white dark:bg-slate-900 rounded-lg text-[11px] font-bold text-indigo-600 dark:text-indigo-400 border border-slate-200 dark:border-slate-700 cursor-pointer select-all">{numero_document}</code>
                        <code class="px-2 py-0.5 bg-white dark:bg-slate-900 rounded-lg text-[11px] font-bold text-indigo-600 dark:text-indigo-400 border border-slate-200 dark:border-slate-700 cursor-pointer select-all">{montant_total}</code>
                    </div>

                    <!-- 3 template cards -->
                    <!-- Factures -->
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="flex items-center gap-3 px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                            <div class="w-7 h-7 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                <FileText class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <span class="font-black text-xs uppercase tracking-widest text-slate-600 dark:text-slate-300">Factures</span>
                        </div>
                        <div class="p-5 space-y-4 bg-white dark:bg-slate-900">
                            <div class="space-y-1.5">
                                <label class="kloxy-label">Sujet</label>
                                <input type="text" v-model="form.invoice_email_subject" class="kloxy-input" placeholder="Ex: Votre facture {numero_document}" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="kloxy-label">Message</label>
                                <VueEditor v-model="form.invoice_email_content" />
                            </div>
                        </div>
                    </div>

                    <!-- Relance -->
                    <div class="rounded-2xl border border-amber-200 dark:border-amber-700/50 overflow-hidden">
                        <div class="flex items-center gap-3 px-5 py-3 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-700/50">
                            <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                                <Bell class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <span class="font-black text-xs uppercase tracking-widest text-amber-700 dark:text-amber-400">Relance facture</span>
                        </div>
                        <div class="p-5 space-y-4 bg-white dark:bg-slate-900">
                            <div class="space-y-1.5">
                                <label class="kloxy-label">Sujet</label>
                                <input type="text" v-model="form.invoice_email_remind_subject" class="kloxy-input" placeholder="Ex: Relance — {numero_document}" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="kloxy-label">Message</label>
                                <VueEditor v-model="form.invoice_email_remind_content" />
                            </div>
                        </div>
                    </div>

                    <!-- Devis -->
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                        <div class="flex items-center gap-3 px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                            <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                <FileText class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <span class="font-black text-xs uppercase tracking-widest text-slate-600 dark:text-slate-300">Devis</span>
                        </div>
                        <div class="p-5 space-y-4 bg-white dark:bg-slate-900">
                            <div class="space-y-1.5">
                                <label class="kloxy-label">Sujet</label>
                                <input type="text" v-model="form.quote_email_subject" class="kloxy-input" placeholder="Ex: Votre devis {numero_document}" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="kloxy-label">Message</label>
                                <VueEditor v-model="form.quote_email_content" />
                            </div>
                        </div>
                    </div>

                </div>

                <!-- ── Relances impayées ────────────────────────────────────── -->
                <div v-if="form.easy_compta_email_addon_active == 1" class="space-y-6">
                    <div class="bg-gradient-to-br from-amber-500 to-orange-500 p-6 rounded-[2rem] text-white shadow-xl relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-48 h-48 bg-white/5 rounded-full blur-3xl"></div>
                        <div class="relative z-10 flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center flex-shrink-0">
                                <Bell class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h3 class="text-lg font-black tracking-tight mb-1">Relances impayées automatiques</h3>
                                <p class="text-amber-100 text-sm leading-relaxed">Envoyez des emails de rappel automatiques à vos clients pour les factures impayées après l'échéance.</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-[2rem] p-6 space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-base font-black text-slate-900 dark:text-white">Activer les relances automatiques</h4>
                                <p class="text-slate-500 text-xs mt-0.5">Un email est envoyé au client pour chaque délai configuré après la date d'échéance.</p>
                            </div>
                            <button type="button" @click="reminders.enabled = !reminders.enabled"
                                    :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors', reminders.enabled ? 'bg-purple-600' : 'bg-slate-200 dark:bg-slate-700']">
                                <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow', reminders.enabled ? 'translate-x-6' : 'translate-x-1']"></span>
                            </button>
                        </div>
                        <div class="space-y-2">
                            <label class="kloxy-label">Délais de relance (jours après l'échéance)</label>
                            <input v-model="reminders.delays" type="text" class="kloxy-input" placeholder="Ex: 7,15,30" :disabled="!reminders.enabled" />
                            <p class="text-xs text-slate-400">Entrez les délais séparés par des virgules (ex: 7,15,30 pour J+7, J+15, J+30).</p>
                        </div>
                        <div class="space-y-2">
                            <label class="kloxy-label">Message de relance</label>
                            <p class="text-xs text-slate-400 -mt-1">Variables : <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded text-xs">{INVOICE_NUMBER}</code> <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded text-xs">{DUE_DATE}</code> <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded text-xs">{AMOUNT}</code> <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded text-xs">{CLIENT_NAME}</code> <code class="bg-slate-100 dark:bg-slate-800 px-1 rounded text-xs">{COMPANY_NAME}</code></p>
                            <textarea v-model="reminders.message" rows="6" class="kloxy-input resize-none" :disabled="!reminders.enabled"></textarea>
                        </div>
                        <div v-if="remindersMsg" :class="['p-4 rounded-xl text-sm font-semibold', remindersMsgType === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300']">{{ remindersMsg }}</div>
                        <div class="flex justify-end">
                            <button @click="saveReminders" :disabled="remindersLoading" class="kloxy-btn-primary">
                                <Loader2 v-if="remindersLoading" class="w-4 h-4 mr-2 animate-spin" />
                                <Save v-else class="w-4 h-4 mr-2" />
                                {{ translations.save || 'Enregistrer' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Email Preview Modal -->
                <div v-if="emailPreviewHtml" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="emailPreviewHtml = null">
                    <div class="w-full max-w-2xl bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                            <p class="font-black text-sm text-slate-800 dark:text-white">Aperçu email</p>
                            <button @click="emailPreviewHtml = null" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                <X class="w-4 h-4 text-slate-500" />
                            </button>
                        </div>
                        <div class="flex-1 overflow-auto p-4">
                            <iframe :srcdoc="emailPreviewHtml" class="w-full rounded-xl" style="height:600px;border:none;"></iframe>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Tab 12: Users Addon (Pro) -->
            <div v-if="selectedTab === 12" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-[2rem] border border-blue-100 dark:border-blue-800">
                    <div class="flex items-center gap-4 mb-4">
                        <User class="w-8 h-8 text-blue-600" />
                        <h3 class="text-xl font-black text-blue-900 dark:text-blue-100">Add-on Multi-utilisateurs</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 font-medium mb-4">Gérez plusieurs accès pour votre comptabilité avec des rôles et permissions personnalisés.</p>
                    
                    <div class="bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm">
                        <h4 class="font-bold text-lg mb-2">Gestion des utilisateurs</h4>
                        <p class="text-sm text-slate-500 mb-4">Ajoutez et gérez vos utilisateurs directement via l'interface WordPress.</p>
                        <a href="/wp-admin/users.php" target="_blank" class="inline-flex items-center gap-2 kloxy-btn-primary">
                            <User class="w-4 h-4" /> Gérer les utilisateurs WordPress
                        </a>
                    </div>
                </div>

                     <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Activer l'add-on Utilisateurs</span>
                        <button 
                            type="button"
                            class="kloxy-toggle"
                            :aria-checked="(form.easy_compta_user_addon_active == 1).toString()"
                            @click="toggleAddon('myeasycompta-compte-client', 'easy_compta_user_addon_active')"
                        >
                            <span class="kloxy-toggle-thumb"></span>
                        </button>
                     </div>
            </div>

            <!-- Tab 13: Stripe Addon (Pro) -->
            <div v-if="selectedTab === 13" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                 <div class="bg-slate-900 p-8 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden mb-6">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-600/30 rounded-full blur-3xl"></div>
                    <div class="relative z-10 flex items-center gap-6">
                        <CreditCard class="w-12 h-12 text-indigo-400" />
                        <div>
                            <h3 class="text-2xl font-black">Paiement Stripe</h3>
                            <p class="text-slate-400 font-medium mt-1">Acceptez les paiements par carte bancaire sur vos factures.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                     <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
                        <span class="font-bold text-slate-700 dark:text-slate-300">Activer le Paiement Stripe</span>
                        <button 
                            type="button"
                            class="kloxy-toggle"
                            :aria-checked="(form.easy_compta_payment_addon_active == 1).toString()"
                            @click="toggleAddon('myeasycompta-payment', 'easy_compta_payment_addon_active')"
                        >
                            <span class="kloxy-toggle-thumb"></span>
                        </button>
                     </div>

                     <div v-if="form.easy_compta_payment_addon_active == 1" class="space-y-6 animate-in fade-in slide-in-from-top-4 duration-500">
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
                            <span class="font-bold text-slate-700 dark:text-slate-300">Mode Test (Sandbox)</span>
                        <button 
                            type="button"
                            class="kloxy-toggle"
                            :aria-checked="(form.stripe_test_mode == 1).toString()"
                            @click="form.stripe_test_mode = (form.stripe_test_mode == 1 ? 0 : 1)"
                        >
                            <span class="kloxy-toggle-thumb"></span>
                        </button>
                    </div>

                    <div v-if="form.stripe_test_mode == 1" class="space-y-4 p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in slide-in-from-top-2">
                        <h4 class="font-bold text-indigo-600 dark:text-indigo-400 flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-indigo-500"></div> Clés API de Test</h4>
                        <div class="space-y-2">
                            <label class="kloxy-label">Clé Publique (Test)</label>
                            <input type="text" v-model="form.stripe_test_publishable_key" class="kloxy-input font-mono text-sm" placeholder="pk_test_..." />
                        </div>
                        <div class="space-y-2">
                            <label class="kloxy-label">Clé Secrète (Test)</label>
                            <input type="password" v-model="form.stripe_test_secret_key" class="kloxy-input font-mono text-sm" placeholder="sk_test_..." />
                        </div>
                    </div>

                    <div v-else class="space-y-4 p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 animate-in fade-in slide-in-from-top-2">
                        <h4 class="font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-emerald-500"></div> Clés API Live</h4>
                        <div class="space-y-2">
                            <label class="kloxy-label">Clé Publique (Live)</label>
                            <input type="text" v-model="form.stripe_live_publishable_key" class="kloxy-input font-mono text-sm" placeholder="pk_live_..." />
                        </div>
                        <div class="space-y-2">
                            <label class="kloxy-label">Clé Secrète (Live)</label>
                            <input type="password" v-model="form.stripe_live_secret_key" class="kloxy-input font-mono text-sm" placeholder="sk_live_..." />
                        </div>
                    </div>

                    <div class="space-y-2 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <label class="kloxy-label">Secret Webhook (Optionnel)</label>
                        <input type="password" v-model="form.stripe_webhook_secret" class="kloxy-input font-mono text-sm" placeholder="whsec_..." />
                    </div>
                 </div>
                </div>
            </div>

            <!-- Tab 14: Stats Addon (Pro) -->
            <div v-if="selectedTab === 14" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">

                <!-- Objectif de CA annuel -->
                <div class="rounded-2xl border border-amber-200 dark:border-amber-700/50 overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-700/50">
                        <div class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                            <BarChart class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <span class="font-black text-xs uppercase tracking-widest text-amber-700 dark:text-amber-400">Objectif de chiffre d'affaires</span>
                    </div>
                    <div class="p-5 space-y-4 bg-white dark:bg-slate-900">
                        <div class="space-y-1.5">
                            <label class="kloxy-label">Objectif annuel ({{ currencySymbol }})</label>
                            <input type="number" min="0" step="100" v-model="form.stats_annual_target" class="kloxy-input" placeholder="Ex: 50000" />
                            <p class="text-[11px] text-slate-400 ml-1">Affiche une barre de progression dans les statistiques pour suivre votre avancement.</p>
                        </div>
                    </div>
                </div>

                <!-- Seuils réglementaires -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                        <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                            <Receipt class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <span class="font-black text-xs uppercase tracking-widest text-slate-600 dark:text-slate-300">Seuils réglementaires</span>
                    </div>
                    <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5 bg-white dark:bg-slate-900">
                        <div class="space-y-1.5">
                            <label class="kloxy-label">Seuil de déclaration ({{ currencySymbol }})</label>
                            <input type="number" min="0" step="100" v-model="form.limit_declaration" class="kloxy-input" placeholder="Ex: 77700" />
                            <p class="text-[11px] text-slate-400 ml-1">Plafond de CA à ne pas dépasser selon votre régime (micro-entreprise, auto-entrepreneur…).</p>
                        </div>
                        <div class="space-y-1.5">
                            <label class="kloxy-label">Seuil franchise TVA ({{ currencySymbol }})</label>
                            <input type="number" min="0" step="100" v-model="form.limit_tva" class="kloxy-input" placeholder="Ex: 36800" />
                            <p class="text-[11px] text-slate-400 ml-1">Au-delà de ce seuil, la TVA devient obligatoire. Affiché sous forme d'alerte dans les stats.</p>
                        </div>
                    </div>
                </div>

                <!-- Exercice fiscal -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                        <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                            <Calendar class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <span class="font-black text-xs uppercase tracking-widest text-slate-600 dark:text-slate-300">Exercice fiscal</span>
                    </div>
                    <div class="p-5 bg-white dark:bg-slate-900">
                        <div class="space-y-1.5">
                            <label class="kloxy-label">Mois de début d'exercice</label>
                            <select v-model="form.stats_fiscal_year_start" class="kloxy-input cursor-pointer">
                                <option value="1">Janvier</option>
                                <option value="2">Février</option>
                                <option value="3">Mars</option>
                                <option value="4">Avril</option>
                                <option value="5">Mai</option>
                                <option value="6">Juin</option>
                                <option value="7">Juillet</option>
                                <option value="8">Août</option>
                                <option value="9">Septembre</option>
                                <option value="10">Octobre</option>
                                <option value="11">Novembre</option>
                                <option value="12">Décembre</option>
                            </select>
                            <p class="text-[11px] text-slate-400 ml-1">Définit la période de référence pour les graphiques annuels et les comparaisons N-1.</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Tab 15: QR Code Addon -->
            <div v-if="selectedTab === 15" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-purple-50 dark:bg-purple-900/20 p-6 rounded-[2rem] border border-purple-100 dark:border-purple-800">
                    <div class="flex items-center gap-4 mb-4">
                        <QrCode class="w-8 h-8 text-purple-600" />
                        <h3 class="text-xl font-black text-purple-900 dark:text-purple-100">Add-on QR Code</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 font-medium">Générez des QR codes de paiement Stripe pour vos factures.</p>
                </div>
                <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
                    <span class="font-bold text-slate-700 dark:text-slate-300">Activer l'add-on QR Code</span>
                    <button 
                        type="button"
                        class="kloxy-toggle"
                        :aria-checked="(form.easy_compta_qrcode_addon_active == 1).toString()"
                        @click="toggleAddon('myeasycompta-qrcode-stripe', 'easy_compta_qrcode_addon_active')"
                    >
                        <span class="kloxy-toggle-thumb"></span>
                    </button>
                </div>
            </div>

            <!-- Tab 30: Application Mobile -->
            <div v-if="selectedTab === 30" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-indigo-50 dark:bg-indigo-900/20 p-6 rounded-[2rem] border border-indigo-100 dark:border-indigo-800">
                    <div class="flex items-center gap-4 mb-2">
                        <QrCode class="w-8 h-8 text-indigo-600 dark:text-indigo-300" />
                        <h3 class="text-xl font-black text-indigo-900 dark:text-indigo-100">Application mobile</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 font-medium text-sm">
                        Générez des tokens d'accès pour connecter l'app iOS/Android à votre instance WordPress.
                        Chaque token est un <span class="font-bold">Bearer token</span> (à garder secret).
                    </p>
                </div>

                <div v-if="mobileError" class="p-4 bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-200 dark:border-red-800 text-red-700 dark:text-red-200 text-sm">
                    {{ mobileError }}
                </div>

                <!-- Sub-tabs -->
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="mobileSubTab = 'tokens'"
                        :class="[
                            'px-4 py-2 rounded-2xl text-sm font-black border transition-colors',
                            mobileSubTab === 'tokens'
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700'
                        ]"
                    >
                        Tokens d’accès
                    </button>
                    <button
                        type="button"
                        @click="mobileSubTab = 'guide'"
                        :class="[
                            'px-4 py-2 rounded-2xl text-sm font-black border transition-colors',
                            mobileSubTab === 'guide'
                                ? 'bg-indigo-600 text-white border-indigo-600'
                                : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-700'
                        ]"
                    >
                        Guide de connexion
                    </button>
                </div>

                <div v-if="mobileSubTab === 'tokens'" class="space-y-6">
                <!-- Create token -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="flex items-center gap-3 px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                        <div class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                            <Plus class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-300" />
                        </div>
                        <span class="font-black text-xs uppercase tracking-widest text-slate-600 dark:text-slate-300">Créer un token</span>
                    </div>
                    <div class="p-5 bg-white dark:bg-slate-900">
                        <div class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3 items-end">
                            <div class="space-y-2">
                                <label class="kloxy-label">Nom du token</label>
                                <input type="text" v-model="mobileTokenName" class="kloxy-input" placeholder="Ex: iPhone Moez / iPad Bureau / Android test…" />
                                <p class="text-[11px] text-slate-400 ml-1">Astuce : crée un token par appareil. Tu peux révoquer un token à tout moment.</p>
                            </div>
                            <button
                                type="button"
                                @click="createMobileToken"
                                :disabled="mobileCreating"
                                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-sm disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                <Loader2 v-if="mobileCreating" class="w-4 h-4 animate-spin" />
                                <Plus v-else class="w-4 h-4" />
                                Générer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- QR / last created token -->
                <div v-if="mobileCreatedToken" class="rounded-2xl border border-emerald-200 dark:border-emerald-800 overflow-hidden bg-emerald-50/60 dark:bg-emerald-900/15">
                    <div class="flex items-center gap-3 px-5 py-3 border-b border-emerald-200/60 dark:border-emerald-800/60">
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-300" />
                        </div>
                        <span class="font-black text-xs uppercase tracking-widest text-emerald-700 dark:text-emerald-200">Token créé</span>
                    </div>
                    <div class="p-5 grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6">
                        <div class="flex flex-col items-center justify-center">
                            <div v-if="!mobileQrImageUrl" class="w-[220px] h-[220px] rounded-2xl border border-emerald-200 dark:border-emerald-800 bg-white flex items-center justify-center">
                                <Loader2 class="w-8 h-8 animate-spin text-emerald-600 dark:text-emerald-300" />
                            </div>
                            <img v-else :src="mobileQrImageUrl" alt="QR code" class="w-[220px] h-[220px] rounded-2xl border border-emerald-200 dark:border-emerald-800 bg-white" />
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-3 text-center">
                                Scanne ce QR code depuis l’écran de connexion de l’app.
                            </p>
                        </div>
                        <div class="space-y-3">
                            <div class="p-4 rounded-2xl bg-white/80 dark:bg-slate-900/60 border border-emerald-200 dark:border-emerald-800">
                                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-300 mb-2">Données scannées</p>
                                <pre class="text-[12px] leading-5 whitespace-pre-wrap break-all text-slate-800 dark:text-slate-100 font-mono">{{ mobileQrPayload }}</pre>
                                <div class="flex flex-wrap gap-2 mt-3">
                                    <button type="button" @click="copyToClipboard(mobileQrPayload)" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs">
                                        <Copy class="w-3.5 h-3.5" /> Copier
                                    </button>
                                    <button type="button" @click="copyToClipboard(mobileCreatedToken.token)" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-900/80 hover:bg-slate-900 text-white font-bold text-xs">
                                        <Eye class="w-3.5 h-3.5" /> Copier le token
                                    </button>
                                    <button type="button" @click="mobileCreatedToken = null" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white/70 dark:bg-slate-900/50 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-200 font-bold text-xs">
                                        <X class="w-3.5 h-3.5" /> Masquer
                                    </button>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-3">
                                    Important : ce token ne sera plus affiché ensuite. Garde-le en lieu sûr.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tokens list -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <div class="flex items-center justify-between gap-3 px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-900/30 flex items-center justify-center">
                                <Shield class="w-3.5 h-3.5 text-slate-600 dark:text-slate-300" />
                            </div>
                            <span class="font-black text-xs uppercase tracking-widest text-slate-600 dark:text-slate-300">Tokens existants</span>
                        </div>
                        <button type="button" @click="loadMobileTokens" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold">
                            <RefreshCcw class="w-3.5 h-3.5" /> Rafraîchir
                        </button>
                    </div>
                    <div class="p-0 bg-white dark:bg-slate-900">
                        <div v-if="mobileLoading" class="p-5 flex items-center gap-3 text-slate-500">
                            <Loader2 class="w-4 h-4 animate-spin" />
                            Chargement…
                        </div>
                        <div v-else-if="mobileTokens.length === 0" class="p-5 text-slate-500 text-sm">
                            Aucun token pour le moment.
                        </div>
                        <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
                            <div v-for="t in mobileTokens" :key="t.id" class="p-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-black text-slate-900 dark:text-slate-100">{{ t.name }}</span>
                                        <span v-if="t.is_active" class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-200 text-[11px] font-black">Actif</span>
                                        <span v-else class="px-2 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-black">Révoqué</span>
                                    </div>
                                    <div class="mt-1 text-[12px] text-slate-500 dark:text-slate-400 font-mono break-all">
                                        {{ t.token_prefix }}
                                    </div>
                                    <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">
                                        Créé : <span class="font-bold">{{ t.created_at || '—' }}</span>
                                        <span class="mx-2">·</span>
                                        Dernier usage : <span class="font-bold">{{ t.last_used_at || '—' }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2 justify-start lg:justify-end">
                                    <button v-if="t.is_active" type="button" @click="revokeMobileToken(t.id)" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-200 text-xs font-bold">
                                        <ShieldOffIcon class="w-3.5 h-3.5" /> Révoquer
                                    </button>
                                    <button type="button" @click="deleteMobileToken(t.id)" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-200 text-xs font-bold">
                                        <Trash2 class="w-3.5 h-3.5" /> Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                </div>

                <!-- Guide -->
                <div v-else class="space-y-6">
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-white dark:bg-slate-900">
                        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60">
                            <h4 class="font-black text-slate-900 dark:text-white">Comment connecter l’application mobile</h4>
                            <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-1">Suivez ces étapes, puis scannez un QR code ou renseignez les infos manuellement.</p>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white font-black flex items-center justify-center flex-shrink-0">1</div>
                                <div class="min-w-0">
                                    <p class="font-black text-slate-900 dark:text-white">Téléchargez l’application</p>
                                    <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-1">App Store / Google Play, ou Expo Go pendant le développement.</p>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        <span class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-black">🍎 App Store</span>
                                        <span class="px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-black">🤖 Google Play</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white font-black flex items-center justify-center flex-shrink-0">2</div>
                                <div class="min-w-0">
                                    <p class="font-black text-slate-900 dark:text-white">Créez un token d’accès</p>
                                    <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-1">Dans l’onglet <span class="font-bold">Tokens d’accès</span>, cliquez sur <span class="font-bold">Générer</span> puis copiez le token.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white font-black flex items-center justify-center flex-shrink-0">3</div>
                                <div class="min-w-0">
                                    <p class="font-black text-slate-900 dark:text-white">Connectez-vous via QR code ou manuellement</p>
                                    <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-1">Renseignez l’URL du site + le token dans l’app, ou scannez le QR code ci-dessous.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-50 dark:bg-slate-800/40">
                        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700">
                            <h4 class="font-black text-slate-900 dark:text-white">QR code de connexion rapide</h4>
                            <p class="text-[12px] text-slate-500 dark:text-slate-400 mt-1">Sélectionnez un token actif, collez le token complet, puis scannez.</p>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="space-y-2 max-w-lg">
                                <label class="kloxy-label">Choisir un token</label>
                                <select v-model="mobileGuideTokenId" class="kloxy-input appearance-none">
                                    <option value="">— Sélectionner un token —</option>
                                    <option v-for="t in mobileActiveTokens" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                                </select>
                                <div v-if="mobileActiveTokens.length === 0" class="p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 text-[12px] font-semibold">
                                    Aucun token actif. Créez d’abord un token dans l’onglet <span class="font-black">Tokens d’accès</span>.
                                </div>
                                <div v-else class="p-3 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 text-indigo-800 dark:text-indigo-200 text-[12px] font-semibold">
                                    Le token complet n’est affiché qu’à la création. Collez-le ici pour générer le QR code.
                                </div>
                            </div>

                            <div v-if="mobileGuideTokenId" class="space-y-2 max-w-lg">
                                <label class="kloxy-label">Token complet (commence par <span class="font-black">mec_</span>)</label>
                                <input v-model="mobileGuideFullToken" type="text" class="kloxy-input font-mono" placeholder="mec_..." />
                                <div v-if="mobileGuideFullToken && !mobileGuideFullToken.trim().startsWith('mec_')" class="text-[12px] text-red-700 dark:text-red-200 font-semibold">
                                    Token invalide. Il doit commencer par <span class="font-black">mec_</span>.
                                </div>
                            </div>

                            <div v-if="mobileGuideQrImageUrl" class="grid grid-cols-1 lg:grid-cols-[260px_1fr] gap-6 items-start">
                                <div class="flex flex-col items-center justify-center">
                                    <div v-if="!mobileGuideQrImageUrl" class="w-[220px] h-[220px] rounded-2xl border border-slate-200 dark:border-slate-700 bg-white flex items-center justify-center">
                                        <Loader2 class="w-8 h-8 animate-spin text-slate-400" />
                                    </div>
                                    <img v-else :src="mobileGuideQrImageUrl" alt="QR code" class="w-[220px] h-[220px] rounded-2xl border border-slate-200 dark:border-slate-700 bg-white" />
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-3 text-center">
                                        Scanne ce QR code depuis l’écran de connexion de l’app.
                                    </p>
                                </div>
                                <div class="space-y-3">
                                    <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Données scannées</p>
                                        <pre class="text-[12px] leading-5 whitespace-pre-wrap break-all text-slate-800 dark:text-slate-100 font-mono">{{ mobileGuideQrPayload }}</pre>
                                        <div class="flex flex-wrap gap-2 mt-3">
                                            <button type="button" @click="copyToClipboard(mobileGuideQrPayload)" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs">
                                                <Copy class="w-3.5 h-3.5" /> Copier
                                            </button>
                                            <button type="button" @click="copyToClipboard(mobileGuideFullToken.trim())" class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-900/80 hover:bg-slate-900 text-white font-bold text-xs">
                                                <Eye class="w-3.5 h-3.5" /> Copier le token
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl overflow-hidden bg-slate-900 border border-slate-800">
                        <div class="px-6 py-5 border-b border-slate-800">
                            <h4 class="font-black text-slate-100">Informations de connexion manuelle</h4>
                            <p class="text-[12px] text-slate-400 mt-1">Clique pour copier.</p>
                        </div>
                        <div class="p-6 space-y-3">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                <span class="text-[12px] text-slate-400 font-black uppercase tracking-widest">URL du site</span>
                                <code class="text-[12px] text-sky-200 bg-white/5 px-3 py-2 rounded-xl cursor-pointer break-all" @click="copyToClipboard(mobileSiteUrl)">{{ mobileSiteUrl }}</code>
                            </div>
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                <span class="text-[12px] text-slate-400 font-black uppercase tracking-widest">Endpoint API</span>
                                <code class="text-[12px] text-sky-200 bg-white/5 px-3 py-2 rounded-xl cursor-pointer break-all" @click="copyToClipboard(mobileApiVerifyUrl)">{{ mobileApiVerifyUrl }}</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 22: Contracts template -->
            <div v-if="selectedTab === 22" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-purple-50 dark:bg-purple-900/20 p-6 rounded-[2rem] border border-purple-100 dark:border-purple-800">
                    <div class="flex items-center gap-4 mb-2">
                        <ScrollText class="w-8 h-8 text-purple-600" />
                        <h3 class="text-xl font-black text-purple-900 dark:text-purple-100">Modèle de contrat par défaut</h3>
                    </div>
                    <p class="text-slate-600 dark:text-slate-400 font-medium text-sm">Ce modèle sera proposé au chargement d'un nouveau contrat. Utilisez les variables entre accolades pour personnaliser automatiquement le contenu.</p>
                </div>

                <!-- Variables reference -->
                <div class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl border border-indigo-100 dark:border-indigo-800">
                    <p class="text-[10px] font-black uppercase tracking-widest text-indigo-500 mb-3">Variables disponibles</p>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="v in contractVariables" :key="v" class="px-2.5 py-1 rounded-lg bg-white dark:bg-slate-800 border border-indigo-200 dark:border-indigo-700 text-[11px] font-black text-indigo-600 dark:text-indigo-400 cursor-default">{{ v }}</span>
                    </div>
                </div>

                <!-- Default title -->
                <div class="space-y-2">
                    <label class="kloxy-label">Titre par défaut</label>
                    <input type="text" v-model="form.contract_default_title" class="kloxy-input" placeholder="Ex: Contrat de prestation de services — {CLIENT_NAME}" />
                </div>

                <!-- Default body -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="kloxy-label">Corps du contrat par défaut</label>
                        <button type="button" @click="loadContractExample" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 text-indigo-600 dark:text-indigo-400 text-xs font-bold hover:bg-indigo-100 dark:hover:bg-indigo-800/40 transition-colors">
                            <FileText class="w-3.5 h-3.5" />
                            Importer un exemple
                        </button>
                    </div>
                    <div class="rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700">
                        <VueEditor v-model="form.contract_default_body" :editor-toolbar="contractEditorToolbar" placeholder="Rédigez ici le corps de votre modèle de contrat..." />
                    </div>
                    <p class="text-[11px] text-slate-400">Ce contenu sera pré-rempli à chaque fois que vous cliquez sur « Charger le modèle » lors de la création d'un contrat.</p>
                </div>
            </div>

            <!-- Tab 23: TimeTracking -->
            <div v-if="selectedTab === 23" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-purple-50 dark:bg-purple-900/20 p-6 rounded-[2rem] border border-purple-100 dark:border-purple-800 flex items-center gap-4">
                    <Clock class="w-8 h-8 text-purple-600 flex-shrink-0" />
                    <div>
                        <h3 class="text-xl font-black text-purple-900 dark:text-purple-100">Temps & Facturation</h3>
                        <p class="text-slate-600 dark:text-slate-400 font-medium text-sm">Chronomètre, entrées manuelles et génération de factures depuis les heures saisies.</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="kloxy-label">Taux horaire par défaut (€/h)</label>
                    <input type="number" v-model="form.timetracking_default_rate" min="0" step="0.5" class="kloxy-input w-48" placeholder="0" />
                    <p class="text-[11px] text-slate-400">Pré-rempli lors de la création d'une nouvelle entrée de temps.</p>
                </div>
            </div>

            <!-- Tab 24: Delivery -->
            <div v-if="selectedTab === 24" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-purple-50 dark:bg-purple-900/20 p-6 rounded-[2rem] border border-purple-100 dark:border-purple-800 flex items-center gap-4">
                    <Truck class="w-8 h-8 text-purple-600 flex-shrink-0" />
                    <div>
                        <h3 class="text-xl font-black text-purple-900 dark:text-purple-100">Bons de livraison</h3>
                        <p class="text-slate-600 dark:text-slate-400 font-medium text-sm">Numérotation automatique et personnalisation du PDF.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="kloxy-label">Préfixe du numéro de BL</label>
                        <input type="text" v-model="form.delivery_prefix" class="kloxy-input" placeholder="BL" />
                        <p class="text-[11px] text-slate-400">Exemple : BL → <strong>BL-0001</strong></p>
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <label class="kloxy-label">Pied de page du bon de livraison</label>
                        <textarea v-model="form.delivery_footer" rows="3" class="kloxy-input resize-none" placeholder="Texte affiché en bas de chaque bon de livraison..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Tab 25: OnlineQuote -->
            <div v-if="selectedTab === 25" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-purple-50 dark:bg-purple-900/20 p-6 rounded-[2rem] border border-purple-100 dark:border-purple-800 flex items-center gap-4">
                    <Globe class="w-8 h-8 text-purple-600 flex-shrink-0" />
                    <div>
                        <h3 class="text-xl font-black text-purple-900 dark:text-purple-100">Devis interactif en ligne</h3>
                        <p class="text-slate-600 dark:text-slate-400 font-medium text-sm">Partagez vos devis via un lien sécurisé — vos clients peuvent accepter ou refuser sans compte.</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="kloxy-label">Durée de validité du lien (jours)</label>
                    <input type="number" v-model="form.online_quote_expiry_days" min="1" max="365" class="kloxy-input w-48" placeholder="30" />
                    <p class="text-[11px] text-slate-400">0 = sans expiration.</p>
                </div>
                <div class="space-y-2">
                    <label class="kloxy-label">Message affiché après acceptation</label>
                    <textarea v-model="form.online_quote_accept_message" rows="3" class="kloxy-input resize-none"></textarea>
                </div>
                <div class="space-y-2">
                    <label class="kloxy-label">Message affiché après refus</label>
                    <textarea v-model="form.online_quote_reject_message" rows="3" class="kloxy-input resize-none"></textarea>
                </div>
            </div>

            <div v-if="selectedTab !== 10" class="flex justify-end pt-6 border-t border-slate-100 dark:border-slate-800">
                <button type="submit" class="kloxy-btn-primary">
                    <Save class="w-4 h-4 mr-2" /> {{ translations.save || 'Enregistrer' }}
                </button>
            </div>
         </form>

         <!-- Tab 26: SMS Notifications -->
         <div v-if="selectedTab === 26" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">
             <div class="bg-purple-50 dark:bg-purple-900/20 p-6 rounded-[2rem] border border-purple-100 dark:border-purple-800 flex items-center justify-between gap-4">
                 <div class="flex items-center gap-4">
                     <MessageSquare class="w-8 h-8 text-purple-600 flex-shrink-0" />
                     <div>
                         <h3 class="text-xl font-black text-purple-900 dark:text-purple-100">Notifications SMS</h3>
                         <p class="text-slate-600 dark:text-slate-400 font-medium text-sm">Envoyez des SMS automatiques à vos clients ou à l'administrateur lors d'événements clés.</p>
                     </div>
                 </div>
                 <div class="flex items-center gap-3 flex-shrink-0">
                     <span :class="['px-3 py-1 rounded-full text-xs font-black uppercase', smsSettings.enabled ? 'bg-emerald-100 text-emerald-600' : 'bg-slate-100 text-slate-400']">
                         {{ smsSettings.enabled ? 'Actif' : 'Inactif' }}
                     </span>
                     <button @click="saveSmsSettings" :disabled="smsSaving" class="kloxy-btn-primary">
                         <Loader2 v-if="smsSaving" class="w-4 h-4 mr-2 animate-spin" />
                         <Save v-else class="w-4 h-4 mr-2" /> Enregistrer
                     </button>
                 </div>
             </div>

             <div v-if="smsLoading" class="flex justify-center py-8"><Loader2 class="w-8 h-8 text-purple-600 animate-spin" /></div>

             <template v-else>
                 <!-- Global toggle -->
                 <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
                     <div>
                         <span class="font-bold text-slate-700 dark:text-slate-300">Activer les notifications SMS</span>
                         <p class="text-xs text-slate-400 mt-0.5">Les SMS ne seront envoyés que si cette option est activée.</p>
                     </div>
                     <button type="button" class="kloxy-toggle" :aria-checked="(!!smsSettings.enabled).toString()" @click="smsSettings.enabled = !smsSettings.enabled">
                         <span class="kloxy-toggle-thumb"></span>
                     </button>
                 </div>

                 <!-- Mode: auto / manual -->
                 <div class="p-6 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 space-y-4">
                     <div>
                         <h4 class="font-black text-slate-900 dark:text-white">Mode d'envoi</h4>
                         <p class="text-xs text-slate-400 mt-0.5">Choisissez si les SMS sont envoyés automatiquement lors des événements ou uniquement à la demande depuis la fiche facture/devis.</p>
                     </div>
                     <div class="flex gap-3">
                         <button type="button" @click="smsSettings.mode = 'auto'"
                             :class="['flex-1 py-3 rounded-2xl border text-sm font-bold transition-all', (smsSettings.mode ?? 'auto') === 'auto' ? 'bg-purple-600 text-white border-purple-600 shadow-lg shadow-purple-500/30' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-purple-300']">
                             ⚡ Automatique
                         </button>
                         <button type="button" @click="smsSettings.mode = 'manual'"
                             :class="['flex-1 py-3 rounded-2xl border text-sm font-bold transition-all', smsSettings.mode === 'manual' ? 'bg-purple-600 text-white border-purple-600 shadow-lg shadow-purple-500/30' : 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-purple-300']">
                             ✋ Manuel
                         </button>
                     </div>
                     <!-- Auto mode warning -->
                     <div v-if="(smsSettings.mode ?? 'auto') === 'auto'" class="flex items-start gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl px-4 py-3">
                         <span class="text-amber-500 text-lg leading-none mt-0.5">⚠️</span>
                         <p class="text-xs text-amber-700 dark:text-amber-300 font-medium">En mode automatique, un SMS/WhatsApp sera envoyé à chaque événement configuré ci-dessous sans confirmation préalable.</p>
                     </div>
                 </div>

                <!-- Provider -->
                 <div class="p-6 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 space-y-5">
                     <h4 class="font-black text-slate-900 dark:text-white">Fournisseur SMS</h4>
                     <div class="flex gap-3">
                         <button v-for="p in [{ value: 'twilio', label: 'Twilio' }, { value: 'ovh', label: 'OVH SMS' }]" :key="p.value"
                             type="button" @click="smsSettings.provider = p.value"
                             :class="['px-5 py-2.5 rounded-2xl border text-sm font-bold transition-all', smsSettings.provider === p.value ? 'bg-purple-600 text-white border-purple-600 shadow-lg shadow-purple-500/30' : 'border-slate-200 dark:border-slate-700 text-slate-600 hover:border-purple-300']">
                             {{ p.label }}
                         </button>
                     </div>

                     <!-- Twilio -->
                     <div v-if="smsSettings.provider === 'twilio'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div class="space-y-2">
                             <label class="kloxy-label">Account SID</label>
                             <input v-model="smsSettings.twilio_sid" class="kloxy-input" placeholder="ACxxxxxxxxxxxxxxxx" />
                         </div>
                         <div class="space-y-2">
                             <label class="kloxy-label">Auth Token</label>
                             <input v-model="smsSettings.twilio_token" type="password" class="kloxy-input" placeholder="••••••••" />
                         </div>
                         <div class="space-y-2">
                             <label class="kloxy-label">Numéro expéditeur</label>
                             <input v-model="smsSettings.twilio_from" class="kloxy-input" placeholder="+33600000000" />
                         </div>
                     </div>

                     <!-- OVH -->
                     <div v-if="smsSettings.provider === 'ovh'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div class="space-y-2"><label class="kloxy-label">Application Key</label><input v-model="smsSettings.ovh_app_key" class="kloxy-input" /></div>
                         <div class="space-y-2"><label class="kloxy-label">Application Secret</label><input v-model="smsSettings.ovh_app_secret" type="password" class="kloxy-input" /></div>
                         <div class="space-y-2"><label class="kloxy-label">Consumer Key</label><input v-model="smsSettings.ovh_consumer_key" class="kloxy-input" /></div>
                         <div class="space-y-2"><label class="kloxy-label">Service Name</label><input v-model="smsSettings.ovh_service_name" class="kloxy-input" placeholder="sms-xxxxx" /></div>
                         <div class="space-y-2"><label class="kloxy-label">Expéditeur (optionnel)</label><input v-model="smsSettings.ovh_sender" class="kloxy-input" /></div>
                     </div>

                     <!-- Admin phone -->
                     <div class="space-y-2">
                         <label class="kloxy-label">Téléphone administrateur</label>
                         <input v-model="smsSettings.admin_phone" class="kloxy-input w-64" placeholder="+33600000000" />
                         <p class="text-[11px] text-slate-400">Utilisé pour les notifications destinées à l'administrateur.</p>
                     </div>
                 </div>

                 <!-- WhatsApp -->
                 <div class="p-6 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 space-y-5">
                     <div class="flex items-center gap-3">
                         <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                             <MessageSquare class="w-4 h-4 text-emerald-600" />
                         </div>
                         <h4 class="font-black text-slate-900 dark:text-white">WhatsApp</h4>
                     </div>
                     <p class="text-xs text-slate-400">Choisissez le fournisseur WhatsApp. Configurez-le pour pouvoir envoyer via WhatsApp sur chaque événement.</p>

                     <!-- WA provider selector -->
                     <div class="flex gap-3">
                         <button v-for="wp in [{ value: 'twilio_wa', label: 'Twilio WhatsApp' }, { value: 'meta', label: 'Meta Business API' }]" :key="wp.value"
                             type="button" @click="smsSettings.wa_provider = wp.value"
                             :class="['px-5 py-2.5 rounded-2xl border text-sm font-bold transition-all', smsSettings.wa_provider === wp.value ? 'bg-emerald-600 text-white border-emerald-600 shadow-lg shadow-emerald-500/30' : 'border-slate-200 dark:border-slate-700 text-slate-600 hover:border-emerald-300']">
                             {{ wp.label }}
                         </button>
                     </div>

                     <!-- Twilio WhatsApp -->
                     <div v-if="smsSettings.wa_provider === 'twilio_wa'" class="space-y-3">
                         <p class="text-[11px] text-slate-400">Utilise les mêmes identifiants Twilio (SID + Token). Renseignez uniquement le numéro WhatsApp expéditeur.</p>
                         <div class="space-y-2">
                             <label class="kloxy-label">Numéro WhatsApp expéditeur</label>
                             <input v-model="smsSettings.twilio_whatsapp_from" class="kloxy-input w-64" placeholder="+14155238886" />
                             <p class="text-[11px] text-slate-400">Numéro approuvé dans la console Twilio (sandbox : +1 415 523 8886).</p>
                         </div>
                     </div>

                     <!-- Meta Business API -->
                     <div v-if="smsSettings.wa_provider === 'meta'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                         <div class="space-y-2">
                             <label class="kloxy-label">Access Token permanent</label>
                             <input v-model="smsSettings.meta_wa_token" type="password" class="kloxy-input" placeholder="EAAxxxxxxxx..." />
                         </div>
                         <div class="space-y-2">
                             <label class="kloxy-label">Phone Number ID</label>
                             <input v-model="smsSettings.meta_wa_phone_id" class="kloxy-input" placeholder="1234567890" />
                             <p class="text-[11px] text-slate-400">Trouvable dans Meta for Developers → WhatsApp → Getting Started.</p>
                         </div>
                     </div>
                 </div>

                 <!-- Test -->
                 <div class="p-6 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 space-y-3">
                     <h5 class="text-sm font-black text-slate-500">Envoyer un message de test</h5>
                     <div class="flex items-center gap-3">
                         <input v-model="smsTestPhone" class="kloxy-input w-64" placeholder="+33600000000" />
                         <button type="button" @click="sendSmsTest" :disabled="smsTesting || !smsTestPhone"
                             class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-2xl text-sm font-bold disabled:opacity-50 transition-colors">
                             <Loader2 v-if="smsTesting" class="w-4 h-4 animate-spin" />
                             <Send v-else class="w-4 h-4" />
                             {{ smsTesting ? 'Envoi...' : 'Tester' }}
                         </button>
                     </div>
                     <p v-if="smsTestResult" :class="['text-sm font-bold', smsTestResult.startsWith('✓') ? 'text-emerald-600' : 'text-rose-600']">{{ smsTestResult }}</p>
                 </div>

                 <!-- Events -->
                 <div class="p-6 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 space-y-4">
                     <h4 class="font-black text-slate-900 dark:text-white">Événements</h4>
                     <div v-for="event in smsEvents" :key="event.key" class="space-y-3 pb-4 border-b border-slate-100 dark:border-slate-800 last:border-0 last:pb-0">
                         <div class="flex items-center justify-between">
                             <div>
                                 <p class="font-bold text-sm text-slate-800 dark:text-white">{{ event.label }}</p>
                                 <p class="text-xs text-slate-400">{{ event.desc }}</p>
                             </div>
                             <button type="button" class="kloxy-toggle"
                                 :aria-checked="(!!(smsSettings.events?.[event.key]?.enabled)).toString()"
                                 @click="toggleSmsEvent(event.key)">
                                 <span class="kloxy-toggle-thumb"></span>
                             </button>
                         </div>
                         <div v-if="smsSettings.events?.[event.key]?.enabled" class="space-y-3 pl-4 border-l-2 border-purple-200 dark:border-purple-800">
                             <!-- Recipient -->
                             <div class="flex gap-3">
                                 <label v-for="r in ['client','admin','both']" :key="r" class="flex items-center gap-1.5 cursor-pointer">
                                     <input type="radio" :name="'recipient_'+event.key" :value="r" v-model="smsSettings.events[event.key].recipient" class="accent-purple-600" />
                                     <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ { client: 'Client', admin: 'Admin', both: 'Les deux' }[r] }}</span>
                                 </label>
                             </div>
                             <!-- Channel -->
                             <div class="flex items-center gap-2">
                                 <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Canal :</span>
                                 <div class="flex gap-2">
                                     <button v-for="ch in [{ value: 'sms', label: '💬 SMS' }, { value: 'whatsapp', label: '📱 WhatsApp' }, { value: 'both', label: '🔀 Les deux' }]" :key="ch.value"
                                         type="button" @click="smsSettings.events[event.key].channel = ch.value"
                                         :class="['px-3 py-1 rounded-xl border text-xs font-bold transition-all', smsSettings.events[event.key].channel === ch.value ? 'bg-emerald-600 text-white border-emerald-600' : 'border-slate-200 dark:border-slate-700 text-slate-500 hover:border-emerald-300']">
                                         {{ ch.label }}
                                     </button>
                                 </div>
                             </div>
                             <textarea v-model="smsSettings.events[event.key].template" rows="2"
                                 class="kloxy-input resize-none font-mono text-xs"
                                 :placeholder="event.defaultTemplate"></textarea>
                             <p class="text-[10px] text-slate-400">Variables: {CLIENT_NAME}, {INVOICE_NUMBER}, {AMOUNT}, {COMPANY_NAME}</p>
                         </div>
                     </div>
                 </div>
             </template>
         </div>

         <!-- Tab 27: Webhooks & Zapier -->
         <div v-if="selectedTab === 27" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
           <Webhooks />
         </div>

         <!-- Tab 28: Export FEC -->
         <div v-if="selectedTab === 28" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
           <FEC />
         </div>

         <!-- Tab 29: Scan Reçus (OCR) -->
         <div v-if="selectedTab === 29" class="animate-in fade-in slide-in-from-bottom-4 duration-500 space-y-8">
           <!-- OCR API Key settings -->
           <div class="bg-gradient-to-br from-violet-50 to-fuchsia-50 dark:from-violet-950/30 dark:to-fuchsia-950/30 rounded-[2rem] p-8 border border-violet-100 dark:border-violet-900/40">
             <div class="flex items-start gap-5">
               <div class="w-14 h-14 bg-violet-600 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-500/30 shrink-0">
                 <ScanLine class="w-7 h-7 text-white" />
               </div>
               <div>
                 <h2 class="text-xl font-black text-slate-900 dark:text-white">Scan de Reçus (OCR)</h2>
                 <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                   Photographiez ou uploadez un reçu pour pré-remplir automatiquement vos dépenses. Propulsé par OCR.space.
                 </p>
               </div>
             </div>
           </div>

           <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-8 border border-slate-100 dark:border-slate-800 space-y-6">
             <h3 class="text-sm font-black uppercase tracking-widest text-slate-400">Configuration API</h3>

             <div class="space-y-2">
               <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">Clé API OCR.space</label>
               <input
                 type="text"
                 v-model="ocrApiKey"
                 placeholder="Votre clé API (optionnel – une clé gratuite suffit)"
                 class="kloxy-input"
               />
               <p class="text-xs text-slate-400 ml-2 mt-1">
                 Créez une clé gratuite sur <a href="https://ocr.space/ocrapi" target="_blank" class="text-violet-500 hover:underline">ocr.space</a>.
                 Sans clé, la démo publique est utilisée (limites strictes).
               </p>
             </div>

             <div class="space-y-2">
               <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-2">Langue par défaut</label>
               <div class="relative">
                 <select v-model="ocrLanguage" class="kloxy-select-native">
                   <option value="fre">Français</option>
                   <option value="eng">Anglais</option>
                   <option value="ger">Allemand</option>
                   <option value="spa">Espagnol</option>
                   <option value="ita">Italien</option>
                   <option value="por">Portugais</option>
                 </select>
                 <ChevronDown class="w-4 h-4 text-slate-400 absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none" />
               </div>
             </div>

             <div class="flex justify-end pt-2">
               <button @click="saveOcrSettings" :disabled="savingOcr" class="flex items-center gap-2 px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest bg-violet-600 text-white hover:scale-105 active:scale-95 transition-all shadow-lg shadow-violet-500/30 disabled:opacity-50">
                 <span v-if="savingOcr" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                 <Save v-else class="w-4 h-4" />
                 Enregistrer
               </button>
             </div>
           </div>

           <div class="bg-slate-50 dark:bg-slate-900/50 rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800">
             <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 flex items-center gap-2">
               <ScanLine class="w-3.5 h-3.5" /> Comment utiliser
             </h4>
             <ol class="space-y-3 text-sm text-slate-600 dark:text-slate-400">
               <li class="flex items-start gap-3">
                 <span class="w-6 h-6 bg-violet-100 dark:bg-violet-900/40 text-violet-600 rounded-lg flex items-center justify-center font-black text-xs shrink-0">1</span>
                 Allez dans <strong>Dépenses</strong> et cliquez sur <strong>"Scanner un reçu"</strong> pour ouvrir le formulaire d'ajout.
               </li>
               <li class="flex items-start gap-3">
                 <span class="w-6 h-6 bg-violet-100 dark:bg-violet-900/40 text-violet-600 rounded-lg flex items-center justify-center font-black text-xs shrink-0">2</span>
                 Uploadez une photo du reçu (JPG, PNG, PDF) — l'OCR extrait automatiquement le montant, la date et le fournisseur.
               </li>
               <li class="flex items-start gap-3">
                 <span class="w-6 h-6 bg-violet-100 dark:bg-violet-900/40 text-violet-600 rounded-lg flex items-center justify-center font-black text-xs shrink-0">3</span>
                 Vérifiez et complétez les champs pré-remplis, puis enregistrez la dépense.
               </li>
             </ol>
           </div>
         </div>

         <!-- Tab 16: License Management -->
         <div v-if="selectedTab === 16" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">

           <!-- ── Hero licence card ─────────────────────────────────────────── -->
           <div class="relative overflow-hidden rounded-[2.5rem] bg-slate-950 dark:bg-[#08061a] shadow-2xl">
             <!-- Ambient blobs -->
             <div class="pointer-events-none absolute -top-16 -right-16 w-64 h-64 rounded-full bg-purple-600/20 blur-3xl"></div>
             <div class="pointer-events-none absolute -bottom-12 -left-12 w-48 h-48 rounded-full bg-indigo-600/15 blur-3xl"></div>
             <!-- Dot grid -->
             <div class="pointer-events-none absolute inset-0 opacity-[0.04]" style="background-image:radial-gradient(circle,#fff 1px,transparent 1px);background-size:24px 24px"></div>

             <div class="relative z-10 p-8 md:p-10">
               <!-- Top row: status + actions -->
               <div class="flex items-start justify-between gap-6 mb-8">
                 <div class="space-y-3">
                   <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/30">Activation de la licence</p>
                   <div class="flex flex-wrap items-center gap-2">
                     <!-- Active/Inactive pill -->
                     <span :class="[
                       'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-widest',
                       licenseData?.valid
                         ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30'
                         : 'bg-slate-700/60 text-slate-400 border border-slate-600/40'
                     ]">
                       <span :class="['w-1.5 h-1.5 rounded-full', licenseData?.valid ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500']"></span>
                       {{ licenseData?.valid ? 'Active' : 'Inactive' }}
                     </span>
                     <!-- Bundle pill -->
                     <span v-if="licenseData?.is_bundle" class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-widest bg-amber-500/15 text-amber-400 border border-amber-500/30">
                       <Star class="w-3 h-3" /> Pack complet
                     </span>
                     <!-- Custom plan pill -->
                     <span v-else-if="licenseData?.valid" class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-widest bg-purple-500/15 text-purple-400 border border-purple-500/30">
                       À la carte
                     </span>
                   </div>
                 </div>
                 <!-- Actions -->
                 <div class="flex gap-2 shrink-0">
                   <button @click="refreshLicense" :title="translations.refresh || 'Rafraîchir'"
                     class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/8 hover:bg-white/14 border border-white/10 text-white/60 hover:text-white transition-all">
                     <RefreshCcw :class="{'animate-spin': licenseLoading}" class="w-4 h-4" />
                   </button>
                   <button v-if="licenseData?.valid" @click="deleteLicense" title="Désactiver la licence"
                     class="flex items-center justify-center w-10 h-10 rounded-xl bg-rose-500/12 hover:bg-rose-500/22 border border-rose-500/25 text-rose-400 hover:text-rose-300 transition-all">
                     <Trash2 class="w-4 h-4" />
                   </button>
                 </div>
               </div>

               <!-- License key row -->
               <div class="space-y-2 mb-8">
                 <p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/30">Clé de licence</p>
                 <div class="flex flex-col md:flex-row gap-3">
                   <div class="relative flex-1">
                     <input
                       v-model="licenseKey"
                       type="text"
                       class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 font-mono text-base font-bold tracking-[0.12em] uppercase text-white placeholder-white/20 focus:outline-none focus:border-purple-500/60 focus:bg-white/8 transition-all"
                       placeholder="MEC-XXXX-XXXX-XXXX-XXXX"
                       :disabled="licenseData?.valid"
                     />
                     <!-- Copy btn when active -->
                     <button v-if="licenseData?.valid" @click="copyLicenseKey"
                       class="absolute right-3 top-1/2 -translate-y-1/2 p-2 rounded-lg bg-white/8 hover:bg-white/15 text-white/40 hover:text-white/80 transition-all" title="Copier">
                       <Copy class="w-3.5 h-3.5" />
                     </button>
                   </div>
                   <button
                     v-if="!licenseData?.valid"
                     @click="handleActivateLicense"
                     :disabled="licenseLoading || !licenseKey"
                     class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-sm tracking-wide shadow-lg shadow-purple-900/40 transition-all disabled:opacity-50 disabled:pointer-events-none whitespace-nowrap"
                   >
                     <Loader2 v-if="licenseLoading" class="w-4 h-4 animate-spin" />
                     <Zap v-else class="w-4 h-4" />
                     {{ translations.activate || 'Activer la licence' }}
                   </button>
                 </div>
               </div>

               <!-- Meta row (visible when valid) -->
               <div v-if="licenseData?.valid" class="grid grid-cols-2 md:grid-cols-4 gap-px bg-white/5 rounded-2xl overflow-hidden border border-white/8">
                 <div v-if="licenseData.client_name" class="bg-white/3 hover:bg-white/5 transition-colors px-5 py-4">
                   <p class="text-[9px] font-black uppercase tracking-[0.18em] text-white/30 mb-1">Client</p>
                   <p class="text-sm font-bold text-white truncate">{{ licenseData.client_name }}</p>
                 </div>
                 <div v-if="licenseData.email" class="bg-white/3 hover:bg-white/5 transition-colors px-5 py-4">
                   <p class="text-[9px] font-black uppercase tracking-[0.18em] text-white/30 mb-1">Email</p>
                   <p class="text-sm font-bold text-white truncate">{{ licenseData.email }}</p>
                 </div>
                 <div v-if="licenseData.domain" class="bg-white/3 hover:bg-white/5 transition-colors px-5 py-4">
                   <p class="text-[9px] font-black uppercase tracking-[0.18em] text-white/30 mb-1">Domaine</p>
                   <p class="text-sm font-bold text-white truncate">{{ licenseData.domain }}</p>
                 </div>
                 <div class="bg-white/3 hover:bg-white/5 transition-colors px-5 py-4">
                   <p class="text-[9px] font-black uppercase tracking-[0.18em] text-white/30 mb-1">Sites</p>
                   <div class="flex items-center gap-2 mt-0.5">
                     <p class="text-sm font-black text-white">{{ licenseData.sites_used ?? 1 }}<span class="text-white/30">/{{ licenseData.max_sites ?? 1 }}</span></p>
                     <!-- Sites progress bar -->
                     <div class="flex-1 h-1 bg-white/10 rounded-full overflow-hidden">
                       <div class="h-full bg-gradient-to-r from-purple-500 to-indigo-400 rounded-full transition-all"
                         :style="{ width: `${Math.min(100, ((licenseData.sites_used ?? 1) / (licenseData.max_sites ?? 1)) * 100)}%` }">
                       </div>
                     </div>
                   </div>
                 </div>
               </div>

               <!-- Unauthenticated state hint -->
               <div v-if="!licenseData?.valid" class="flex items-center gap-3 p-4 rounded-2xl bg-white/4 border border-white/8">
                 <ShieldCheck class="w-5 h-5 text-purple-400 shrink-0" />
                 <p class="text-sm text-white/50 font-medium">Entrez votre clé de licence pour accéder à vos modules et les installer en un clic.</p>
               </div>
             </div>
           </div>

           <!-- ── Addons grid ──────────────────────────────────────────────── -->
           <template v-if="licenseData?.plugins && Object.keys(licenseData.plugins).length > 0">
             <div class="flex items-center justify-between px-1">
               <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">Modules inclus — {{ Object.keys(licenseData.plugins).length }} addon{{ Object.keys(licenseData.plugins).length > 1 ? 's' : '' }}</p>
               <p class="text-[10px] font-bold text-slate-400">{{ Object.values(licenseData.plugins).filter(p => installedVersions[Object.keys(licenseData.plugins).find(k => licenseData.plugins[k] === p)]).length }} installé(s)</p>
             </div>

             <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
               <div
                 v-for="(plugin, slug) in licenseData.plugins"
                 :key="slug"
                 class="group relative bg-white dark:bg-slate-900 rounded-[1.75rem] border border-slate-100 dark:border-slate-800 overflow-hidden hover:border-purple-400/40 dark:hover:border-purple-500/30 hover:shadow-lg hover:shadow-purple-500/5 transition-all duration-200"
               >
                 <!-- Status accent line -->
                 <div :class="['absolute top-0 left-0 right-0 h-0.5', installedVersions[slug] ? (isUpdateAvailable(slug, plugin.version) ? 'bg-amber-400' : 'bg-emerald-400') : 'bg-transparent group-hover:bg-purple-500/40']"></div>

                 <div class="p-5">
                   <div class="flex items-start gap-3 mb-5">
                     <!-- Icon -->
                     <div :class="['w-11 h-11 rounded-xl flex items-center justify-center shrink-0 transition-all', installedVersions[slug] ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' : 'bg-slate-50 dark:bg-slate-800 text-slate-400']">
                       <BadgeCheck v-if="installedVersions[slug] && !isUpdateAvailable(slug, plugin.version)" class="w-5 h-5" />
                       <RefreshCcw v-else-if="isUpdateAvailable(slug, plugin.version)" class="w-5 h-5 text-amber-500" />
                       <ShoppingBag v-else class="w-5 h-5" />
                     </div>
                     <!-- Name + slug -->
                     <div class="flex-1 min-w-0">
                       <h4 class="font-black text-slate-800 dark:text-slate-100 text-sm leading-tight truncate">{{ plugin.product_name }}</h4>
                       <p class="text-[10px] font-mono text-slate-400 mt-0.5 truncate">{{ slug }}</p>
                     </div>
                   </div>

                   <!-- Bottom row: status + action -->
                   <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                     <div>
                       <!-- Installed & up to date -->
                       <template v-if="installedVersions[slug] && !isUpdateAvailable(slug, plugin.version)">
                         <span class="flex items-center gap-1 text-[10px] font-black text-emerald-500 uppercase tracking-widest">
                           <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Installé
                         </span>
                         <p class="text-[10px] text-slate-400 mt-0.5">v{{ installedVersions[slug] }} · À jour</p>
                       </template>
                       <!-- Update available -->
                       <template v-else-if="isUpdateAvailable(slug, plugin.version)">
                         <span class="flex items-center gap-1 text-[10px] font-black text-amber-500 uppercase tracking-widest">
                           <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Mise à jour
                         </span>
                         <p class="text-[10px] text-slate-400 mt-0.5">v{{ installedVersions[slug] }} → v{{ plugin.version }}</p>
                       </template>
                       <!-- Not installed -->
                       <template v-else>
                         <span class="flex items-center gap-1 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                           <span class="w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span> Disponible
                         </span>
                         <p class="text-[10px] text-slate-400 mt-0.5">v{{ plugin.version }}</p>
                       </template>
                     </div>

                     <!-- CTA button -->
                     <button
                       v-if="!installedVersions[slug] || isUpdateAvailable(slug, plugin.version)"
                       @click="handleInstallAddon(slug)"
                       :disabled="processingAddon === slug"
                       :class="[
                         'inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-[11px] font-black tracking-wide transition-all disabled:opacity-60',
                         isUpdateAvailable(slug, plugin.version)
                           ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-700/40 hover:bg-amber-100 dark:hover:bg-amber-900/30'
                           : 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-700/40 hover:bg-purple-100 dark:hover:bg-purple-900/30'
                       ]"
                     >
                       <Loader2 v-if="processingAddon === slug" class="w-3 h-3 animate-spin" />
                       <Download v-else-if="!installedVersions[slug]" class="w-3 h-3" />
                       <RefreshCcw v-else class="w-3 h-3" />
                       {{ processingAddon === slug ? '…' : (installedVersions[slug] ? 'Mettre à jour' : 'Installer') }}
                     </button>
                     <span v-else class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-[10px] font-bold text-slate-400 bg-slate-50 dark:bg-slate-800 italic">
                       ✓ À jour
                     </span>
                   </div>
                 </div>
               </div>
             </div>
           </template>

           <!-- No addons placeholder -->
           <div v-if="licenseData?.valid && (!licenseData.plugins || Object.keys(licenseData.plugins).length === 0)"
             class="flex flex-col items-center justify-center py-16 bg-slate-50 dark:bg-slate-900/30 rounded-[2rem] border border-dashed border-slate-200 dark:border-slate-800 text-center">
             <div class="w-14 h-14 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mb-4">
               <HelpCircle class="w-7 h-7 text-slate-300 dark:text-slate-600" />
             </div>
             <p class="text-sm font-bold text-slate-400">Aucun module complémentaire n'est lié à cette licence.</p>
             <p class="text-xs text-slate-400 mt-1">Contactez le support ou vérifiez votre plan.</p>
           </div>
         </div>

         <!-- Tab 17: Facturation Électronique / PDP -->
         <div v-if="selectedTab === 17" class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">

            <!-- Hero Banner -->
            <div class="bg-gradient-to-br from-blue-700 via-indigo-700 to-purple-800 p-8 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden">
               <div class="absolute -right-16 -top-16 w-56 h-56 bg-white/5 rounded-full blur-3xl"></div>
               <div class="absolute -left-8 -bottom-12 w-40 h-40 bg-purple-500/20 rounded-full blur-2xl"></div>
               <div class="relative z-10 flex flex-col md:flex-row gap-6 items-start md:items-center justify-between">
                  <div class="space-y-2">
                     <div class="flex items-center gap-3">
                        <FileInput class="w-8 h-8 text-blue-200" />
                        <h3 class="text-2xl font-black">Facturation Électronique</h3>
                     </div>
                     <p class="text-blue-200 font-medium max-w-xl">Conformité EN 16931 / Factur-X. Connectez un ou plusieurs PDPs agréés DGFiP pour transmettre vos factures B2B directement depuis myEasyCompta.</p>
                  </div>
                  <div class="flex items-center gap-3 shrink-0">
                     <span :class="['px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest flex items-center gap-2', einvoicingForm.e_invoicing_enabled == '1' ? 'bg-emerald-400/20 text-emerald-300 border border-emerald-400/30' : 'bg-white/10 text-white/60 border border-white/20']">
                        <span :class="['w-2 h-2 rounded-full', einvoicingForm.e_invoicing_enabled == '1' ? 'bg-emerald-400' : 'bg-white/40']"></span>
                        {{ einvoicingForm.e_invoicing_enabled == '1' ? 'Activée' : 'Désactivée' }}
                     </span>
                  </div>
               </div>
            </div>

            <!-- Section 1 : Paramètres globaux -->
            <div class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl border border-slate-200 dark:border-slate-700 space-y-6">
               <h4 class="font-black text-xs uppercase tracking-widest text-slate-400">Paramètres globaux</h4>

               <!-- Enable toggle -->
               <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800">
                  <div>
                     <p class="font-bold text-slate-700 dark:text-slate-300">Activer la facturation électronique</p>
                     <p class="text-xs text-slate-400 mt-0.5">Active la génération Factur-X et la transmission aux PDPs</p>
                  </div>
                  <button type="button" class="kloxy-toggle" :aria-checked="(einvoicingForm.e_invoicing_enabled == '1').toString()" @click="einvoicingForm.e_invoicing_enabled = einvoicingForm.e_invoicing_enabled == '1' ? '0' : '1'">
                     <span class="kloxy-toggle-thumb"></span>
                  </button>
               </div>

               <div v-if="einvoicingForm.e_invoicing_enabled == '1'" class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-in fade-in slide-in-from-top-2 duration-300">
                  <!-- Mode -->
                  <div class="space-y-2">
                     <label class="kloxy-label">Mode</label>
                     <select v-model="einvoicingForm.e_invoicing_mode" class="kloxy-input appearance-none">
                        <option value="sandbox">Bac à sable (test)</option>
                        <option value="production">Production</option>
                     </select>
                  </div>
                  <!-- Types de transactions -->
                  <div class="space-y-2">
                     <label class="kloxy-label">Types de transactions</label>
                     <select v-model="einvoicingForm.e_invoicing_transaction_types" class="kloxy-input appearance-none">
                        <option value="B2B">B2B uniquement</option>
                        <option value="B2C">B2C uniquement</option>
                        <option value="B2B_B2C">B2B et B2C</option>
                        <option value="B2G">B2G (Marchés publics)</option>
                     </select>
                  </div>
                  <!-- Profil Factur-X -->
                  <div class="space-y-2">
                     <label class="kloxy-label">Profil Factur-X</label>
                     <select v-model="einvoicingForm.e_invoicing_facturx_profile" class="kloxy-input appearance-none">
                        <option value="minimum">Minimum</option>
                        <option value="basicwl">Basic WL</option>
                        <option value="en16931">EN 16931 (recommandé)</option>
                        <option value="extended">Extended</option>
                     </select>
                  </div>
                  <!-- Auto-validation -->
                  <div class="space-y-2">
                     <label class="kloxy-label">Validation automatique</label>
                     <select v-model="einvoicingForm.e_invoicing_auto_validate" class="kloxy-input appearance-none">
                        <option value="0">Manuelle (recommandé)</option>
                        <option value="1">Automatique à la création</option>
                     </select>
                  </div>
               </div>
            </div>

            <!-- Section 2 : Données fiscales entreprise -->
            <div v-if="einvoicingForm.e_invoicing_enabled == '1'" class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-3xl border border-slate-200 dark:border-slate-700 space-y-6 animate-in fade-in slide-in-from-bottom-2 duration-300">
               <div class="flex items-center justify-between">
                  <h4 class="font-black text-xs uppercase tracking-widest text-slate-400">Données fiscales de l'entreprise</h4>
                  <span class="text-[10px] bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 px-2 py-1 rounded-full font-bold">Requis pour EN 16931</span>
               </div>

               <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="space-y-2">
                     <label class="kloxy-label">SIREN / SIRET <span class="text-red-500">*</span></label>
                     <input type="text" v-model="einvoicingForm.company_siren" class="kloxy-input font-mono" placeholder="123456789" maxlength="14" />
                  </div>
                  <div class="space-y-2">
                     <label class="kloxy-label">Numéro de TVA intracommunautaire</label>
                     <input type="text" v-model="einvoicingForm.company_vat_number" class="kloxy-input font-mono" placeholder="FR12345678901" />
                  </div>
                  <div class="space-y-2">
                     <label class="kloxy-label">Code pays (ISO 3166-1 alpha-2)</label>
                     <input type="text" v-model="einvoicingForm.company_country_code" class="kloxy-input font-mono uppercase" placeholder="FR" maxlength="2" />
                  </div>
                  <div class="space-y-2">
                     <label class="kloxy-label">Régime de TVA</label>
                     <select v-model="einvoicingForm.company_vat_regime" class="kloxy-input appearance-none">
                        <option value="normal">Régime normal</option>
                        <option value="simplifie">Régime simplifié</option>
                        <option value="franchise">Franchise en base (sans TVA)</option>
                        <option value="micro">Micro-entreprise</option>
                     </select>
                  </div>
                  <div class="space-y-2 md:col-span-2">
                     <label class="kloxy-label">Adresse légale complète</label>
                     <input type="text" v-model="einvoicingForm.company_legal_address" class="kloxy-input" placeholder="12 rue de la Paix, 75001 Paris, France" />
                  </div>
               </div>
            </div>

            <!-- Section 3 : Connexions PDP -->
            <div v-if="einvoicingForm.e_invoicing_enabled == '1'" class="space-y-4 animate-in fade-in slide-in-from-bottom-2 duration-300">
               <div class="flex items-center justify-between mb-2">
                  <div>
                     <h4 class="font-black text-xs uppercase tracking-widest text-slate-400">Connexions PDP</h4>
                     <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Activez et configurez un ou plusieurs Plateformes de Dématérialisation Partenaires.</p>
                  </div>
                  <div v-if="einvoicingForm.pdp_active" class="flex items-center gap-2 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-1.5 rounded-xl border border-emerald-100 dark:border-emerald-800">
                     <Wifi class="w-4 h-4 text-emerald-500" />
                     <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">PDP actif : {{ availablePdps.find(p => p.id === einvoicingForm.pdp_active)?.name || einvoicingForm.pdp_active }}</span>
                  </div>
               </div>

               <!-- Active PDP selector -->
               <div v-if="availablePdps.some(p => getPdpConfig(p.id).enabled)" class="p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center gap-4">
                  <div class="flex-1 space-y-1">
                     <label class="kloxy-label">PDP actif (utilisé pour la transmission)</label>
                     <select v-model="einvoicingForm.pdp_active" class="kloxy-input appearance-none">
                        <option value="">— Sélectionner un PDP —</option>
                        <option v-for="pdp in availablePdps.filter(p => getPdpConfig(p.id).enabled)" :key="pdp.id" :value="pdp.id">{{ pdp.name }}</option>
                     </select>
                  </div>
               </div>

               <!-- PDP Cards -->
               <div v-for="pdp in availablePdps" :key="pdp.id" class="bg-white dark:bg-slate-900 rounded-3xl border transition-all duration-300"
                    :class="getPdpConfig(pdp.id).enabled ? 'border-indigo-200 dark:border-indigo-700 shadow-lg shadow-indigo-500/5' : 'border-slate-100 dark:border-slate-800'">

                  <!-- Card Header -->
                  <div class="flex items-center gap-4 p-5 cursor-pointer" @click="expandedPdp = (expandedPdp === pdp.id ? null : pdp.id)">
                     <!-- Icon -->
                     <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center shrink-0', getPdpConfig(pdp.id).enabled ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-400']">
                        <component :is="getPdpIcon(pdp.id)" class="w-6 h-6" />
                     </div>

                     <!-- Name & desc -->
                     <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                           <h5 class="font-bold text-slate-900 dark:text-white">{{ pdp.name }}</h5>
                           <span v-if="pdp.official" class="text-[9px] bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded-full font-black uppercase tracking-widest">Officiel</span>
                           <span v-if="pdp.builtin" class="text-[9px] bg-slate-100 dark:bg-slate-800 text-slate-500 px-2 py-0.5 rounded-full font-black uppercase tracking-widest">Built-in</span>
                           <span v-if="pdp.addon" class="text-[9px] bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 px-2 py-0.5 rounded-full font-black uppercase tracking-widest">Addon</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">{{ pdp.description }}</p>
                     </div>

                     <!-- Status badges -->
                     <div class="flex items-center gap-3 shrink-0">
                        <!-- Test result badge -->
                        <div v-if="pdpTestResults[pdp.id]" :class="['flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-[11px] font-bold', pdpTestResults[pdp.id].success ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400']">
                           <component :is="pdpTestResults[pdp.id].success ? Wifi : WifiOff" class="w-3.5 h-3.5" />
                           {{ pdpTestResults[pdp.id].success ? 'Connecté' : 'Échec' }}
                        </div>

                        <!-- Enable toggle -->
                        <button type="button" @click.stop="togglePdpEnabled(pdp.id)"
                           :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-300 focus:outline-none', getPdpConfig(pdp.id).enabled ? 'bg-indigo-600' : 'bg-slate-300 dark:bg-slate-600']">
                           <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-300', getPdpConfig(pdp.id).enabled ? 'translate-x-6' : 'translate-x-1']"></span>
                        </button>

                        <!-- Expand chevron -->
                        <component :is="expandedPdp === pdp.id ? ChevronUp : ChevronDown" class="w-4 h-4 text-slate-400" />
                     </div>
                  </div>

                  <!-- Card Body (expanded) -->
                  <div v-if="expandedPdp === pdp.id" class="px-5 pb-6 space-y-6 border-t border-slate-100 dark:border-slate-800 pt-5 animate-in fade-in slide-in-from-top-2 duration-200">

                     <!-- Dynamic fields from catalog -->
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="field in (pdp.fields || [])" :key="field.key"
                             :class="['space-y-2', (field.type === 'url' || field.key === 'api_endpoint') ? 'md:col-span-2' : '']">
                           <label class="kloxy-label">{{ field.label }}</label>
                           <select v-if="field.type === 'select'" v-model="getPdpConfig(pdp.id)[field.key]" class="kloxy-input appearance-none">
                              <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                           </select>
                           <input v-else
                              :type="field.type || 'text'"
                              v-model="getPdpConfig(pdp.id)[field.key]"
                              class="kloxy-input"
                              :class="field.type === 'password' ? 'font-mono' : ''"
                              :placeholder="field.placeholder || ''"
                           />
                        </div>
                     </div>

                     <!-- Actions bar -->
                     <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                        <a v-if="pdp.docs_url" :href="pdp.docs_url" target="_blank" rel="noopener" class="text-xs font-bold text-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-300 underline underline-offset-2 transition-colors">
                           Documentation →
                        </a>
                        <span v-else class="flex-1"></span>

                        <div class="flex items-center gap-3">
                           <!-- Test result message -->
                           <span v-if="pdpTestResults[pdp.id]" :class="['text-xs font-bold', pdpTestResults[pdp.id].success ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500']">
                              {{ pdpTestResults[pdp.id].message }}
                           </span>

                           <!-- Test button -->
                           <button type="button" @click="testPdpConnection(pdp.id)"
                              :disabled="pdpTesting[pdp.id]"
                              class="kloxy-btn-secondary py-2 px-4 text-sm shadow-none">
                              <Loader2 v-if="pdpTesting[pdp.id]" class="w-3.5 h-3.5 mr-2 animate-spin" />
                              <Wifi v-else class="w-3.5 h-3.5 mr-2" />
                              Tester la connexion
                           </button>
                        </div>
                     </div>
                  </div>
               </div>
            </div>

            <!-- Save button -->
            <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
               <button type="button" @click="saveEInvoicingSettings" :disabled="einvoicingSaving" class="kloxy-btn-primary">
                  <Loader2 v-if="einvoicingSaving" class="w-4 h-4 mr-2 animate-spin" />
                  <Save v-else class="w-4 h-4 mr-2" />
                  Enregistrer les réglages
               </button>
            </div>
         </div>

         <!-- Generic Tables -->
         <div v-if="isTableTab" class="space-y-12">

             <!-- Tab 7: TVA block (settings + taux) -->
             <div v-if="selectedTab === 7" class="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                 <!-- Header -->
                 <div class="flex items-center justify-between px-5 py-3 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-700">
                     <div class="flex items-center gap-3">
                         <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                             <Receipt class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                         </div>
                         <span class="font-black text-xs uppercase tracking-widest text-slate-600 dark:text-slate-300">TVA</span>
                     </div>
                     <button @click="openAddVATModal" class="kloxy-btn-secondary py-2 px-4 shadow-none">
                         <Plus class="w-3 h-3 mr-2" /> {{ translations.add || 'Ajouter' }}
                     </button>
                 </div>
                 <!-- Settings -->
                 <div class="p-5 bg-white dark:bg-slate-900 space-y-5">
                     <div class="flex items-center justify-between">
                         <div>
                             <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Activer la gestion de TVA</p>
                             <p class="text-xs text-slate-400 mt-0.5">Affiche les colonnes TVA sur les factures, devis et avoirs.</p>
                         </div>
                         <button type="button" @click="form.vat_active = form.vat_active == 1 ? 0 : 1"
                             :class="['relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none', form.vat_active == 1 ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-700']">
                             <span :class="['pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out', form.vat_active == 1 ? 'translate-x-5' : 'translate-x-0']"></span>
                         </button>
                     </div>
                     <div v-if="form.vat_active == 1" class="space-y-1.5">
                         <label class="kloxy-label">Taux TVA par défaut</label>
                         <select v-model="form.default_vat" class="kloxy-input cursor-pointer">
                             <option value="0">Aucun (0%)</option>
                             <option v-for="v in vats" :key="v.id" :value="v.rate">{{ v.rate }}% — {{ v.description }}</option>
                         </select>
                         <p class="text-[11px] text-slate-400 ml-1">Pré-sélectionné à l'ajout d'un article sur une nouvelle facture ou devis.</p>
                     </div>
                 </div>
                 <!-- Taux table -->
                 <div class="px-5 pb-2 border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900">
                     <table class="w-full border-separate border-spacing-y-2">
                         <thead>
                             <tr class="text-xs font-black uppercase tracking-widest text-slate-400 text-left">
                                 <th class="px-6 pb-2">Taux (%)</th>
                                 <th class="px-6 pb-2">Description</th>
                                 <th class="px-6 pb-2 text-right">Actions</th>
                             </tr>
                         </thead>
                         <tbody>
                             <tr v-for="v in vats" :key="v.id" class="bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors group">
                                 <td class="px-6 py-4 font-bold text-slate-900 dark:text-white rounded-l-2xl">{{ v.rate }}%</td>
                                 <td class="px-6 py-4 text-slate-600 dark:text-slate-400">{{ v.description }}</td>
                                 <td class="px-6 py-4 rounded-r-2xl text-right">
                                     <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                         <button @click="editVAT(v)" class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-slate-900 rounded-lg transition-colors"><Pencil class="w-4 h-4" /></button>
                                         <button @click="deleteItem('vat', v.id)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-900 rounded-lg transition-colors"><Trash2 class="w-4 h-4" /></button>
                                     </div>
                                 </td>
                             </tr>
                         </tbody>
                     </table>
                 </div>
                 <!-- Footer save -->
                 <div class="flex justify-end px-5 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-200 dark:border-slate-700">
                     <button type="button" @click="handleSubmit" class="kloxy-btn-primary">
                         <Save class="w-4 h-4 mr-2" /> {{ translations.save || 'Enregistrer' }}
                     </button>
                 </div>
             </div>

             <!-- Primary Table -->
             <div class="overflow-x-auto">
                 <div v-if="selectedTab === 7" class="mb-4 flex items-center justify-between">
                     <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200">Devises</h3>
                     <button @click="openAddModal" class="kloxy-btn-secondary py-2 px-4 shadow-none">
                         <Plus class="w-3 h-3 mr-2" /> {{ translations.add || 'Ajouter' }}
                     </button>
                 </div>
                 <table class="w-full border-separate border-spacing-y-2">
                    <thead>
                       <tr class="text-xs font-black uppercase tracking-widest text-slate-400 text-left">
                          <th v-for="col in currentTableColumns" :key="col.key" class="px-6 pb-2">{{ col.label }}</th>
                          <th class="px-6 pb-2 text-right">{{ translations.actions || 'Actions' }}</th>
                       </tr>
                    </thead>
                    <tbody>
                       <tr v-for="item in currentTableData" :key="item.id" class="bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors group">
                          <td v-for="(col, idx) in currentTableColumns" :key="col.key" :class="['px-6 py-4 font-medium text-slate-700 dark:text-slate-300', idx===0 ? 'rounded-l-2xl' : '']">
                             <span v-if="col.type === 'color'" class="inline-flex items-center gap-2">
                               <span class="w-6 h-6 rounded-md inline-block border border-slate-200 dark:border-slate-600 flex-shrink-0" :style="{ backgroundColor: item[col.key] || 'transparent' }"></span>
                               <span class="text-xs font-mono text-slate-400">{{ item[col.key] || '—' }}</span>
                             </span>
                             <span v-else>{{ item[col.key] }}</span>
                          </td>
                          <td class="px-6 py-4 rounded-r-2xl text-right">
                             <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="editItem(item)" class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-slate-900 rounded-lg transition-colors"><Pencil class="w-4 h-4" /></button>
                                <button @click="deleteItem(getCurrentDeleteType, item.id)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-900 rounded-lg transition-colors"><Trash2 class="w-4 h-4" /></button>
                             </div>
                          </td>
                       </tr>
                    </tbody>
                 </table>
             </div>


             <!-- Secondary Table for Categories in Tab 3 -->
             <div v-if="selectedTab === 3" class="overflow-x-auto pt-8 border-t border-slate-100 dark:border-slate-800">
                 <div class="mb-4 flex items-center justify-between">
                     <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200">Catégories d'articles</h3>
                     <button @click="openAddCategoryModal" class="kloxy-btn-secondary py-2 px-4 shadow-none">
                         <Plus class="w-3 h-3 mr-2" /> {{ translations.add || 'Ajouter' }}
                     </button>
                 </div>
                 <table class="w-full border-separate border-spacing-y-2">
                    <thead>
                       <tr class="text-xs font-black uppercase tracking-widest text-slate-400 text-left">
                          <th class="px-6 pb-2">Nom</th>
                          <th class="px-6 pb-2 text-right">Actions</th>
                       </tr>
                    </thead>
                    <tbody>
                       <tr v-for="c in categories" :key="c.id" class="bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors group">
                          <td class="px-6 py-4 font-bold text-slate-900 dark:text-white rounded-l-2xl">{{ c.name }}</td>
                          <td class="px-6 py-4 rounded-r-2xl text-right">
                             <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="editCategory(c)" class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-slate-900 rounded-lg transition-colors"><Pencil class="w-4 h-4" /></button>
                                <button @click="deleteItem('article-category', c.id)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-900 rounded-lg transition-colors"><Trash2 class="w-4 h-4" /></button>
                             </div>
                          </td>
                       </tr>
                    </tbody>
                 </table>
             </div>

         </div>

         <!-- Save button for Planning tab (tab 10) — shown after the categories table -->
         <div v-if="selectedTab === 10" class="flex justify-end pt-6 mt-4 border-t border-slate-100 dark:border-slate-800">
             <button type="button" @click="handleSubmit" class="kloxy-btn-primary">
                 <Save class="w-4 h-4 mr-2" /> {{ translations.save || 'Enregistrer' }}
             </button>
         </div>

         <!-- ══════════════════════════════════════════════════════
              Tab 18: Support
         ══════════════════════════════════════════════════════ -->
         <div v-if="selectedTab === 18" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">

             <!-- Hero -->
             <div class="bg-gradient-to-br from-indigo-600 to-purple-700 p-8 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden">
                 <div class="absolute -right-10 -top-10 w-48 h-48 bg-white/5 rounded-full blur-3xl"></div>
                 <div class="relative z-10 flex items-center gap-6">
                     <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center flex-shrink-0">
                         <Headphones class="w-8 h-8 text-white" />
                     </div>
                     <div>
                         <h3 class="text-2xl font-black tracking-tight mb-1">Support & Documentation</h3>
                         <p class="text-indigo-200 text-sm">Notre équipe est disponible pour vous aider avec myEasyCompta.</p>
                     </div>
                 </div>
             </div>

             <!-- Cards grid -->
             <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                 <a href="https://myeasycompta.com/docs" target="_blank" rel="noopener"
                    class="group bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 hover:border-purple-500/40 rounded-[2rem] p-6 flex flex-col gap-3 transition-all shadow-sm hover:shadow-lg hover:-translate-y-1">
                     <div class="w-11 h-11 rounded-xl bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center">
                         <FileText class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                     </div>
                     <div class="flex-1">
                         <h4 class="font-black text-slate-900 dark:text-white text-sm mb-1">Documentation</h4>
                         <p class="text-slate-500 text-xs leading-relaxed">Guides complets, tutoriels et références techniques pour tous les modules.</p>
                     </div>
                     <span class="text-xs font-bold text-purple-500 flex items-center gap-1 mt-auto">Ouvrir <ExternalLink class="w-3 h-3" /></span>
                 </a>

                 <a href="https://wordpress.org/support/plugin/my-easy-compta/" target="_blank" rel="noopener"
                    class="group bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 hover:border-blue-500/40 rounded-[2rem] p-6 flex flex-col gap-3 transition-all shadow-sm hover:shadow-lg hover:-translate-y-1">
                     <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                         <Users class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                     </div>
                     <div class="flex-1">
                         <h4 class="font-black text-slate-900 dark:text-white text-sm mb-1">Forum communautaire</h4>
                         <p class="text-slate-500 text-xs leading-relaxed">Posez vos questions et partagez vos expériences avec la communauté WordPress.org.</p>
                     </div>
                     <span class="text-xs font-bold text-blue-500 flex items-center gap-1 mt-auto">Accéder <ExternalLink class="w-3 h-3" /></span>
                 </a>

                 <a href="https://myeasycompta.com/contact" target="_blank" rel="noopener"
                    class="group bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 hover:border-emerald-500/40 rounded-[2rem] p-6 flex flex-col gap-3 transition-all shadow-sm hover:shadow-lg hover:-translate-y-1">
                     <div class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center">
                         <Mail class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                     </div>
                     <div class="flex-1">
                         <h4 class="font-black text-slate-900 dark:text-white text-sm mb-1">Support prioritaire</h4>
                         <p class="text-slate-500 text-xs leading-relaxed">Assistance directe par email pour les licences actives. Réponse sous 24h ouvrées.</p>
                     </div>
                     <span class="text-xs font-bold text-emerald-500 flex items-center gap-1 mt-auto">Contacter <ExternalLink class="w-3 h-3" /></span>
                 </a>

                 <a href="https://myeasycompta.com/changelog" target="_blank" rel="noopener"
                    class="group bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 hover:border-amber-500/40 rounded-[2rem] p-6 flex flex-col gap-3 transition-all shadow-sm hover:shadow-lg hover:-translate-y-1">
                     <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center">
                         <RefreshCcw class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                     </div>
                     <div class="flex-1">
                         <h4 class="font-black text-slate-900 dark:text-white text-sm mb-1">Changelog</h4>
                         <p class="text-slate-500 text-xs leading-relaxed">Toutes les nouveautés, corrections et améliorations de chaque version.</p>
                     </div>
                     <span class="text-xs font-bold text-amber-500 flex items-center gap-1 mt-auto">Voir <ExternalLink class="w-3 h-3" /></span>
                 </a>

                 <a href="https://wordpress.org/plugins/my-easy-compta/#reviews" target="_blank" rel="noopener"
                    class="group bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 hover:border-rose-500/40 rounded-[2rem] p-6 flex flex-col gap-3 transition-all shadow-sm hover:shadow-lg hover:-translate-y-1">
                     <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center">
                         <Heart class="w-5 h-5 text-rose-500" />
                     </div>
                     <div class="flex-1">
                         <h4 class="font-black text-slate-900 dark:text-white text-sm mb-1">Laisser un avis</h4>
                         <p class="text-slate-500 text-xs leading-relaxed">Votre retour sur WordPress.org nous aide à améliorer le plugin et à le faire connaître.</p>
                     </div>
                     <span class="text-xs font-bold text-rose-500 flex items-center gap-1 mt-auto">Noter ⭐ <ExternalLink class="w-3 h-3" /></span>
                 </a>

                 <a href="https://github.com/mizou1255/myeasycompta/issues" target="_blank" rel="noopener"
                    class="group bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 hover:border-slate-500/40 rounded-[2rem] p-6 flex flex-col gap-3 transition-all shadow-sm hover:shadow-lg hover:-translate-y-1">
                     <div class="w-11 h-11 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center">
                         <AlertCircle class="w-5 h-5 text-slate-500" />
                     </div>
                     <div class="flex-1">
                         <h4 class="font-black text-slate-900 dark:text-white text-sm mb-1">Signaler un bug</h4>
                         <p class="text-slate-500 text-xs leading-relaxed">Ouvrez un ticket sur GitHub avec une description détaillée du problème rencontré.</p>
                     </div>
                     <span class="text-xs font-bold text-slate-500 flex items-center gap-1 mt-auto">GitHub <ExternalLink class="w-3 h-3" /></span>
                 </a>

             </div>

             <!-- Info licence -->
             <div v-if="licenseData?.valid" class="bg-purple-50 dark:bg-purple-900/10 border border-purple-200 dark:border-purple-800/40 rounded-[1.5rem] p-5 flex items-center gap-4">
                 <BadgeCheck class="w-6 h-6 text-purple-500 flex-shrink-0" />
                 <p class="text-sm text-purple-700 dark:text-purple-300 font-semibold">
                     Vous bénéficiez du support prioritaire avec votre licence active. Utilisez l'email <strong>support@myeasycompta.com</strong> en précisant votre clé de licence.
                 </p>
             </div>
             <div v-else class="bg-slate-50 dark:bg-slate-900/30 border border-dashed border-slate-200 dark:border-slate-700 rounded-[1.5rem] p-5 flex items-center gap-4">
                 <HelpCircle class="w-6 h-6 text-slate-400 flex-shrink-0" />
                 <p class="text-sm text-slate-500">Activez une licence pro pour accéder au support prioritaire.</p>
             </div>

         </div>

         <!-- ══════════════════════════════════════════════════════
              Tab 19: Affiliation
         ══════════════════════════════════════════════════════ -->
         <div v-if="selectedTab === 19" class="space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500">

             <!-- Hero -->
             <div class="bg-gradient-to-br from-rose-500 to-pink-600 p-8 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden">
                 <div class="absolute -right-10 -top-10 w-48 h-48 bg-white/5 rounded-full blur-3xl"></div>
                 <div class="relative z-10 flex items-start gap-6">
                     <div class="w-16 h-16 rounded-2xl bg-white/10 flex items-center justify-center flex-shrink-0">
                         <Heart class="w-8 h-8 text-white" />
                     </div>
                     <div>
                         <h3 class="text-2xl font-black tracking-tight mb-2">Programme d'affiliation</h3>
                         <p class="text-rose-100 text-sm leading-relaxed">Recommandez myEasyCompta à vos clients et collègues, et gagnez une commission sur chaque vente générée.</p>
                         <div class="flex flex-wrap gap-4 mt-4">
                             <div class="bg-white/15 rounded-xl px-4 py-2 text-center">
                                 <p class="text-xl font-black">20%</p>
                                 <p class="text-xs text-rose-200">Commission / vente</p>
                             </div>
                             <div class="bg-white/15 rounded-xl px-4 py-2 text-center">
                                 <p class="text-xl font-black">Cookie 30j</p>
                                 <p class="text-xs text-rose-200">Suivi de référence</p>
                             </div>
                             <div class="bg-white/15 rounded-xl px-4 py-2 text-center">
                                 <p class="text-xl font-black">Mensuel</p>
                                 <p class="text-xs text-rose-200">Versement</p>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>

             <!-- Formulaire -->
             <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-[2rem] p-8 space-y-6">
                 <div>
                     <h4 class="text-lg font-black text-slate-900 dark:text-white mb-1">Candidater au programme</h4>
                     <p class="text-slate-500 text-sm">Remplissez ce formulaire et notre équipe reviendra vers vous sous 48h.</p>
                 </div>

                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                     <div class="space-y-1">
                         <label class="kloxy-label">Nom complet *</label>
                         <input v-model="affiliateForm.name" type="text" class="kloxy-input" placeholder="Jean Dupont" />
                     </div>
                     <div class="space-y-1">
                         <label class="kloxy-label">Email *</label>
                         <input v-model="affiliateForm.email" type="email" class="kloxy-input" placeholder="jean@exemple.fr" />
                     </div>
                     <div class="space-y-1">
                         <label class="kloxy-label">Site web</label>
                         <input v-model="affiliateForm.website" type="url" class="kloxy-input" placeholder="https://votre-site.fr" />
                     </div>
                     <div class="space-y-1">
                         <label class="kloxy-label">Email de paiement (PayPal / virement)</label>
                         <input v-model="affiliateForm.payment_email" type="email" class="kloxy-input" placeholder="paiement@exemple.fr" />
                     </div>
                     <div class="space-y-1 md:col-span-2">
                         <label class="kloxy-label">Message (optionnel)</label>
                         <textarea v-model="affiliateForm.message" class="kloxy-input min-h-[80px] resize-none" placeholder="Décrivez votre audience, votre site, comment vous comptez promouvoir myEasyCompta…"></textarea>
                     </div>
                 </div>

                 <div v-if="affiliateMsg" :class="['p-4 rounded-xl text-sm font-semibold', affiliateMsgType === 'success' ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300']">
                     {{ affiliateMsg }}
                 </div>

                 <div class="flex justify-end">
                     <button @click="registerAffiliate" :disabled="affiliateLoading || !affiliateForm.name || !affiliateForm.email"
                             class="kloxy-btn-primary">
                         <Loader2 v-if="affiliateLoading" class="w-4 h-4 mr-2 animate-spin" />
                         <Send v-else class="w-4 h-4 mr-2" />
                         Envoyer la candidature
                     </button>
                 </div>
             </div>

             <!-- Comment ça marche -->
             <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                 <div v-for="(step, i) in [{icon: 'link', title:'Obtenez votre lien', desc:'Un lien unique vous est fourni après validation de votre candidature.'}, {icon: 'share', title:'Partagez', desc:'Intégrez votre lien dans vos contenus, emails ou sur votre site.'}, {icon: 'wallet', title:'Touchez vos commissions', desc:'20% sur chaque vente générée. Paiement mensuel via PayPal.'}]" :key="i"
                      class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-[1.5rem] p-6">
                     <div class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-rose-900/20 flex items-center justify-center mb-4">
                         <span class="text-rose-500 font-black text-sm">{{ i + 1 }}</span>
                     </div>
                     <h5 class="font-black text-slate-900 dark:text-white text-sm mb-2">{{ step.title }}</h5>
                     <p class="text-slate-500 text-xs leading-relaxed">{{ step.desc }}</p>
                 </div>
             </div>

         </div>

         <!-- Save button for Planning tab (tab 10) — shown after the categories table -->
         <div v-if="selectedTab === 10" class="flex justify-end pt-6 mt-4 border-t border-slate-100 dark:border-slate-800">
             <button type="button" @click="handleSubmit" class="kloxy-btn-primary">
                 <Save class="w-4 h-4 mr-2" /> {{ translations.save || 'Enregistrer' }}
             </button>
         </div>

         <!-- ── Entreprises (tab 21) ───────────────────────────────────────── -->
         <div v-if="selectedTab === 21">
           <Entities />
         </div>

         </div><!-- /p-8 content wrapper -->
      </div>
    </div>

    <!-- Generic Modal -->
     <dialog id="settings_modal" class="bg-transparent p-0 border-none shadow-none backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm open:animate-in open:fade-in open:zoom-in-95 duration-200" ref="settingsModalRef">
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] p-8 w-[90vw] max-w-lg shadow-2xl border border-slate-100 dark:border-slate-800 relative">
             <button @click="closeModal" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"><X class="w-6 h-6" /></button>
             <h3 class="text-xl font-black text-slate-900 dark:text-white mb-6">
                {{ isEditing ? (translations.edit || 'Editer') : (translations.add || 'Ajouter') }}
                {{ isEditing ? (translations.edit || 'Editer') : (translations.add || 'Ajouter') }}
                {{ isVATModal ? ' TVA' : (isCategoryModal ? ' Catégorie' : '') }}
             </h3>
             
             <form @submit.prevent="isVATModal ? saveVAT() : (isCategoryModal ? saveCategory() : saveItem())" class="space-y-4">
                <div v-for="field in (isVATModal ? vatFields : (isCategoryModal ? categoryFields : currentModalFields))" :key="field.key" class="space-y-2">
                    <label class="kloxy-label">{{ field.label }}</label>
                    <template v-if="field.type === 'textarea'">
                        <textarea v-model="modalForm[field.key]" class="kloxy-input min-h-[100px]"></textarea>
                    </template>
                     <template v-else-if="field.type === 'color'">
                        <div class="flex gap-2">
                           <input type="text" v-model="modalForm[field.key]" class="kloxy-input" />
                           <input type="color" v-model="modalForm[field.key]" class="h-12 w-12 rounded-xl border-none cursor-pointer bg-transparent" />
                        </div>
                    </template>
                    <template v-else>
                        <input type="text" v-model="modalForm[field.key]" class="kloxy-input" required />
                    </template>
                </div>
                
                <div class="flex justify-end gap-3 pt-4">
                   <button type="button" @click="closeModal" class="kloxy-btn-secondary">{{ translations.cancel || 'Annuler' }}</button>
                   <button type="submit" class="kloxy-btn-primary">{{ translations.save || 'Enregistrer' }}</button>
                </div>
             </form>
        </div>
        <form method="dialog" class="fixed inset-0 z-[-1] cursor-default bg-transparent w-full h-full outline-none" @click="closeModal"></form>
     </dialog>

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { VueEditor } from "vue3-editor";
import axios from 'axios';
import QRCode from 'qrcode';
import MainLayout from '@/components/layout/MainLayout.vue';
import RemoveModal from "@/components/RemoveAlert.vue";
import Entities from "@/components/Entities.vue";
import Webhooks from "@/components/Webhooks.vue";
import FEC from "@/components/FEC.vue";
import { Home, FileText, FileImage, Receipt, Undo, HelpCircle, DollarSign, CreditCard, ShoppingBag, Calendar, Mail, Bell, User, BarChart, QrCode, BadgeCheck, Save, Upload, Plus, Pencil, Trash2, X, CheckCircle2, AlertCircle, Zap, RefreshCcw, Loader2, Download, Building2, Globe, Plug, Wifi, WifiOff, ChevronDown, ChevronUp, Shield, ShieldOff as ShieldOffIcon, FileInput, Headphones, Heart, ExternalLink, Send, Users, ScrollText, Clock, Truck, MessageSquare, Eye, Copy, Star, ShieldCheck, FileDown, ScanLine } from 'lucide-vue-next';

// State
const selectedTab = ref(1);
const loading = ref(false);
const toast = reactive({ visible: false, message: "", type: "success" });
const showRemoveModal = ref(false);
const deleteType = ref(null);
const selectedId = ref(null);
const logoPreviewUrl = ref(null);
const ocrApiKey  = ref('');
const ocrLanguage = ref('fre');
const savingOcr   = ref(false);
const settingsModalRef = ref(null);

// VAT Management
const isVATModal = ref(false);
const vats = ref([]);
const vatFields = [
    { key: 'rate', label: 'Taux (%)' },
    { key: 'rate', label: 'Taux (%)' },
    { key: 'description', label: 'Description' }
];

// Category Management
const isCategoryModal = ref(false);
const categoryFields = [
    { key: 'name', label: 'Nom de la catégorie' }
];

// License specific
const licenseData = ref(null);
const licenseKey = ref("");
const licenseLoading = ref(false);
const installedVersions = ref({});
const processingAddon = ref(null);

const form = reactive({
    company_code: '', tax_number: '', company_name: '', company_email: '', company_address: '', postal_code: '', city: '', country: '', company_phone: '', mobile_phone: '', fax: '', date_format: 'DD-MM-YYYY',
    logo_mentions_active: '0', logo_mentions: '', logo_width: 150,
    show_phone: '1', show_email: '1', show_siren: '1', show_vat: '1',
    show_watermark: '0', show_watermark_only_paid: '0',
    payment_mode: 'Virement bancaire', payment_conditions: '30 jours',
    default_currency: '', currency_position: 'after', vat_active: 0, default_vat: 0,
    invoice_prefix: 'INV', invoice_first: 1, invoice_color: '#7c3aed', invoice_footer: '', invoice_terms: '', invoice_iban: '', invoice_bic: '',
    credit_prefix: 'AVR', credit_color: '#f59e0b', credit_footer: '', credit_terms: '',
    quote_prefix: 'EST', quote_first: 1, quote_color: '#10b981', quote_footer: '', quote_terms: '',
    easy_compta_planning_addon_active: 0, easy_compta_email_addon_active: 0, easy_compta_user_addon_active: 0, easy_compta_payment_addon_active: 0, easy_compta_stats_addon_active: 0, easy_compta_qrcode_addon_active: 0, easy_compta_advance_addon_active: 0, easy_compta_backup_addon_active: 0,
    stripe_test_mode: 0, stripe_test_publishable_key: '', stripe_test_secret_key: '', stripe_live_publishable_key: '', stripe_live_secret_key: '', stripe_webhook_secret: '',
    ecwp_email_theme: 'dark',
    ecwp_notify_quote_action: '1',
    ecwp_notify_invoice_paid: '1',
    ecwp_notify_partial_payment: '1',
    ecwp_notify_new_client: '1',
    ecwp_notify_quote_converted: '0',
    ecwp_notify_backup_done: '1',
    ecwp_notify_backup_deleted: '0',
    ecwp_notify_planning_event: '1',
    invoice_email_subject: 'Votre facture {numero_document}',
    invoice_email_content: '<p>Bonjour {nom_client},</p><p>Veuillez trouver ci-joint votre facture <strong>{numero_document}</strong> d\'un montant de <strong>{montant_total}</strong>.</p><p>Merci de procéder au règlement dans les délais indiqués sur le document.</p><p>N\'hésitez pas à nous contacter pour toute question.</p><p>Cordialement,</p>',
    invoice_email_remind_subject: 'Relance — Facture {numero_document} en attente de paiement',
    invoice_email_remind_content: '<p>Bonjour {nom_client},</p><p>Sauf erreur de votre part, nous n\'avons pas encore reçu le règlement de la facture <strong>{numero_document}</strong> d\'un montant de <strong>{montant_total}</strong>.</p><p>Nous vous serions reconnaissants de bien vouloir régulariser cette situation dans les meilleurs délais.</p><p>Si vous avez déjà effectué ce règlement, merci de ne pas tenir compte de ce message.</p><p>Cordialement,</p>',
    quote_email_subject: 'Votre devis {numero_document}',
    quote_email_content: '<p>Bonjour {nom_client},</p><p>Veuillez trouver ci-joint votre devis <strong>{numero_document}</strong> d\'un montant de <strong>{montant_total}</strong>.</p><p>Ce devis est valable 30 jours. N\'hésitez pas à nous contacter pour toute question ou modification.</p><p>Cordialement,</p>',
    pdf_template: 'modern', pdf_primary_color: '#7c3aed', pdf_secondary_color: '#4b5563', pdf_font: 'dejavusanscondensed', pdf_footer_text: '',
    // Number format settings
    invoice_number_format: 'prefix',
    quote_number_format: 'prefix',
    credit_number_format: 'prefix',
    // Stats settings
    stats_annual_target: 0,
    limit_declaration: 77700,
    limit_tva: 36800,
    stats_fiscal_year_start: 1,
    // Contracts template
    contract_default_title: '',
    contract_default_body: '',
    // TimeTracking settings
    timetracking_default_rate: 0,
    // Delivery settings
    delivery_prefix: 'BL',
    delivery_footer: '',
    // OnlineQuote settings
    online_quote_expiry_days: 30,
    online_quote_accept_message: 'Merci ! Votre accord a bien été enregistré. Nous vous contacterons très prochainement.',
    online_quote_reject_message: 'Votre réponse a bien été prise en compte. N\'hésitez pas à nous contacter pour toute question.',
});

// Contract template variables reference
const contractVariables = [
    '{CLIENT_NAME}', '{CLIENT_EMAIL}', '{CLIENT_ADDRESS}', '{CLIENT_PHONE}',
    '{COMPANY_NAME}', '{COMPANY_SIRET}', '{COMPANY_ADDRESS}',
    '{START_DATE}', '{TODAY}', '{AMOUNT}', '{INVOICE_NUMBER}', '{QUOTE_NUMBER}', '{CONTRACT_ID}',
];

const contractEditorToolbar = [
    [{ header: [1, 2, 3, false] }],
    ['bold', 'italic', 'underline'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ align: [] }],
    ['clean'],
];

function loadContractExample() {
    form.contract_default_title = 'Contrat de prestation de services — {CLIENT_NAME}';
    form.contract_default_body = `<h2 style="text-align: center;">CONTRAT DE PRESTATION DE SERVICES</h2>
<p>Entre les soussignés :</p>
<p><strong>Le Prestataire :</strong><br>{COMPANY_NAME}<br>{COMPANY_ADDRESS}<br>SIRET : {COMPANY_SIRET}</p>
<p><strong>Le Client :</strong><br>{CLIENT_NAME}<br>{CLIENT_ADDRESS}</p>
<p>Il a été convenu ce qui suit :</p>
<h3>Article 1 — Objet de la mission</h3>
<p>[Décrire ici la nature des prestations à réaliser]</p>
<h3>Article 2 — Durée</h3>
<p>La mission débutera le <strong>{START_DATE}</strong>. Le présent contrat est conclu pour la durée nécessaire à la réalisation des prestations décrites à l'article 1.</p>
<h3>Article 3 — Rémunération</h3>
<p>En contrepartie des services rendus, le Client versera au Prestataire la somme de <strong>{AMOUNT} €</strong> HT, selon les modalités convenues entre les parties.</p>
<h3>Article 4 — Obligations du Prestataire</h3>
<p>Le Prestataire s'engage à réaliser les missions décrites à l'article 1 avec soin et professionnalisme, dans le respect des délais convenus.</p>
<h3>Article 5 — Confidentialité</h3>
<p>Le Prestataire s'engage à garder confidentielles toutes les informations relatives au Client dont il pourrait avoir connaissance dans le cadre de la présente mission.</p>
<h3>Article 6 — Propriété intellectuelle</h3>
<p>Sauf disposition contraire, les livrables produits dans le cadre de cette mission sont cédés au Client à compter du paiement intégral des sommes dues.</p>
<h3>Article 7 — Résiliation</h3>
<p>Chacune des parties pourra résilier le présent contrat avec un préavis de 15 jours par lettre recommandée avec accusé de réception.</p>
<h3>Article 8 — Loi applicable</h3>
<p>Le présent contrat est soumis au droit français. Tout litige relèvera de la compétence des tribunaux compétents.</p>
<p>Fait le {TODAY}</p>
<table style="width: 100%; margin-top: 40px;">
  <tr>
    <td style="width: 50%; vertical-align: top;"><p><strong>Signature du Prestataire</strong></p><br><br><p>_______________________________</p></td>
    <td style="width: 50%; vertical-align: top;"><p><strong>Signature du Client</strong></p><br><br><p>_______________________________</p></td>
  </tr>
</table>`;
}

// Table data
const currencies = ref([]);
const articles = ref([]);
const categories = ref([]);
const payments = ref([]);
const expenses = ref([]);
const planning = ref([]);

// E-Invoicing / PDP state
const einvoicingForm = reactive({
    e_invoicing_enabled: '0',
    e_invoicing_mode: 'sandbox',
    e_invoicing_transaction_types: 'B2B',
    e_invoicing_facturx_profile: 'en16931',
    e_invoicing_auto_validate: '0',
    company_siren: '',
    company_vat_number: '',
    company_legal_address: '',
    company_country_code: 'FR',
    company_vat_regime: 'normal',
    pdp_active: '',
});
const pdpConfigurations = ref({});
const availablePdps = ref([]);
const expandedPdp = ref(null);
const pdpTestResults = ref({});
const pdpTesting = ref({});
const einvoicingSaving = ref(false);

// Modal State
const isEditing = ref(false);
const modalForm = ref({});

// Computed
const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

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

const visibleTabs = computed(() => {
    const t = translations.value;
    const f = form;
    const tabs = [
        { id: 1, label: t.general_settings || 'Général', icon: Home },
        { id: 20, label: 'Documents PDF', icon: FileImage },
        { id: 3, label: t.articles_settings || 'Articles', icon: FileText },
        { id: 4, label: translations.invoices || 'Factures', icon: FileText },
        { id: 5, label: translations.credits || 'Avoirs', icon: Undo },
        { id: 6, label: translations.quotes || 'Devis', icon: HelpCircle },
        { id: 7, label: translations.vats || 'TVA & Devises', icon: Receipt },
        { id: 8, label: t.payments_settings || 'Paiements', icon: CreditCard },
        { id: 9, label: t.expenses_settings || 'Dépenses', icon: ShoppingBag },
    ];

    const hasP = checkSlug('myeasycompta-planning');

    const hasU = checkSlug('myeasycompta-compte-client');
    const hasS = checkSlug('myeasycompta-payment');
    const hasSt = checkSlug('myeasycompta-stats');
    const hasQ = checkSlug('myeasycompta-qrcode-stripe');
    const hasMobile = checkSlug('my-easy-compta-mobile') || !!window.myEasyComptaAdmin?.addonsStatus?.mobile;

    if(hasP || f.easy_compta_planning_addon_active == 1) tabs.push({ id: 10, label: t.planning_settings || 'Planning', icon: Calendar });
    tabs.push({ id: 11, label: t.email_settings || 'Emails', icon: Mail }); // Always visible — notification settings available without addon
    if(hasU || f.easy_compta_user_addon_active == 1) tabs.push({ id: 12, label: t.users_settings || 'Utilisateurs', icon: User });
    if(hasS || f.easy_compta_payment_addon_active == 1) tabs.push({ id: 13, label: t.stripe_settings || 'Stripe', icon: CreditCard });
    if(hasSt || f.easy_compta_stats_addon_active == 1) tabs.push({ id: 14, label: t.stats_settings || 'Stats', icon: BarChart });
    if(hasQ || f.easy_compta_qrcode_addon_active == 1) tabs.push({ id: 15, label: 'QR Code', icon: QrCode });
    if (hasMobile) tabs.push({ id: 30, label: 'App Mobile', icon: QrCode });
    if(window.myEasyComptaAdmin?.contractsAddonActive)    tabs.push({ id: 22, label: 'Contrats',     icon: ScrollText });
    if(window.myEasyComptaAdmin?.timetrackingAddonActive) tabs.push({ id: 23, label: 'Temps & Fact.', icon: Clock });
    if(window.myEasyComptaAdmin?.deliveryAddonActive)     tabs.push({ id: 24, label: 'Livraisons',    icon: Truck });
    if(window.myEasyComptaAdmin?.onlineQuoteAddonActive)  tabs.push({ id: 25, label: 'Devis en ligne', icon: Globe });
    if(window.myEasyComptaAdmin?.smsAddonActive)          tabs.push({ id: 26, label: 'SMS',           icon: MessageSquare });
    if(window.myEasyComptaAdmin?.webhooksAddonActive)     tabs.push({ id: 27, label: 'Webhooks',       icon: Zap });
    if(window.myEasyComptaAdmin?.fecAddonActive)          tabs.push({ id: 28, label: 'Export FEC',      icon: FileDown });
    if(window.myEasyComptaAdmin?.ocrAddonActive)          tabs.push({ id: 29, label: 'Scan Reçus',      icon: ScanLine });

    tabs.push({ id: 17, label: 'Fact. Électronique', icon: FileInput });
    tabs.push({ id: 16, label: t.license_settings || 'Licence', icon: BadgeCheck });
    tabs.push({ id: 18, label: 'Support', icon: Headphones });
    tabs.push({ id: 19, label: 'Affiliation', icon: Heart });
    return tabs;
});

const getCurrentTabTitle = computed(() => visibleTabs.value.find(t => t.id === selectedTab.value)?.label || 'Paramètres');

const tabGroups = computed(() => {
    const allTabs = visibleTabs.value;
    const find = (id) => allTabs.find(t => t.id === id);
    const groups = [
        { label: 'Général', tabs: [find(1)].filter(Boolean) },
        { label: 'Documents', tabs: [find(20), find(4), find(5), find(6)].filter(Boolean) },
        { label: 'Références', tabs: [find(3), find(7), find(8), find(9)].filter(Boolean) },
    ];
    const addonIds = [10, 11, 12, 13, 14, 15, 22, 23, 24, 25, 26, 27, 28, 29];
    if (find(30)) addonIds.push(30);
    const addonTabs = addonIds.map(id => find(id)).filter(Boolean);
    if (addonTabs.length > 0) groups.push({ label: 'Add-ons', tabs: addonTabs });
    const advTabs = [find(17), find(16), find(18), find(19)].filter(Boolean);
    if (advTabs.length > 0) groups.push({ label: 'Avancé', tabs: advTabs });
    return groups;
});

const currentTabIcon = computed(() => visibleTabs.value.find(t => t.id === selectedTab.value)?.icon);

const getCurrentTabDesc = computed(() => {
    const descs = {
        1: 'Informations de votre entreprise, coordonnées, devise et format de date',
        3: 'Catalogue d\'articles et catégories d\'articles',
        4: 'Numérotation, couleur et pieds de page des factures',
        5: 'Numérotation, couleur et pieds de page des avoirs',
        6: 'Numérotation, couleur et pieds de page des devis',
        7: 'Gestion de la TVA et devises disponibles',
        8: 'Méthodes de paiement acceptées',
        9: 'Catégories de dépenses',
        10: 'Catégories de l\'agenda et vue calendrier',
        11: 'Modèles d\'e-mails pour factures, relances et devis',
        12: 'Accès et rôles des utilisateurs',
        13: 'Clés API Stripe et mode test/production',
        14: 'Paramètres du module statistiques',
        15: 'Configuration du QR Code et paiement en ligne',
        16: 'Activation de la licence et gestion des modules',
        17: 'Conformité EN 16931 / Factur-X et connecteurs PDP',
        18: 'Documentation, contact et assistance technique',
        19: 'Rejoindre le programme d\'affiliation myEasyCompta',
        20: 'Logo, filigrane, mentions légales et coordonnées bancaires',
        21: 'Gérez plusieurs entités légales depuis la même interface',
        22: 'Modèle de contrat pré-rempli proposé à la création',
        23: 'Taux horaire par défaut et options du suivi de temps',
        24: 'Préfixe des bons de livraison et texte de pied de page',
        25: 'Durée d\'expiration des liens et message aux clients',
        26: 'Fournisseur SMS, clés API et événements à notifier',
        27: 'Endpoints HTTP, événements déclencheurs et logs de livraison',
        30: 'Connexion de l’application mobile : génération de tokens et QR code',
    };
    return descs[selectedTab.value] || '';
});

const isTableTab = computed(() => [3, 7, 8, 9, 10].includes(selectedTab.value));

// ── Number format helpers ────────────────────────────────────────────────────
const pdfDisplayOptions = [
    { key: 'show_phone',  label: 'Téléphone' },
    { key: 'show_email',  label: 'E-mail' },
    { key: 'show_siren',  label: 'SIRET' },
    { key: 'show_vat',    label: 'N° TVA' },
];

const numberFormatOptions = [
    { value: 'prefix',            label: 'Préfixe — Numéro',                   note: null },
    { value: 'prefix_year',       label: 'Préfixe — Année — Numéro',           note: 'La numérotation reste continue même en changeant de format.' },
    { value: 'prefix_year_month', label: 'Préfixe — Année — Mois — Numéro',   note: 'La numérotation reste continue même en changeant de format.' },
    { value: 'year',              label: 'Année — Numéro (sans préfixe)',       note: 'La numérotation reste continue même en changeant de format.' },
];

function buildNumberPreview(prefix, format, startNum) {
    const p   = prefix || '???';
    const num = String(startNum || 1).padStart(4, '0');
    const y   = new Date().getFullYear();
    const m   = String(new Date().getMonth() + 1).padStart(2, '0');
    switch (format) {
        case 'prefix_year':       return `${p}-${y}-${num}`;
        case 'prefix_year_month': return `${p}-${y}-${m}-${num}`;
        case 'year':              return `${y}-${num}`;
        default:                  return `${p}-${num}`;
    }
}

const invoiceNumberPreview = computed(() => buildNumberPreview(form.invoice_prefix, form.invoice_number_format, form.invoice_first));
const quoteNumberPreview   = computed(() => buildNumberPreview(form.quote_prefix,   form.quote_number_format,   form.quote_first));
const creditNumberPreview  = computed(() => buildNumberPreview(form.credit_prefix,  form.credit_number_format,  1));
// ────────────────────────────────────────────────────────────────────────────

const currencySymbol = computed(() => {
    const match = currencies.value.find(c => c.id == form.default_currency);
    return match?.symbol || '€';
});


const currentTableColumns = computed(() => {
    const t = translations.value;
    if(selectedTab.value === 3) return [{key: 'ref', label: t.item_ref}, {key: 'name', label: t.name}, {key: 'unit_price', label: t.unit_price}];
    if(selectedTab.value === 7) return [{key: 'name', label: t.name}, {key: 'symbol', label: t.symbol}, {key: 'code', label: t.code}];
    if(selectedTab.value === 8) return [{key: 'method_name', label: t.name}];
    if(selectedTab.value === 9) return [{key: 'name', label: t.name}];
    if(selectedTab.value === 10) return [{key: 'name', label: t.name}, {key: 'background', label: t.background, type: 'color'}, {key: 'color', label: t.color, type: 'color'}];
    return [];
});

const currentTableData = computed(() => {
    if(selectedTab.value === 3) return articles.value;
    if(selectedTab.value === 7) return currencies.value;
    if(selectedTab.value === 8) return payments.value;
    if(selectedTab.value === 9) return expenses.value;
    if(selectedTab.value === 10) return planning.value;
    return [];
});

const currentModalFields = computed(() => {
    const t = translations.value;
    if(selectedTab.value === 3) return [{key: 'ref', label: t.item_ref}, {key: 'name', label: t.name}, {key: 'description', label: t.description, type: 'textarea'}, {key: 'unit_price', label: t.unit_price}];
    if(selectedTab.value === 7) return [{key: 'name', label: t.name}, {key: 'symbol', label: t.symbol}, {key: 'code', label: t.code}];
    if(selectedTab.value === 8) return [{key: 'method_name', label: t.name}];
    if(selectedTab.value === 9) return [{key: 'name', label: t.name}];
    if(selectedTab.value === 10) return [{key: 'name', label: t.name}, {key: 'background', label: t.background, type: 'color'}, {key: 'color', label: t.color, type: 'color'}];
    return [];
});

const getCurrentDeleteType = computed(() => {
    if(selectedTab.value === 3) return 'article';
    if(selectedTab.value === 7) return 'currency';
    if(selectedTab.value === 8) return 'payment';
    if(selectedTab.value === 9) return 'expense';
    if(selectedTab.value === 10) return 'planning';
    return '';
});

// Logic
const showToast = (msg, type = "success") => {
    toast.message = msg;
    toast.type = type;
    toast.visible = true;
    setTimeout(() => toast.visible = false, 3000);
};

const loadData = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/wp-json/my-easy-compta/v1/settings/get', { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        if(res.data) {
            Object.assign(form, res.data);
        }
        
        const [articlesData] = await Promise.all([
             fetchArticles(),
             fetchGeneric('currencies', currencies),
             fetchGeneric('vats', vats),
             fetchGeneric('payments-methods', payments),
             fetchGeneric('expenses-cat', expenses),
             form.easy_compta_planning_addon_active ? fetchGeneric('planning-categories', planning) : null
        ]);
    } catch(e) { 
        console.error(e);
        showToast("Erreur lors du chargement des données", 'error'); 
    }
    finally { loading.value = false; }
};

const fetchArticles = async () => {
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/settings/articles', { headers: {"X-WP-Nonce": window.myEasyComptaAdmin.nonce}});
        const data = await res.json();
        articles.value = data.articles || [];
        categories.value = data.categories || [];
    } catch(e) { console.error(e); }
};

const fetchGeneric = async (endpoint, targetRef) => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/settings/${endpoint}`, { headers: {"X-WP-Nonce": window.myEasyComptaAdmin.nonce}});
        const data = await res.json();
        targetRef.value = data || [];
    } catch(e) { console.error(e); }
};

const toggleAddon = (slug, formKey) => {
    // Turning OFF is always allowed
    if (form[formKey] == 1) {
        form[formKey] = 0;
        return;
    }

    const hasLicense = checkSlug(slug);

    if (hasLicense) {
        form[formKey] = 1;
        showToast("Module activé", "success");
    } else {
        const available = licenseData.value?.plugins ? Object.keys(licenseData.value.plugins).join(', ') : 'aucune';
        showToast(`Licence requise pour activer ce module. (Détectés: ${available})`, "error");
        console.log(`Mismatch slug: ${slug} not in`, licenseData.value?.plugins);
        form[formKey] = 0;
    }
};

const handleLogoUpload = (e) => {
   const file = e.target.files[0];
   if(file) {
      logoPreviewUrl.value = URL.createObjectURL(file);
      // Upload logic would involve FormData
   }
};

const handleSubmit = async () => {
    loading.value = true;
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/settings/save', {
            method: 'POST',
            headers: { "Content-Type": "application/json", "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
            body: JSON.stringify(form)
        });
        const data = await res.json();
        if(data.success) showToast(translations.value.saved_successfully || 'Enregistré', 'success');
        else throw new Error('Error');
    } catch(e) { showToast("Erreur lors de l'enregistrement", 'error'); }
    finally { loading.value = false; }
};

// Modal Logic
const openAddModal = () => {
    isEditing.value = false;
    isVATModal.value = false;
    isCategoryModal.value = false;
    modalForm.value = {};
    if(settingsModalRef.value) settingsModalRef.value.showModal();
};

const editItem = (item) => {
    isEditing.value = true;
    isVATModal.value = false;
    isCategoryModal.value = false;
    modalForm.value = {...item};
    if(settingsModalRef.value) settingsModalRef.value.showModal();
};

const openAddCategoryModal = () => {
    isEditing.value = false;
    isVATModal.value = false;
    isCategoryModal.value = true;
    modalForm.value = {};
    if(settingsModalRef.value) settingsModalRef.value.showModal();
};

const editCategory = (category) => {
    isEditing.value = true;
    isVATModal.value = false;
    isCategoryModal.value = true;
    modalForm.value = {...category};
    if(settingsModalRef.value) settingsModalRef.value.showModal();
};

const closeModal = () => {
    if(settingsModalRef.value) settingsModalRef.value.close();
};

const saveCategory = async () => {
    const url = `/wp-json/my-easy-compta/v1/settings/categories-articles` + (isEditing.value ? `/${modalForm.value.id}` : '');
    const method = isEditing.value ? 'PUT' : 'POST';
    
    try {
        loading.value = true;
        const res = await fetch(url, {
           method,
           headers: { "Content-Type": "application/json", "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
           body: JSON.stringify(modalForm.value)
        });
        const data = await res.json();

        if(data.success) {
            showToast(translations.value.saved_successfully || 'Enregistré avec succès', 'success');
            closeModal();
            fetchArticles();
        } else {
             throw new Error(data.message || 'Error');
        }
    } catch(e) { showToast('Error', 'error'); }
    finally { loading.value = false; }
};

const saveItem = async () => {
    let endpoint = '';
    if(selectedTab.value === 3) endpoint = 'articles';
    if(selectedTab.value === 7) endpoint = 'currencies';
    if(selectedTab.value === 8) endpoint = 'payments-methods';
    if(selectedTab.value === 9) endpoint = 'expenses-categories';
    if(selectedTab.value === 10) endpoint = 'planning-categories';
    
    const url = `/wp-json/my-easy-compta/v1/settings/${endpoint}` + (isEditing.value ? `/${modalForm.value.id}` : '');
    const method = isEditing.value ? 'PUT' : 'POST';
    
    try {
        loading.value = true;
        const res = await fetch(url, {
           method,
           headers: { "Content-Type": "application/json", "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
           body: JSON.stringify(modalForm.value)
        });
        if(res.ok) {
            showToast(translations.value.saved_successfully || 'Enregistré avec succès', 'success');
            closeModal();
            // Refresh specifics
            if(selectedTab.value === 3) fetchArticles();
            if(selectedTab.value === 7) fetchGeneric('currencies', currencies);
            if(selectedTab.value === 8) fetchGeneric('payments-methods', payments);
            if(selectedTab.value === 9) fetchGeneric('expenses-cat', expenses);
            if(selectedTab.value === 10) fetchGeneric('planning-categories', planning);
        } else {
            const err = await res.json().catch(() => ({}));
            showToast(err.message || 'Erreur lors de l\'enregistrement', 'error');
        }
    } catch(e) { showToast('Erreur réseau', 'error'); }
    finally { loading.value = false; }
};

const getRefByType = (type) => {
    if(type === 'currency') return currencies;
    if(type === 'payment') return payments;
    if(type === 'planning') return planning;
    return null;
};

const deleteItem = (type, id) => {
    deleteType.value = type;
    selectedId.value = id;
    showRemoveModal.value = true;
};

const handleDeletion = async (type, id) => {
   showRemoveModal.value = false;
   const endpoints = {
       article: 'articles',
       'article-category': 'categories-articles',
       currency: 'currencies',
       payment: 'payments-methods',
       expense: 'expenses-categories',
       planning: 'planning-categories'
   };
   
   if(endpoints[type]) {
       try {
           await fetch(`/wp-json/my-easy-compta/v1/settings/${endpoints[type]}/${id}`, {
               method: 'DELETE',
               headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
           });
           showToast("Supprimé", "success");
           if(type === 'article' || type === 'article-category') fetchArticles();
           else if(type === 'currency') fetchGeneric('currencies', currencies);
           else if(type === 'payment') fetchGeneric('payments-methods', payments);
           else if(type === 'expense') fetchGeneric('expenses-cat', expenses);
           else if(type === 'planning') fetchGeneric('planning-categories', planning);
       } catch(e) { 
           console.error(e); 
           showToast('Erreur', 'error');
       }
   }
};

// ── E-Invoicing Methods ────────────────────────────────────────────────────────

const loadEInvoicingSettings = async () => {
    try {
        const [settingsRes, pdpsRes] = await Promise.all([
            fetch('/wp-json/my-easy-compta/v1/e-invoicing/settings', { headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce } }),
            fetch('/wp-json/my-easy-compta/v1/e-invoicing/pdps', { headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce } }),
        ]);
        const settingsData = await settingsRes.json();
        const pdpsData = await pdpsRes.json();

        // Populate einvoicingForm
        Object.keys(einvoicingForm).forEach(key => {
            if (settingsData[key] !== undefined) einvoicingForm[key] = settingsData[key];
        });

        // Populate pdpConfigurations
        if (settingsData.pdp_configurations && typeof settingsData.pdp_configurations === 'object') {
            pdpConfigurations.value = { ...settingsData.pdp_configurations };
        }

        // Populate available PDPs catalog
        availablePdps.value = Array.isArray(pdpsData) ? pdpsData : Object.values(pdpsData);
    } catch (e) {
        console.error('Erreur chargement e-invoicing:', e);
    }
};

const saveEInvoicingSettings = async () => {
    einvoicingSaving.value = true;
    try {
        const payload = {
            ...einvoicingForm,
            pdp_configurations: pdpConfigurations.value,
        };
        const res = await fetch('/wp-json/my-easy-compta/v1/e-invoicing/settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.success) {
            showToast('Réglages de facturation électronique enregistrés.', 'success');
        } else {
            showToast(data.message || 'Erreur lors de l\'enregistrement.', 'error');
        }
    } catch (e) {
        showToast('Erreur serveur.', 'error');
    } finally {
        einvoicingSaving.value = false;
    }
};

const getPdpConfig = (pdpId) => {
    if (!pdpConfigurations.value[pdpId]) {
        pdpConfigurations.value[pdpId] = { enabled: false, environment: 'sandbox' };
    }
    return pdpConfigurations.value[pdpId];
};

const togglePdpEnabled = (pdpId) => {
    const config = getPdpConfig(pdpId);
    config.enabled = !config.enabled;
    if (config.enabled) {
        expandedPdp.value = pdpId;
    }
};

const testPdpConnection = async (pdpId) => {
    pdpTesting.value[pdpId] = true;
    pdpTestResults.value[pdpId] = null;
    try {
        const config = pdpConfigurations.value[pdpId] || {};
        const res = await fetch('/wp-json/my-easy-compta/v1/e-invoicing/pdp/test-connection', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({ pdp_id: pdpId, config }),
        });
        const data = await res.json();
        pdpTestResults.value[pdpId] = data;
    } catch (e) {
        pdpTestResults.value[pdpId] = { success: false, message: 'Erreur de connexion au serveur.' };
    } finally {
        pdpTesting.value[pdpId] = false;
    }
};

const getPdpIcon = (pdpId) => {
    const icons = {
        chorus_pro: Building2,
        pennylane: Shield,
        jefacture: Receipt,
        generic: Globe,
    };
    return icons[pdpId] || Plug;
};

const getPdpBadgeColor = (pdpId) => {
    const colors = {
        chorus_pro: 'bg-blue-900 text-blue-300',
        pennylane: 'bg-emerald-900 text-emerald-300',
        jefacture: 'bg-orange-900 text-orange-300',
        generic: 'bg-indigo-900 text-indigo-300',
    };
    return colors[pdpId] || 'bg-slate-700 text-slate-300';
};

// ──────────────────────────────────────────────────────────────────────────────

import { useRouter, useRoute } from 'vue-router';
const router = useRouter();
const route = useRoute();

// ── SMS Settings ──────────────────────────────────────────────────────────────
const smsSettings  = reactive({ enabled: false, provider: 'twilio', twilio_sid: '', twilio_token: '', twilio_from: '', ovh_app_key: '', ovh_app_secret: '', ovh_consumer_key: '', ovh_service_name: '', ovh_sender: '', admin_phone: '', events: {} });
const smsLoading   = ref(false);
const smsSaving    = ref(false);
const smsTesting   = ref(false);
const smsTestPhone = ref('');
const smsTestResult = ref('');

// ── App Mobile (tab 30) ───────────────────────────────────────────────────────
const mobileTokens = ref([]);
const mobileLoading = ref(false);
const mobileCreating = ref(false);
const mobileError = ref('');
const mobileTokenName = ref('');
const mobileCreatedToken = ref(null);
const mobileSubTab = ref('tokens'); // 'tokens' | 'guide'

const mobileGuideTokenId = ref('');
const mobileGuideFullToken = ref('');

const mobileSiteUrl = computed(() => {
    return window.myEasyComptaAdmin?.site_url || window.location.origin;
});

const mobileApiVerifyUrl = computed(() => {
    // L'app mobile utilise l'API du plugin mobile; on affiche l'endpoint "verify" à titre indicatif.
    return (window.myEasyComptaAdmin?.rest_url || (window.location.origin + '/wp-json')) + '/my-easy-compta/v1/mobile/auth/verify';
});

const mobileActiveTokens = computed(() => {
    return (mobileTokens.value || []).filter(t => !!t.is_active);
});

const mobileQrPayload = computed(() => {
    if (!mobileCreatedToken.value) return '';
    return JSON.stringify({ url: mobileSiteUrl.value, token: mobileCreatedToken.value.token });
});

const MOBILE_QR_SIZE = 220;

const mobileQrImageUrl = ref('');
const mobileGuideQrImageUrl = ref('');

async function setLocalQrDataUrl(targetRef, text) {
    if (!text) {
        targetRef.value = '';
        return;
    }
    try {
        targetRef.value = await QRCode.toDataURL(text, {
            width: MOBILE_QR_SIZE,
            margin: 1,
            errorCorrectionLevel: 'M',
        });
    } catch (_) {
        targetRef.value = '';
    }
}

watch(mobileQrPayload, (payload) => {
    setLocalQrDataUrl(mobileQrImageUrl, payload);
}, { immediate: true });

const mobileGuideQrPayload = computed(() => {
    const token = (mobileGuideFullToken.value || '').trim();
    if (!mobileGuideTokenId.value || !token || !token.startsWith('mec_')) return '';
    return JSON.stringify({ url: mobileSiteUrl.value, token });
});

watch(mobileGuideQrPayload, (payload) => {
    setLocalQrDataUrl(mobileGuideQrImageUrl, payload);
}, { immediate: true });

watch(mobileGuideTokenId, () => {
    mobileGuideFullToken.value = '';
});

async function loadMobileTokens() {
    mobileError.value = '';
    mobileLoading.value = true;
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/mobile-tokens', {
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
        });
        const data = await res.json();
        if (Array.isArray(data)) {
            mobileTokens.value = data;
        } else if (data && data.data && Array.isArray(data.data)) {
            mobileTokens.value = data.data;
        } else {
            throw new Error(data?.message || 'Erreur de chargement');
        }
    } catch (_) {
        mobileError.value = 'Impossible de charger les tokens. Vérifiez que le plugin “Application Mobile” est activé.';
    } finally {
        mobileLoading.value = false;
    }
}

async function createMobileToken() {
    const name = (mobileTokenName.value || '').trim();
    if (!name) {
        mobileError.value = 'Veuillez saisir un nom de token.';
        return;
    }
    mobileError.value = '';
    mobileCreating.value = true;
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/mobile-tokens', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({ name }),
        });
        const data = await res.json();
        if (data?.token) {
            mobileCreatedToken.value = data;
            mobileTokenName.value = '';
            await loadMobileTokens();
            showToast('Token créé', 'success');
        } else {
            throw new Error(data?.message || 'Erreur de création');
        }
    } catch (_) {
        mobileError.value = 'Impossible de créer le token.';
    } finally {
        mobileCreating.value = false;
    }
}

async function revokeMobileToken(id) {
    if (!confirm('Révoquer ce token ? L’app connectée ne pourra plus accéder à l’API.')) return;
    mobileError.value = '';
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/mobile-tokens/${id}/revoke`, {
            method: 'POST',
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
        });
        const data = await res.json();
        if (data?.success) {
            await loadMobileTokens();
            showToast('Token révoqué', 'success');
        } else {
            throw new Error(data?.message || 'Erreur');
        }
    } catch (_) {
        mobileError.value = 'Impossible de révoquer le token.';
    }
}

async function deleteMobileToken(id) {
    if (!confirm('Supprimer ce token ? Cette action est irréversible.')) return;
    mobileError.value = '';
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/mobile-tokens/${id}`, {
            method: 'DELETE',
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
        });
        const data = await res.json();
        if (data?.success) {
            await loadMobileTokens();
            showToast('Token supprimé', 'success');
        } else {
            throw new Error(data?.message || 'Erreur');
        }
    } catch (_) {
        mobileError.value = 'Impossible de supprimer le token.';
    }
}

function copyToClipboard(text) {
    try {
        navigator.clipboard?.writeText(String(text || ''));
        showToast('Copié', 'success');
    } catch (_) {
        showToast('Copie impossible', 'error');
    }
}

const smsEvents = [
    { key: 'invoice_sent',      label: 'Facture envoyée',            desc: 'Quand une facture est envoyée au client',           defaultTemplate: 'Bonjour {CLIENT_NAME}, votre facture {INVOICE_NUMBER} de {AMOUNT} vous a été envoyée.' },
    { key: 'payment_received',  label: 'Paiement reçu',              desc: 'Quand un paiement est enregistré',                  defaultTemplate: 'Bonjour {CLIENT_NAME}, votre paiement de {AMOUNT} a bien été reçu. Merci !' },
    { key: 'quote_accepted',    label: 'Devis accepté',              desc: 'Quand un client accepte un devis',                  defaultTemplate: 'Bonjour, le devis {INVOICE_NUMBER} a été accepté par {CLIENT_NAME}.' },
    { key: 'invoice_overdue',   label: 'Facture en retard',          desc: 'Pour les relances de factures impayées',            defaultTemplate: 'Rappel : la facture {INVOICE_NUMBER} de {AMOUNT} est en attente de règlement.' },
];

function toggleSmsEvent(key) {
    if (!smsSettings.events[key]) smsSettings.events[key] = { enabled: false, recipient: 'client', channel: 'sms', template: '' };
    if (!smsSettings.events[key].channel) smsSettings.events[key].channel = 'sms';
    smsSettings.events[key].enabled = !smsSettings.events[key].enabled;
}

async function loadSmsSettings() {
    smsLoading.value = true;
    try {
        const res  = await fetch('/wp-json/my-easy-compta/v1/sms/settings', { headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce } });
        const data = await res.json();
        Object.assign(smsSettings, data);
        if (!smsSettings.events) smsSettings.events = {};
    } catch (_) {}
    smsLoading.value = false;
}

async function loadOcrSettings() {
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/ocr/settings', {
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
        });
        if (res.ok) {
            const data = await res.json();
            ocrApiKey.value   = data.ocr_api_key  || '';
            ocrLanguage.value = data.ocr_language || 'fre';
        }
    } catch (_) {}
}

async function saveOcrSettings() {
    savingOcr.value = true;
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/ocr/settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({ ocr_api_key: ocrApiKey.value, ocr_language: ocrLanguage.value }),
        });
        const data = await res.json();
        if (data.success) showToast(translations.value.saved_successfully || 'Enregistré', 'success');
        else throw new Error('Error');
    } catch (_) { showToast('Erreur lors de l\'enregistrement', 'error'); }
    savingOcr.value = false;
}

async function saveSmsSettings() {
    smsSaving.value = true;
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/sms/settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
            body: JSON.stringify(smsSettings),
        });
        const data = await res.json();
        if (data.success) showToast(translations.value.saved_successfully || 'Enregistré', 'success');
        else throw new Error('Error');
    } catch (_) { showToast('Erreur lors de l\'enregistrement', 'error'); }
    smsSaving.value = false;
}

async function sendSmsTest() {
    smsTesting.value  = true;
    smsTestResult.value = '';
    try {
        const res  = await fetch('/wp-json/my-easy-compta/v1/sms/test', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({ phone: smsTestPhone.value }),
        });
        const data = await res.json();
        smsTestResult.value = data.success ? '✓ SMS envoyé avec succès' : '✗ ' + (data.message || 'Erreur d\'envoi');
    } catch (_) { smsTestResult.value = '✗ Erreur de connexion'; }
    smsTesting.value = false;
}

// Load SMS settings when switching to tab 26
watch(selectedTab, (val) => {
    if (val === 26) loadSmsSettings();
    if (val === 29) loadOcrSettings();
    if (val === 30) loadMobileTokens();
});

onMounted(() => {
    loadData();
    fetchLicenseStatus();
    loadEInvoicingSettings();

    // Handle tab selection from query params (SPA style)
    if (route.query.tab) {
        const tabId = parseInt(route.query.tab);
        if (!isNaN(tabId)) {
            selectedTab.value = tabId;
        }
    }
});

// Persist tab change to URL
watch(selectedTab, (newTab) => {
    router.replace({
        query: { ...route.query, tab: newTab }
    });
    if (newTab === 20) loadReminders();
});

// Update tab if URL changes (e.g. browser back/forward)
watch(() => route.query.tab, (newTab) => {
    if (newTab) {
        const tabId = parseInt(newTab);
        if (!isNaN(tabId) && selectedTab.value !== tabId) {
            selectedTab.value = tabId;
        }
    }
});

// License Methods
const fetchLicenseStatus = async () => {
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/license/check-license', {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        if(data.success) {
            licenseData.value = data.license_data;
            installedVersions.value = data.installed_versions || {};
        }
    } catch(e) { console.error(e); }
};

const refreshLicense = async () => {
    licenseLoading.value = true;
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/license/refresh-license', {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        if(data.success) {
            licenseData.value = data.license_data;
            installedVersions.value = data.installed_versions || {};
            showToast('Statut de la licence rafraîchi', 'success');
        }
    } catch(e) { showToast('Erreur lors du rafraîchissement', 'error'); }
    finally { licenseLoading.value = false; }
};

const handleInstallAddon = async (slug) => {
    processingAddon.value = slug;
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/license/download-update', {
            method: 'POST',
            headers: { "Content-Type": "application/json", "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({ plugin_slug: slug })
        });
        const data = await res.json();
        if(data.success) {
            showToast('Module opérationnel !', 'success');
            await fetchLicenseStatus();
        } else {
            showToast(data.message || 'Erreur lors de l’opération', 'error');
        }
    } catch(e) {
        showToast('Erreur de connexion serveur', 'error');
    } finally {
        processingAddon.value = null;
    }
};

const isUpdateAvailable = (slug, latestVersion) => {
    const current = installedVersions.value[slug];
    if (!current) return false;
    
    // Basic semver compare (split by dots)
    const v1 = current.split('.');
    const v2 = latestVersion.split('.');
    for (let i = 0; i < Math.max(v1.length, v2.length); i++) {
        const num1 = parseInt(v1[i] || 0);
        const num2 = parseInt(v2[i] || 0);
        if (num2 > num1) return true;
        if (num1 > num2) return false;
    }
    return false;
};

const handleActivateLicense = async () => {
    licenseLoading.value = true;
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/license/validate-license', {
            method: 'POST',
            headers: { "Content-Type": "application/json", "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({ license_key: licenseKey.value })
        });
        const data = await res.json();
        if(data.valid) {
             // Store it
             await fetch('/wp-json/my-easy-compta/v1/license/store-license', {
                method: 'POST',
                headers: { "Content-Type": "application/json", "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
                body: JSON.stringify({ license_key: licenseKey.value, license_data: data })
             });
             showToast(translations.value.license_activated || 'Licence activée !', 'success');
             fetchLicenseStatus();
        } else {
             showToast(data.message || 'Clé invalide', 'error');
        }
    } catch(e) { showToast('Erreur serveur', 'error'); }
    finally { licenseLoading.value = false; }
};


const copyLicenseKey = () => {
    if (!licenseKey.value) return;
    navigator.clipboard?.writeText(licenseKey.value).then(() => showToast('Clé copiée !', 'success'));
};

const deleteLicense = async () => {
    try {
        await fetch('/wp-json/my-easy-compta/v1/license/delete-license', {
            method: 'DELETE',
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        licenseData.value = null;
        licenseKey.value = "";
        showToast('Licence supprimée', 'success');
    } catch(e) { showToast('Erreur', 'error'); }
};

// Affiliate
const affiliateForm = reactive({ name: '', email: '', website: '', payment_email: '', message: '' });
const affiliateLoading = ref(false);
const affiliateMsg = ref('');
const affiliateMsgType = ref('success');

const registerAffiliate = async () => {
    affiliateLoading.value = true;
    affiliateMsg.value = '';
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/license/apply-affiliate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
            body: JSON.stringify(affiliateForm)
        });
        const data = await res.json();
        affiliateMsgType.value = data.success ? 'success' : 'error';
        affiliateMsg.value = data.message || (data.success ? 'Candidature envoyée !' : 'Une erreur est survenue.');
        if (data.success) Object.assign(affiliateForm, { name: '', email: '', website: '', payment_email: '', message: '' });
    } catch(e) {
        affiliateMsgType.value = 'error';
        affiliateMsg.value = 'Erreur de connexion au serveur.';
    } finally {
        affiliateLoading.value = false;
    }
};

// Reminders
const reminders = reactive({ enabled: true, delays: '7,15,30', message: '' });
const remindersLoading = ref(false);
const remindersMsg = ref('');
const remindersMsgType = ref('success');

const emailPreviewHtml = ref(null);
const _addons = window.myEasyComptaAdmin?.addons || {};
const addonActive = (slug) => !!_addons[slug];

const testEmailLoading = ref(false);
const testEmailMsg = ref(null); // { ok: bool, text: string }

const sendTestEmail = async () => {
    testEmailLoading.value = true;
    testEmailMsg.value = null;
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/settings/send-test-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.myEasyComptaAdmin.nonce
            }
        });
        const data = await res.json();
        testEmailMsg.value = { ok: !!data.success, text: data.message || (data.success ? 'Email envoyé !' : 'Échec de l\'envoi') };
    } catch (e) {
        testEmailMsg.value = { ok: false, text: 'Erreur réseau : ' + e.message };
    } finally {
        testEmailLoading.value = false;
    }
};

const previewEmail = async (type) => {
    try {
        const theme = form.ecwp_email_theme || 'dark';
        const res = await fetch(`/wp-json/my-easy-compta/v1/settings/email-preview?type=${type}&theme=${theme}`, {
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        emailPreviewHtml.value = data.html;
    } catch (e) {
        console.error(e);
    }
};

const loadReminders = async () => {
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/reminders/settings', {
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin?.nonce }
        });
        const data = await res.json();
        if (data.enabled !== undefined) reminders.enabled = data.enabled;
        if (data.delays) reminders.delays = data.delays;
        if (data.message) reminders.message = data.message;
    } catch(e) { /* silent */ }
};

const saveReminders = async () => {
    remindersLoading.value = true;
    remindersMsg.value = '';
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/reminders/settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin?.nonce },
            body: JSON.stringify({ enabled: reminders.enabled, delays: reminders.delays, message: reminders.message })
        });
        const data = await res.json();
        remindersMsgType.value = data.success ? 'success' : 'error';
        remindersMsg.value = data.success ? 'Paramètres enregistrés.' : 'Erreur lors de la sauvegarde.';
    } catch(e) {
        remindersMsgType.value = 'error';
        remindersMsg.value = 'Erreur de connexion au serveur.';
    } finally {
        remindersLoading.value = false;
    }
};

const openAddVATModal = () => {
    isVATModal.value = true;
    isEditing.value = false;
    modalForm.value = { rate: 20, description: '' };
    settingsModalRef.value.showModal();
};

const editVAT = (vat) => {
    isVATModal.value = true;
    isEditing.value = true;
    modalForm.value = { ...vat };
    settingsModalRef.value.showModal();
};

const saveVAT = async () => {
    try {
        const url = isEditing.value ? `/wp-json/my-easy-compta/v1/settings/vats/${modalForm.value.id}` : '/wp-json/my-easy-compta/v1/settings/vats';
        const method = isEditing.value ? 'PUT' : 'POST';
        const res = await axios({ method, url, data: modalForm.value, headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        if(res.data.success) {
            const msg = isEditing.value ? 
                (translations.value.updated_successfully || 'Mis à jour avec succès') : 
                (translations.value.added_successfully || 'Ajouté avec succès');
            showToast(msg, 'success');
            closeModal();
            loadData();
        }
    } catch(e) { console.error(e); }
};
</script>

<style scoped>
.settings-sidebar-scroll::-webkit-scrollbar { display: none; }
.settings-sidebar-scroll { scrollbar-width: none; }
</style>
