<template>
  <MainLayout :title="invoice.invoice_number || (translations.loading || 'Loading...')" :subtitle="invoice.created_at ? `${translations.created_at}: ${invoice.created_at}` : ''">
    
    <!-- Toast -->
    <div v-if="toast.visible" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-[9999] toast-animate-in">
      <div :class="['flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md', toast.type === 'success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
        <component :is="toast.type === 'success' ? 'CheckCircle2' : 'AlertCircle'" class="w-6 h-6" />
        <span class="font-bold text-sm">{{ toast.message }}</span>
        <button @click="toast.visible = false" class="ml-2 hover:bg-white/20 p-1 rounded-full transition-colors"><X class="w-4 h-4" /></button>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="loading" class="fixed inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center">
       <div class="w-10 h-10 border-4 border-purple-600 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Remove Item Modal -->
    <remove-modal
      :show-modal="showRemoveModal"
      modal-id="remove_item_modal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      :confirmText="translations.yes_delete_it"
      :cancelText="translations.cancel"
      @confirm="removeItem"
      @cancel="showRemoveModal = false"
    />

    <!-- Remove Payment Modal -->
    <remove-modal
      :show-modal="showDeletePaymentModal"
      modal-id="remove_payment_modal"
      :title="translations.are_you_sure || 'Êtes-vous sûr ?'"
      :message="'Ce paiement sera supprimé et le statut de la facture sera mis à jour.'"
      :confirmText="translations.yes_delete_it || 'Oui, supprimer'"
      :cancelText="translations.cancel || 'Annuler'"
      @confirm="deletePayment"
      @cancel="showDeletePaymentModal = false"
    />

    <div class="space-y-8">
      <!-- Navbar (Actions) -->
      <InvoiceNavBar
        ref="navBarRef"
        :invoiceInfo="invoice"
        :currencyDefault="defaultCurrency"
        :currencyClient="clientCurrency"
        :emailActive="isEmailActive"
        :emailSubject="settings.invoice_email_subject"
        :emailContent="settings.invoice_email_content"
        :remindSubject="settings.invoice_email_remind_subject"
        :remindContent="settings.invoice_email_remind_content"
        :qrCodeActive="isQrCodeActive"
        :recurringActive="isRecurringActive"
        :smsActive="isSmsActive"
        :noItems="no_items"
        @refresh="fetchInvoiceDetails"
        @show-toast="showToast"
      />

      <!-- Overdue Alert -->
      <div v-if="isInvoiceOverdue && (invoice.status == 'unpaid' || invoice.status == 'partial')" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700/50 rounded-2xl p-6 flex items-center justify-between gap-4 text-amber-700 dark:text-amber-400">
          <div class="flex items-center gap-4">
              <AlertTriangle class="w-6 h-6 stroke-2 flex-shrink-0" />
              <h3 class="font-bold">{{ translations.invoice_overdue }}</h3>
          </div>
          <button
              v-if="isEmailActive"
              @click="navBarRef?.sendRemind()"
              class="flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-black uppercase tracking-widest px-4 py-2 rounded-xl shadow shadow-amber-500/30 transition-all active:scale-95 flex-shrink-0"
          >
              <Bell class="w-4 h-4" />
              {{ translations.remind_invoice || 'Relancer' }}
          </button>
      </div>

      <!-- Main Content Card -->
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 shadow-xl shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800 relative overflow-hidden">
         
         <!-- Header Section -->
         <div class="flex flex-col md:flex-row justify-between gap-10 mb-12 border-b border-slate-100 dark:border-slate-800 pb-12">
            <!-- Logo -->
            <div class="flex-1">
               <img v-if="settings.logo_url" :src="settings.logo_url" :style="{ width: settings.logo_width + 'px' }" class="max-w-full h-auto object-contain rounded-xl" alt="Company Logo" />
            </div>
            
            <!-- Invoice Meta -->
            <div class="text-right space-y-3">
               <h2 class="text-4xl font-black text-purple-600">{{ invoice.invoice_number }}</h2>
               <div class="space-y-1">
                  <p class="text-slate-500 font-bold text-sm"><span class="text-slate-900 dark:text-white">{{ translations.created_at }}:</span> {{ invoice.created_at }}</p>
                  <p class="text-slate-500 font-bold text-sm"><span class="text-slate-900 dark:text-white">{{ translations.due_date }}:</span> {{ invoice.due_date }}</p>
               </div>
                <div class="pt-2 flex flex-col items-end gap-2">
                   <div class="flex items-center justify-end gap-3">
                       <span class="text-slate-900 dark:text-white font-black text-sm uppercase tracking-widest">{{ translations.status || 'Statut' }}:</span>
                       <span :class="[
                           'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest',
                           invoice.status == 'draft' ? 'bg-slate-100 text-slate-500' :
                           invoice.status == 'unpaid' ? 'bg-amber-100 text-amber-600' :
                           invoice.status == 'partial' ? 'bg-orange-100 text-orange-600' :
                           'bg-emerald-100 text-emerald-600'
                       ]">
                           {{ invoice.status == 'draft' ? (translations.draft || 'Brouillon') :
                              invoice.status == 'unpaid' ? (translations.unpaid || 'Impayée') :
                              invoice.status == 'partial' ? (translations.partial || 'Partielle') :
                              (translations.paid || 'Payée') }}
                       </span>
                   </div>
                   <div v-if="invoice.fiscal_status" class="flex items-center justify-end gap-3">
                       <span class="text-slate-900 dark:text-white font-black text-[10px] uppercase tracking-widest">{{ translations.fiscal || 'Fiscal' }}:</span>
                       <FiscalStatusBadge :status="invoice.fiscal_status" />
                   </div>
                   <!-- PDP rejection reason -->
                   <div v-if="invoice.pdp_rejection_reason" class="mt-1 text-xs text-rose-600 font-bold text-right max-w-xs">
                       <span class="font-black">{{ translations.pdp_rejection || 'Rejet PDP' }} :</span> {{ invoice.pdp_rejection_reason }}
                   </div>
                   <!-- PDP transmission ID -->
                   <div v-if="invoice.pdp_transmission_id" class="mt-1 text-xs text-slate-400 font-mono text-right">
                       {{ translations.pdp_ref || 'Réf. PDP' }} : {{ invoice.pdp_transmission_id }}
                   </div>
                   <!-- Factur-X download -->
                   <button
                     v-if="invoice.fiscal_status && invoice.fiscal_status !== 'draft'"
                     @click="downloadFacturX"
                     class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-black text-[10px] uppercase tracking-widest hover:bg-indigo-100 transition-colors"
                   >
                     <FileDown class="w-3.5 h-3.5" /> Factur-X PDF
                   </button>
                </div>
            </div>
         </div>

         <!-- Addresses -->
         <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mb-12">
             <!-- Bill To -->
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

             <!-- Bill From -->
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
         <div class="overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-800 mb-10">
             <table class="w-full text-left border-collapse">
                 <thead>
                     <tr class="bg-slate-50 dark:bg-slate-950 text-xs font-black text-slate-400 uppercase tracking-widest border-b border-slate-200 dark:border-slate-800">
                         <th class="p-4 w-12"></th> <!-- Grip -->
                         <th class="p-4">{{ translations.item_ref || translations.reference }}</th>
                         <th class="p-4">{{ translations.item_name || translations.name }}</th>
                         <th class="p-4">{{ translations.description }}</th>
                         <th class="p-4 text-center">{{ translations.quantity }}</th>
                         <th class="p-4 text-right">{{ translations.unit_price }}</th>
                         <th v-if="settings.vat_active == 1" class="p-4 text-center">{{ translations.tax }}</th>
                         <th class="p-4 text-right">{{ translations.total }}</th>
                         <th class="p-4 text-center w-24">{{ translations.actions }}</th>
                     </tr>
                 </thead>
                 <tbody id="items-body">
                     <tr v-for="item in items" :key="item.id" :data-id="item.id" class="group border-b border-slate-100 dark:border-slate-800 last:border-0 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                         <td class="p-4 text-center text-slate-300 cursor-move drag-handle group-hover:text-slate-500"><GripVertical v-if="!isLocked" class="w-4 h-4" /></td>
                          <td class="p-4 text-sm font-bold text-slate-600 dark:text-slate-300">{{ item.item_ref }}</td>
                          <td class="p-4 text-sm font-bold text-slate-900 dark:text-white">{{ item.item_name }}</td>
                          <td class="p-4 text-sm text-slate-500 dark:text-slate-400 max-w-xs" :title="item.item_description ? item.item_description.replace(/<[^>]*>/g, '') : ''"><div class="truncate">{{ item.item_description ? item.item_description.replace(/<[^>]*>/g, '') : '' }}</div></td>
                          <td class="p-4 text-sm font-bold text-center text-slate-900 dark:text-white">{{ item.quantity }}</td>
                          <td class="p-4 text-sm font-mono text-right text-slate-600 dark:text-slate-300">{{ formatCurrency(item.unit_price).replace(clientCurrency.value || defaultCurrency.value, '') }}</td>
                          <td v-if="settings.vat_active == 1" class="p-4 text-sm text-center text-slate-500">{{ item.vat_rate }}%</td>
                          <td class="p-4 text-sm font-bold font-mono text-right text-slate-900 dark:text-white">{{ formatCurrency(item.total_amount) }}</td>
                         <td class="p-4 text-center">
                             <div 
                                v-if="!isLocked"
                                class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity"
                             >
                                 <button @click="editItem(item)" class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"><Pencil class="w-4 h-4" /></button>
                                 <button @click="confirmRemoveItem(item)" class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition-colors"><Trash2 class="w-4 h-4" /></button>
                             </div>
                             <div v-else class="text-slate-300">
                                 <Lock class="w-4 h-4 mx-auto opacity-20" />
                             </div>
                         </td>
                     </tr>
                     <tr v-if="items.length === 0">
                         <td colspan="9" class="p-12 text-center text-slate-400 font-medium italic">{{ translations.no_items || 'Aucun élément' }}</td>
                     </tr>
                     <!-- Add Item Button -->
                     <tr v-if="invoice.id && !isLocked">
                         <td colspan="9" class="p-6 text-center bg-slate-50/50 dark:bg-slate-950/30">
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
         <div class="flex flex-col items-end">
             <div class="w-full md:w-1/3 space-y-4 bg-slate-50 dark:bg-slate-950/50 rounded-3xl p-8 border border-slate-100 dark:border-slate-800">
                 <template v-if="settings.vat_active == 1">
                     <div class="flex justify-between items-center text-slate-500 font-bold text-sm">
                         <span>{{ translations.subtotal }}:</span>
                         <span class="font-mono text-slate-900 dark:text-white">{{ formatCurrency(invoice.subtotal) }}</span>
                     </div>
                     <div class="flex justify-between items-center text-slate-500 font-bold text-sm">
                         <span>{{ translations.tax }}:</span>
                         <span class="font-mono text-slate-900 dark:text-white">{{ formatCurrency(invoice.tax) }}</span>
                     </div>
                     <div class="h-px bg-slate-200 dark:bg-slate-800 my-4"></div>
                 </template>
                 <div class="flex justify-between items-center text-lg font-black text-slate-900 dark:text-white">
                     <span>{{ translations.total }}:</span>
                     <span class="text-purple-600 font-mono">{{ formatCurrency(invoice.total_amount) }}</span>
                 </div>
             </div>
         </div>

      </div>

      <!-- Paiements partiels (addon Advance requis) -->
      <div v-if="isAdvanceActive && invoice.status && invoice.status !== 'draft'" class="mt-8 bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center">
              <CreditCard class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">{{ translations.payments_section || 'Payments' }}</h3>
              <p v-if="paymentsData.total_amount" class="text-xs text-slate-400 font-bold mt-0.5">
                {{ formatCurrency(paymentsData.paid_amount) }} / {{ formatCurrency(paymentsData.total_amount) }}
              </p>
            </div>
          </div>
          <button
            v-if="isAdvanceActive && invoice.status !== 'paid'"
            @click="showPartialPaymentModal = true"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-emerald-600 text-white font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-emerald-500/30"
          >
            <Plus class="w-4 h-4" />
            {{ translations.add_payment || 'Add payment' }}
          </button>
        </div>

        <!-- Progress bar -->
        <div v-if="paymentsData.total_amount > 0" class="mb-6">
          <div class="flex justify-between text-xs font-bold text-slate-500 mb-2">
            <span>{{ Math.round((paymentsData.paid_amount / paymentsData.total_amount) * 100) }}% réglé</span>
            <span class="text-slate-400">Reste : <span class="text-rose-500 font-black font-mono">{{ formatCurrency(paymentsData.remaining_amount) }}</span></span>
          </div>
          <div class="h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-500"
              :class="paymentsData.remaining_amount <= 0 ? 'bg-emerald-500' : 'bg-amber-400'"
              :style="{ width: Math.min(100, Math.round((paymentsData.paid_amount / paymentsData.total_amount) * 100)) + '%' }"
            ></div>
          </div>
        </div>

        <!-- Payment list -->
        <div v-if="paymentsLoading" class="space-y-3">
          <div v-for="i in 2" :key="i" class="h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl animate-pulse"></div>
        </div>
        <div v-else-if="paymentsData.payments && paymentsData.payments.length === 0" class="py-8 text-center text-slate-400 font-bold text-sm">
          {{ translations.no_payments_recorded || 'No payment recorded.' }}
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="payment in paymentsData.payments"
            :key="payment.id"
            class="flex items-center justify-between p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700 group"
          >
            <div class="flex items-center gap-4">
              <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/20 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <CheckCircle2 class="w-4 h-4" />
              </div>
              <div>
                <div class="font-black text-slate-900 dark:text-white text-sm font-mono">{{ formatCurrency(payment.amount) }}</div>
                <div class="text-xs text-slate-400 font-bold mt-0.5">{{ payment.payment_date }} · {{ payment.payment_method }}</div>
                <div v-if="payment.notes" class="text-xs text-slate-500 mt-0.5 italic">{{ payment.notes }}</div>
              </div>
            </div>
            <button
              @click="confirmDeletePayment(payment)"
              class="p-2 text-slate-300 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-colors opacity-0 group-hover:opacity-100"
              title="Supprimer ce paiement"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Historique fiscal -->
      <div v-if="fiscalHistory.length > 0" class="mt-12 bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-900/30 text-violet-600 flex items-center justify-center">
            <History class="w-5 h-5" />
          </div>
          <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">Historique fiscal</h3>
        </div>
        <div class="space-y-3">
          <div v-for="entry in fiscalHistory" :key="entry.id" class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
            <div class="w-2 h-2 rounded-full mt-2 flex-shrink-0" :class="{
              'bg-emerald-500': entry.action === 'validate',
              'bg-purple-500': entry.action === 'transmit' || entry.action === 'sent_pdp',
              'bg-rose-500':   entry.action === 'reject' || entry.action === 'rejected',
              'bg-indigo-500': entry.action === 'accept' || entry.action === 'accepted',
              'bg-slate-400':  !['validate','transmit','sent_pdp','reject','rejected','accept','accepted'].includes(entry.action)
            }"></div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-black text-slate-900 dark:text-white capitalize">{{ entry.action.replace('_', ' ') }}</span>
                <span class="text-[10px] text-slate-400 font-mono flex-shrink-0">{{ entry.created_at }}</span>
              </div>
              <p v-if="entry.description" class="text-xs text-slate-500 mt-1">{{ entry.description }}</p>
              <p v-if="entry.new_value" class="text-[10px] font-mono text-slate-400 mt-1">{{ entry.new_value }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notes internes -->
    <div v-if="invoice.internal_notes" class="mt-6 bg-amber-50 dark:bg-amber-900/10 rounded-[2.5rem] p-8 border border-amber-100 dark:border-amber-800/30">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
          <StickyNote class="w-5 h-5" />
        </div>
        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">{{ translations.internal_notes || 'Notes internes' }}</h3>
        <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 rounded-lg">Non imprimé</span>
      </div>
      <p class="text-sm text-slate-600 dark:text-slate-300 font-medium whitespace-pre-wrap">{{ invoice.internal_notes }}</p>
    </div>

    <!-- Historique des modifications -->
    <div v-if="invoiceHistory.length > 0" class="mt-6 bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
      <button @click="showHistory = !showHistory" class="w-full flex items-center justify-between gap-3 group">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 flex items-center justify-center">
            <History class="w-5 h-5" />
          </div>
          <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">{{ translations.invoice_history || 'Historique' }}</h3>
          <span class="text-[10px] font-black px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500">{{ invoiceHistory.length }}</span>
        </div>
        <ChevronDown class="w-4 h-4 text-slate-400 transition-transform" :class="{ 'rotate-180': showHistory }" />
      </button>
      <div v-if="showHistory" class="mt-6 space-y-2">
        <div v-for="entry in invoiceHistory" :key="entry.id" class="flex items-start gap-3 p-3 rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
          <div class="w-2 h-2 rounded-full mt-2 flex-shrink-0" :class="{
            'bg-emerald-500': ['payment_added','invoice_created'].includes(entry.action),
            'bg-rose-500': ['payment_deleted','item_deleted'].includes(entry.action),
            'bg-amber-500': ['item_updated','price_updated','invoice_updated'].includes(entry.action),
            'bg-blue-400': entry.action === 'item_added',
            'bg-slate-400': !['payment_added','invoice_created','payment_deleted','item_deleted','item_updated','price_updated','invoice_updated','item_added'].includes(entry.action),
          }"></div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-2">
              <span class="text-xs font-bold text-slate-700 dark:text-slate-300">{{ entry.action_label || entry.action }}</span>
              <span class="text-[10px] text-slate-400 font-mono flex-shrink-0">{{ entry.created_at }}</span>
            </div>
            <p v-if="entry.description" class="text-xs text-slate-500 mt-0.5">{{ entry.description }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Partial Payment Modal (addon Advance) -->
    <PartialPaymentModal
      v-if="isAdvanceActive"
      :show-modal="showPartialPaymentModal"
      modal-id="modal_partial_payment"
      :modal-title="translations.add_payment || 'Add payment'"
      :invoice-id="invoice.id"
      :remaining-amount="paymentsData.remaining_amount || 0"
      :currency="clientCurrency || defaultCurrency"
      :payment-methods="paymentMethods"
      @close="showPartialPaymentModal = false"
      @success="onPaymentAdded"
    />

    <!-- Edit Item Modal -->
    <EditItemModal
      v-if="editItemsModal"
      :show-modal="editItemsModal"
      modal-id="edit_item_modal"
      :modal-title="translations.edit_item || 'Modifier'"
      :item="selectedItem"
      @close="editItemsModal = false"
      @itemEdited="onItemEdited"
    />

    <!-- Add Item Modal -->
    <AddItemModal
      :show-modal="showAddItemModal"
      modal-id="modal_add_item_invoice"
      :modal-title="translations.add_item || 'Ajouter une ligne'"
      :categories="categories"
      :invoice-id="invoice.id"
      @close="showAddItemModal = false"
      @itemAdded="fetchInvoiceDetails"
    />

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick, onUpdated } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import MainLayout from '@/components/layout/MainLayout.vue';
import RemoveModal from "@/components/RemoveAlert.vue";
import InvoiceNavBar from "@/components/invoices/NavBar.vue";
import EditItemModal from "@/components/invoices/Modal_Edit_Item.vue";
import AddItemModal from "@/components/invoices/Modal_Add_Item.vue";
import FiscalStatusBadge from "@/components/invoices/FiscalStatusBadge.vue";
import { CheckCircle2, AlertCircle, X, AlertTriangle, Bell, User, Building, GripVertical, Pencil, Trash2, Plus, Lock, FileDown, History, CreditCard, StickyNote, ChevronDown } from 'lucide-vue-next';
import PartialPaymentModal from "@/components/invoices/PartialPaymentModal.vue";
import Sortable from "sortablejs";



