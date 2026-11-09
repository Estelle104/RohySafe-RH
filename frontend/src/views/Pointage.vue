<script setup>
import { computed, onMounted, ref } from 'vue'
import SidebarMenu from '../components/SidebarMenu.vue'
import { getPointageOptions, getPointages } from '../services/pointage'

const pointages = ref([])
const filtres = ref({ idEmploye: '', dateDebut: '', dateFin: '' })
const loading = ref(false)
const error = ref('')
const menuOuvert = ref(false)
const employes = ref([])
const rechercheEmploye = ref('')

const employesFiltres = computed(() => employes.value.filter((employe) => {
  const recherche = rechercheEmploye.value.trim().toLowerCase()
  if (!recherche) return true
  return `${employe.nom_complet} ${employe.matricule}`.toLowerCase().includes(recherche)
}))

function choisirEmploye(employe) {
  filtres.value.idEmploye = employe.id
  rechercheEmploye.value = `${employe.nom_complet} (${employe.matricule})`
}

async function charger() {
  loading.value = true
  error.value = ''
  try {
    const response = await getPointages(filtres.value)
    pointages.value = response.pointages || []
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function chargerOptions() {
  try { employes.value = (await getPointageOptions()).employes || [] } catch (e) { error.value = e.message }
}

onMounted(async () => { await chargerOptions(); await charger() })
</script>

<template>
  <div class="app-layout">
    <SidebarMenu nom="Ressources humaines" role="RH" code-role="RH" initiales="RH"
      :ouvert="menuOuvert" @fermer="menuOuvert = false" />
    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
      <div class="contenu">
        <p class="eyebrow">Ressources humaines</p>
        <div class="titre-ligne"><div><h1>Pointages</h1><p class="subtitle">Consultez les pointages par employé et par période.</p></div><RouterLink class="button" to="/backoffice/rh/pointages/nouveau">Nouveau pointage</RouterLink></div>
        <p v-if="error" class="alert error">{{ error }}</p>
        <section class="filtres">
          <label class="recherche-label">Employé
            <input v-model="rechercheEmploye" type="search" placeholder="Rechercher un employé" @focus="filtres.idEmploye = ''" />
            <span v-if="rechercheEmploye && !filtres.idEmploye" class="suggestions"><button v-for="employe in employesFiltres" :key="employe.id" type="button" @click="choisirEmploye(employe)">{{ employe.nom_complet }} ({{ employe.matricule }})</button><small v-if="!employesFiltres.length">Aucun employé trouvé.</small></span>
          </label>
          <label>Du<input v-model="filtres.dateDebut" type="date" /></label>
          <label>Au<input v-model="filtres.dateFin" type="date" /></label>
          <button class="button" type="button" :disabled="loading" @click="charger">Rechercher</button>
        </section>
        <section class="card">
          <p v-if="loading" class="empty">Chargement...</p>
          <p v-else-if="!pointages.length" class="empty">Aucun pointage trouvé.</p>
          <div v-else class="table-wrap"><table><thead><tr><th>Employé</th><th>Date et heure</th><th>Type</th><th>Saisi par</th></tr></thead>
            <tbody><tr v-for="pointage in pointages" :key="pointage.id"><td>{{ pointage.nom_complet || pointage.matricule || pointage.id_employe }}</td><td>{{ pointage.date_heure }}</td><td>{{ pointage.type_pointage }}</td><td>{{ pointage.saisi_par || '—' }}</td></tr></tbody>
          </table></div>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:1100px;margin:auto;padding:40px 20px}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}.subtitle{color:#667085;font-size:14px}.titre-ligne{display:flex;align-items:center;justify-content:space-between;gap:16px}.button{padding:10px 16px;border:0;border-radius:20px;background:teal;color:#fff;font-weight:700;text-decoration:none;cursor:pointer}.button:disabled{opacity:.5}.filtres{display:flex;align-items:end;gap:14px;margin-top:22px;padding:16px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.filtres label{display:flex;position:relative;flex-direction:column;gap:7px;color:#667085;font-size:12px;font-weight:700}.filtres input{min-width:180px;padding:10px;border:1px solid #c8ced8;border-radius:7px;background:#fff;font:inherit}.recherche-label{min-width:230px}.suggestions{position:absolute;top:62px;z-index:2;display:grid;width:100%;max-height:180px;overflow:auto;padding:4px;background:#fff;border:1px solid #c8ced8;border-radius:7px;box-shadow:0 4px 12px #0002}.suggestions button{padding:8px;border:0;background:#fff;text-align:left;cursor:pointer}.suggestions button:hover{background:#eef7f7}.suggestions small{padding:8px;color:#667085}.card{margin-top:22px;padding:22px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:13px 10px;border-bottom:1px solid #e5e7eb;text-align:left;white-space:nowrap}th{color:#667085;font-size:11px;text-transform:uppercase}.empty{color:#667085;font-size:13px}.alert{padding:12px;border-radius:8px}.error{background:#fdecec;color:#b42318}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}@media(max-width:700px){.titre-ligne,.filtres{align-items:stretch;flex-direction:column}}
</style>
