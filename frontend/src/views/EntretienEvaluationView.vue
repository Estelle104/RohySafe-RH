<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { enregistrerEvaluation, getDetailEntretien } from '../services/entretienService'

const route = useRoute()
const router = useRouter()
const detail = ref(null)
const form = ref({ note: '', remarques: '' })
const chargement = ref(true)
const enregistrement = ref(false)
const erreur = ref('')
const message = ref('')

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
    detail.value = await getDetailEntretien(route.params.id)
    form.value.note = detail.value.note === null ? '' : Number(detail.value.note).toFixed(2)
    form.value.remarques = detail.value.remarques || ''
  } catch (e) {
    erreur.value = e.message
  } finally {
    chargement.value = false
  }
}

async function sauvegarder() {
  if (!detail.value) {
    return
  }

  erreur.value = ''
  message.value = ''
  enregistrement.value = true

  try {
    const resultat = await enregistrerEvaluation(detail.value.id_candidature, {
      note: form.value.note === '' ? null : Number(form.value.note),
      remarques: form.value.remarques,
    })

    detail.value.note = resultat.evaluation.note
    detail.value.remarques = resultat.evaluation.remarques
    detail.value.date_evaluation = resultat.evaluation.date_evaluation
    detail.value.statut_entretien = 'realise'
    message.value = 'L’évaluation a bien été enregistrée.'
  } catch (e) {
    erreur.value = e.message
  } finally {
    enregistrement.value = false
  }
}

function retour() {
  if (detail.value?.id_offre) {
    router.push({ name: 'entretiens-candidats', params: { id: detail.value.id_offre } })
    return
  }

  router.push({ name: 'entretiens-offres' })
}

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
      <button class="retour" type="button" @click="retour">← Retour aux candidats</button>

      <p v-if="chargement" class="message">Chargement de l’entretien...</p>
      <p v-else-if="erreur && !detail" class="message erreur">{{ erreur }}</p>

      <template v-else-if="detail">
        <section class="fiche">
          <div class="entete">
            <div>
              <p class="surtitre">Évaluation du jury</p>
              <h1>{{ detail.prenom }} {{ detail.nom }}</h1>
              <p class="offre">{{ detail.offre }}</p>
            </div>
            <span class="statut" :class="detail.statut_entretien">
              {{ detail.statut_entretien || 'planifie' }}
            </span>
          </div>

          <dl class="informations">
            <div>
              <dt>Email</dt>
              <dd>{{ detail.email }}</dd>
            </div>
            <div>
              <dt>Téléphone</dt>
              <dd>{{ detail.telephone || 'Non renseigné' }}</dd>
            </div>
            <div>
              <dt>Entretien</dt>
              <dd>{{ formaterDate(detail.date_entretien) }}</dd>
            </div>
            <div>
              <dt>Lieu</dt>
              <dd>{{ detail.lieu_entretien || 'Non renseigné' }}</dd>
            </div>
          </dl>

          <p v-if="detail.commentaire_entretien" class="commentaire">
            {{ detail.commentaire_entretien }}
          </p>
        </section>

        <form class="evaluation" @submit.prevent="sauvegarder">
          <h2>Saisie de l’évaluation</h2>
          <p class="aide">
            Une seule évaluation par candidature. La modifier remplace la note et les remarques précédentes.
          </p>

          <label>
            Note sur 20
            <input
              v-model="form.note"
              type="number"
              min="0"
              max="20"
              step="0.01"
              placeholder="Ex. 15,50"
              required
            >
          </label>

          <label>
            Remarques
            <textarea
              v-model="form.remarques"
              rows="6"
              placeholder="Points forts, points à améliorer, décision du jury..."
            />
          </label>

          <p v-if="erreur" class="message erreur">{{ erreur }}</p>
          <p v-if="message" class="message succes">{{ message }}</p>

          <button class="btn" type="submit" :disabled="enregistrement">
            {{ enregistrement ? 'Enregistrement…' : 'Enregistrer l’évaluation' }}
          </button>
        </form>
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
  max-width: 820px;
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

.fiche,
.evaluation {
  padding: 24px;
  border-radius: 12px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.12) 0 3px 12px;
}

.evaluation {
  margin-top: 18px;
}

.entete {
  display: flex;
  justify-content: space-between;
  gap: 18px;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 18px;
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
  margin: 0 0 6px;
  color: #00534f;
  font-size: 28px;
}

.offre {
  margin: 0;
  color: #667085;
}

.statut {
  align-self: flex-start;
  padding: 5px 11px;
  border-radius: 10px;
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

.informations {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin: 20px 0 0;
}

.informations div {
  padding: 12px;
  border-radius: 8px;
  background: #f7f9fa;
}

dt {
  color: #667085;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

dd {
  margin: 5px 0 0;
  font-size: 14px;
}

.commentaire {
  margin: 18px 0 0;
  padding: 14px;
  border-left: 3px solid teal;
  background: #f7f9fa;
  color: #475467;
  line-height: 1.6;
}

.evaluation h2 {
  margin: 0 0 6px;
  color: #00534f;
}

.aide {
  margin: 0 0 20px;
  color: #667085;
  font-size: 13px;
  line-height: 1.5;
}

label {
  display: grid;
  gap: 7px;
  margin-bottom: 16px;
  color: #344054;
  font-size: 13px;
  font-weight: 700;
}

input,
textarea {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 11px 12px;
  font: inherit;
  font-weight: 400;
}

input:focus,
textarea:focus {
  border-color: teal;
  outline: 0;
  box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.12);
}

textarea {
  resize: vertical;
}

.btn {
  border: 0;
  border-radius: 22px;
  padding: 11px 20px;
  background: teal;
  color: #fff;
  font: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.55;
}

.message {
  padding: 12px 14px;
  border-radius: 8px;
  background: #fff;
  color: #667085;
}

.message.erreur {
  color: #b42318;
  background: #fdecec;
}

.message.succes {
  color: #137333;
  background: #e8f5e9;
}

@media (max-width: 640px) {
  .entete {
    flex-direction: column;
  }

  .informations {
    grid-template-columns: 1fr;
  }
}
</style>
