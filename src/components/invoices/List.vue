<template>
  <MainLayout :title="translations.invoices || 'Invoices'" :subtitle="translations.invoices_subtitle || 'Manage your invoices and tracking'">
    
    <!-- Toast -->
    <div v-if="toast.visible" class="fixed bottom-8 right-8 z-[9999] animate-in fade-in slide-in-from-bottom-8 duration-300">
      <div :class="['flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl border backdrop-blur-md', toast.type === 'success' ? 'bg-emerald-500/90 text-white border-emerald-400/50' : 'bg-rose-500/90 text-white border-rose-400/50']">
        <component :is="toast.type === 'success' ? 'CheckCircle2' : 'AlertCircle'" class="w-6 h-6" />
        <span class="font-bold text-sm">{{ toast.message }}</span>
        <button @click="toast.visible = false" class="ml-2 hover:bg-white/20 p-1 rounded-full transition-colors"><X class="w-4 h-4" /></button>
      </div>
    </div>

    <template #actions>
      <router-link :to="{ name: 'InvoiceNew' }" custom v-slot="{ navigate }">
           <button 
             @click="navigate"
             class="bg-purple-600 text-white px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all shadow-lg shadow-purple-500/30 flex items-center gap-2"
           >
             <Plus class="w-4 h-4" />
             {{ translations.add || 'Ajouter' }}
           </button>
      </router-link>
       <button 
         v-if="exportActive"
         @click="goToExport"
         class="bg-white dark:bg-slate-800 text-slate-900 dark:text-white border border-slate-100 dark:border-slate-700 px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-105 active:scale-95 transition-all flex items-center gap-2"
       >
          <Download class="w-4 h-4" />
          {{ translations.export || 'Export' }}
       </button>
    </template>

    <!-- Notices E-Invoicing -->
    <template #before-content>
       <div v-if="eInvoicingNotices.untransmittedCount > 0 || eInvoicingNotices.pdpConfigIncomplete" class="px-8 pb-4">
           <EInvoicingNotices
            :untransmitted-count="eInvoicingNotices.untransmittedCount"
            :show-legal-notice="eInvoicingNotices.showLegalNotice"
            :pdp-config-incomplete="eInvoicingNotices.pdpConfigIncomplete"
            :company-data-incomplete="eInvoicingNotices.companyDataIncomplete"
          />
       </div>
    </template>

    <div class="space-y-6">
      <!-- Filters Top Bar -->
      <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-6 shadow-sm border border-slate-100 dark:border-slate-800">
         <!-- Row 1: text/select filters -->
         <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[200px] relative group">
                <Search class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-purple-500 transition-colors" />
                <input 
                  v-model="filters.invoice_number"
                  @input="debouncedFetch"
                  type="text" 
                  class="kloxy-input !pr-12"
                  :placeholder="translations.invoice_number_placeholder || 'Invoice n°...'"
                >
            </div>
            
            <select 
               v-model="filters.client" 
               @change="fetchInvoices(1)"
               class="flex-1 min-w-[200px] kloxy-input appearance-none cursor-pointer"
            >
               <option value="">{{ translations.all_clients || 'All clients' }}</option>
               <option v-for="client in clients" :key="client.id" :value="client.company_name">{{ client.company_name }}</option>
            </select>

            <select 
               v-model="filters.status"
               @change="fetchInvoices(1)"
               class="flex-1 min-w-[160px] kloxy-input appearance-none cursor-pointer"
            >
               <option value="">{{ translations.all_statuses || 'All statuses' }}</option>
               <option value="draft">{{ translations.draft || 'Draft' }}</option>
               <option value="unpaid">{{ translations.unpaid || 'Unpaid' }}</option>
               <option value="partial">{{ translations.partial || 'Partial' }}</option>
               <option value="paid">{{ translations.paid || 'Paid' }}</option>
            </select>

             <select 
                v-model="filters.fiscal_status"
                @change="fetchInvoices(1)"
                class="flex-1 min-w-[160px] kloxy-input appearance-none cursor-pointer"
            >
               <option value="">{{ translations.fiscal_status || 'Fiscal status' }}</option>
               <option value="draft">{{ translations.fiscal_status_draft || 'Draft' }}</option>
               <option value="validated">{{ translations.fiscal_status_validated || 'Validated' }}</option>
               <option value="sent_pdp">{{ translations.fiscal_status_sent_pdp || 'Sent PDP' }}</option>
               <option value="transmitted">{{ translations.fiscal_status_transmitted || 'Transmitted DGFiP' }}</option>
               <option value="accepted">{{ translations.fiscal_status_accepted || 'Accepted' }}</option>
               <option value="rejected">{{ translations.fiscal_status_rejected || 'Rejected' }}</option>
            </select>

         </div>
         <!-- Row 2: date range + reset -->
         <div class="flex items-center gap-4 mt-4">
            <DateRangePicker
              class="flex-1"
              v-model:from="filters.date_from"
              v-model:to="filters.date_to"
              @change="fetchInvoices(1)"
            />
            <button @click="resetFilters" class="p-4 bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-purple-600 rounded-2xl transition-colors shadow-sm" :title="translations.reset || 'Reset'">
                <RefreshCcw class="w-5 h-5" />
            </button>
         </div>
         <!-- Quick date filters -->
         <div class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 mr-1">{{ translations.period || 'Période :' }}</span>
            <button v-for="qf in quickFilters" :key="qf.value"
               @click="setQuickFilter(qf.value)"
               :class="activeQuickFilter === qf.value ? 'bg-purple-600 text-white shadow-md shadow-purple-500/30' : 'bg-slate-50 dark:bg-slate-800 text-slate-500 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20'"
               class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
            >{{ qf.label }}</button>
            <div class="w-px h-4 bg-slate-200 dark:bg-slate-700 mx-1"></div>
            <button
               @click="toggleOverdueFilter"
               :class="filters.overdue === '1' ? 'bg-red-600 text-white shadow-md shadow-red-500/30' : 'bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/30'"
               class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center gap-1.5"
            >
               <span class="w-1.5 h-1.5 rounded-full" :class="filters.overdue === '1' ? 'bg-white' : 'bg-red-500'"></span>
               {{ translations.overdue || 'En retard' }}
            </button>
            <button v-if="activeQuickFilter || filters.overdue === '1'" @click="clearQuickFilter"
               class="px-3 py-1.5 rounded-xl text-xs font-black uppercase tracking-widest bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-500 transition-all"
            >✕</button>
         </div>
      </div>

      <!-- Main List -->
      <div class="space-y-6">
          
        <!-- Stats / Controls -->
        <div class="flex items-center justify-between px-4">
            <div class="text-sm font-bold text-slate-500 dark:text-slate-400">
                <span v-if="!loading">{{ totalCount }} {{ translations.invoices || 'invoices' }}</span>
                <span v-else class="animate-pulse">{{ translations.loading || 'Loading...' }}</span>
            </div>
            
             <select
                  v-model="perPage"
                  @change="fetchInvoices(1)"
                  class="bg-transparent border-none text-slate-500 font-bold text-sm focus:ring-0 cursor-pointer"
                >
                  <option :value="10">{{ translations.per_page_10 || '10 per page' }}</option>
                  <option :value="20">{{ translations.per_page_20 || '20 per page' }}</option>
                  <option :value="50">{{ translations.per_page_50 || '50 per page' }}</option>
             </select>
        </div>
        
        <div v-if="loading" class="space-y-4">
           <!-- Skeletons -->
           <div v-for="i in 5" :key="i" class="h-24 bg-white dark:bg-slate-900 rounded-[2rem] animate-pulse"></div>
        </div>

        <div v-else-if="invoices.length === 0" class="text-center py-20 bg-white dark:bg-slate-900 rounded-[3rem] border border-dashed border-slate-200 dark:border-slate-800">
            <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <FileCheck class="w-8 h-8 text-slate-300" />
            </div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white">{{ translations.no_invoices_found || 'No invoice found' }}</h3>
            <p class="text-slate-500 mt-2 font-medium">{{ translations.invoice_help_text || 'Start by creating a new invoice.' }}</p>
             <router-link :to="{ name: 'InvoiceNew' }" class="inline-block mt-6 text-purple-600 font-black text-sm uppercase tracking-widest hover:underline">
                {{ translations.new_invoice || 'Create an invoice' }}
             </router-link>
        </div>

        <table v-else class="w-full border-separate border-spacing-y-3">
             <thead>
                <tr class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-4">
                    <th class="px-4 pb-2 text-left w-8">
                        <button @click="toggleSelectAll" class="text-slate-400 hover:text-purple-600 transition-colors">
                            <CheckSquare v-if="selectedIds.size === invoices.length && invoices.length > 0" class="w-4 h-4 text-purple-600" />
                            <Square v-else class="w-4 h-4" />
                        </button>
                    </th>
                    <th class="px-4 pb-2 text-left w-[30%]">{{ translations.invoice_client_col || 'Invoice / Client' }}</th>
                    <th class="px-4 pb-2 text-left hidden md:table-cell w-[15%]">{{ translations.status || 'Status' }}</th>
                    <th class="px-4 pb-2 text-left hidden lg:table-cell w-[15%]">{{ translations.fiscal || 'Fiscal' }}</th>
                    <th class="px-4 pb-2 text-right w-[15%]">{{ translations.total || 'Total' }}</th>
                    <th class="px-4 pb-2 text-right w-[25%]">{{ translations.actions || 'Actions' }}</th>
                </tr>
            </thead>
            <tbody>
                 <tr
                    v-for="invoice in invoices"
                    :key="invoice.id"
                    :class="selectedIds.has(invoice.id) ? 'bg-purple-50 dark:bg-purple-900/10 border-purple-200 dark:border-purple-800' : 'bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800'"
                    class="border shadow-sm hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-none transition-all duration-300 group"
                >
                    <td class="pl-4 py-6 rounded-l-[2rem]">
                        <button @click="toggleSelectOne(invoice.id)" class="text-slate-300 hover:text-purple-600 transition-colors">
                            <CheckSquare v-if="selectedIds.has(invoice.id)" class="w-4 h-4 text-purple-600" />
                            <Square v-else class="w-4 h-4" />
                        </button>
                    </td>
                    <td class="px-8 py-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl flex items-center justify-center font-black text-indigo-600 dark:text-indigo-400 text-lg">
                                <FileText class="w-5 h-5" v-if="!invoice.advance" />
                                <BadgePercent class="w-5 h-5" v-else />
                            </div>
                            <div>
                                <div class="font-black text-slate-900 dark:text-white text-base flex items-center gap-2">
                                     <span v-if="invoice.advance == 1" class="bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 text-[9px] px-1.5 py-0.5 rounded uppercase tracking-wider">{{ translations.advance || 'Advance' }}</span>
                                     {{ invoice.invoice_number }}
                                </div>
                                <div class="text-xs font-bold text-slate-400 mt-1">{{ invoice.client_name }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-6 hidden md:table-cell">
                        <div class="flex flex-col gap-1">
                         <span
                           class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest inline-flex items-center gap-1.5"
                           :class="{
                               'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400': invoice.status === 'draft',
                               'bg-amber-50 text-amber-600 dark:bg-amber-900/20 dark:text-amber-400': invoice.status === 'unpaid',
                               'bg-orange-50 text-orange-600 dark:bg-orange-900/20 dark:text-orange-400': invoice.status === 'partial',
                               'bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400': invoice.status === 'paid'
                           }"
                         >
                            <span class="w-1.5 h-1.5 rounded-full" :class="{
                               'bg-slate-400': invoice.status === 'draft',
                               'bg-amber-500': invoice.status === 'unpaid',
                               'bg-orange-500': invoice.status === 'partial',
                               'bg-green-500': invoice.status === 'paid'
                            }"></span>
                             {{
                                invoice.status == 'draft' ? (translations.draft || 'Draft') :
                                invoice.status == 'unpaid' ? (translations.unpaid || 'Unpaid') :
                                invoice.status == 'partial' ? (translations.partial || 'Partial') :
                                (translations.paid || 'Paid')
                             }}
                         </span>
                         <span v-if="isOverdue(invoice)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest inline-flex items-center gap-1.5 bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400">
                             <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                             {{ translations.overdue || 'En retard' }}
                         </span>
                        </div>
                    </td>
                    <td class="px-8 py-6 hidden lg:table-cell">
                        <FiscalStatusBadge :status="invoice.fiscal_status" />
                    </td>
                    <td class="px-4 py-6 text-right">
                        <div class="font-black text-slate-900 dark:text-white">{{ invoice.total_amount }} {{ invoice.client_currency }}</div>
                        <div v-if="invoice.status === 'partial' && invoice.paid_amount > 0" class="text-xs font-bold text-amber-500 mt-0.5">
                            {{ translations.remaining || 'Restant' }}: {{ (parseFloat(invoice.total_amount) - parseFloat(invoice.paid_amount)).toFixed(2) }} {{ invoice.client_currency }}
                        </div>
                    </td>
                    <td class="px-4 py-6 rounded-r-[2rem] text-right">
                         <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <router-link :to="{ name: 'InvoiceViewDetail', params: { id: invoice.id } }" class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-slate-800 rounded-xl transition-colors" title="Voir">
                                <Eye class="w-4 h-4" />
                            </router-link>
                            <router-link v-if="canEditOrDelete(invoice)" :to="{ name: 'InvoiceEdit', params: { id: invoice.id } }" class="p-2 text-slate-400 hover:text-amber-500 hover:bg-amber-50 dark:hover:bg-slate-800 rounded-xl transition-colors" title="Editer">
                                <Pencil class="w-4 h-4" />
                            </router-link>
                            
                            <button @click="downloadPDF(invoice)" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-slate-800 rounded-xl transition-colors" title="Télécharger PDF">
                                <FileText class="w-4 h-4" />
                            </button>
                            <button v-if="emailActive" @click="openSendModal(invoice)" class="p-2 text-slate-400 hover:text-green-500 hover:bg-green-50 dark:hover:bg-slate-800 rounded-xl transition-colors" title="Envoyer">
                                <Send class="w-4 h-4" />
                            </button>

                            <!-- Factur-X PDF -->
                            <button @click="downloadFacturX(invoice)" class="p-2 text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-xl transition-colors" title="Télécharger Factur-X PDF">
                                <FileCheck class="w-4 h-4" />
                            </button>

                            <!-- Dropdown -->
                            <div class="relative group/dropdown">
                                <button class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition-colors">
                                    <MoreVertical class="w-4 h-4" />
                                </button>
                                <div class="absolute right-0 top-full mt-2 w-56 bg-white dark:bg-slate-900 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-none py-2 invisible group-hover/dropdown:visible opacity-0 group-hover/dropdown:opacity-100 transition-all transform origin-top-right border border-slate-100 dark:border-slate-800 z-50">
                                    <!-- Valider : disponible si brouillon fiscal ou rejeté -->
                                    <template v-if="canValidate(invoice)">
                                        <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>
                                        <button @click="validateInvoice(invoice.id)" class="w-full text-left px-4 py-2.5 text-xs font-bold text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 flex items-center gap-2">
                                            <CheckCircle class="w-3.5 h-3.5" :class="{'animate-spin': validatingInvoice === invoice.id}"/> {{ translations.validate_fiscally || 'Validate fiscally' }}
                                        </button>
                                    </template>

                                    <!-- Transmettre PDP : disponible si validé -->
                                    <template v-if="invoice.fiscal_status === 'validated'">
                                        <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>
                                        <button @click="transmitToPDP(invoice.id)" class="w-full text-left px-4 py-2.5 text-xs font-bold text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 flex items-center gap-2">
                                            <Upload class="w-3.5 h-3.5" :class="{'animate-spin': transmittingInvoice === invoice.id}"/> {{ translations.transmit_pdp || 'Transmit PDP' }}
                                        </button>
                                    </template>

                                    <!-- Dupliquer -->
                                    <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>
                                    <button @click="duplicateInvoice(invoice.id)" class="w-full text-left px-4 py-2.5 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 flex items-center gap-2">
                                        <Copy class="w-3.5 h-3.5" /> {{ translations.duplicate || 'Dupliquer' }}
                                    </button>

                                    <!-- Sauvegarder comme modèle -->
                                    <button @click="saveAsTemplate(invoice.id)" class="w-full text-left px-4 py-2.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 flex items-center gap-2">
                                        <BookmarkPlus class="w-3.5 h-3.5" /> {{ translations.save_as_template || 'Sauvegarder comme modèle' }}
                                    </button>

                                    <template v-if="canEditOrDelete(invoice)">
                                        <div class="h-px bg-slate-100 dark:bg-slate-800 my-1"></div>
                                        <button @click="confirmDelete(invoice)" class="w-full text-left px-4 py-2.5 text-xs font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                                            <Trash2 class="w-3.5 h-3.5" /> {{ translations.delete || 'Supprimer' }}
                                        </button>
                                    </template>
                                </div>
                            </div>
                         </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex justify-center mt-8">
            <nav class="flex gap-2 bg-white dark:bg-slate-900 p-2 rounded-2xl shadow-lg shadow-slate-200/50 dark:shadow-none border border-slate-100 dark:border-slate-800">
                <button 
                  @click="goToPage(currentPage - 1)" 
                  :disabled="currentPage === 1"
                  class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50 disabled:hover:bg-transparent text-slate-500 transition-colors"
                >
                    <ChevronLeft class="w-5 h-5" />
                </button>
                
                <button 
                   v-for="page in paginationButtons" 
                   :key="page"
                   @click="goToPage(page)"
                   :class="page === currentPage 
                     ? 'bg-purple-600 text-white shadow-lg shadow-purple-500/30' 
                     : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'"
                   class="w-10 h-10 flex items-center justify-center rounded-xl font-black text-sm transition-all"
                   :disabled="page === '...'"
                >
                    {{ page }}
                </button>

                <button 
                  @click="goToPage(currentPage + 1)" 
                  :disabled="currentPage === totalPages"
                  class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 disabled:opacity-50 disabled:hover:bg-transparent text-slate-500 transition-colors"
                >
                    <ChevronRight class="w-5 h-5" />
                </button>
            </nav>
        </div>

      </div>
    </div>
    
    <!-- Bulk action bar -->
    <Transition name="slide-up">
      <div v-if="selectedIds.size > 0" class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-6 py-3.5 rounded-2xl shadow-2xl shadow-slate-900/40 border border-white/10 dark:border-slate-200">
        <span class="font-black text-sm mr-2">{{ selectedIds.size }} {{ translations.selected || 'sélectionné(s)' }}</span>
        <button @click="bulkAction('mark_paid')" :disabled="bulkLoading" class="flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white font-black text-xs px-4 py-2 rounded-xl transition-colors disabled:opacity-50">
          <CheckCheck class="w-3.5 h-3.5" />{{ translations.mark_as_paid || 'Marquer payé' }}
        </button>
        <button @click="bulkAction('delete')" :disabled="bulkLoading" class="flex items-center gap-2 bg-red-500 hover:bg-red-600 text-white font-black text-xs px-4 py-2 rounded-xl transition-colors disabled:opacity-50">
          <Trash2 class="w-3.5 h-3.5" />{{ translations.delete || 'Supprimer' }}
        </button>
        <button @click="clearSelection" class="p-2 rounded-xl hover:bg-white/10 dark:hover:bg-slate-100 transition-colors">
          <X class="w-4 h-4" />
        </button>
      </div>
    </Transition>

    <remove-modal
      modal-id="modal_invoice_remove"
      :show-modal="showRemoveModal"
      :title="translations.are_you_sure"
      :message="translations.no_turning_back"
      @confirm="deleteInvoice(selectedInvoice)"
      @cancel="showRemoveModal = false"
    />

    <!-- Send Modal -->
    <SendInvoiceModal 
       v-if="showSendModal"
       :show-modal="showSendModal"
       modal-id="modal_send_invoice"
       :client="{ email: sendForm.client_email }"
       :invoice-id="sendForm.invoice_id"
       :subject="sendForm.email_subject"
       :content="sendForm.email_message"
       @close="showSendModal = false"
       @success="handleSendSuccess"
       @error="handleSendError"
    />

  </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import MainLayout from '@/components/layout/MainLayout.vue';
