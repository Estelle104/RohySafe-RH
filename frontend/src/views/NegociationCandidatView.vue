<template>
  <div class="page">
    <header class="navbar">
      <span class="brand">RohySafe</span>
      <div class="nav-actions">
        <button class="btn secondary" type="button" @click="router.push({ name: 'candidat-notifications' })">Mes notifications</button>
        <button class="btn secondary" type="button" @click="retourOffres">Retour aux offres</button>
        <button class="btn secondary" type="button" @click="seDeconnecter">Se déconnecter</button>
      </div>
    </header>

    <main class="contenu">
      <div class="entete">
        <h1>Détails du contrat / Négociation</h1>
        <p>Vérifiez les informations de votre contrat et proposez une modification de salaire ou refusez le contrat d'essai.</p>
      </div>

      <p v-if="chargement" class="message">Chargement...</p>
      <p v-else-if="erreur" class="message erreur">{{ erreur }}</p>

      <template v-else>
        <section class="carte">
          <h2>Informations sur le poste</h2>
          <div class="grille">
            <div><span class="label">Offre</span><span>{{ donnees.candidature?.offre }}</span></div>
            <div><span class="label">Poste</span><span>{{ donnees.candidature?.poste }}</span></div>
            <div><span class="label">Département</span><span>{{ donnees.candidature?.departement }}</span></div>
            <div><span class="label">Type de contrat</span><span>{{ donnees.candidature?.type_contrat }}</span></div>
            <div><span class="label">Date de prise de poste</span><span>{{ formaterDate(donnees.candidature?.date_prise_poste) }}</span></div>
            <div><span class="label">Nombre de postes</span><span>{{ donnees.candidature?.nombre_demande }}</span></div>
          </div>
        </section>

        <section class="carte">
          <h2>Rémunération</h2>
          <div class="grille">
            <div><span class="label">Salaire proposé initial</span><span class="montant">{{ argent(donnees.candidature?.salaire_propose) }}</span></div>
            <div><span class="label">Salaire proposé (dernier historique)</span><span class="montant">{{ argent(dernierSalaire) }}</span></div>
          </div>
        </section>

        <section class="carte">
          <h2>Actions</h2>
          <p class="info">Vous pouvez modifier le salaire proposé (cela enverra une demande au RH). Vous pouvez aussi accepter ou refuser le contrat d'essai.</p>
          <div class="actions-ligne">
            <input v-model.number="nouveauSalaire" type="number" min="0" class="input" placeholder="Nouveau salaire proposé" />
            <button class="btn" type="button" :disabled="enAction" @click="modifierSalaire">Modifier le salaire</button>
            <button class="btn" type="button" :disabled="enAction" @click="accepter">Accepter</button>
            <button class="btn danger" type="button" :disabled="enAction" @click="refuser">Refuser le contrat d'essai</button>
          </div>
        </section>

        <section v-if="donnees.historique?.length" class="carte">
          <h2>Historique</h2>
          <ul class="historique">
            <li v-for="h in donnees.historique" :key="h.id">
              <div class="ligne-h">
                <span>{{ formaterDate(h.date_action) }}</span>
                <span>{{ h.statut }}</span>
                <span class="montant">{{ argent(h.salaire_propose) }}</span>
              </div>
              <p class="motif">{{ h.motif }}</p>
            </li>
          </ul>
        </section>
      </template>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { deconnecterCandidat, getCandidatConnecte } from '../services/candidatAuth'

const route = useRoute()
const router = useRouter()
const candidat = ref(getCandidatConnecte())
const donnees = ref({ candidature: null, proposition: null, historique: [] })
const chargement = ref(true)
const erreur = ref('')
const enAction = ref(false)
const nouveauSalaire = ref(null)

const dernierSalaire = computed(() => {
  if (donnees.value.historique?.length) {
    return donnees.value.historique[0].salaire_propose
  }
  return donnees.value.candidature?.salaire_propose
})

function formaterDate(valeur) {
  if (!valeur) return '-'
  const date = new Date(String(valeur).replace(' ', 'T'))
  if (Number.isNaN(date.getTime())) return valeur
  return date.toLocaleString('fr-FR')
}

function argent(v) {
  if (v === null || v === undefined || v === '') return '-'
  return `${Math.round(Number(v)).toLocaleString('fr-FR')} Ar`
}

