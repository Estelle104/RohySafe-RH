<script setup>
import { ref, computed, onMounted } from 'vue'
import OffreCard from '../components/OffreCard.vue'
import { getOffres } from '../services/offreService'

const recherche = ref('')
const filtreActif = ref('Toutes')
const filtres = ['Toutes', 'Informatique', 'Technique', 'Santé', 'CDI', 'CDD']

const offres = ref([])
const chargement = ref(true)
const erreur = ref('')

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
  console.log('details', offre.id) // TODO: router.push({ name: 'offre-detail', params: { id: offre.id } })
}

function postuler(offre) {
  console.log('postuler', offre.id) // TODO: ouvrir le formulaire de candidature (CV)
}
</script>

<template>
  <div class="page">
    <header class="navbar">
      <span class="brand">RohySafe</span>
      <input
        v-model="recherche"
        type="text"
        class="search"
        placeholder="Rechercher une offre..."
      />
      <div class="nav-right">
        <span class="nav-link">Mes candidatures</span>
        <div class="avatar-me">TH</div>
      </div>
    </header>

    <div class="filters">
      <button
        v-for="f in filtres"
        :key="f"
        class="chip"
        :class="{ active: filtreActif === f }"
        @click="filtreActif = f"
      >
        {{ f }}
      </button>
    </div>

    <main class="feed">
      <p v-if="chargement" class="end">Chargement des offres...</p>

      <template v-else-if="erreur">
        <p class="end error">{{ erreur }}</p>
        <button class="btn retry" @click="chargerOffres">Réessayer</button>
      </template>

      <template v-else>
        <OffreCard
          v-for="o in offresFiltrees"
          :key="o.id"
          :offre="o"
          @details="voirDetails"
          @postuler="postuler"
        />

        <p v-if="offresFiltrees.length === 0" class="end">Aucune offre trouvée</p>
        <p v-else class="end">Vous avez vu toutes les offres</p>
      </template>
    </main>
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #f1f3f6;
  font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
    "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
  color: #222;
}

.navbar {
  position: sticky;
  top: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 20px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.15) 0 2px 8px;
}

.brand {
  font-size: 22px;
  font-weight: 800;
  color: teal;
}

.search {
  flex: 1;
  max-width: 360px;
  border-radius: 20px;
  border: 1px solid #c0c0c0;
  padding: 10px 15px;
  outline: 0;
  font-family: inherit;
  transition: border-color 0.2s;
}

.search:focus {
  border-color: teal;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.nav-link {
  font-size: 13px;
  font-weight: 700;
  color: #747474;
  cursor: pointer;
}

.nav-link:hover {
  color: #000;
}

.avatar-me {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: teal;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 700;
}

.filters {
  max-width: 620px;
  margin: 20px auto 0;
  padding: 0 16px;
  display: flex;
  gap: 8px;
  overflow-x: auto;
}

.chip {
  flex: none;
  padding: 8px 16px;
  border-radius: 20px;
  border: 1px solid #c0c0c0;
  background: #fff;
  font-family: inherit;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.chip:hover {
  border-color: teal;
}

.chip.active {
  background: teal;
  border-color: teal;
  color: #fff;
}

.feed {
  max-width: 620px;
  margin: 0 auto;
  padding: 20px 16px 60px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.end {
  text-align: center;
  font-size: 12px;
  color: #747474;
}

.end.error {
  color: #b42318;
  font-size: 14px;
}

.retry {
  align-self: center;
  padding: 10px 22px;
  font-family: inherit;
  border-radius: 20px;
  border: 0;
  background: teal;
  color: #fff;
  cursor: pointer;
}

.retry:hover {
  background: #006666;
}
</style>