const route = useRoute();
const navBarRef = ref(null);
const invoice = ref({});
const client_detail = ref({});
const items = ref([]);
const loading = ref(true);
const settings = ref({});
const showRemoveModal = ref(false);
const fiscalHistory = ref([]);
const itemToRemove = ref(null);
const editItemsModal = ref(false);
const selectedItem = ref({});
const no_items = ref(false);
const defaultCurrency = ref("EUR");
const clientCurrency = ref("EUR");
const categories = ref([]);
const showAddItemModal = ref(false);
const toast = reactive({ visible: false, message: "", type: "success" });
const licenseData = ref(null);
const showPartialPaymentModal = ref(false);
const showDeletePaymentModal = ref(false);
const paymentToDelete = ref(null);
const paymentsLoading = ref(false);
const paymentsData = ref({ payments: [], total_amount: 0, paid_amount: 0, remaining_amount: 0 });
const paymentMethods = ref([]);
const invoiceHistory = ref([]);
const showHistory = ref(false);

const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});

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

const isQrCodeActive = computed(() => {
    const settingActive = settings.value.easy_compta_qrcode_addon_active == 1;
    const licenseActive = licenseData.value?.valid && checkSlug('myeasycompta-qrcode-stripe');
    return settingActive && licenseActive ? 1 : 0;
});

