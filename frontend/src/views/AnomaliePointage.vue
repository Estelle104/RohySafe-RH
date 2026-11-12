<script setup>
import { onMounted, ref } from 'vue'
import SidebarMenu from '../components/SidebarMenu.vue'
import { corrigerAnomalie, detecterAnomaliesApi, getAnomalies, getStatutsAnomalies, traiterAnomalie } from '../services/anomaliePointage'
import { getPointageOptions } from '../services/pointage'

const anomalies = ref([])
const types = ref([])
const statuts = ref([])
const selection = ref(null)
const formulaire = ref({ date_heure: '', id_type_pointage: '', motif: '' })
const filtres = ref({ idEmploye: '', dateDebut: '', dateFin: '', statut: '' })
const loading = ref(false)
const error = ref('')
const message = ref('')
const menuOuvert = ref(false)

async function charger() {
  loading.value = true
  error.value = ''
  try {
    await detecterAnomaliesApi(filtres.value)
    anomalies.value = (await getAnomalies(filtres.value)).anomalies || []
  } catch (e) { error.value = e.message } finally { loading.value = false }
}

function ouvrirModification(anomalie) {
  selection.value = anomalie
  formulaire.value = {
    date_heure: anomalie.date_heure ? anomalie.date_heure.replace(' ', 'T').slice(0, 16) : '',
    id_type_pointage: anomalie.id_type_pointage || '',
    motif: anomalie.commentaire || '',
  }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function corriger() {
  try {
    await corrigerAnomalie(selection.value.id, {
      ...formulaire.value,
      date_heure: formulaire.value.date_heure.replace('T', ' '),
    })
    message.value = 'Pointage corrigé et historisé.'
    selection.value = null
    await charger()
  } catch (e) { error.value = e.message }
}

async function traiter(anomalie, statut) {
  const commentaire = window.prompt('Commentaire de traitement', anomalie.commentaire || '')
  if (commentaire === null) return
  try {
    await traiterAnomalie(anomalie.id, { id_statut: statut, commentaire })
    await charger()
  } catch (e) { error.value = e.message }
}

onMounted(async () => {
  try {
    types.value = (await getPointageOptions()).types || []
    statuts.value = (await getStatutsAnomalies()).statuts || []
  } catch (e) { error.value = e.message }
  await charger()
})
</script>

<template>
  <div class="app-layout">
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
        <header class="page-header">
          <p class="eyebrow">Ressources humaines</p>
          <h1>Anomalies de pointage</h1>
          <p class="subtitle">Consultez, corrigez et historisez les anomalies détectées.</p>
        </header>

        <p v-if="error" class="alert error">{{ error }}</p>
        <p v-if="message" class="alert success">{{ message }}</p>

        <section class="card filters">
          <label>
            Employé (ID)
            <input v-model="filtres.idEmploye" type="number" min="1" placeholder="Tous" />
          </label>
          <label>Du<input v-model="filtres.dateDebut" type="date" /></label>
          <label>Au<input v-model="filtres.dateFin" type="date" /></label>
          <label>
            Statut
            <select v-model="filtres.statut">
              <option value="">Tous</option>
              <option v-for="statut in statuts" :key="statut.id" :value="statut.libelle">
                {{ statut.libelle }}
              </option>
            </select>
          </label>
          <button class="primary-button" type="button" :disabled="loading" @click="charger">{{ loading ? 'Recherche...' : 'Rechercher' }}</button>
        </section>

        <section v-if="selection" class="card correction-card">
          <div class="card-header">
            <div>
              <p class="eyebrow">Correction</p>
              <h2>Modifier le pointage</h2>
            </div>
            <button class="close-button" type="button" aria-label="Fermer" @click="selection = null">×</button>
          </div>

          <form class="correction-form" @submit.prevent="corriger">
            <label>Date et heure<input v-model="formulaire.date_heure" type="datetime-local" required /></label>
            <label>
              Type de pointage
              <select v-model="formulaire.id_type_pointage" required>
                <option v-for="type in types" :key="type.id" :value="type.id">{{ type.libelle }}</option>
              </select>
            </label>
            <label class="full-width">Motif<textarea v-model="formulaire.motif" rows="3" required /></label>
            <div class="form-actions">
              <button class="secondary-button" type="button" @click="selection = null">Annuler</button>
              <button class="primary-button" type="submit">Enregistrer la correction</button>
            </div>
          </form>
        </section>

        <section class="card table-card">
          <div class="card-header">
            <div>
              <h2>Liste des anomalies</h2>
              <p class="card-help">{{ anomalies.length }} anomalie(s) détectée(s)</p>
            </div>
          </div>
          <p v-if="loading" class="empty">Chargement...</p>
          <p v-else-if="!anomalies.length" class="empty">Aucune anomalie trouvée.</p>
          <div v-else class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Employé</th>
                  <th>Date</th>
                  <th>Anomalie</th>
                  <th>Statut</th>
                  <th>Commentaire</th>
                  <th class="actions-column">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="anomalie in anomalies" :key="anomalie.id">
                  <td class="employee-cell">{{ anomalie.nom_complet || anomalie.matricule }}</td>
                  <td>{{ anomalie.date_jour }}</td>
                  <td><span class="anomaly-label">{{ anomalie.type_anomalie }}</span></td>
                  <td><span class="status" :class="anomalie.statut?.toLowerCase()">{{ anomalie.statut }}</span></td>
                  <td class="comment-cell">{{ anomalie.commentaire || '—' }}</td>
                  <td class="actions">
                    <button v-if="anomalie.id_pointage" class="edit-button" type="button" @click="ouvrirModification(anomalie)">
                      Modifier
                    </button>
                    <button class="justify-button" type="button" @click="traiter(anomalie, 'JUSTIFIEE')">
                      Justifier
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.app-layout {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  min-height: 100vh;
  background: #f1f3f6;
  color: #222;
  font-family: Inter, system-ui, sans-serif;
}

.page { min-width: 0; }
.burger { display: none; }
.contenu { max-width: 1250px; margin: 0 auto; padding: 40px 24px; }
.page-header { display: block; }
.eyebrow { margin: 0 0 6px; color: #667085; font-size: 12px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
h1 { margin: 0; color: #008b8b; font-size: 30px; }
.subtitle { margin: 8px 0 0; color: #667085; }
.card { margin-top: 22px; padding: 24px; background: #fff; border-radius: 12px; box-shadow: 0 3px 14px #00000014; }
.filters { display: flex; align-items: end; gap: 16px; }
.filters label, .correction-form label { display: flex; flex: 1; flex-direction: column; gap: 7px; color: #344054; font-size: 12px; font-weight: 700; }
.filters input, .correction-form input, .correction-form select, .correction-form textarea { padding: 11px; border: 1px solid #c8ced8; border-radius: 8px; background: #fff; font: inherit; font-weight: 400; }
.primary-button, .secondary-button, .edit-button, .justify-button, .close-button { border: 0; border-radius: 8px; font: inherit; font-weight: 700; cursor: pointer; }
.primary-button { padding: 11px 18px; background: #008b8b; color: #fff; }
.primary-button:disabled { opacity: .55; cursor: wait; }
.secondary-button { padding: 11px 18px; background: #eef2f4; color: #344054; }
.correction-card { border-left: 4px solid #008b8b; }
.card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
.card-header h2 { margin: 0; font-size: 19px; }
.card-help { margin: 5px 0 0; color: #667085; font-size: 13px; }
.close-button { width: 30px; height: 30px; background: #eef2f4; color: #344054; font-size: 20px; line-height: 1; }
.correction-form { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 20px; }
.full-width { grid-column: 1 / -1; }
.form-actions { grid-column: 1 / -1; display: flex; justify-content: flex-end; gap: 10px; }
.table-card { overflow: hidden; }
.table-wrap { overflow-x: auto; margin-top: 18px; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th, td { padding: 14px 12px; border-bottom: 1px solid #e7eaee; text-align: left; vertical-align: middle; }
th { color: #667085; font-size: 11px; letter-spacing: .03em; text-transform: uppercase; }
tbody tr:hover { background: #f8fbfb; }
.employee-cell { font-weight: 700; }
.comment-cell { max-width: 220px; overflow: hidden; text-overflow: ellipsis; }
.anomaly-label { color: #b42318; font-weight: 700; }
.status { display: inline-block; padding: 5px 9px; border-radius: 12px; background: #fff4d6; color: #946200; font-size: 11px; font-weight: 700; }
.status.corrigee, .status.justifiee { background: #e8f5e9; color: #137333; }
.actions { display: flex; gap: 7px; }
.edit-button, .justify-button { padding: 8px 10px; }
.edit-button { background: #e7f6f6; color: #007878; }
.justify-button { background: #eef2f4; color: #344054; }
.empty { padding: 18px 0; color: #667085; font-size: 13px; }
.alert { padding: 13px; border-radius: 8px; }
.error { background: #fdecec; color: #b42318; }
.success { background: #e8f5e9; color: #137333; }

@media (max-width: 960px) {
  .app-layout { grid-template-columns: 1fr; }
  .burger { display: block; margin: 16px; padding: 9px 16px; border: 0; border-radius: 10px; background: #fff; color: teal; font-weight: 700; cursor: pointer; }
}

@media (max-width: 760px) {
  .contenu { padding: 24px 14px; }
  .filters { align-items: stretch; flex-direction: column; }
  .correction-form { grid-template-columns: 1fr; }
  .full-width, .form-actions { grid-column: auto; }
  .form-actions { justify-content: stretch; }
  .form-actions button { flex: 1; }
  .actions { align-items: flex-start; flex-direction: column; }
  .actions button { width: 100%; }
}
</style>
