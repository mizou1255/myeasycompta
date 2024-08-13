import { createApp, h } from "vue";
import Payments from "@/components/Payments.vue";

const app = createApp({
  render: () => h(Payments),
});
app.mount("#my-easy-compta-payments-app");
