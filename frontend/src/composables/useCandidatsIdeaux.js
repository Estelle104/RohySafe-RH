import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  getCandidatsIdeauxByDemande,
  getCandidatsIdeauxByOffre,
  getCvCandidat,
} from '../services/candidatService'
import { creerEntretien } from '../services/entretienService'

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
  const entretienCandidat = ref(null)
  const entretienEnCours = ref(false)

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

  function ouvrirEntretien(candidat) {
    erreurAction.value = ''
    messageAction.value = ''

    if (!candidat.id_candidature) {
      erreurAction.value = 'Ce candidat n’a pas de candidature pour cette offre.'
      return
    }

    if (candidat.entretien_cree) {
      erreurAction.value = 'Un entretien est déjà planifié pour cette candidature.'
      return
    }

    entretienCandidat.value = candidat
  }

  function fermerEntretien() {
    if (entretienEnCours.value) {
      return
    }

    entretienCandidat.value = null
    erreurAction.value = ''
  }

  async function planifierEntretien(donnees) {
    if (!entretienCandidat.value) {
      return
    }

    erreurAction.value = ''
    entretienEnCours.value = true
    const candidat = entretienCandidat.value

    try {
      await creerEntretien(candidat.id_candidature, donnees)
      candidat.entretien_cree = true
      candidat.date_entretien = donnees.date_entretien
      candidat.lieu_entretien = donnees.lieu_entretien
      candidat.statut_entretien = 'planifie'
      entretienCandidat.value = null
      messageAction.value = `L’entretien de ${candidat.prenom} ${candidat.nom} a été planifié.`
    } catch (e) {
      erreurAction.value = e.message
    } finally {
      entretienEnCours.value = false
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
    entretienCandidat,
    entretienEnCours,
    voirCv,
    fermerCv,
    ouvrirEntretien,
    fermerEntretien,
    planifierEntretien,
    retourner,
    gererExigences,
    peutGererExigences,
  }
}
