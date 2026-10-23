import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  getCandidatsIdeauxByDemande,
  getCandidatsIdeauxByOffre,
  getCvCandidat,
  preselectionnerCandidature,
} from '../services/candidatService'

export function useCandidatsIdeaux() {
  const route = useRoute()
  const router = useRouter()
  const estContexteOffre = computed(() =>
    ['candidats-ideaux', 'backoffice-candidats-ideaux-offre'].includes(route.name)
  )
  const candidats = ref([])
  const chargement = ref(true)
  const erreur = ref('')
  const recherche = ref('')
  const cvCandidat = ref(null)
  const cvDetails = ref(null)
  const cvDocuments = ref([])
  const erreurAction = ref('')
  const messageAction = ref('')
  const candidatEnCours = ref(null)

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
      const idOffre = estContexteOffre.value ? route.params.id : null
      const resultat = await getCvCandidat(candidat.id_candidat, idOffre)
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
    if (route.name === 'backoffice-candidats-ideaux-offre') {
      router.push({ name: 'backoffice-candidats-ideaux' })
      return
    }

    if (window.history.length > 1) {
      router.back()
      return
    }

    router.push({ name: 'offres' })
  }

  function gererExigences() {
    if (!estContexteOffre.value) {
      return
    }

    router.push({ name: 'exigences-offre', params: { id: route.params.id } })
  }

  async function preselectionnerCandidat(candidat) {
    erreurAction.value = ''
    messageAction.value = ''

    if (!candidat.id_candidature) {
      erreurAction.value = 'Ce candidat n’a pas de candidature pour cette offre.'
      return
    }

    candidatEnCours.value = candidat.id_candidature

    try {
      await preselectionnerCandidature(candidat.id_candidature)
      candidat.preselectionne = true
      messageAction.value = `${candidat.prenom} ${candidat.nom} a été présélectionné(e).`
    } catch (e) {
      erreurAction.value = e.message
    } finally {
      candidatEnCours.value = null
    }
  }

  const peutGererExigences = estContexteOffre

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
    messageAction,
    candidatEnCours,
    voirCv,
    fermerCv,
    preselectionnerCandidat,
    retourner,
    gererExigences,
    peutGererExigences,
  }
}
