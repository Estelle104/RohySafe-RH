<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useOffre } from '../composables/useOffre.js'
import OffreCard from '../components/OffreCard.vue'
import ContratCard from '../components/ContratCard.vue'
import { deconnecterCandidat, getCandidatConnecte } from '../services/candidatAuth'

const router = useRouter()
const candidat = ref(getCandidatConnecte())
const initiales = computed(() => {
  if (!candidat.value) {
    return ''
  }

  return `${candidat.value.prenom?.charAt(0) ?? ''}${candidat.value.nom?.charAt(0) ?? ''}`
})

function seDeconnecter() {
  deconnecterCandidat()
  candidat.value = null
  router.push({ name: 'candidat-login' })
}

const {
  recherche,
  filtreActif,
  filtres,
  offresFiltrees,
  chargement,
  erreur,
  chargerOffres,
  offreDetail,
  chargementDetail,
  erreurDetail,
  voirDetails,
  fermerDetails,
  postuler,
} = useOffre()
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
        <router-link v-if="!candidat" class="nav-link" to="/entretiens">Entretiens</router-link>
        <!-- <router-link class="nav-link" to="/backoffice">Backoffice</router-link> -->
        <template v-if="candidat">
          <span class="nav-link">{{ candidat.prenom }}</span>
          <button class="nav-link nav-button" type="button" @click="seDeconnecter">
            Déconnexion
          </button>
          <div class="avatar-me">{{ initiales }}</div>
        </template>
        <router-link v-else class="nav-link" to="/candidats/login">Espace candidat</router-link>
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

    <div v-if="offreDetail || chargementDetail" class="modal-backdrop" @click.self="fermerDetails">
      <section class="detail-modal">
        <button class="close" @click="fermerDetails">Fermer</button>
        <p v-if="chargementDetail">Chargement du détail...</p>
        <p v-else-if="erreurDetail" class="error">{{ erreurDetail }}</p>
        <template v-else-if="offreDetail">
          <ContratCard :offre="offreDetail" />
        </template>
      </section>
    </div>
  </div>
</template>

<style scoped>
.app-layout {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  min-height: 100vh;
  background: #f1f3f6;
  color: #222;
  font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
    "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
}
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
  text-decoration: none;
}

.nav-link:hover {
  color: #000;
}

.nav-button {
  border: 0;
  background: transparent;
  font-family: inherit;
  padding: 0;
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

.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 20;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgb(0 0 0 / 45%);
}

.detail-modal {
  position: relative;
  width: min(620px, 100%);
  max-height: 90vh;
  overflow-y: auto;
  padding: 26px;
  border-radius: 12px;
  background: #fff;
}

.close {
  position: absolute;
  top: 14px;
  right: 14px;
  border: 0;
  background: transparent;
  color: #667085;
  cursor: pointer;
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