<script setup>
import { computed, onMounted, ref } from 'vue'
import SidebarMenu from '../components/SidebarMenu.vue'
import { getDemandesConge, getDemandeConge, validerDemandeConge, refuserDemandeConge } from '../services/demandesConge'

const demandes = ref([])
const loading = ref(true)
const error = ref('')
const message = ref('')
const menuOuvert = ref(false)
const filtreStatut = ref('')
const actionId = ref(null)
const detail = ref(null)

const statuts = ['', 'En attente', 'Validée', 'Refusée', 'Annulée']
const lignes = computed(() => demandes.value.filter(d => !filtreStatut.value || normaliser(d.statut) === normaliser(filtreStatut.value)))

function normaliser(s) { return String(s || '').toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '') }
function estEnAttente(d) { return normaliser(d.statut) === 'en attente' }
function classeStatut(s) {
  const n = normaliser(s)
  if (n === 'validee' || n === 'valide') return 'ok'
  if (n === 'refusee') return 'ko'
  if (n === 'annulee') return 'muted'
  return 'wait'
}

async function charger() {
  loading.value = true; error.value = ''
  try { demandes.value = (await getDemandesConge()).demandes || [] }
  catch (e) { error.value = e.message }
  finally { loading.value = false }
}

async function voir(id) {
  error.value = ''
  try { detail.value = (await getDemandeConge(id)).demande }
  catch (e) { error.value = e.message }
}

async function valider(id) {
  if (!confirm('Valider cette demande de congé ? Le solde sera re-vérifié.')) return
  actionId.value = id; error.value = ''; message.value = ''
  try {
    message.value = (await validerDemandeConge(id)).message || 'Demande validée.'
    if (detail.value?.id === id) await voir(id)
    await charger()
  } catch (e) { error.value = e.message }
  finally { actionId.value = null }
}

async function refuser(id) {
  const motif = prompt('Motif du refus (optionnel) :') ?? undefined
  if (motif === undefined) return
  actionId.value = id; error.value = ''; message.value = ''
  try {
    message.value = (await refuserDemandeConge(id, motif)).message || 'Demande refusée.'
    if (detail.value?.id === id) await voir(id)
    await charger()
  } catch (e) { error.value = e.message }
  finally { actionId.value = null }
}

onMounted(charger)
</script>

<template>
  <div class="app-layout">
    <SidebarMenu nom="Ressources humaines" role="RH" code-role="RH" initiales="RH" :ouvert="menuOuvert" @fermer="menuOuvert = false" />
    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
      <div class="contenu">
        <p class="eyebrow">Ressources humaines</p>
        <h1>Demandes de congés</h1>
        <p class="subtitle">Consultez, validez ou refusez les demandes. Le solde est vérifié avant validation (pas de solde négatif/avance non autorisée).</p>
        <p v-if="message" class="alert success">{{ message }}</p>
        <p v-if="error" class="alert error">{{ error }}</p>

        <section class="card">
          <div class="toolbar">
            <label>Statut
              <select v-model="filtreStatut">
                <option v-for="s in statuts" :key="s || 'tous'" :value="s">{{ s || 'Tous' }}</option>
              </select>
            </label>
            <button class="button secondary" type="button" @click="charger">Actualiser</button>
          </div>
          <p v-if="loading" class="empty">Chargement...</p>
          <p v-else-if="!lignes.length" class="empty">Aucune demande.</p>
          <div v-else class="table-wrap">
            <table>
              <thead><tr><th>Employé</th><th>Type</th><th>Période</th><th>Remplaçant</th><th>Statut</th><th>Historique</th><th>Actions</th></tr></thead>
              <tbody>
                <tr v-for="d in lignes" :key="d.id">
                  <td><strong>{{ d.employe }}</strong><div class="sub">{{ d.matricule }}</div></td>
                  <td>{{ d.type_conge }}</td>
                  <td>{{ d.date_debut }} → {{ d.date_fin }}</td>
                  <td>{{ d.remplacant || '—' }}</td>
                  <td><span class="badge" :class="classeStatut(d.statut)">{{ d.statut }}</span></td>
                  <td>{{ d.nb_historiques || 0 }} entrée(s)</td>
                  <td class="actions">
                    <button class="link" type="button" @click="voir(d.id)">Détails</button>
                    <button v-if="estEnAttente(d)" class="button sm" type="button" :disabled="actionId===d.id" @click="valider(d.id)">Valider</button>
                    <button v-if="estEnAttente(d)" class="button sm danger" type="button" :disabled="actionId===d.id" @click="refuser(d.id)">Refuser</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section v-if="detail" class="card">
          <div class="detail-head">
            <h2>Détail de la demande #{{ detail.id }}</h2>
            <button class="link" type="button" @click="detail=null">Fermer</button>
          </div>
          <p><strong>{{ detail.employe }}</strong> ({{ detail.matricule }}) — {{ detail.type_conge }} — {{ detail.date_debut }} → {{ detail.date_fin }} — <span class="badge" :class="classeStatut(detail.statut)">{{ detail.statut }}</span></p>
          <h3>Historique des changements de statut</h3>
          <ul class="histo">
            <li v-for="h in (detail.historique||[])" :key="h.id"><strong>{{ h.statut }}</strong> — {{ h.date }} <span class="sub">({{ h.utilisateur || 'système' }})</span></li>
          </ul>
          <h3>Remplacements affectés</h3>
          <p v-if="!(detail.remplacements||[]).length" class="sub">Aucun.</p>
          <ul v-else class="histo">
            <li v-for="r in detail.remplacements" :key="r.id">{{ r.remplacant }} ({{ r.matricule }}) — {{ r.date_debut }} → {{ r.date_fin }} <span class="sub">{{ r.commentaire || '' }}</span></li>
          </ul>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:1200px;margin:auto;padding:40px 20px}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}h2{margin:0;font-size:18px}h3{margin:16px 0 8px;font-size:14px}.subtitle{color:#667085;font-size:14px}.card{margin-top:22px;padding:22px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.toolbar{display:flex;gap:12px;align-items:end;margin-bottom:14px;flex-wrap:wrap}label{display:flex;flex-direction:column;gap:6px;font-size:12px;font-weight:700}select{padding:10px;border:1px solid #c8ced8;border-radius:7px;font:inherit}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:12px 10px;border-bottom:1px solid #e5e7eb;text-align:left;vertical-align:top}th{color:#667085;font-size:11px;text-transform:uppercase}.sub{color:#667085;font-size:12px}.actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.button{padding:10px 16px;border:0;border-radius:20px;background:teal;color:#fff;font-weight:700;cursor:pointer}.button.secondary{background:#344054}.button.danger{background:#b42318}.button.sm{padding:6px 10px;border-radius:14px;font-size:12px}.button:disabled{opacity:.5}.link{border:0;background:none;color:teal;cursor:pointer;font-weight:700;padding:0}.badge{display:inline-block;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:700}.badge.wait{background:#fff7e6;color:#b54708}.badge.ok{background:#e8f5e9;color:#137333}.badge.ko{background:#fdecec;color:#b42318}.badge.muted{background:#f2f4f7;color:#667085}.detail-head{display:flex;justify-content:space-between;align-items:center;gap:12px}.histo{margin:0;padding-left:18px;color:#344054;font-size:13px}.empty{color:#667085;font-size:13px}.alert{padding:12px;border-radius:8px;margin-top:14px}.success{background:#e8f5e9;color:#137333}.error{background:#fdecec;color:#b42318}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}
</style>
