<script setup>
import { computed, onMounted, ref } from 'vue'
import SidebarMenu from '../components/SidebarMenu.vue'
import { getRemplacements } from '../services/remplacement'

const remplacements = ref([])
const loading = ref(true)
const error = ref('')
const recherche = ref('')
const menuOuvert = ref(false)
const lignes = computed(() => remplacements.value.filter((r) =>
  `${r.remplacant || ''} ${r.employe_absent || ''}`.toLowerCase().includes(recherche.value.toLowerCase())))
const total = computed(() => lignes.value.reduce((sum, r) => sum + Number(r.cout || 0), 0).toFixed(2))
async function charger() {
  try { remplacements.value = (await getRemplacements()).remplacements || [] } catch (e) { error.value = e.message } finally { loading.value = false }
}
onMounted(charger)
</script>

<template>
  <div class="app-layout"><SidebarMenu nom="Ressources humaines" role="RH" code-role="RH" initiales="RH" :ouvert="menuOuvert" @fermer="menuOuvert = false" />
    <main class="page"><button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button><div class="contenu">
      <p class="eyebrow">Ressources humaines</p><h1>Coût des remplacements</h1><p class="subtitle">Remplacements effectués et rémunérations correspondantes.</p>
      <p v-if="error" class="alert">{{ error }}</p><input v-model="recherche" class="recherche" type="search" placeholder="Employé ou remplaçant">
      <section class="card"><p v-if="loading">Chargement...</p><p v-else-if="!lignes.length">Aucun remplacement.</p><div v-else class="table-wrap"><table><thead><tr><th>Employé absent</th><th>Remplaçant</th><th>Période</th><th>Heures</th><th>Salaire horaire</th><th>Coût</th></tr></thead><tbody>
        <tr v-for="ligne in lignes" :key="ligne.id"><td>{{ ligne.employe_absent }}</td><td>{{ ligne.remplacant }}</td><td>{{ ligne.date_debut }} – {{ ligne.date_fin }}</td><td>{{ ligne.heures }}</td><td>{{ Number(ligne.salaire_horaire).toFixed(2) }} Ar</td><td>{{ Number(ligne.cout).toFixed(2) }} Ar</td></tr>
        <tr><th colspan="5">Total</th><th>{{ total }} Ar</th></tr></tbody></table></div></section>
    </div></main>
  </div>
</template>

<style scoped>
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:1200px;margin:auto;padding:40px 20px}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}.subtitle{color:#667085;font-size:14px}.recherche{margin:20px 0;padding:10px;border:1px solid #c8ced8;border-radius:7px;min-width:260px}.card{padding:22px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:13px 10px;border-bottom:1px solid #e5e7eb;text-align:left;white-space:nowrap}th{color:#667085;font-size:11px;text-transform:uppercase}.alert{padding:12px;background:#fdecec;color:#b42318;border-radius:8px}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}@media(max-width:600px){.contenu{padding:20px 12px}}
</style>
