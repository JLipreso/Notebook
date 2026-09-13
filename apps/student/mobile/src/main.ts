import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { router } from './router'
// Brand typefaces (D-032 identity, confirmed from the design canvases) —
// bundled via @fontsource so they load offline, never from a CDN.
import '@fontsource-variable/fraunces'
import '@fontsource/figtree/400.css'
import '@fontsource/figtree/500.css'
import '@fontsource/figtree/600.css'
import '@fontsource/figtree/700.css'
import './style.css'

createApp(App).use(createPinia()).use(router).mount('#app')
