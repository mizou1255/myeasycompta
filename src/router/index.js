import { createRouter, createWebHashHistory } from "vue-router";
import Quotes from "@/components/quotes/List.vue";
import QuoteViewDetail from "@/components/quotes/ViewDetail.vue";
import QuoteNew from "@/components/quotes/New.vue";
import QuoteEdit from "@/components/quotes/Edit.vue";
import Invoices from "@/components/invoices/List.vue";
import InvoiceViewDetail from "@/components/invoices/ViewDetail.vue";
import InvoiceNew from "@/components/invoices/New.vue";
import InvoiceEdit from "@/components/invoices/Edit.vue";

const routes = [
  {
    path: "/quotes",
    name: "Quote",
    component: Quotes,
  },
  {
    path: "/quote/detail/:id",
    name: "QuoteViewDetail",
    component: QuoteViewDetail,
  },
  {
    path: "/quote/new",
    name: "QuoteNew",
    component: QuoteNew,
  },
  {
    path: "/quote/edit/:id",
    name: "QuoteEdit",
    component: QuoteEdit,
  },
  {
    path: "/invoices",
    name: "Invoice",
    component: Invoices,
  },
  {
    path: "/invoice/detail/:id",
    name: "InvoiceViewDetail",
    component: InvoiceViewDetail,
  },
  {
    path: "/invoice/edit/:id",
    name: "InvoiceEdit",
    component: InvoiceEdit,
  },
  {
    path: "/invoice/new",
    name: "InvoiceNew",
    component: InvoiceNew,
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
