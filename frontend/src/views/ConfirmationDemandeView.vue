<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FicheDemande from '../components/FicheDemande.vue'

const route = useRoute()
const router = useRouter()
const demande = ref(null)
const chargement = ref(true)
const erreur = ref('')
const enCours = ref(false)
const termine = ref(false)
const proposition = ref(null)

const estProposition = computed(() => route.params.action === 'proposition')
const estRefus = computed(() => route.params.action === 'refus')

function formatMontant(valeur) {
  return `${Math.round(Number(valeur || 0)).toLocaleString('fr-FR').replace(/\u202f/g, ' ')} Ar`
}

async function charger() {
  if (!estProposition.value && !estRefus.value) {
    router.replace('/notification')
    return
  }

  if (estProposition.value) {
    const sauvegarde = sessionStorage.getItem(`proposition-salaire-${route.params.id}`)
    proposition.value = sauvegarde ? JSON.parse(sauvegarde) : null
    if (proposition.value === null || proposition.value.salaire_propose === '') {
      router.replace({ name: 'demande-detail', params: { id: route.params.id } })
      return
    }
  }

  try {
    const res = await fetch(`/api/demandes/${route.params.id}`, { headers: { Accept: 'application/json' } })
    if (!res.ok) throw new Error()
    demande.value = await res.json()
  } catch {
    erreur.value = 'Impossible de charger cette demande.'
  } finally {
    chargement.value = false
  }
}

async function confirmer() {
  enCours.value = true
  erreur.value = ''
  try {
    const endpoint = estProposition.value ? 'proposition-salaire' : 'refuser'
    const options = {
      method: 'POST',
      headers: { Accept: 'application/json' }
    }
    if (estProposition.value) {
      options.headers['Content-Type'] = 'application/json'
      options.body = JSON.stringify(proposition.value)
    }
    const res = await fetch(`/api/demandes/${route.params.id}/${endpoint}`, options)
    const resultat = await res.json()
    if (!res.ok) throw new Error(resultat.message)
    if (estProposition.value) sessionStorage.removeItem(`proposition-salaire-${route.params.id}`)
    termine.value = true
  } catch (e) {
    erreur.value = e.message || 'Impossible d’enregistrer votre action.'
  } finally {
    enCours.value = false
  }
}

onMounted(charger)
</script>

<template>
  <main class="page">
    <button class="retour" type="button" @click="router.back()">← Modifier mon choix</button>
    <p v-if="chargement">Préparation de la confirmation…</p>
    <p v-else-if="erreur && !demande" class="erreur">{{ erreur }}</p>
    <template v-else-if="demande">
      <section v-if="!termine" class="confirmation" :class="{ danger: estRefus }">
        <p class="eyebrow">Confirmation requise</p>
        <h1 v-if="estProposition">Envoyer la proposition à la Finance ?</h1>
        <h1 v-else>Refuser cette demande ?</h1>
        <p v-if="estProposition">
          Proposer <strong>{{ formatMontant(proposition.salaire_propose) }}</strong>
          pour le poste de <strong>{{demande.poste}}</strong>.
        </p>
        <p v-else>Ce demande sera <strong>Refusée</strong> définitivement.</p>
        <p v-if="estProposition && proposition.remarque" class="remarque">Remarque : {{ proposition.remarque }}</p>
      </section>

      <FicheDemande :demande="demande" />

      <section v-if="!termine" class="actions">
        <button type="button" class="annuler" :disabled="enCours" @click="router.back()">Annuler</button>
        <button type="button" :class="estRefus ? 'refuser' : 'envoyer'" :disabled="enCours" @click="confirmer">
          {{ enCours ? 'Traitement…' : estProposition ? 'Confirmer l’envoi à Finance' : 'Confirmer le refus' }}
        </button>
      </section>

      <section v-else class="succes">
        <h2>Action enregistrée</h2>
        <p v-if="estProposition">La proposition a été envoyée à Finance pour traitement.</p>
        <p v-else>La demande a été refusée.</p>
        <button type="button" @click="router.push('/notification')">Retour aux notifications</button>
      </section>
      <p v-if="erreur" class="erreur">{{ erreur }}</p>
    </template>
  </main>
</template>

<style scoped>
.page { max-width: 850px; min-height: 100vh; margin: 0 auto; padding: 24px 16px; display: grid; gap: 20px; background: #f4f7f7; }.retour { justify-self: start; border: 0; padding: 0; background: transparent; color: teal; font: inherit; font-weight: 700; cursor: pointer; }.confirmation, .succes { border-radius: 16px; padding: 24px; background: #e0f4ee; border: 1px solid #9bd7ca; color: #004d4d; }.confirmation.danger { background: #fde8e6; border-color: #efb3ad; color: #8f1d15; }.eyebrow { margin: 0; font-size: 12px; font-weight: 800; text-transform: uppercase; }.confirmation h1, .succes h2 { margin: 6px 0 10px; }.confirmation p { margin: 8px 0 0; }.remarque { font-style: italic; }.actions { display: flex; justify-content: end; gap: 12px; padding: 16px 20px; border-radius: 12px; background: #e7ecec; }.actions button, .succes button { border: 0; border-radius: 8px; padding: 11px 15px; font: inherit; font-weight: 700; cursor: pointer; }.annuler { background: #fff; color: #374151; border: 1px solid #aeb8b8 !important; }.envoyer, .succes button { background: teal; color: #fff; }.refuser { background: #b42318; color: #fff; }.actions button:disabled { opacity: .6; cursor: wait; }.erreur { color: #b42318; } @media (max-width: 540px) { .actions { flex-direction: column-reverse; } }
</style>