import { Plus, Download, Search, RefreshCcw, FileText, FileCheck, Eye, Pencil, MoreVertical, Send, Trash2, ChevronLeft, ChevronRight, BadgePercent, CheckCircle, Upload, X, CheckCircle2, AlertCircle, CheckSquare, Square, CheckCheck, Copy, BookmarkPlus, LayoutTemplate } from 'lucide-vue-next';
import RemoveModal from "@/components/RemoveAlert.vue";
import SendInvoiceModal from "@/components/invoices/Send.vue";
import DateRangePicker from "@/components/DateRangePicker.vue";
import FiscalStatusBadge from "@/components/invoices/FiscalStatusBadge.vue";
import EInvoicingNotices from "@/components/EInvoicingNotices.vue";
import { generatePaginationButtons } from "@/utils/helpers";
import { fetchSettings } from "@/api/api"; 

const router = useRouter();
const route = useRoute();

// State
const invoices = ref([]);
const clients = ref([]);
const loading = ref(true);
const totalCount = ref(0);
const totalPages = ref(1);
const currentPage = ref(1);
const perPage = ref(10);
const paginationButtons = ref([]);
const toast = reactive({ visible: false, message: "", type: "success" });

const filters = reactive({
    invoice_number: "",
    client: "",
    client_id: "",
    status: "",
    fiscal_status: "",
    total_amount: "",
    date_from: "",
    date_to: "",
    overdue: "",
});
const selectedInvoice = ref(null);
const showRemoveModal = ref(false);
const showSendModal = ref(false);
const selectedIds = ref(new Set());
const bulkLoading = ref(false);
const validatingInvoice = ref(null);
const transmittingInvoice = ref(null);
const settings = ref({});
const eInvoicingNotices = reactive({
    untransmittedCount: 0,
    showLegalNotice: false,
    pdpConfigIncomplete: false,
    companyDataIncomplete: false,
});
const sendForm = reactive({
    invoice_id: null,
    client_email: "",
    email_subject: "",
    email_message: ""
});

