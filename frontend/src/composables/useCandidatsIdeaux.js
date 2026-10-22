import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  creerProposition,
  getCandidatsIdeauxByDemande,
  getCandidatsIdeauxByOffre,
  getCvCandidat,
} from '../services/candidatService'

export function useCandidatsIdeaux() {
  const route = useRoute()
  const router = useRouter()
  const candidats = ref([])
  const chargement = ref(true)
  const erreur = ref('')
  const recherche = ref('')
  const cvCandidat = ref(null)
  const cvDetails = ref(null)
  const cvDocuments = ref([])
  const erreurAction = ref('')
  const propositionEnCours = ref(null)

  async function chargerCandidats() {
    chargement.value = true
    erreur.value = ''

    try {
      candidats.value = route.name === 'candidats-ideaux-demande'
        ? await getCandidatsIdeauxByDemande(route.params.id)
        : await getCandidatsIdeauxByOffre(route.params.id)
    } catch (e) {
      erreur.value = e.message
    } finally {
      chargement.value = false
    }
  }

  async function voirCv(candidat) {
    erreurAction.value = ''

    try {
      const resultat = await getCvCandidat(candidat.id_candidat)
      cvDetails.value = resultat.cv
      cvDocuments.value = resultat.cv?.documents ?? []
      cvCandidat.value = candidat
    } catch (e) {
      erreurAction.value = e.message
    }
  }

  function fermerCv() {
    cvCandidat.value = null
    cvDetails.value = null
    cvDocuments.value = []
  }
  function retourner() {
    if (window.history.length > 1) {
      router.back()
      return
    }

    router.push({ name: 'offres' })
  }

  async function faireProposition(candidat) {
    if (!candidat.id_candidature) {
      erreurAction.value = 'Ce candidat n’a pas de candidature pour cette offre.'
      return
    }

    erreurAction.value = ''
    propositionEnCours.value = candidat.id_candidat

    try {
      await creerProposition(candidat.id_candidature)
      candidat.proposition_creee = true
    } catch (e) {
      erreurAction.value = e.message
    } finally {
      propositionEnCours.value = null
    }
  }

  const candidatsFiltres = computed(() => {
    const rechercheNormalisee = recherche.value.trim().toLowerCase()

    if (!rechercheNormalisee) {
      return candidats.value
    }

    return candidats.value.filter((candidat) =>
      [candidat.nom, candidat.prenom, candidat.email, candidat.ville]
        .filter(Boolean)
        .some((valeur) => valeur.toLowerCase().includes(rechercheNormalisee))
    )
  })

  onMounted(chargerCandidats)

  return {
    candidatsFiltres,
    chargement,
    erreur,
    recherche,
    cvCandidat,
    cvDetails,
    cvDocuments,
    erreurAction,
    propositionEnCours,
    voirCv,
    fermerCv,
    faireProposition,
    retourner,
  }
}
