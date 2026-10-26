<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getOffresEntretiens } from '../services/entretienService'

const router = useRouter()
const offres = ref([])
const chargement = ref(true)
const erreur = ref('')

function formaterDate(valeur) {
  if (!valeur) {
    return 'Date non renseignée'
  }

  const date = new Date(String(valeur).replace(' ', 'T'))
  return Number.isNaN(date.getTime())
    ? valeur
    : date.toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' })
}

async function charger() {
  chargement.value = true
  erreur.value = ''

  try {
    offres.value = await getOffresEntretiens()
  } catch (e) {
    erreur.value = e.message
  } finally {
    chargement.value = false
  }
}

onMounted(charger)
</script>

<template>
  <div class="page">
    <header class="navbar">
      <span class="brand">RohySafe</span>
      <div class="nav-right">
        <router-link class="nav-link" :to="{ name: 'offres' }">Offres</router-link>
        <span class="avatar-me">RH</span>
      </div>
    </header>

    <main class="contenu">
      <div class="entete">
        <div>
          <p class="surtitre">Suivi des candidatures</p>
          <h1>Entretiens</h1>
          <p class="sous-titre">
            Choisissez une offre pour retrouver les candidats convoqués et saisir leur évaluation.
          </p>
        </div>
      </div>

      <p v-if="chargement" class="message">Chargement des offres...</p>
      <p v-else-if="erreur" class="message erreur">{{ erreur }}</p>
      <p v-else-if="offres.length === 0" class="message">
        Aucun entretien n’a encore été planifié.
      </p>

      <section v-else class="liste">
        <article v-for="offre in offres" :key="offre.id" class="carte">
          <div>
            <p class="compteur">{{ offre.nb_candidats }} candidat(s)</p>
            <h2>{{ offre.titre }}</h2>
            <p class="date">Prochain entretien : {{ formaterDate(offre.prochaine_date) }}</p>
          </div>
          <button
            class="btn"
            type="button"
            @click="router.push({ name: 'entretiens-candidats', params: { id: offre.id } })"
          >
            Voir les candidats
          </button>
        </article>
      </section>
    </main>
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #f1f3f6;
  color: #222;
  font-family: "Lucida Sans", sans-serif;
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
  color: teal;
  font-size: 22px;
  font-weight: 800;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 14px;
}

.nav-link {
  color: #747474;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
}

.nav-link:hover {
  color: teal;
}

.avatar-me {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: teal;
  color: #fff;
  font-weight: 700;
}

.contenu {
  max-width: 900px;
  margin: 0 auto;
  padding: 40px 20px;
}

.entete {
  margin-bottom: 24px;
}

.surtitre {
  margin: 0 0 6px;
  color: #667085;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

h1 {
  margin: 0 0 8px;
  color: #00534f;
  font-size: 30px;
}

.sous-titre {
  margin: 0;
  color: #667085;
  line-height: 1.6;
}

.liste {
  display: grid;
  gap: 14px;
}

.carte {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 20px;
  border-radius: 12px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.12) 0 3px 12px;
}

.compteur {
  margin: 0 0 6px;
  color: teal;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.carte h2 {
  margin: 0 0 6px;
  font-size: 18px;
}

.date {
  margin: 0;
  color: #667085;
  font-size: 13px;
}

.btn {
  flex-shrink: 0;
  border: 0;
  border-radius: 20px;
  padding: 10px 16px;
  background: teal;
  color: #fff;
  font: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.message {
  padding: 18px;
  border-radius: 10px;
  background: #fff;
  color: #667085;
}

.erreur {
  color: #b42318;
}

@media (max-width: 640px) {
  .carte {
    align-items: flex-start;
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>