// Computed
const translations = computed(() => window.myEasyComptaAdmin?.easyComptaTranslations || {});
const exportActive = computed(() => settings.value.easy_compta_export_addon_active == 1);
const emailActive = computed(() => settings.value.easy_compta_email_addon_active == 1);

// Helpers
const showToast = (message, type = "success") => {
    toast.message = message;
    toast.type = type;
    toast.visible = true;
    setTimeout(() => toast.visible = false, 3000);
};

const debouncedFetch = (() => {
    let timer;
    return () => {
        clearTimeout(timer);
        timer = setTimeout(() => fetchInvoices(1), 500);
    }
})();

const activeQuickFilter = ref('');

const quickFilters = computed(() => [
    { value: 'week',    label: translations.value.this_week    || 'Cette semaine' },
    { value: 'month',   label: translations.value.this_month   || 'Ce mois' },
    { value: 'quarter', label: translations.value.this_quarter || 'Ce trimestre' },
    { value: 'year',    label: translations.value.this_year    || 'Cette année' },
]);

const setQuickFilter = (period) => {
    const now = new Date();
    let from, to;
    if (period === 'week') {
        const day = now.getDay();
        const diffToMon = day === 0 ? -6 : 1 - day;
        from = new Date(now); from.setDate(now.getDate() + diffToMon);
        to = new Date(from); to.setDate(from.getDate() + 6);
    } else if (period === 'month') {
        from = new Date(now.getFullYear(), now.getMonth(), 1);
        to = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    } else if (period === 'quarter') {
        const q = Math.floor(now.getMonth() / 3);
        from = new Date(now.getFullYear(), q * 3, 1);
        to = new Date(now.getFullYear(), q * 3 + 3, 0);
    } else if (period === 'year') {
        from = new Date(now.getFullYear(), 0, 1);
        to = new Date(now.getFullYear(), 11, 31);
    }
    const fmt = d => d.toISOString().slice(0, 10);
    filters.date_from = fmt(from);
    filters.date_to = fmt(to);
    activeQuickFilter.value = period;
    fetchInvoices(1);
};

