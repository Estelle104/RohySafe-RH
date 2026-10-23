import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { enregistrerCvCandidature, getCvCandidature } from '../services/candidatService'
import { deconnecterCandidat, getCandidatConnecte } from '../services/candidatAuth'

function estVrai(valeur) {
  return valeur === true || valeur === 1 || valeur === '1' || valeur === 't' || valeur === 'true'
}

function construireLignes(offre, existantes) {
  const parExigence = new Map(
    (existantes ?? []).map((experience) => [Number(experience.id_exigence), experience])
  )

  return (offre?.exigences_experience ?? []).map((exigence) => {
    const enregistree = parExigence.get(Number(exigence.id))

    return {
      id_exigence: Number(exigence.id),
      poste: exigence.poste,
      duree_min: Number(exigence.duree_min ?? 0),
      duree_max: exigence.duree_max === null ? null : Number(exigence.duree_max),
      obligatoire: estVrai(exigence.obligatoire),
      date_debut: enregistree?.date_debut?.slice(0, 10) ?? '',
      date_fin: enregistree?.date_fin?.slice(0, 10) ?? '',
    }
  })
}

function construireExperiencesProfil(cv) {
  return (cv?.experiences ?? []).map((experience) => ({
    id_poste: experience.id_poste === null ? '' : Number(experience.id_poste),
    date_debut: experience.date_debut?.slice(0, 10) ?? '',
    date_fin: experience.date_fin?.slice(0, 10) ?? '',
    descriptions: experience.description ?? '',
  }))
}

function construireFormations(cv) {
  return (cv?.formations ?? []).map((formation) => ({
    diplome: formation.diplome ?? '',
    etablissement: formation.etablissement ?? '',
    date_debut: formation.date_debut?.slice(0, 10) ?? '',
    date_fin: formation.date_fin?.slice(0, 10) ?? '',
    description: formation.description ?? '',
  }))
}

function construireCompetences(cv) {
  return (cv?.competences ?? []).map((competence) => ({
    id_competence:
      competence.id_competence === null ? '' : Number(competence.id_competence),
    niveau: competence.niveau === null ? '' : Number(competence.niveau),
  }))
}

