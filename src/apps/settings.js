import { createApp, h } from "vue";
import Settings from "@/components/Settings.vue";
import ColorInput from "vue-color-input";

const app = createApp({
  render: () => h(Settings),
});
app.use(ColorInput);
app.mount("#my-easy-compta-settings-app");
