import { defineConfig } from "vite";

import react from "@vitejs/plugin-react";

export default defineConfig({
  plugins: [react()],
  publicDir: false,
  build: {
    outDir: "public/assets/build",
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: "front/react/main.tsx",
      },
    },
  },
});
