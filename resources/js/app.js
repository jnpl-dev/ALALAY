import { createApp, h } from 'vue'
import { createInertiaApp, Link, Head } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Aura from '@primeuix/themes/aura'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import StyleClass from 'primevue/styleclass'
import 'primeicons/primeicons.css'
import '../css/app.css'
import './layout/scss/styles.scss'

createInertiaApp({
  title: (title) => (title ? `${title} — ALALAY` : 'ALALAY'),
  resolve: (name) =>
    resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    const pinia = createPinia()

    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(pinia)
      .use(PrimeVue, {
        theme: {
          preset: Aura,
          options: {
            darkModeSelector: '.app-dark',
          },
        },
      })
      .use(ToastService)
      .use(ConfirmationService)
      .directive('styleclass', StyleClass)
      .component('Link', Link)
      .component('Head', Head)
      .mount(el)
  },
})
