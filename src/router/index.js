import { createRouter, createWebHashHistory } from 'vue-router';
import Dashboard from '@/components/Dashboard.vue';
import Clients from '@/components/Clients.vue';
import ClientAdd from '@/components/clients/Add.vue';
import ClientEdit from '@/components/clients/Edit.vue';
import ClientView from '@/components/clients/View.vue';
import QuoteList from '@/components/quotes/List.vue';
import QuoteNew from '@/components/quotes/New.vue';
import QuoteEdit from '@/components/quotes/Edit.vue';
import QuoteViewDetail from '@/components/quotes/ViewDetail.vue';
import QuoteSend from '@/components/quotes/Send.vue';
import InvoiceList from '@/components/invoices/List.vue';
import InvoiceNew from '@/components/invoices/New.vue';
import InvoiceEdit from '@/components/invoices/Edit.vue';
import InvoiceViewDetail from '@/components/invoices/ViewDetail.vue';
import Payments from '@/components/Payments.vue';
import PaymentEdit from '@/components/payments/Edit.vue';
import Credits from '@/components/Credits.vue';
import Expenses from '@/components/Expenses.vue';
import ExpenseAdd from '@/components/expenses/Add.vue';
import ExpenseEdit from '@/components/expenses/Edit.vue';
import Settings from '@/components/Settings.vue';
import Contracts from '@/components/Contracts.vue';
import TimeTracking from '@/components/TimeTracking.vue';
import Delivery from '@/components/Delivery.vue';
import AddonProPage from '@/components/AddonProPage.vue';

const routes = [
  {
    path: '/',
    name: 'Dashboard',
    component: Dashboard,
    meta: { title: 'Tableau de bord' }
  },

  // Clients
  {
    path: '/clients',
    name: 'Clients',
    component: Clients,
    meta: { title: 'Clients' }
  },
  {
    path: '/clients/add',
    name: 'ClientAdd',
    component: ClientAdd,
    meta: { title: 'Nouveau client' }
  },
  {
    path: '/clients/edit/:id',
    name: 'ClientEdit',
    component: ClientEdit,
    meta: { title: 'Modifier client' }
  },
  {
    path: '/clients/view/:id',
    name: 'ClientView',
    component: ClientView,
    meta: { title: 'Détails client' }
  },

  // Quotes
  {
    path: '/quotes',
    name: 'Quotes',
    component: QuoteList,
    meta: { title: 'Devis' }
  },
  {
    path: '/quotes/new',
    name: 'QuoteNew',
    component: QuoteNew,
    meta: { title: 'Nouveau devis' }
  },
  {
    path: '/quotes/edit/:id',
    name: 'QuoteEdit',
    component: QuoteEdit,
    meta: { title: 'Modifier devis' }
  },
  {
    path: '/quotes/detail/:id',
    name: 'QuoteViewDetail',
    component: QuoteViewDetail,
    meta: { title: 'Détails devis' }
  },
  {
    path: '/quotes/send/:id',
    name: 'QuoteSend',
    component: QuoteSend,
    meta: { title: 'Envoyer devis' }
  },

  // Invoices
  {
    path: '/invoices',
    name: 'Invoices',
    component: InvoiceList,
    meta: { title: 'Factures' }
  },
  {
    path: '/invoices/new',
    name: 'InvoiceNew',
    component: InvoiceNew,
    meta: { title: 'Nouvelle facture' }
  },
  {
    path: '/invoices/edit/:id',
    name: 'InvoiceEdit',
    component: InvoiceEdit,
    meta: { title: 'Modifier facture' }
  },
  {
    path: '/invoices/detail/:id',
    name: 'InvoiceViewDetail',
    component: InvoiceViewDetail,
    meta: { title: 'Détails facture' }
  },

  // Payments
  {
    path: '/payments',
    name: 'Payments',
    component: Payments,
    meta: { title: 'Paiements' }
  },
  {
    path: '/payments/edit/:id',
    name: 'PaymentEdit',
    component: PaymentEdit,
    meta: { title: 'Modifier paiement' }
  },

  // Credits
  {
    path: '/credits',
    name: 'Credits',
    component: Credits,
    meta: { title: 'Avoirs' }
  },

  // Expenses
  {
    path: '/expenses',
    name: 'Expenses',
    component: Expenses,
    meta: { title: 'Dépenses' }
  },
  {
    path: '/expenses/add',
    name: 'ExpenseAdd',
    component: ExpenseAdd,
    meta: { title: 'Nouvelle dépense' }
  },
  {
    path: '/expenses/edit/:id',
    name: 'ExpenseEdit',
    component: ExpenseEdit,
    meta: { title: 'Modifier dépense' }
  },

  // Settings
  {
    path: '/settings',
    name: 'Settings',
    component: Settings,
    meta: { title: 'Réglages' }
  },

  // Contracts (addon)
  {
    path: '/contracts',
    name: 'Contracts',
    component: Contracts,
    meta: { title: 'Contrats' }
  },

  // TimeTracking (addon)
  {
    path: '/timetracking',
    name: 'TimeTracking',
    component: TimeTracking,
    meta: { title: 'Temps & Facturation' }
  },

  // Delivery (addon)
  {
    path: '/delivery',
    name: 'Delivery',
    component: Delivery,
    meta: { title: 'Bons de livraison' }
  },

  // Addon upsell page
  {
    path: '/addon/:slug',
    name: 'AddonPro',
    component: AddonProPage,
    meta: { title: 'Module PRO' }
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  if (to.meta.title) {
    document.title = `${to.meta.title} - myEasyCompta`;
  }
  next();
});

export default router;
