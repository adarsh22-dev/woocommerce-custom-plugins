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
    emptyOutDir: false,
    rollupOptions: {
      input: "src/block/index.ts",
      external: [
        "react",
        "react-dom",
        "@wordpress/element",
        "@wordpress/i18n",
        "@wordpress/blocks",
        "@wordpress/block-editor",
        "@wordpress/components",
      ],
      output: {
        format: "iife",
        entryFileNames: `block.js`,
        assetFileNames: `block.css`,
        globals: {
          react: "React",
          "react-dom": "ReactDOM",
          "@wordpress/element": "wp.element",
          "@wordpress/i18n": "wp.i18n",
          "@wordpress/blocks": "wp.blocks",
          "@wordpress/block-editor": "wp.blockEditor",
          "@wordpress/components": "wp.components",
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
