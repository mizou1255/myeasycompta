import { createApp, h } from "vue";
import Invoices from "@/components/Invoices.vue";
import router from "@/router";

const app = createApp({
  render: () => h(Invoices),
});

app.use(router).mount("#my-easy-compta-invoices-app");