const clearQuickFilter = () => {
    filters.date_from = '';
    filters.date_to = '';
    filters.overdue = '';
    activeQuickFilter.value = '';
    fetchInvoices(1);
};

const toggleOverdueFilter = () => {
    filters.overdue = filters.overdue === '1' ? '' : '1';
    if (filters.overdue === '1') {
        // Clear date filters when switching to overdue
        filters.date_from = '';
        filters.date_to = '';
        activeQuickFilter.value = '';
    }
    fetchInvoices(1);
};

const resetFilters = () => {
    Object.keys(filters).forEach(key => { filters[key] = ""; });
    activeQuickFilter.value = '';
    fetchInvoices(1);
};

const fetchInvoices = async (page = null) => {
    if(page) currentPage.value = page;
    loading.value = true;
    
    const activeFilters = {};
    Object.keys(filters).forEach(key => {
        if (filters[key] !== null && filters[key] !== "") {
            activeFilters[key] = filters[key];
        }
    });

    const query = new URLSearchParams({
        page: currentPage.value,
        per_page: perPage.value,
        ...activeFilters
    });
    
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/invoices?${query.toString()}`, {
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        selectedIds.value = new Set();
        if(data.invoices) {
            invoices.value = data.invoices;
            totalCount.value = parseInt(data.total_count || 0);
            totalPages.value = Math.ceil(totalCount.value / (perPage.value || 10)) || 1;
        } else if (data.message) {
            showToast(data.message, "error");
            invoices.value = [];
            totalCount.value = 0;
            totalPages.value = 1;
        } else {
             invoices.value = Array.isArray(data) ? data : [];
             totalCount.value = invoices.value.length;
             totalPages.value = 1;
        }
        paginationButtons.value = generatePaginationButtons(currentPage.value, totalPages.value);
    } catch(e) { 
        showToast("Erreur lors de la récupération des factures", "error");
    } finally { loading.value = false; }
};

const fetchClients = async () => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/clients?per_page=999`, {
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        clients.value = data.clients || data || [];
    } catch (e) {}
};

