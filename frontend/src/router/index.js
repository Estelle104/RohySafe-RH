import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import LoginCandidatsView from '../views/LoginCandidatsView.vue'

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: LoginView },
    { path: '/candidats/login', component: LoginCandidatsView },
    { path: '/offres', name: 'offres', component: () => import('../views/OffresView.vue') },
    { path: '/demandes/nouvelle', name: 'demande-create', component: () => import('../views/DemandeCreateView.vue') }
  ],
})