async function charger() {
  chargement.value = true
  erreur.value = ''
  try {
    if (!candidat.value?.id) {
      router.push({ name: 'candidat-login' })
      return
    }
    const res = await fetch(`/api/candidats/${candidat.value.id}/negociation/${route.params.idCandidature}`, { headers: { Accept: 'application/json' } })
    if (!res.ok) throw new Error('Erreur lors du chargement.')
    donnees.value = await res.json()
    if (!nouveauSalaire.value && donnees.value.candidature?.salaire_propose) {
      nouveauSalaire.value = Number(donnees.value.candidature.salaire_propose)
    }
  } catch (e) {
    erreur.value = e.message
  } finally {
    chargement.value = false
  }
}

async function modifierSalaire() {
  if (!nouveauSalaire.value && nouveauSalaire.value !== 0) {
    erreur.value = 'Indiquez un salaire.'
    return
  }
  enAction.value = true
  erreur.value = ''
  try {
    const res = await fetch(`/api/candidats/${candidat.value.id}/negociation/${route.params.idCandidature}/modifier`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ salaire_propose: nouveauSalaire.value }),
    })
    const o = await res.json().catch(() => ({}))
    if (!res.ok) throw new Error(o.message || 'Erreur')
    alert(o.message)
    await charger()
  } catch (e) {
    erreur.value = e.message
  } finally {
    enAction.value = false
  }
}

async function accepter() {
  enAction.value = true
  erreur.value = ''
  try {
    const res = await fetch(`/api/candidats/${candidat.value.id}/negociation/${route.params.idCandidature}/accepter`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
    })
    const o = await res.json().catch(() => ({}))
    if (!res.ok) throw new Error(o.message || 'Erreur')
    alert(o.message)
    router.push({ name: 'candidat-notifications' })
  } catch (e) {
    erreur.value = e.message
  } finally {
    enAction.value = false
  }
}

async function refuser() {
  if (!confirm("Refuser le contrat d'essai ?")) return
  enAction.value = true
  erreur.value = ''
  try {
    const res = await fetch(`/api/candidats/${candidat.value.id}/negociation/${route.params.idCandidature}/refuser`, {
      method: 'POST',
      headers: { Accept: 'application/json' },
    })
    const o = await res.json().catch(() => ({}))
    if (!res.ok) throw new Error(o.message || 'Erreur')
    alert(o.message)
    router.push({ name: 'candidat-notifications' })
  } catch (e) {
    erreur.value = e.message
  } finally {
    enAction.value = false
  }
}

function retourOffres() {
  router.push({ name: 'offres' })
}

function seDeconnecter() {
  deconnecterCandidat()
  router.push({ name: 'candidat-login' })
}

onMounted(charger)
</script>

<style scoped>
.page { min-height: 100vh; background: #f7f9fb; font-family: "Lucida Sans", sans-serif; }
.navbar { display: flex; align-items: center; justify-content: space-between; padding: 14px 32px; background: #fff; box-shadow: 0 4px 12px rgba(15,23,42,0.08); }
.brand { font-weight: 800; color: teal; font-size: 18px; }
.nav-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn { padding: 9px 16px; border-radius: 20px; border: 0; background: teal; color: #fff; cursor: pointer; font-family: inherit; }
.btn.secondary { background: #fff; color: teal; border: 1px solid teal; }
.btn.danger { background: #b42318; }
.contenu { max-width: 980px; margin: 0 auto; padding: 32px 16px 60px; display: flex; flex-direction: column; gap: 18px; }
.entete h1 { margin: 0; color: teal; }
.entete p { margin: 6px 0 0; color: #555; }
.carte { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(15,23,42,0.08); padding: 20px 24px; display: flex; flex-direction: column; gap: 14px; }
.carte h2 { margin: 0; color: #1f2933; }
.grille { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px 18px; }
.grille > div { display: flex; flex-direction: column; gap: 4px; }
.label { color: #747474; font-size: 13px; }
.montant { font-weight: 700; color: teal; }
.actions-ligne { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.input { padding: 9px 12px; border-radius: 10px; border: 1px solid #d7dce3; font-family: inherit; }
.info { margin: 0; color: #555; }
.historique { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
.historique li { border: 1px solid #eef2f7; border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 6px; }
.ligne-h { display: flex; flex-wrap: wrap; gap: 12px; justify-content: space-between; }
.motif { margin: 0; color: #555; }
.message { text-align: center; }
.message.erreur { color: #b42318; }
</style>