const fetchInvoicingSettings = async () => {
     try {
         const res = await fetchSettings();
         if(res && res.settings) settings.value = res.settings;
     } catch (e) {}
};

const checkEInvoicingNotices = async () => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/invoices/einvoicing-notices`, {
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        Object.assign(eInvoicingNotices, data);
    } catch (e) {}
};

const goToPage = (page) => {
    if(page === '...' || page < 1 || page > totalPages.value) return;
    fetchInvoices(page);
};

// Une facture est éditable/supprimable si commercialement en brouillon ET fiscalement non validée
const canEditOrDelete = (invoice) => {
    const fiscalDraft = !invoice.fiscal_status || invoice.fiscal_status === 'draft' || invoice.fiscal_status === 'rejected';
    return invoice.status === 'draft' && fiscalDraft;
};

// On peut valider si le statut fiscal est brouillon ou rejeté
const canValidate = (invoice) => {
    return !invoice.fiscal_status || invoice.fiscal_status === 'draft' || invoice.fiscal_status === 'rejected';
};

const confirmDelete = (invoice) => {
    selectedInvoice.value = invoice.id;
    showRemoveModal.value = true;
};

const deleteInvoice = async (id) => {
    showRemoveModal.value = false;
    try {
        await fetch(`/wp-json/my-easy-compta/v1/invoices/delete/${id}`, {
            method: "DELETE",
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        showToast("Facture supprimée", "success");
        fetchInvoices();
    } catch (e) {}
};

const isOverdue = (invoice) => {
    if (invoice.status === 'paid' || invoice.status === 'draft') return false;
    if (!invoice.due_date_raw) return false;
    return new Date(invoice.due_date_raw) < new Date(new Date().toDateString());
};

const duplicateInvoice = async (id) => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/invoices/${id}/duplicate`, {
            method: "POST",
            headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        if (!res.ok || data.code) {
            showToast(data.message || "Erreur lors de la duplication", "error");
        } else {
            showToast(data.message || "Facture dupliquée", "success");
            fetchInvoices();
        }
    } catch (e) {}
};

