<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FicheDemande from '../components/FicheDemande.vue'
import GrilleSalariale from '../components/GrilleSalariale.vue'
import FormulairePropositionSalaire from '../components/FormulairePropositionSalaire.vue'
import SidebarMenu from '../components/SidebarMenu.vue'

const route = useRoute()
const router = useRouter()
const demande = ref(null)
const grille = ref([])
const chargement = ref(true)
const erreur = ref('')
const enregistrement = ref(false)
const succes = ref('')
const menuOuvert = ref(false)

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
  <div class="app-layout">
    <!-- Nom / initiales : à remplacer par l'utilisateur connecté -->
    <SidebarMenu
      nom="Ressources humaines"
      role="RH"
      code-role="RH"
      initiales="RH"
      :ouvert="menuOuvert"
      @fermer="menuOuvert = false"
    />

    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>

      <div class="contenu">
        <button class="retour" type="button" @click="router.push('/notification')">← Retour aux notifications</button>

        <p v-if="chargement" class="message">Chargement de la demande…</p>
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
  background: #f1f3f6;
}

.burger {
  display: none;
  margin: 16px 0 0 16px;
  padding: 8px 16px;
  border: 0;
  border-radius: 10px;
  background: #fff;
  color: teal;
  font-family: inherit;
  font-weight: 700;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.contenu {
  max-width: 1050px;
  margin: 0 auto;
  padding: 32px 20px 60px;
  display: grid;
  gap: 20px;
}

.retour {
  justify-self: start;
  padding: 8px 16px;
  border: 1px solid teal;
  border-radius: 10px;
  background: #fff;
  color: teal;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.retour:hover {
  background: #e6f2f2;
}

.colonnes {
  display: grid;
  grid-template-columns: minmax(280px, .85fr) minmax(0, 1.15fr);
  gap: 20px;
}

/* Barre d'actions */
.actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 16px 20px;
  border-radius: 10px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
}

.actions p {
  margin: 0;
  font-weight: 700;
}

.actions div {
  display: flex;
  gap: 10px;
}

.actions button {
  padding: 11px 22px;
  border: 0;
  border-radius: 10px;
  color: #fff;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.actions button:disabled {
  opacity: .6;
  cursor: wait;
}

.envoyer {
  background: teal;
}

.envoyer:hover:not(:disabled) {
  background: #006666;
}

.refuser {
  background: #b42318;
}

.refuser:hover:not(:disabled) {
  background: #8f1d14;
}

/* Messages */
.message {
  margin: 0;
  color: #747474;
  font-size: 14px;
}

.succes {
  margin: 0;
  padding: 12px 15px;
  border-radius: 8px;
  background: #e8f5e9;
  color: #137333;
  font-size: 13px;
}

.erreur {
  margin: 0;
  padding: 12px 15px;
  border-radius: 8px;
  background: #fdecec;
  color: #b42318;
  font-size: 13px;
}

@media (max-width: 960px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .burger {
    display: block;
  }
}

@media (max-width: 720px) {
  .colonnes {
    grid-template-columns: 1fr;
  }

  .actions {
    align-items: stretch;
    flex-direction: column;
  }

  .actions div {
    flex-direction: column;
  }
}
</style>