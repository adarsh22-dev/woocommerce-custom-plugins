import path from "path";

import tailwindcss from "@tailwindcss/vite";
import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  publicDir: false,
  build: {
    outDir: "build",
    rollupOptions: {
      input: "src/main.tsx",
      external: [
        "react",
        "react-dom",
        "@wordpress/dom-ready",
        "@wordpress/element",
        "@wordpress/i18n",
      ],
      output: {
        format: "iife",
        entryFileNames: `index.js`,
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith(".css")) {
            return "index.css";
          }
          return assetInfo.name ?? "unknown.asset";
        },
        globals: {
          react: "React",
          "react-dom": "ReactDOM",
          "@wordpress/dom-ready": "wp.domReady",
          "@wordpress/element": "wp.element",
          "@wordpress/i18n": "wp.i18n",
        },
      },
    },
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
});
