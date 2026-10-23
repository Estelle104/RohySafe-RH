import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import LoginCandidatsView from '../views/LoginCandidatsView.vue'
import { estCandidatConnecte } from '../services/candidatAuth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: LoginView },
    { path: '/candidats/login', name: 'candidat-login', component: LoginCandidatsView },
    {
      path: '/candidats/inscription',
      name: 'candidat-inscription',
      component: () => import('../views/InscriptionCandidatView.vue'),
    },

    { path: '/offres', name: 'offres', component: () => import('../views/OffresView.vue') },
    {
      path: '/offres/:id/postuler',
      name: 'postuler',
      meta: { requiresCandidat: true },
      component: () => import('../views/CvCandidatureView.vue')
    },
    {
      path: '/offres/:id/candidats',
      name: 'candidats-ideaux',
      component: () => import('../views/CandidatsIdeauxView.vue')
    },
    {
      path: '/backoffice/offres',
      name: 'backoffice-offres',
      component: () => import('../views/BackofficeOffresView.vue')
    },
    {
      path: '/backoffice/offres/:id/exigences',
      name: 'exigences-offre',
      component: () => import('../views/ExigencesOffreView.vue')
    },
    {
      path: '/demandes/:id/candidats',
      name: 'candidats-ideaux-demande',
      component: () => import('../views/CandidatsIdeauxView.vue')
    }
    ,{ path: '/finance', name: 'finance', component: () => import('../views/FinanceView.vue') }
    ,{ path: '/direction-generale', name: 'direction-generale', component: () => import('../views/DirectionGeneraleView.vue') }

  ],
})

router.beforeEach((to) => {
  if (to.meta.requiresCandidat && !estCandidatConnecte()) {
    return {
      name: 'candidat-login',
      query: { redirect: to.fullPath },
    }
  }
})

export default router
