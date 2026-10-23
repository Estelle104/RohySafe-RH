import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getOffreDetail, getOffres } from '../services/offreService'

export function useOffre() {
    

    const router = useRouter()
    const recherche = ref('')
    const filtreActif = ref('Toutes')
    const filtres = ['Toutes', 'Informatique', 'Technique', 'Santé', 'CDI', 'CDD']

    const offres = ref([])
    const chargement = ref(true)
    const erreur = ref('')
    const offreDetail = ref(null)
    const chargementDetail = ref(false)
    const erreurDetail = ref('')

    async function chargerOffres() {
      chargement.value = true
      erreur.value = ''
      try {
        offres.value = await getOffres()
      } catch (e) {
        erreur.value = e.message
      } finally {
        chargement.value = false
      }
    }

    onMounted(chargerOffres)

    const offresFiltrees = computed(() => {
      const q = recherche.value.trim().toLowerCase()
      return offres.value.filter((o) => {
        const okFiltre =
          filtreActif.value === 'Toutes' ||
          o.departement === filtreActif.value ||
          o.type_contrat === filtreActif.value
        const okRecherche =
          !q ||
          (o.titre ?? '').toLowerCase().includes(q) ||
          (o.descriptions ?? '').toLowerCase().includes(q)
        return okFiltre && okRecherche
      })
    })

    function voirDetails(offre) {
      chargementDetail.value = true
      erreurDetail.value = ''
      getOffreDetail(offre.id)
        .then((detail) => {
          offreDetail.value = detail
        })
        .catch((e) => {
          erreurDetail.value = e.message
        })
        .finally(() => {
          chargementDetail.value = false
        })
    }

    function fermerDetails() {
      offreDetail.value = null
    }

    function postuler(offre) {
      console.log('postuler', offre.id) // TODO: ouvrir le formulaire de candidature (CV)
    }

    function voirCandidatsIdeaux(offre) {
      router.push({ name: 'candidats-ideaux', params: { id: offre.id } })
    }

    return {
      recherche,
      filtreActif,
      filtres,
      offresFiltrees,
      chargement,
      erreur,
      offreDetail,
      chargementDetail,
      erreurDetail,
      voirDetails,
      fermerDetails,
      postuler,
      voirCandidatsIdeaux,
      router,
      chargerOffres,
    }
}