const isRecurringActive = computed(() => {
    const settingActive = settings.value.easy_compta_recurring_invoices_addon_active == 1;
    const licenseActive = licenseData.value?.valid && checkSlug('myeasycompta-recurring-invoices');
    return settingActive && licenseActive ? 1 : 0;
});

const advanceRouteAvailable = ref(false);

const isAdvanceActive = computed(() => {
    return settings.value.easy_compta_advance_addon_active == 1 || advanceRouteAvailable.value;
});

const isLocked = computed(() => {
    if (!invoice.value.status) return true;
    // Lock if not draft commercially OR if validated fiscally
    const isCommercialDraft = invoice.value.status === 'draft';
    const isFiscalDraft = !invoice.value.fiscal_status || invoice.value.fiscal_status === 'draft' || invoice.value.fiscal_status === 'rejected';
    
    return !isCommercialDraft || !isFiscalDraft;
});

const isInvoiceOverdue = computed(() => {
    if (!invoice.value.due_date) return false;
    // Simple date comparison strings yyyy-mm-dd works if format is consistent, but safer to parse
    // Assuming backend sends YYYY-MM-DD.
    // However, if backend sends DD-MM-YYYY we might have issues.
    // legacy code did new Date(invoice.due_date).
    // Let's stick to simple new Date() logic from legacy.
    const today = new Date();
    const dueDate = new Date(invoice.value.due_date);
    return today > dueDate;
});

