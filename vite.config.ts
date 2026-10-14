import { defineConfig } from "vite";

export default defineConfig({
  publicDir: false,
  build: {
    outDir: "public/assets/build",
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: "front/typescript/main.ts",
        admin: "front/typescript/admin.ts",
      },
    },
  },
});
