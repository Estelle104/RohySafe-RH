<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import FicheDemande from '../components/FicheDemande.vue'
import SidebarMenu from '../components/SidebarMenu.vue'

const route = useRoute()
const router = useRouter()
const demande = ref(null)
const chargement = ref(true)
const erreur = ref('')
const enCours = ref(false)
const termine = ref(false)
const proposition = ref(null)
const menuOuvert = ref(false)

const estProposition = computed(() => route.params.action === 'proposition')
const estRefus = computed(() => route.params.action === 'refus')

function formatMontant(valeur) {
  return `${Math.round(Number(valeur || 0)).toLocaleString('fr-FR').replace(/ /g, ' ')} Ar`
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
        <button class="retour" type="button" @click="router.back()">← Modifier mon choix</button>

        <p v-if="chargement" class="message">Préparation de la confirmation…</p>
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
  max-width: 850px;
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

/* Bloc de confirmation / succès */
.confirmation,
.succes {
  padding: 24px;
  border-radius: 10px;
  background: #fff;
  border-left: 6px solid teal;
  box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
  color: #004d4d;
}

.confirmation.danger {
  border-left-color: #b42318;
  color: #8f1d15;
}

.eyebrow {
  margin: 0;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
}

.confirmation h1,
.succes h2 {
  margin: 6px 0 10px;
  font-size: 22px;
  font-weight: 800;
}

.confirmation p {
  margin: 8px 0 0;
  line-height: 1.5;
}

.remarque {
  font-style: italic;
}

/* Boutons */
.actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 16px 20px;
  border-radius: 10px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
}

.actions button,
.succes button {
  padding: 11px 22px;
  border: 0;
  border-radius: 10px;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.annuler {
  background: #fff;
  color: #374151;
  border: 1px solid #c0c0c0 !important;
  box-shadow: none !important;
}

.annuler:hover:not(:disabled) {
  background: #f1f3f6;
}

.envoyer,
.succes button {
  background: teal;
  color: #fff;
}

.envoyer:hover:not(:disabled),
.succes button:hover {
  background: #006666;
}

.refuser {
  background: #b42318;
  color: #fff;
}

.refuser:hover:not(:disabled) {
  background: #8f1d14;
}

.actions button:disabled {
  opacity: .6;
  cursor: wait;
}

.succes button {
  margin-top: 14px;
}

/* Messages */
.message {
  margin: 0;
  color: #747474;
  font-size: 14px;
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

@media (max-width: 540px) {
  .actions {
    flex-direction: column-reverse;
  }
}
</style>