const validateInvoice = async (id) => {
    if(validatingInvoice.value) return;
    validatingInvoice.value = id;
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/invoices/${id}/validate`, {
             method: "POST",
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        if (!res.ok || data.code) {
            showToast(data.message || "Erreur lors de la validation", "error");
        } else {
            showToast(data.message || "Facture validée", "success");
            fetchInvoices();
        }
    } catch (e) {} finally { validatingInvoice.value = null; }
};

const transmitToPDP = async (id) => {
    if(transmittingInvoice.value) return;
    transmittingInvoice.value = id;
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/invoices/${id}/transmit`, {
             method: "POST",
             headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }
        });
        const data = await res.json();
        if (!res.ok || data.code) {
            showToast(data.message || "Erreur lors de la transmission", "error");
        } else {
            showToast(data.message || "Transmise au PDP", "success");
            fetchInvoices();
        }
    } catch (e) {} finally { transmittingInvoice.value = null; }
};

const downloadFacturX = (invoice) => {
    window.open(`/wp-json/my-easy-compta/v1/invoices/pdf-facturx/${invoice.id}?_wpnonce=${window.myEasyComptaAdmin.nonce}`, "_blank");
};

const downloadPDF = (invoice) => {
    window.open(`/wp-json/my-easy-compta/v1/invoices/pdf/${invoice.id}?_wpnonce=${window.myEasyComptaAdmin.nonce}`, "_blank");
};

