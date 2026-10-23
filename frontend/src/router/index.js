import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import LoginCandidatsView from '../views/LoginCandidatsView.vue'
import NotificationsPage from '../components/NotificationsPage.vue'
import DemandeDetailView from '../views/DemandeDetailView.vue'
import ConfirmationDemandeView from '../views/ConfirmationDemandeView.vue'

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: LoginView },
    { path: '/notification', component: NotificationsPage },
    { path: '/demandes/:id', name: 'demande-detail', component: DemandeDetailView },
    { path: '/demandes/:id/confirmation/:action', name: 'demande-confirmation', component: ConfirmationDemandeView },
    { path: '/candidats/login', component: LoginCandidatsView },
    { path: '/offres', name: 'offres', component: () => import('../views/OffresView.vue') }
    ,{ path: '/offres/a-publier', name: 'offres-a-publier', component: () => import('../views/DemandesAPublierView.vue') }
    ,{ path: '/offres/nouvelle/:id', name: 'offre-formulaire', component: () => import('../views/OffreFormView.vue') }
    ,{ path: '/offres/publiee/:id', name: 'offre-publiee', component: () => import('../views/OffrePublieeView.vue') }
    ,{ path: '/candidatures', name: 'candidatures', component: () => import('../views/CandidaturesView.vue') }
    ,{ path: '/candidatures/:id/entretien', name: 'entretien-formulaire', component: () => import('../views/EntretienFormView.vue') }
    ,{ path: '/candidatures/:id/entretien/confirmation', name: 'entretien-confirme', component: () => import('../views/EntretienConfirmationView.vue') }
    ,{ path: '/candidatures/:id/contrat', name: 'contrat-fiche', component: () => import('../views/ContratFicheView.vue') }
  ],
})