const showToast = (message, type = "success") => {
    toast.message = message;
    toast.type = type;
    toast.visible = true;
    setTimeout(() => toast.visible = false, 3000);
};

const fetchSettings = async () => {
    try {
        const res = await axios.get(`/wp-json/my-easy-compta/v1/settings/get`, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        if(res.data.success && res.data.data) {
            settings.value = res.data.data;
        } else {
            settings.value = res.data;
        }
} catch (e) {}
};

const fetchInvoiceDetails = async () => {
    loading.value = true;
    try {
        const res = await axios.get(`/wp-json/my-easy-compta/v1/invoices/${route.params.id}`, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        if(res.data.success) {
            invoice.value = res.data.data;
            // Ensure client_detail is an object even if null
            client_detail.value = invoice.value.client_detail ? invoice.value.client_detail : {};
            
            // Debug if empty
            if (Object.keys(client_detail.value).length === 0) {
            }

            items.value = invoice.value.items || [];
            defaultCurrency.value = invoice.value.currency_symbol || "€";
            clientCurrency.value = invoice.value.client_currency_symbol || "€";
            no_items.value = items.value.length === 0;
            
            nextTick(() => initSortable());
        }
    } catch(e) {
        showToast(translations.value.error_fetching_data, "error");
    } finally { loading.value = false; }
};

const fetchCategories = async () => {
    try {
         const res = await axios.get("/wp-json/my-easy-compta/v1/categories-articles", { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
         categories.value = res.data;
    } catch (e) {}
};

const formatCurrency = (amount) => {
    if (!amount) return "0.00 " + defaultCurrency.value;
    return parseFloat(amount).toFixed(2) + " " + (clientCurrency.value || defaultCurrency.value);
};

const confirmRemoveItem = (item) => {
    itemToRemove.value = item;
    showRemoveModal.value = true;
};

const removeItem = async () => {
    if(!itemToRemove.value) return;
    showRemoveModal.value = false;
    loading.value = true;
    try {
        const res = await axios.delete(`/wp-json/my-easy-compta/v1/invoices/element-delete/${itemToRemove.value.id}`, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        if(res.data.success) {
            const message = res.data.message || translations.value.item_deleted_successfully || 'Élément supprimé';
            showToast(message, "success");
            fetchInvoiceDetails();
        } else {
             showToast(translations.value.error_deleting_item, "error");
        }
    } catch(e) { 
        showToast(translations.value.error_deleting_item, "error");
    } finally {
        loading.value = false;
        itemToRemove.value = null;
    }
};

const editItem = (item) => {
    selectedItem.value = { ...item };
    editItemsModal.value = true;
};

const onItemEdited = () => {
    editItemsModal.value = false;
    fetchInvoiceDetails();
};

const updateItemOrder = async (sortedIds) => {
    try {
        const res = await axios.post(`/wp-json/my-easy-compta/v1/invoices/update-invoice-items-order`, { order: sortedIds }, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce } });
        if (res.data.success) {
            showToast(translations.value.order_updated_successfully || "Ordre mis à jour");
        }
    } catch(e) { 
        showToast("Erreur lors de la mise à jour de l'ordre", "error");
    }
};

const downloadFacturX = () => {
    window.open(`/wp-json/my-easy-compta/v1/invoices/pdf-facturx/${route.params.id}?_wpnonce=${window.myEasyComptaAdmin.nonce}`, '_blank');
};

const fetchFiscalHistory = async () => {
    try {
        const res = await axios.get(`/wp-json/my-easy-compta/v1/invoices/${route.params.id}/fiscal-history`, {
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        if (Array.isArray(res.data)) {
            fiscalHistory.value = res.data;
        } else if (res.data && Array.isArray(res.data.data)) {
            fiscalHistory.value = res.data.data;
        }
} catch (e) {}
};

const initSortable = () => {
    // Only allow sorting for non-locked invoices
    if (isLocked.value) return;

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

const fetchInvoicePayments = async () => {
    if (!route.params.id) return;
    paymentsLoading.value = true;
    try {
        const res = await axios.get(`/wp-json/my-easy-compta/v1/invoices/${route.params.id}/payments`, {
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce }
        });
        if (res.data.success) {
            advanceRouteAvailable.value = true;
            paymentsData.value = res.data.data;
        }
    } catch (e) {
        // 404 = addon inactif, 403 = licence invalide — section masquée
        advanceRouteAvailable.value = false;
    } finally { paymentsLoading.value = false; }
};

const fetchInvoiceHistory = async () => {
    try {
        const res = await axios.get(`/wp-json/my-easy-compta/v1/invoices/${route.params.id}/history`, {
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce }
        });
        if (res.data.success && Array.isArray(res.data.history)) {
            invoiceHistory.value = res.data.history;
        }
} catch (e) {}
};

const fetchPaymentMethods = async () => {
    try {
        const res = await axios.get('/wp-json/my-easy-compta/v1/payments/methods', {
            headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce }
        });
        paymentMethods.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
    } catch (e) {}
};

const onPaymentAdded = () => {
    fetchInvoicePayments();
    fetchInvoiceDetails();
    showToast('Paiement enregistré avec succès', 'success');
};

const confirmDeletePayment = (payment) => {
    paymentToDelete.value = payment;
    showDeletePaymentModal.value = true;
};

const deletePayment = async () => {
    if (!paymentToDelete.value) return;
    showDeletePaymentModal.value = false;
    try {
        const res = await axios.delete(
            `/wp-json/my-easy-compta/v1/invoices/${route.params.id}/payments/${paymentToDelete.value.id}`,
            { headers: { 'X-WP-Nonce': window.myEasyComptaAdmin.nonce } }
        );
        if (res.data.success) {
            showToast('Paiement supprimé', 'success');
            fetchInvoicePayments();
            fetchInvoiceDetails();
        } else {
            showToast(res.data.message || 'Erreur lors de la suppression', 'error');
        }
    } catch (e) {
        showToast('Erreur lors de la suppression du paiement', 'error');
    } finally {
        paymentToDelete.value = null;
    }
};

onMounted(() => {
    fetchInvoiceDetails();
    fetchSettings();
    fetchCategories();
    checkLicense();
    fetchFiscalHistory();
    fetchInvoicePayments(); // détecte automatiquement si l'addon Advance est actif
    fetchPaymentMethods();
    fetchInvoiceHistory();
});

onUpdated(() => {
    initSortable();
});

</script>