const openSendModal = async (invoice) => {
    sendForm.invoice_id = invoice.id;
    sendForm.client_email = invoice.client_email || ""; // If not in list, might need fetch.
    // Try to fetch invoice details to get latest email if not present
    try {
         const res = await fetch(`/wp-json/my-easy-compta/v1/invoices/${invoice.id}`, { headers: { "X-WP-Nonce": window.myEasyComptaAdmin.nonce }});
         const data = await res.json();
         const clientDetail = data?.data?.client_detail || {};
         sendForm.client_email = clientDetail.email || "";
         const clientName = clientDetail.company_name || "";
         const replacements = {
             '{nom_client}': clientName,
             '{numero_document}': invoice.invoice_number || "",
             '{montant_total}': (invoice.total_amount || "0.00") + " " + (settings.value.currency || "€"),
         };
         const applyReplacements = (str) => Object.keys(replacements).reduce((s, k) => s.split(k).join(replacements[k]), str || "");
         const defaultSubject = `${translations.value.invoice || 'Facture'} #${invoice.invoice_number}`;
         const defaultContent = `${translations.value.hello || 'Bonjour'},\n\n${translations.value.please_find_attached_invoice || 'Veuillez trouver ci-joint'} #${invoice.invoice_number}.\n\n${translations.value.cordially || 'Cordialement'},`;
         sendForm.email_subject = applyReplacements(settings.value.invoice_email_subject || defaultSubject);
         sendForm.email_message = applyReplacements(settings.value.invoice_email_content || defaultContent);
         showSendModal.value = true;
    } catch (e) {}
};

