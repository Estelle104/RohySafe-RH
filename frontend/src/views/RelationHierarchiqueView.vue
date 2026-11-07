<script setup>
import { onMounted, ref } from 'vue'
import SidebarMenu from '../components/SidebarMenu.vue'
import { closeRelation, createRelation, getEmployes, getRelations } from '../services/relationHierarchique'

const employes = ref([])
const relations = ref([])
const formulaire = ref({ id_superieur: '', id_subordonne: '', date_debut: new Date().toISOString().slice(0, 10) })
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const message = ref('')
const menuOuvert = ref(false)

async function charger() {
  loading.value = true
  try {
    const [employesResponse, relationsResponse] = await Promise.all([getEmployes(), getRelations(false)])
    employes.value = employesResponse.employes
    relations.value = relationsResponse.relations
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function enregistrer() {
  error.value = ''
  message.value = ''
  saving.value = true
  try {
    await createRelation(formulaire.value)
    message.value = 'La relation hiérarchique a été enregistrée.'
    formulaire.value = { id_superieur: '', id_subordonne: '', date_debut: new Date().toISOString().slice(0, 10) }
    await charger()
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

async function cloturer(relation) {
  const dateFin = window.prompt('Date de fin (AAAA-MM-JJ)', new Date().toISOString().slice(0, 10))
  if (!dateFin) return
  error.value = ''
  try {
    await closeRelation(relation.id, dateFin)
    message.value = 'La relation hiérarchique a été clôturée.'
    await charger()
  } catch (e) {
    error.value = e.message
  }
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
        <h1>Relations hiérarchiques</h1>
        <p class="subtitle">Affectez le supérieur hiérarchique d’un employé et conservez l’historique des affectations.</p>
        <p v-if="message" class="alert success">{{ message }}</p>
        <p v-if="error" class="alert error">{{ error }}</p>

        <section class="card">
          <h2>Nouvelle affectation</h2>
          <form class="form" @submit.prevent="enregistrer">
            <label>Supérieur hiérarchique
              <select v-model="formulaire.id_superieur" required>
                <option value="" disabled>Choisir un employé</option>
                <option v-for="employe in employes" :key="employe.id" :value="employe.id">{{ employe.nom_complet }} ({{ employe.matricule }})</option>
              </select>
            </label>
            <label>Employé subordonné
              <select v-model="formulaire.id_subordonne" required>
                <option value="" disabled>Choisir un employé</option>
                <option v-for="employe in employes" :key="employe.id" :value="employe.id">{{ employe.nom_complet }} ({{ employe.matricule }})</option>
              </select>
            </label>
            <label>Date de début<input v-model="formulaire.date_debut" type="date" required></label>
            <button class="button" type="submit" :disabled="saving">{{ saving ? 'Enregistrement...' : 'Affecter' }}</button>
          </form>
        </section>

        <section class="card">
          <div class="section-header"><h2>Historique des relations</h2><RouterLink to="/backoffice/rh/organigramme">Voir l’organigramme →</RouterLink></div>
          <p v-if="loading" class="empty">Chargement...</p>
          <p v-else-if="!relations.length" class="empty">Aucune relation enregistrée.</p>
          <div v-else class="table-wrap">
            <table><thead><tr><th>Supérieur</th><th>Subordonné</th><th>Du</th><th>Au</th><th>Action</th></tr></thead>
              <tbody><tr v-for="relation in relations" :key="relation.id"><td>{{ relation.superieur }}</td><td>{{ relation.subordonne }}</td><td>{{ relation.date_debut }}</td><td>{{ relation.date_fin || 'En cours' }}</td><td><button v-if="!relation.date_fin" class="link-button" @click="cloturer(relation)">Clôturer</button><span v-else>—</span></td></tr></tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:1100px;margin:auto;padding:40px 20px}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}.subtitle{color:#667085;font-size:14px}.card{margin-top:22px;padding:22px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.card h2{margin:0 0 18px;font-size:18px}.form{display:grid;grid-template-columns:repeat(3,1fr) auto;gap:14px;align-items:end}label{display:flex;flex-direction:column;gap:7px;font-size:12px;font-weight:700}select,input{padding:10px;border:1px solid #c8ced8;border-radius:7px;background:#fff;font:inherit;font-weight:400}.button{padding:11px 20px;border:0;border-radius:20px;background:teal;color:#fff;font-weight:700}.button:disabled{opacity:.5}.section-header{display:flex;justify-content:space-between;align-items:center}.section-header a{color:teal;font-size:13px;font-weight:700;text-decoration:none}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:13px 10px;border-bottom:1px solid #e5e7eb;text-align:left}th{color:#667085;font-size:11px;text-transform:uppercase}.link-button{border:0;background:none;color:#b42318;cursor:pointer;font-weight:700}.empty{color:#667085;font-size:13px}.alert{padding:12px;border-radius:8px}.success{background:#e8f5e9;color:#137333}.error{background:#fdecec;color:#b42318}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}@media(max-width:760px){.form{grid-template-columns:1fr}.section-header{align-items:flex-start;gap:10px;flex-direction:column}}
</style>
