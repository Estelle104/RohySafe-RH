import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import './assets/style.css'

createApp(App)
  .use(createPinia())   // le gestionnaire d'état partagé
  .use(router)
  .mount('#app')
