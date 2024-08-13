import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "path";
import obfuscator from "rollup-plugin-obfuscator";

export default defineConfig({
  plugins: [
    vue()
  ],
  css: {
    postcss: "./postcss.config.js",
  },
  build: {
    rollupOptions: {
      input: {
        app: path.resolve(__dirname, "src/js/app.js"),
        clients: path.resolve(__dirname, "src/apps/clients.js"),
        quotes: path.resolve(__dirname, "src/apps/quotes.js"),
        invoices: path.resolve(__dirname, "src/apps/invoices.js"),
        payments: path.resolve(__dirname, "src/apps/payments.js"),
        expenses: path.resolve(__dirname, "src/apps/expenses.js"),
        settings: path.resolve(__dirname, "src/apps/settings.js"),
        style: path.resolve(__dirname, "src/css/style.css"),
        setup: path.resolve(__dirname, "src/css/setup.css"),
      },
      output: {
        entryFileNames: "[name].min.js",
        chunkFileNames: "[name].min.js",
        assetFileNames: (assetInfo) => {
          if (assetInfo.name.endsWith(".css")) {
            return "[name].min.css";
          }
          return "[name][extname]";
        },
        dir: "assets/dist",
        format: "es",
      },
      plugins: [
        obfuscator({
          compact: true,
          controlFlowFlattening: true,
          deadCodeInjection: true,
          debugProtection: false,
          debugProtectionInterval: false,
          disableConsoleOutput: true,
          identifierNamesGenerator: "hexadecimal",
          log: false,
          renameGlobals: false,
          selfDefending: true,
          stringArray: true,
          stringArrayEncoding: ["rc4"],
          stringArrayThreshold: 0.75,
          unicodeEscapeSequence: false,
        })
      ],
    },
    assetsDir: "",
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
});