export function useCvCandidature() {
  const route = useRoute()
  const router = useRouter()
  const offre = ref(null)
  const candidature = ref(null)
  const experiences = ref([])
  const candidat = ref({
    nom: '',
    prenom: '',
    email: '',
    telephone: '',
    ville: '',
  })
  const experiencesProfil = ref([])
  const formations = ref([])
  const competencesCandidat = ref([])
  const postes = ref([])
  const competencesDisponibles = ref([])
  const chargement = ref(true)
  const erreur = ref('')
  const succes = ref('')
  const enregistrement = ref(false)

  const idOffre = computed(() => Number(route.params.id))
  const idCandidat = computed(() => Number(getCandidatConnecte()?.id ?? 0))
  const aujourdHui = computed(() => {
    const date = new Date()
    const mois = String(date.getMonth() + 1).padStart(2, '0')
    const jour = String(date.getDate()).padStart(2, '0')
    return `${date.getFullYear()}-${mois}-${jour}`
  })

  function appliquerCv(cv) {
    candidat.value = {
      nom: cv?.nom ?? '',
      prenom: cv?.prenom ?? '',
      email: cv?.email ?? '',
      telephone: cv?.telephone ?? '',
      ville: cv?.ville ?? '',
    }
    experiencesProfil.value = construireExperiencesProfil(cv)
    formations.value = construireFormations(cv)
    competencesCandidat.value = construireCompetences(cv)
  }

  async function charger() {
    chargement.value = true
    erreur.value = ''
    succes.value = ''

    if (idCandidat.value <= 0) {
      chargement.value = false
      router.replace({
        name: 'candidat-login',
        query: { redirect: route.fullPath },
      })
      return
    }

    try {
      const resultat = await getCvCandidature(idCandidat.value, idOffre.value)
      offre.value = resultat.offre
      candidature.value = resultat.candidature
      postes.value = resultat.postes ?? []
      competencesDisponibles.value = resultat.competences ?? []
      appliquerCv(resultat.cv)
      experiences.value = construireLignes(resultat.offre, resultat.experiences)
    } catch (e) {
      erreur.value = e.message
    } finally {
      chargement.value = false
    }
  }

  function marquerEnCours(experience) {
    experience.date_fin = aujourdHui.value
  }

  function dureeJours(experience) {
    if (!experience.date_debut || !experience.date_fin) {
      return null
    }

    const [anneeDebut, moisDebut, jourDebut] = experience.date_debut.split('-').map(Number)
    const [anneeFin, moisFin, jourFin] = experience.date_fin.split('-').map(Number)
    const debut = Date.UTC(anneeDebut, moisDebut - 1, jourDebut)
    const fin = Date.UTC(anneeFin, moisFin - 1, jourFin)
    const jours = Math.round((fin - debut) / 86400000)

    return jours >= 0 ? jours : null
  }

  function ajouterExperienceProfil() {
    experiencesProfil.value.push({
      id_poste: '',
      date_debut: '',
      date_fin: '',
      descriptions: '',
    })
  }

  function supprimerExperienceProfil(index) {
    experiencesProfil.value.splice(index, 1)
  }

  function ajouterFormation() {
    formations.value.push({
      diplome: '',
      etablissement: '',
      date_debut: '',
      date_fin: '',
      description: '',
    })
  }

  function supprimerFormation(index) {
    formations.value.splice(index, 1)
  }

  function ajouterCompetence() {
    competencesCandidat.value.push({
      id_competence: '',
      niveau: '',
    })
  }

  function supprimerCompetence(index) {
    competencesCandidat.value.splice(index, 1)
  }

  async function enregistrer() {
    erreur.value = ''
    succes.value = ''
    enregistrement.value = true

    try {
      if (idCandidat.value <= 0) {
        throw new Error('Veuillez vous connecter pour postuler.')
      }

      if (!candidat.value.nom.trim() || !candidat.value.prenom.trim()) {
        throw new Error('Le nom et le prénom sont obligatoires.')
      }

      if (!candidat.value.email.trim()) {
        throw new Error('L’adresse email est obligatoire.')
      }

      const renseignees = experiences.value.filter(
        (experience) => experience.date_debut || experience.date_fin
      )

      const incomplete = renseignees.find(
        (experience) => !experience.date_debut || !experience.date_fin
      )

      if (incomplete) {
        throw new Error(`Les deux dates sont obligatoires pour ${incomplete.poste}.`)
      }

      const profilRenseigne = experiencesProfil.value.filter(
        (experience) =>
          experience.id_poste ||
          experience.date_debut ||
          experience.date_fin ||
          experience.descriptions
      )

      const profilIncomplet = profilRenseigne.find(
        (experience) => !experience.id_poste || !experience.date_debut
      )

      if (profilIncomplet) {
        throw new Error('Le poste et la date de début sont obligatoires pour une expérience.')
      }

      const formationsRenseignees = formations.value.filter(
        (formation) =>
          formation.diplome ||
          formation.etablissement ||
          formation.date_debut ||
          formation.date_fin ||
          formation.description
      )

      const formationIncomplete = formationsRenseignees.find(
        (formation) => !formation.diplome.trim()
      )

      if (formationIncomplete) {
        throw new Error('Le diplôme est obligatoire pour une formation.')
      }

      const competencesRenseignees = competencesCandidat.value.filter(
        (competence) => competence.id_competence
      )

      const competenceIncomplete = competencesRenseignees.find(
        (competence) =>
          competence.niveau !== '' &&
          (Number.isNaN(Number(competence.niveau)) ||
            Number(competence.niveau) < 0 ||
            Number(competence.niveau) > 20)
      )

      if (competenceIncomplete) {
        throw new Error('Le niveau d’une compétence doit être entre 0 et 20.')
      }

      const resultat = await enregistrerCvCandidature(idCandidat.value, idOffre.value, {
        candidat: {
          nom: candidat.value.nom.trim(),
          prenom: candidat.value.prenom.trim(),
          email: candidat.value.email.trim(),
          telephone: candidat.value.telephone.trim(),
          ville: candidat.value.ville.trim(),
        },
        experiences: renseignees.map((experience) => ({
          id_exigence: experience.id_exigence,
          date_debut: experience.date_debut,
          date_fin: experience.date_fin,
        })),
        experiences_candidat: profilRenseigne.map((experience) => ({
          id_poste: Number(experience.id_poste),
          date_debut: experience.date_debut,
          date_fin: experience.date_fin || null,
          descriptions: experience.descriptions.trim(),
        })),
        formations: formationsRenseignees.map((formation) => ({
          diplome: formation.diplome.trim(),
          etablissement: formation.etablissement.trim(),
          date_debut: formation.date_debut || null,
          date_fin: formation.date_fin || null,
          description: formation.description.trim(),
        })),
        competences: competencesRenseignees.map((competence) => ({
          id_competence: Number(competence.id_competence),
          niveau: competence.niveau === '' ? null : Number(competence.niveau),
        })),
      })

      candidature.value = resultat.candidature
      appliquerCv(resultat.cv)
      experiences.value = construireLignes(offre.value, resultat.experiences)
      succes.value = resultat.message
    } catch (e) {
      erreur.value = e.message
    } finally {
      enregistrement.value = false
    }
  }

  function retourner() {
    router.push({ name: 'offres' })
  }

  function seDeconnecter() {
    deconnecterCandidat()
    router.push({ name: 'candidat-login' })
  }

  onMounted(charger)

  return {
    offre,
    candidature,
    experiences,
    candidat,
    experiencesProfil,
    formations,
    competencesCandidat,
    postes,
    competencesDisponibles,
    chargement,
    erreur,
    succes,
    enregistrement,
    idOffre,
    idCandidat,
    aujourdHui,
    dureeJours,
    marquerEnCours,
    ajouterExperienceProfil,
    supprimerExperienceProfil,
    ajouterFormation,
    supprimerFormation,
    ajouterCompetence,
    supprimerCompetence,
    enregistrer,
    retourner,
    seDeconnecter,
    charger,
  }
}
