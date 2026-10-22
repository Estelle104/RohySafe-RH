import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import LoginCandidatsView from '../views/LoginCandidatsView.vue'

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: LoginView },
    { path: '/candidats/login', component: LoginCandidatsView },
    { path: '/offres', name: 'offres', component: () => import('../views/OffresView.vue') }
    ,{ path: '/finance', name: 'finance', component: () => import('../views/FinanceView.vue') }
    ,{ path: '/direction-generale', name: 'direction-generale', component: () => import('../views/DirectionGeneraleView.vue') }
  ],
})
