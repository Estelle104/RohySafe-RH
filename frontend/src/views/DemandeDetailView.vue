<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FicheDemande from '../components/FicheDemande.vue'
import GrilleSalariale from '../components/GrilleSalariale.vue'
import FormulairePropositionSalaire from '../components/FormulairePropositionSalaire.vue'

const route = useRoute()
const router = useRouter()
const demande = ref(null)
const grille = ref([])
const chargement = ref(true)
const erreur = ref('')
const enregistrement = ref(false)
const succes = ref('')

async function charger() {
  chargement.value = true
  erreur.value = ''
  try {
    const [demandeRes, grilleRes] = await Promise.all([
      fetch(`/api/demandes/${route.params.id}`, { headers: { Accept: 'application/json' } }),
      fetch(`/api/demandes/${route.params.id}/grille-salariale`, { headers: { Accept: 'application/json' } })
    ])
    if (!demandeRes.ok || !grilleRes.ok) throw new Error('chargement')
    demande.value = await demandeRes.json()
    grille.value = await grilleRes.json()
  } catch {
    erreur.value = 'Impossible de charger cette demande.'
  } finally {
    chargement.value = false
  }
}

async function enregistrerProposition(payload) {
  sessionStorage.setItem(`proposition-salaire-${route.params.id}`, JSON.stringify(payload))
  router.push({ name: 'demande-confirmation', params: { id: route.params.id, action: 'proposition' } })
}

function demanderRefus() {
  router.push({ name: 'demande-confirmation', params: { id: route.params.id, action: 'refus' } })
}

onMounted(charger)
</script>

<template>
  <main class="page">
    <button class="retour" type="button" @click="router.push('/notification')">← Retour aux notifications</button>
    <p v-if="chargement">Chargement de la demande…</p>
    <p v-else-if="erreur && !demande" class="erreur">{{ erreur }}</p>
    <template v-else-if="demande">
      <FicheDemande :demande="demande" />
      <div class="colonnes">
        <FormulairePropositionSalaire :salaire-actuel="demande.salaire_propose" :en-cours="enregistrement" @soumettre="enregistrerProposition" />
        <GrilleSalariale :grille="grille" />
      </div>
      <section class="actions" aria-label="Actions sur la demande">
        <p>Actions sur cette demande</p>
        <div>
          <button class="envoyer" type="submit" form="proposition-salaire-form" :disabled="enregistrement">
            {{ enregistrement ? 'Enregistrement…' : 'Envoyer la proposition' }}
          </button>
          <button class="refuser" type="button" :disabled="enregistrement" @click="demanderRefus">Refuser</button>
        </div>
      </section>
      <p v-if="succes" class="succes">{{ succes }}</p>
      <p v-if="erreur" class="erreur">{{ erreur }}</p>
    </template>
  </main>
</template>

<style scoped>
.page { max-width: 1050px; margin: 0 auto; padding: 24px 16px; display: grid; gap: 20px; background: #f4f7f7; min-height: 100vh; }
.retour { justify-self: start; background: transparent; border: 0; color: teal; padding: 0; font: inherit; font-weight: 700; cursor: pointer; }.colonnes { display: grid; grid-template-columns: minmax(280px, .85fr) minmax(0, 1.15fr); gap: 20px; }.actions { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 20px; border-radius: 12px; background: #e7ecec; }.actions p { margin: 0; font-weight: 700; }.actions div { display: flex; gap: 10px; }.actions button { border: 0; border-radius: 8px; padding: 11px 15px; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }.actions button:disabled { opacity: .6; cursor: wait; }.envoyer { background: teal; }.refuser { background: #b42318; }.succes { color: #006666; }.erreur { color: #b42318; } @media (max-width: 720px) { .colonnes { grid-template-columns: 1fr; }.actions { align-items: stretch; flex-direction: column; }.actions div { flex-direction: column; } }
</style>
