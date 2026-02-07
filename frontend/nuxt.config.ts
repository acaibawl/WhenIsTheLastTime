// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  modules: ['@nuxt/eslint', '@nuxt/ui', '@pinia/nuxt'],

  runtimeConfig: {
    public: {
      apiBaseUrl: '',
      baseUrl: '',
    },
  },

  devtools: {
    enabled: true,
  },

  css: [
    'modern-css-reset',
    '~/assets/css/main.css',
  ],

  compatibilityDate: '2025-01-15',

  vite: {
    server: {
      allowedHosts: ['frontend.local'],
    },
  },

  nitro: {
    preset: 'aws-lambda',
    serveStatic: true,
    publicAssets: [
      {
        baseURL: '/',
        dir: 'public',
        maxAge: 31536000,
      },
    ],
  },
});
