<script setup>
import { computed, onMounted, ref } from 'vue'
import SidebarMenu from '../components/SidebarMenu.vue'
import { getSoldes } from '../services/soldeConge'

const soldes = ref([])
const loading = ref(true)
const error = ref('')
const menuOuvert = ref(false)
const employeSelectionne = ref(null)
const rechercheEmploye = ref('')
const typeFiltre = ref('')

const typesConges = computed(() => [...new Set(soldes.value.map((solde) => solde.libelle).filter(Boolean))])

const soldesParEmploye = computed(() => {
  const groupes = new Map()
  for (const solde of soldes.value) {
    const nom = `${solde.nom_complet || ''} ${solde.matricule || ''}`.toLowerCase()
    if (rechercheEmploye.value && !nom.includes(rechercheEmploye.value.toLowerCase())) continue
    if (typeFiltre.value && solde.libelle !== typeFiltre.value) continue
    if (!groupes.has(solde.id_employe)) groupes.set(solde.id_employe, { ...solde, details: [] })
    groupes.get(solde.id_employe).details.push(solde)
  }
  return [...groupes.values()].map((employe) => {
    const annuel = employe.details.find((solde) => solde.libelle === 'annuel')
    return annuel ? { ...employe, ...annuel, details: employe.details } : employe
  })
})

async function charger() {
  loading.value = true
  error.value = ''
  try {
    const response = await getSoldes()
    soldes.value = response.soldes || []
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function afficherDetails(employe) {
  employeSelectionne.value = employeSelectionne.value?.id_employe === employe.id_employe ? null : employe
}

onMounted(charger)
</script>

<template>
  <div class="app-layout">
    <SidebarMenu nom="Ressources humaines" role="RH" code-role="RH" initiales="RH"
      :ouvert="menuOuvert" @fermer="menuOuvert = false" />

    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
      <div class="contenu">
        <p class="eyebrow">Ressources humaines</p>
        <h1>Soldes de congés</h1>
        <p class="subtitle">Consultez les droits acquis, les jours pris et le solde disponible de chaque employé.</p>

        <p v-if="error" class="alert error">{{ error }}</p>

        <section class="filtres">
          <label>Rechercher un employé
            <input v-model="rechercheEmploye" type="search" placeholder="Nom ou matricule" />
          </label>
          <label>Type de congé
            <select v-model="typeFiltre">
              <option value="">Tous les types</option>
              <option v-for="type in typesConges" :key="type" :value="type">{{ type }}</option>
            </select>
          </label>
          <button class="reset" type="button" @click="rechercheEmploye = ''; typeFiltre = ''">Réinitialiser</button>
        </section>

        <section class="card">
          <p v-if="loading" class="empty">Chargement...</p>
          <p v-else-if="!soldesParEmploye.length" class="empty">Aucun solde de congé disponible.</p>
          <div v-else class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Employé</th>
                  <th>Date d'embauche</th>
                  <th>Type de congé</th>
                  <th>Période</th>
                  <th>Droits acquis</th>
                  <th>Jours pris</th>
                  <th>Solde restant</th>
                </tr>
              </thead>
              <tbody><template v-for="employe in soldesParEmploye" :key="employe.id_employe">
                <tr class="ligne-employe" :class="{ selectionnee: employeSelectionne?.id_employe === employe.id_employe }"
                  tabindex="0" @click="afficherDetails(employe)" @keydown.enter="afficherDetails(employe)">
                  <td>{{ employe.nom_complet || employe.matricule || employe.id_employe }}</td>
                  <td>{{ employe.date_embauche }}</td>
                  <td>Annuel</td>
                  <td>{{ employe.periode_debut }} – {{ employe.periode_fin }}</td>
                  <td>{{ employe.droits_acquis }} j</td>
                  <td>{{ employe.jours_pris }} j</td>
                  <td class="solde">{{ employe.solde_restant }} j</td>
                </tr>
                <tr v-if="employeSelectionne?.id_employe === employe.id_employe" class="details-row">
                  <td colspan="7"><div class="details"><strong>Détail des congés de {{ employe.nom_complet || employe.matricule }}</strong>
                    <div class="details-grid"><div v-for="detail in employe.details" :key="detail.id" class="detail-item">
                      <span>{{ detail.libelle }}</span><span>{{ detail.droits_acquis }} acquis · {{ detail.jours_pris }} pris · {{ detail.solde_restant }} restant</span>
                    </div></div>
                  </div></td>
                </tr>
              </template></tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:1200px;margin:auto;padding:40px 20px}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}.subtitle{color:#667085;font-size:14px}.filtres{display:flex;align-items:end;gap:14px;margin-top:22px;padding:16px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.filtres label{display:flex;flex-direction:column;gap:7px;color:#667085;font-size:12px;font-weight:700}.filtres input,.filtres select{min-width:190px;padding:10px;border:1px solid #c8ced8;border-radius:7px;background:#fff;font:inherit;font-weight:400}.reset{padding:10px 16px;border:0;border-radius:20px;background:#eef2f4;color:#344054;font-weight:700;cursor:pointer}.card{margin-top:22px;padding:22px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:13px 10px;border-bottom:1px solid #e5e7eb;text-align:left;white-space:nowrap}th{color:#667085;font-size:11px;text-transform:uppercase}.ligne-employe{cursor:pointer}.ligne-employe:hover,.ligne-employe.selectionnee{background:#f0fafa}.details-row td{padding:0;background:#f8fafb}.details{padding:16px 20px;white-space:normal}.details-grid{display:grid;gap:8px;margin-top:12px}.detail-item{display:flex;justify-content:space-between;gap:20px;padding:9px 0;border-bottom:1px solid #e5e7eb;font-size:12px}.detail-item span:first-child{font-weight:700;text-transform:capitalize}.solde{color:teal;font-weight:700}.empty{color:#667085;font-size:13px}.alert{padding:12px;border-radius:8px}.error{background:#fdecec;color:#b42318}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}@media(max-width:600px){.contenu{padding:20px 12px}.card{padding:12px}.filtres{align-items:stretch;flex-direction:column}.filtres input,.filtres select{min-width:0}.detail-item{align-items:flex-start;flex-direction:column}}
</style>
