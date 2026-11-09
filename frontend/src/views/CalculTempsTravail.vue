<script setup>
import { onMounted, ref } from 'vue'
import SidebarMenu from '../components/SidebarMenu.vue'
import { getPointageOptions } from '../services/pointage'
import { getTempsTravail, recalculerTempsTravail } from '../services/tempsTravail'

const employes = ref([])
const resultats = ref([])
const filtres = ref({ idEmploye: '', dateDebut: '', dateFin: '' })
const loading = ref(false)
const error = ref('')
const menuOuvert = ref(false)

function heures(minutes) {
  const valeur = Number(minutes || 0)
  return `${Math.floor(valeur / 60)} h ${String(valeur % 60).padStart(2, '0')}`
}

async function charger() {
  loading.value = true
  error.value = ''
  try {
    await recalculerTempsTravail(filtres.value)
    const response = await getTempsTravail(filtres.value)
    resultats.value = response.temps_travail || []
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  try { employes.value = (await getPointageOptions()).employes || [] } catch (e) { error.value = e.message }
  await charger()
})
</script>

<template>
  <div class="app-layout">
    <SidebarMenu nom="Ressources humaines" role="RH" code-role="RH" initiales="RH" :ouvert="menuOuvert" @fermer="menuOuvert = false" />
    <main class="page"><button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
      <div class="contenu"><p class="eyebrow">Ressources humaines</p><h1>Calcul du temps de travail</h1><p class="subtitle">Consultez le temps prévu, le temps travaillé, les retards et les heures supplémentaires.</p>
        <p v-if="error" class="alert error">{{ error }}</p>
        <section class="filtres"><label>Employé<select v-model="filtres.idEmploye"><option value="">Tous les employés</option><option v-for="employe in employes" :key="employe.id" :value="employe.id">{{ employe.nom_complet }} ({{ employe.matricule }})</option></select></label><label>Du<input v-model="filtres.dateDebut" type="date" /></label><label>Au<input v-model="filtres.dateFin" type="date" /></label><button class="button" type="button" :disabled="loading" @click="charger">Rechercher</button></section>
        <section class="card"><p v-if="loading" class="empty">Chargement...</p><p v-else-if="!resultats.length" class="empty">Aucun résultat disponible.</p><div v-else class="table-wrap"><table><thead><tr><th>Employé</th><th>Date</th><th>Heures prévues</th><th>Heures travaillées</th><th>Retard</th><th>Départ anticipé</th><th>Heures supplémentaires</th></tr></thead><tbody><tr v-for="ligne in resultats" :key="ligne.id"><td>{{ ligne.nom_complet || ligne.matricule }}</td><td>{{ ligne.date_jour }}</td><td>{{ heures(ligne.minutes_prevues) }}</td><td>{{ heures(ligne.minutes_travaillees) }}</td><td>{{ heures(ligne.minutes_retard) }}</td><td>{{ heures(ligne.minutes_depart_anticipe) }}</td><td class="hs">{{ heures(ligne.minutes_hs) }}</td></tr></tbody></table></div></section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:1250px;margin:auto;padding:40px 20px}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}.subtitle{color:#667085;font-size:14px}.filtres{display:flex;align-items:end;gap:14px;margin-top:22px;padding:16px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.filtres label{display:flex;flex-direction:column;gap:7px;color:#667085;font-size:12px;font-weight:700}.filtres input,.filtres select{min-width:170px;padding:10px;border:1px solid #c8ced8;border-radius:7px;background:#fff;font:inherit}.button{padding:10px 16px;border:0;border-radius:20px;background:teal;color:#fff;font-weight:700;cursor:pointer}.button:disabled{opacity:.5}.card{margin-top:22px;padding:22px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:13px 10px;border-bottom:1px solid #e5e7eb;text-align:left;white-space:nowrap}th{color:#667085;font-size:11px;text-transform:uppercase}.hs{color:teal;font-weight:700}.empty{color:#667085;font-size:13px}.alert{padding:12px;border-radius:8px}.error{background:#fdecec;color:#b42318}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}@media(max-width:760px){.filtres{align-items:stretch;flex-direction:column}}
</style>
