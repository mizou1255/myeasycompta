import { createApp, h } from "vue";
import Quotes from "@/components/Quotes.vue";
import router from "@/router";

const app = createApp({
  render: () => h(Quotes),
});
app.use(router).mount("#my-easy-compta-quotes-app");
