<script setup>
import { onMounted, ref } from 'vue'
import SidebarMenu from '../components/SidebarMenu.vue'
import { detecterAnomaliesApi, getAnomalies, traiterAnomalie } from '../services/anomaliePointage'

const anomalies = ref([])
const filtres = ref({ idEmploye: '', dateDebut: '', dateFin: '' })
const loading = ref(false)
const traitementId = ref(null)
const error = ref('')
const message = ref('')
const menuOuvert = ref(false)

async function charger() {
  loading.value = true; error.value = ''; message.value = ''
  try { await detecterAnomaliesApi(filtres.value); anomalies.value = (await getAnomalies(filtres.value)).anomalies || [] } catch (e) { error.value = e.message } finally { loading.value = false }
}

async function traiter(anomalie, statut) {
  const commentaire = window.prompt('Commentaire de traitement', anomalie.commentaire || '')
  if (commentaire === null) return
  traitementId.value = anomalie.id; error.value = ''
  try { await traiterAnomalie(anomalie.id, { id_statut: statut, commentaire }); message.value = 'Anomalie traitée.'; await charger() } catch (e) { error.value = e.message } finally { traitementId.value = null }
}

onMounted(charger)
</script>

<template>
  <div class="app-layout"><SidebarMenu nom="Ressources humaines" role="RH" code-role="RH" initiales="RH" :ouvert="menuOuvert" @fermer="menuOuvert = false" />
    <main class="page"><button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button><div class="contenu">
      <p class="eyebrow">Ressources humaines</p><h1>Anomalies de pointage</h1><p class="subtitle">Consultez, justifiez et corrigez les anomalies détectées.</p>
      <p v-if="error" class="alert error">{{ error }}</p><p v-if="message" class="alert success">{{ message }}</p>
      <section class="filtres"><label>Employé (ID)<input v-model="filtres.idEmploye" type="number" min="1" /></label><label>Du<input v-model="filtres.dateDebut" type="date" /></label><label>Au<input v-model="filtres.dateFin" type="date" /></label><button class="button" type="button" :disabled="loading" @click="charger">Rechercher</button></section>
      <section class="card"><p v-if="loading" class="empty">Chargement...</p><p v-else-if="!anomalies.length" class="empty">Aucune anomalie trouvée.</p><div v-else class="table-wrap"><table><thead><tr><th>Employé</th><th>Date</th><th>Anomalie</th><th>Statut</th><th>Commentaire</th><th>Actions</th></tr></thead><tbody><tr v-for="anomalie in anomalies" :key="anomalie.id"><td>{{ anomalie.nom_complet || anomalie.matricule || anomalie.id_employe }}</td><td>{{ anomalie.date_jour }}</td><td>{{ anomalie.type_anomalie || anomalie.libelle }}</td><td>{{ anomalie.statut || anomalie.statut_libelle }}</td><td>{{ anomalie.commentaire || '—' }}</td><td class="actions"><button type="button" :disabled="traitementId === anomalie.id" @click="traiter(anomalie, 'JUSTIFIEE')">Justifier</button><button type="button" :disabled="traitementId === anomalie.id" @click="traiter(anomalie, 'CORRIGEE')">Corriger</button></td></tr></tbody></table></div></section>
    </div></main>
  </div>
</template>

<style scoped>
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:1250px;margin:auto;padding:40px 20px}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}.subtitle{color:#667085;font-size:14px}.filtres{display:flex;align-items:end;gap:14px;margin-top:22px;padding:16px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.filtres label{display:flex;flex-direction:column;gap:7px;color:#667085;font-size:12px;font-weight:700}.filtres input{min-width:150px;padding:10px;border:1px solid #c8ced8;border-radius:7px;font:inherit}.button{padding:10px 16px;border:0;border-radius:20px;background:teal;color:#fff;font-weight:700;cursor:pointer}.button:disabled{opacity:.5}.card{margin-top:22px;padding:22px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:13px 10px;border-bottom:1px solid #e5e7eb;text-align:left;white-space:nowrap}th{color:#667085;font-size:11px;text-transform:uppercase}.actions{display:flex;gap:8px}.actions button{padding:7px 10px;border:0;border-radius:6px;background:#eef2f4;color:#344054;cursor:pointer}.actions button:last-child{background:teal;color:#fff}.actions button:disabled{opacity:.5}.empty{color:#667085;font-size:13px}.alert{padding:12px;border-radius:8px}.error{background:#fdecec;color:#b42318}.success{background:#e8f5e9;color:#137333}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}@media(max-width:760px){.filtres{align-items:stretch;flex-direction:column}}
</style>
