<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getCandidatsEntretiens } from '../services/entretienService'

const route = useRoute()
const router = useRouter()
const offre = ref(null)
const candidats = ref([])
const chargement = ref(true)
const erreur = ref('')
const enAction = ref(null)

// Note minimale (sur 20) pour pouvoir engager un candidat.
const NOTE_ENGAGEMENT = 15

function formaterDate(valeur) {
  if (!valeur) {
    return 'Date non renseignée'
  }

  const date = new Date(String(valeur).replace(' ', 'T'))
  return Number.isNaN(date.getTime())
    ? valeur
    : date.toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' })
}

function aUneEvaluation(candidat) {
  return candidat.note !== null && candidat.note !== undefined && candidat.note !== ''
}

function peutEngager(candidat) {
  return Number(candidat.note) >= NOTE_ENGAGEMENT
}

async function charger() {
  chargement.value = true
  erreur.value = ''

  try {
    const resultat = await getCandidatsEntretiens(route.params.id)
    offre.value = resultat.offre
    candidats.value = resultat.candidats
  } catch (e) {
    erreur.value = e.message
  } finally {
    chargement.value = false
  }
}

// Change le statut de la candidature : "engager" ou "attente".
// Adapter les URL à tes routes CodeIgniter 4.
async function changerStatut(candidat, action) {
  enAction.value = candidat.id_candidature
  erreur.value = ''

  try {
    const res = await fetch(`/api/candidatures/${candidat.id_candidature}/${action}`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
      credentials: 'include',
    })
    const resultat = await res.json().catch(() => ({}))
    if (!res.ok) throw new Error(resultat.message || 'Action impossible.')
    await charger()
  } catch (e) {
    erreur.value = e.message
  } finally {
    enAction.value = null
  }
}

const engager = candidat => changerStatut(candidat, 'engager')
const mettreEnAttente = candidat => changerStatut(candidat, 'attente')

onMounted(charger)
</script>

<template>
  <div class="page">
    <header class="navbar">
      <span class="brand">RohySafe</span>
      <div class="nav-right">
        <router-link class="nav-link" :to="{ name: 'entretiens-offres' }">Entretiens</router-link>
        <router-link class="nav-link" :to="{ name: 'offres' }">Offres</router-link>
        <span class="avatar-me">RH</span>
      </div>
    </header>

    <main class="contenu">
      <button class="retour" type="button" @click="router.push({ name: 'entretiens-offres' })">
        ← Retour aux offres
      </button>

      <p class="surtitre">Candidats convoqués</p>
      <h1>{{ offre?.titre || 'Entretiens de l’offre' }}</h1>
      <p class="sous-titre">Une note et des remarques par candidature après l’entretien.</p>

      <p v-if="chargement" class="message">Chargement des candidats...</p>
      <p v-else-if="erreur && candidats.length === 0" class="message erreur">{{ erreur }}</p>
      <p v-else-if="candidats.length === 0" class="message">
        Aucun candidat n’a d’entretien planifié pour cette offre.
      </p>

      <template v-else>
        <p v-if="erreur" class="message erreur">{{ erreur }}</p>

        <section class="liste">
          <article v-for="candidat in candidats" :key="candidat.id_candidature" class="carte">
            <div class="identite">
              <h2>{{ candidat.prenom }} {{ candidat.nom }}</h2>
              <p>{{ candidat.email }} · {{ candidat.ville || 'Ville non renseignée' }}</p>
              <p class="entretien">
                {{ formaterDate(candidat.date_entretien) }}
                <span v-if="candidat.lieu_entretien"> · {{ candidat.lieu_entretien }}</span>
              </p>
            </div>

            <div class="etat">
              <span class="statut" :class="candidat.statut_entretien">
                {{ candidat.statut_entretien || 'planifie' }}
              </span>
              <p v-if="aUneEvaluation(candidat)" class="note">
                {{ Number(candidat.note).toFixed(2) }} / 20
              </p>
              <p v-else class="sans-note">Pas encore évalué</p>

              <div class="actions">
                <button
                  class="btn"
                  type="button"
                  @click="router.push({ name: 'entretien-evaluation', params: { id: candidat.id_candidature } })"
                >
                  {{ aUneEvaluation(candidat) ? 'Modifier l’évaluation' : 'Évaluer' }}
                </button>

                <!-- Décision : seulement une fois la note saisie -->
                <template v-if="aUneEvaluation(candidat)">
                  <button
                    v-if="peutEngager(candidat)"
                    class="btn engager"
                    type="button"
                    :disabled="enAction === candidat.id_candidature"
                    @click="engager(candidat)"
                  >
                    Engager
                  </button>
                  <button
                    v-else
                    class="btn attente"
                    type="button"
                    :disabled="enAction === candidat.id_candidature"
                    @click="mettreEnAttente(candidat)"
                  >
                    En attente
                  </button>
                </template>
              </div>
            </div>
          </article>
        </section>
      </template>
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
  padding: 34px 20px 50px;
}

.retour {
  margin-bottom: 22px;
  border: 1px solid #cbd5e1;
  border-radius: 18px;
  padding: 8px 14px;
  background: #fff;
  color: #344054;
  font: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
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
  font-size: 28px;
}

.sous-titre {
  margin: 0 0 24px;
  color: #667085;
}

.liste {
  display: grid;
  gap: 14px;
}

.carte {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding: 20px;
  border-radius: 12px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.12) 0 3px 12px;
}

.identite {
  flex: 1;
}

.identite h2 {
  margin: 0 0 6px;
  font-size: 18px;
}

.identite p {
  margin: 0 0 5px;
  color: #667085;
  font-size: 13px;
}

.identite .entretien {
  color: #00534f;
  font-weight: 700;
}

.etat {
  min-width: 190px;
  text-align: right;
}

.statut {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  background: #e6f2f2;
  color: #006666;
  font-size: 11px;
  font-weight: 800;
  text-transform: capitalize;
}

.statut.realise {
  background: #e8f5e9;
  color: #137333;
}

.statut.annule {
  background: #fdecec;
  color: #b42318;
}

.note {
  margin: 10px 0 6px;
  color: teal;
  font-size: 20px;
  font-weight: 800;
}

.sans-note {
  margin: 10px 0 6px;
  color: #667085;
  font-size: 12px;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 8px;
}

.btn {
  border: 0;
  border-radius: 20px;
  padding: 9px 16px;
  background: teal;
  color: #fff;
  font: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.6;
  cursor: wait;
}

/* Décision : engager (note >= 15) */
.btn.engager {
  background: #137333;
}

.btn.engager:hover:not(:disabled) {
  background: #0f5c28;
}

/* Décision : en attente (note < 15) */
.btn.attente {
  border: 1px solid #b54708;
  background: #fff;
  color: #b54708;
}

.btn.attente:hover:not(:disabled) {
  background: #fef3e6;
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

  .etat {
    width: 100%;
    text-align: left;
  }

  .actions {
    justify-content: flex-start;
  }
}
</style>