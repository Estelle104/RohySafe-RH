import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  enregistrerCompetenceRequise,
  enregistrerExigenceExperience,
  getExigencesOffre,
  supprimerCompetenceRequise,
  supprimerExigenceExperience,
} from '../services/exigenceService'

function formulaireExperienceVide() {
  return {
    id: null,
    id_poste_experience: '',
    duree_min: 0,
    duree_max: '',
    obligatoire: false,
  }
}

function formulaireCompetenceVide() {
  return {
    id: null,
    id_competence: '',
    note_minimale: '',
    obligatoire: false,
  }
}

export function useExigencesOffre() {
  const route = useRoute()
  const router = useRouter()
  const offre = ref(null)
  const postes = ref([])
  const competences = ref([])
  const chargement = ref(true)
  const erreur = ref('')
  const succes = ref('')
  const enregistrement = ref(false)
  const experienceForm = ref(formulaireExperienceVide())
  const competenceForm = ref(formulaireCompetenceVide())

  const idOffre = computed(() => Number(route.params.id))

  function appliquerOffre(donnees) {
    if (donnees) {
      offre.value = donnees
    }
  }

  function reinitialiserExperience() {
    experienceForm.value = formulaireExperienceVide()
  }

  function reinitialiserCompetence() {
    competenceForm.value = formulaireCompetenceVide()
  }

  async function charger() {
    chargement.value = true
    erreur.value = ''
    succes.value = ''

    try {
      const resultat = await getExigencesOffre(idOffre.value)
      appliquerOffre(resultat.offre)
      postes.value = resultat.postes ?? []
      competences.value = resultat.competences ?? []
    } catch (e) {
      erreur.value = e.message
    } finally {
      chargement.value = false
    }
  }

  function modifierExperience(exigence) {
    experienceForm.value = {
      id: exigence.id,
      id_poste_experience: exigence.id_poste_experience ?? '',
      duree_min: Number(exigence.duree_min ?? 0),
      duree_max:
        exigence.duree_max === null || exigence.duree_max === ''
          ? ''
          : Number(exigence.duree_max),
      obligatoire: exigence.obligatoire === true,
    }
    succes.value = ''
    erreur.value = ''
  }

  function modifierCompetence(critere) {
    competenceForm.value = {
      id: critere.id,
      id_competence: critere.id_competence ?? '',
      note_minimale:
        critere.note_minimale === null || critere.note_minimale === ''
          ? ''
          : Number(critere.note_minimale),
      obligatoire: critere.obligatoire === true,
    }
    succes.value = ''
    erreur.value = ''
  }

  async function enregistrerExperience() {
    erreur.value = ''
    succes.value = ''
    enregistrement.value = true

    try {
      if (!experienceForm.value.id_poste_experience) {
        throw new Error('Sélectionnez le poste concerné par l’expérience.')
      }

      const resultat = await enregistrerExigenceExperience(idOffre.value, {
        id: experienceForm.value.id,
        id_poste_experience: Number(experienceForm.value.id_poste_experience),
        duree_min:
          experienceForm.value.duree_min === ''
            ? 0
            : Number(experienceForm.value.duree_min),
        duree_max:
          experienceForm.value.duree_max === ''
            ? null
            : Number(experienceForm.value.duree_max),
        obligatoire: experienceForm.value.obligatoire,
      })

      appliquerOffre(resultat.offre)
      succes.value = resultat.message
      reinitialiserExperience()
    } catch (e) {
      erreur.value = e.message
    } finally {
      enregistrement.value = false
    }
  }

  async function supprimerExperience(exigence) {
    if (!window.confirm(`Supprimer l’exigence « ${exigence.poste} » ?`)) {
      return
    }

    erreur.value = ''
    succes.value = ''
    enregistrement.value = true

    try {
      const resultat = await supprimerExigenceExperience(idOffre.value, exigence.id)
      appliquerOffre(resultat.offre)
      succes.value = resultat.message

      if (experienceForm.value.id === exigence.id) {
        reinitialiserExperience()
      }
    } catch (e) {
      erreur.value = e.message
    } finally {
      enregistrement.value = false
    }
  }

  async function enregistrerCompetence() {
    erreur.value = ''
    succes.value = ''
    enregistrement.value = true

    try {
      if (!competenceForm.value.id_competence) {
        throw new Error('Sélectionnez la compétence à exiger.')
      }

      const resultat = await enregistrerCompetenceRequise(idOffre.value, {
        id: competenceForm.value.id,
        id_competence: Number(competenceForm.value.id_competence),
        note_minimale:
          competenceForm.value.note_minimale === ''
            ? null
            : Number(competenceForm.value.note_minimale),
        obligatoire: competenceForm.value.obligatoire,
      })

      appliquerOffre(resultat.offre)
      succes.value = resultat.message
      reinitialiserCompetence()
    } catch (e) {
      erreur.value = e.message
    } finally {
      enregistrement.value = false
    }
  }

  async function supprimerCompetence(critere) {
    if (!window.confirm(`Supprimer l’exigence « ${critere.competence} » ?`)) {
      return
    }

    erreur.value = ''
    succes.value = ''
    enregistrement.value = true

    try {
      const resultat = await supprimerCompetenceRequise(idOffre.value, critere.id)
      appliquerOffre(resultat.offre)
      succes.value = resultat.message

      if (competenceForm.value.id === critere.id) {
        reinitialiserCompetence()
      }
    } catch (e) {
      erreur.value = e.message
    } finally {
      enregistrement.value = false
    }
  }

  function retourner() {
    router.push({ name: 'backoffice-offres' })
  }

  onMounted(charger)

  return {
    offre,
    postes,
    competences,
    chargement,
    erreur,
    succes,
    enregistrement,
    experienceForm,
    competenceForm,
    modifierExperience,
    modifierCompetence,
    reinitialiserExperience,
    reinitialiserCompetence,
    enregistrerExperience,
    supprimerExperience,
    enregistrerCompetence,
    supprimerCompetence,
    retourner,
    charger,
  }
}
