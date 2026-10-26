import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import LoginCandidatsView from '../views/LoginCandidatsView.vue'
import { estCandidatConnecte } from '../services/candidatAuth'
import { chargerUtilisateurInterne, peutAccederAuxEntretiens } from '../services/authInterne'
import NotificationsPage from '../components/NotificationsPage.vue'
import DemandeDetailView from '../views/DemandeDetailView.vue'
import ConfirmationDemandeView from '../views/ConfirmationDemandeView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', name: 'login', component: LoginView },
    { path: '/candidats/login', name: 'candidat-login', component: LoginCandidatsView },
    {
      path: '/candidats/inscription',
      name: 'candidat-inscription',
      component: () => import('../views/InscriptionCandidatView.vue'),
    },
    { path: '/candidats/login', component: LoginCandidatsView },
    { path: '/offres', name: 'offres', component: () => import('../views/OffresView.vue') },
    { path: '/demandes/nouvelle', name: 'demande-create', meta: { requiresAuth: true, roles: ['Manager'] }, component: () => import('../views/demande/NouvelleDemandeView.vue') },

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
      path: '/backoffice',
      name: 'backoffice',
      component: () => import('../views/BackofficeAccueilView.vue')
    },
    {
      path: '/backoffice/candidats-ideaux',
      name: 'backoffice-candidats-ideaux',
      component: () => import('../views/CandidatsOffresView.vue')
    },
    {
      path: '/backoffice/candidats-ideaux/:id',
      name: 'backoffice-candidats-ideaux-offre',
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
    ,{ path: '/finance', name: 'finance', meta: { requiresAuth: true, roles: ['Finance'] }, component: () => import('../views/FinanceView.vue') }
    ,{ path: '/direction-generale', name: 'direction-generale', meta: { requiresAuth: true, roles: ['DG'] }, component: () => import('../views/DirectionGeneraleView.vue') },

    { path: '/notification', meta: { requiresAuth: true, roles: ['RH'] }, component: NotificationsPage },
    { path: '/demandes/:id', name: 'demande-detail', meta: { requiresAuth: true, roles: ['RH'] }, component: DemandeDetailView },
    { path: '/demandes/:id/confirmation/:action', name: 'demande-confirmation', component: ConfirmationDemandeView },
    { path: '/candidats/login', component: LoginCandidatsView },
    { path: '/offres', name: 'offres', component: () => import('../views/OffresView.vue') }
    , { path: '/offres/a-publier', name: 'offres-a-publier', component: () => import('../views/DemandesAPublierView.vue') }
    , { path: '/offres/nouvelle/:id', name: 'offre-formulaire', component: () => import('../views/OffreFormView.vue') }
    , { path: '/offres/publiee/:id', name: 'offre-publiee', component: () => import('../views/OffrePublieeView.vue') }
    , { path: '/candidatures', name: 'candidatures', component: () => import('../views/CandidaturesView.vue') }
    , { path: '/candidatures/:id/entretien', name: 'entretien-formulaire', component: () => import('../views/EntretienFormView.vue') }
    , { path: '/candidatures/:id/entretien/confirmation', name: 'entretien-confirme', component: () => import('../views/EntretienConfirmationView.vue') }
    , { path: '/candidatures/:id/contrat', name: 'contrat-fiche', component: () => import('../views/ContratFicheView.vue') }
    , {
      path: '/entretiens',
      name: 'entretiens-offres',
      meta: { rolesEntretiens: true },
      component: () => import('../views/EntretiensOffresView.vue')
    }
    , {
      path: '/entretiens/offres/:id',
      name: 'entretiens-candidats',
      meta: { rolesEntretiens: true },
      component: () => import('../views/EntretiensCandidatsView.vue')
    }
    , {
      path: '/entretiens/candidatures/:id',
      name: 'entretien-evaluation',
      meta: { rolesEntretiens: true },
      component: () => import('../views/EntretienEvaluationView.vue')
    }
    ,{ path: '/offres/a-publier', name: 'offres-a-publier', meta: { requiresAuth: true, roles: ['RH'] }, component: () => import('../views/DemandesAPublierView.vue') }
    ,{ path: '/offres/nouvelle/:id', name: 'offre-formulaire', meta: { requiresAuth: true, roles: ['RH'] }, component: () => import('../views/OffreFormView.vue') }
    ,{ path: '/offres/publiee/:id', name: 'offre-publiee', component: () => import('../views/OffrePublieeView.vue') }
    ,{ path: '/candidatures', name: 'candidatures', component: () => import('../views/CandidaturesView.vue') }
    ,{ path: '/candidatures/:id/entretien', name: 'entretien-formulaire', component: () => import('../views/EntretienFormView.vue') }
    ,{ path: '/candidatures/:id/entretien/confirmation', name: 'entretien-confirme', component: () => import('../views/EntretienConfirmationView.vue') }
    ,{ path: '/candidatures/:id/contrat', name: 'contrat-fiche', component: () => import('../views/ContratFicheView.vue') }
  ],
})

router.beforeEach(async (to) => {
  if (to.meta.requiresCandidat && !estCandidatConnecte()) {
    return {
      name: 'candidat-login',
      query: { redirect: to.fullPath },
    }
  }

  if (!to.meta.requiresAuth) return true

  try {
    const response = await fetch('/api/auth/me', { credentials: 'include' })
    if (!response.ok) throw new Error()
    const { utilisateur } = await response.json()
    if (!utilisateur) throw new Error()
    if (to.meta.roles && !to.meta.roles.includes(utilisateur.role)) {
      return '/login'
    }
    return true
  } catch {
    return { path: '/login', query: { redirect: to.fullPath } }
  }
})

export default router
//     { path: '/notification', component: NotificationsPage },
//     { path: '/demandes/:id', name: 'demande-detail', component: DemandeDetailView },
//     { path: '/demandes/:id/confirmation/:action', name: 'demande-confirmation', component: ConfirmationDemandeView },
//     { path: '/candidats/login', component: LoginCandidatsView },
//     { path: '/offres', name: 'offres', component: () => import('../views/OffresView.vue') }
//     ,{ path: '/offres/a-publier', name: 'offres-a-publier', component: () => import('../views/DemandesAPublierView.vue') }
//     ,{ path: '/offres/nouvelle/:id', name: 'offre-formulaire', component: () => import('../views/OffreFormView.vue') }
//     ,{ path: '/offres/publiee/:id', name: 'offre-publiee', component: () => import('../views/OffrePublieeView.vue') }
//     ,{ path: '/candidatures', name: 'candidatures', component: () => import('../views/CandidaturesView.vue') }
//     ,{ path: '/candidatures/:id/entretien', name: 'entretien-formulaire', component: () => import('../views/EntretienFormView.vue') }
//     ,{ path: '/candidatures/:id/entretien/confirmation', name: 'entretien-confirme', component: () => import('../views/EntretienConfirmationView.vue') }
//     ,{ path: '/candidatures/:id/contrat', name: 'contrat-fiche', component: () => import('../views/ContratFicheView.vue') }
//   ],
// })
