import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getOffresBackoffice } from '../services/exigenceService'

export function useBackofficeOffres() {
  const router = useRouter()
  const offres = ref([])
  const chargement = ref(true)
  const erreur = ref('')
  const recherche = ref('')

  async function charger() {
    chargement.value = true
    erreur.value = ''

    try {
      offres.value = await getOffresBackoffice()
    } catch (e) {
      erreur.value = e.message
    } finally {
      chargement.value = false
    }
  }

  function ajouterExigences(offre) {
    router.push({ name: 'exigences-offre', params: { id: offre.id } })
  }

  function retourner() {
    router.push({ name: 'offres' })
  }

  const offresFiltrees = computed(() => {
    const rechercheNormalisee = recherche.value.trim().toLowerCase()

    if (!rechercheNormalisee) {
      return offres.value
    }

    return offres.value.filter((offre) =>
      [offre.titre, offre.departement, offre.type_contrat, offre.statut]
        .filter(Boolean)
        .some((valeur) => valeur.toLowerCase().includes(rechercheNormalisee))
    )
  })

  onMounted(charger)

  return {
    offresFiltrees,
    chargement,
    erreur,
    recherche,
    charger,
    ajouterExigences,
    retourner,
  }
}
