import { createApp, h } from "vue";
import Credits from "@/components/Credits.vue";

const app = createApp({
  render: () => h(Credits),
});

app.mount("#my-easy-compta-credits-app");