const handleSendSuccess = (msg) => {
    showToast(msg, "success");
};

const handleSendError = (msg) => {
    showToast(msg, "error");
};

const saveAsTemplate = async (id) => {
    try {
        const res = await fetch(`/wp-json/my-easy-compta/v1/invoices/${id}/save-as-template`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "X-WP-Nonce": window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({ is_template: 1 }),
        });
        const data = await res.json();
        if (!res.ok || data.code) {
            showToast(data.message || "Erreur lors de la sauvegarde du modèle", "error");
        } else {
            showToast(translations.value.template_saved || "Modèle sauvegardé", "success");
            fetchInvoices();
        }
    } catch (e) {}
};

const goToExport = () => {
     window.location.href = '/wp-admin/admin.php?page=my-easy-compta-export#tab3';
};

// ── Bulk selection ────────────────────────────────────────────────────
const toggleSelectAll = () => {
    if (selectedIds.value.size === invoices.value.length && invoices.value.length > 0) {
        selectedIds.value = new Set();
    } else {
        selectedIds.value = new Set(invoices.value.map(i => i.id));
    }
};

const toggleSelectOne = (id) => {
    const s = new Set(selectedIds.value);
    s.has(id) ? s.delete(id) : s.add(id);
    selectedIds.value = s;
};

const clearSelection = () => { selectedIds.value = new Set(); };

const bulkAction = async (action) => {
    if (selectedIds.value.size === 0 || bulkLoading.value) return;
    bulkLoading.value = true;
    try {
        const res = await fetch('/wp-json/my-easy-compta/v1/invoices/bulk', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.myEasyComptaAdmin.nonce },
            body: JSON.stringify({ action, ids: Array.from(selectedIds.value) }),
        });
        const data = await res.json();
        showToast(data.message || (action === 'delete' ? 'Supprimé' : 'Marqué payé'), data.success ? 'success' : 'error');
        clearSelection();
        fetchInvoices();
    } catch(e) {
        showToast('Erreur lors de l\'action groupée', 'error');
    } finally {
        bulkLoading.value = false;
    }
};

onMounted(() => {
    if (route.query.client_id) {
        filters.client_id = route.query.client_id;
    }
    fetchInvoices();
    fetchClients();
    fetchInvoicingSettings();
    checkEInvoicingNotices();
});
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.25s ease; }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateX(-50%) translateY(20px); }
</style>