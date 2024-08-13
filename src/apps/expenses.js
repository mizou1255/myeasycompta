import { createApp, h } from "vue";
import Expenses from "@/components/Expenses.vue";

const app = createApp({
  render: () => h(Expenses),
});

app.mount("#my-easy-compta-expenses-app");
