import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import path from "path";
import obfuscator from "rollup-plugin-obfuscator";

export default defineConfig({
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
    'process.env': '({})',
    'process.versions': '({})',
  },
  plugins: [vue()],
  css: {
    postcss: "./postcss.config.js",
  },
  build: {
    chunkSizeWarningLimit: 600,
    rollupOptions: {
      input: {
        app: path.resolve(__dirname, "src/js/app.js"),
        clients: path.resolve(__dirname, "src/apps/clients.js"),
        quotes: path.resolve(__dirname, "src/apps/quotes.js"),
        invoices: path.resolve(__dirname, "src/apps/invoices.js"),
        credits: path.resolve(__dirname, "src/apps/credits.js"),
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
        manualChunks(id) {
          if (id.includes("node_modules/vue/") || id.includes("node_modules/@vue/")) {
            return "vendor-vue";
          }
          if (id.includes("node_modules/vue-router/")) {
            return "vendor-router";
          }
          if (id.includes("node_modules/lucide-vue-next/")) {
            return "vendor-icons";
          }
          if (id.includes("node_modules/axios/")) {
            return "vendor-axios";
          }
          if (id.includes("node_modules/@vuepic/")) {
            return "vendor-datepicker";
          }
        },
      },
      plugins: [
        obfuscator({
          compact: true,
          controlFlowFlattening: false,
          deadCodeInjection: false,
          debugProtection: false,
          debugProtectionInterval: false,
          disableConsoleOutput: false,
          identifierNamesGenerator: "hexadecimal",
          log: false,
          renameGlobals: false,
          selfDefending: false,
          // stringArray disabled: obfuscating dynamic import paths breaks
          // ES module resolution at runtime (browser can't resolve encoded chunk paths)
          stringArray: false,
          unicodeEscapeSequence: false,
        }),
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
