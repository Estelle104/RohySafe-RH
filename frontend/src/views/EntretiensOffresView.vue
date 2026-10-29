<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getOffresEntretiens } from '../services/entretienService'
import SidebarMenu from '../components/SidebarMenu.vue'

const router = useRouter()
const offres = ref([])
const chargement = ref(true)
const erreur = ref('')
const menuOuvert = ref(false)

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
  <div class="app-layout">
    <!-- Nom / initiales : à remplacer par l'utilisateur connecté -->
    <SidebarMenu
      nom="Ressources humaines"
      role="RH"
      codeRole="RH"
      initiales="RH"
      :ouvert="menuOuvert"
      @fermer="menuOuvert = false"
    />

    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>

      <div class="contenu">
        <div class="entete">
          <p class="surtitre">Suivi des candidatures</p>
          <h1>Entretiens</h1>
          <p class="sous-titre">
            Choisissez une offre pour retrouver les candidats convoqués et saisir leur évaluation.
          </p>
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
      </div>
    </main>
  </div>
</template>

<style scoped>
/* Structure globale : sidebar + page */
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
  min-width: 0;
  min-height: 100vh;
}

.burger {
  display: none;
  margin: 16px 0 0 16px;
  padding: 8px 16px;
  border: 0;
  border-radius: 20px;
  background: #fff;
  color: teal;
  font-family: inherit;
  font-weight: 700;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
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

@media (max-width: 960px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .burger {
    display: block;
  }
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