import { createApp, h } from "vue";
import Clients from "@/components/Clients.vue";
import router from "@/router";

const app = createApp({
  render: () => h(Clients),
});
app.use(router).mount("#my-easy-compta-clients-app");
