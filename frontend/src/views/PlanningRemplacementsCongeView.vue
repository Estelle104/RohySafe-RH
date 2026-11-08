<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import SidebarMenu from '../components/SidebarMenu.vue'
import { getCongesPrevus, getEmployesEligiblesConges } from '../services/demandesConge'
import { affecterRemplacant, suggererRemplacants, getEmployesDisponiblesRemplacement } from '../services/remplacement'

const menuOuvert = ref(false)
const loading = ref(true)
const error = ref('')
const message = ref('')
const conges = ref([])
const eligibles = ref([])
const suggestions = ref([])
const congeSelectionne = ref(null)
const form = ref({ date_debut: '', date_fin: '', id_employe_remplacant: '', commentaire: '' })
const busy = ref(false)

const absentsActuels = computed(() => conges.value.filter(c => ['en attente','validée','validee','valide'].includes(normaliser(c.statut))))
function normaliser(s){ return String(s||'').toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu,'') }

async function charger() {
  loading.value = true; error.value = ''
  try {
    const [prev, elig] = await Promise.all([getCongesPrevus(), getEmployesEligiblesConges()])
    conges.value = prev.conges || prev.demandes || []
    eligibles.value = elig.employes || []
  } catch (e) { error.value = e.message }
  finally { loading.value = false }
}

function choisirConge(c) {
  congeSelectionne.value = c
  form.value.date_debut = c.date_debut
  form.value.date_fin = c.date_fin
  form.value.id_employe_remplacant = ''
  form.value.commentaire = ''
  suggestions.value = []
  rechercher()
}

async function rechercher() {
  if (!congeSelectionne.value || !form.value.date_debut || !form.value.date_fin) return
  error.value = ''
  try {
    const res = await suggererRemplacants({
      idEmployeAbsent: congeSelectionne.value.id_employe,
      dateDebut: form.value.date_debut,
      dateFin: form.value.date_fin,
    })
    suggestions.value = res.remplacants || res.employes || []
  } catch (e) {
    // fallback disponibilité simple
    try {
      const res = await getEmployesDisponiblesRemplacement({
        dateDebut: form.value.date_debut, dateFin: form.value.date_fin,
        idEmployeAbsent: congeSelectionne.value.id_employe, idConge: congeSelectionne.value.id,
      })
      suggestions.value = res.employes || []
    } catch (e2) { error.value = e2.message }
  }
}

async function affecter() {
  if (!congeSelectionne.value || !form.value.id_employe_remplacant) return
  busy.value = true; error.value = ''; message.value = ''
  try {
    await affecterRemplacant({
      id_conge: congeSelectionne.value.id,
      id_employe_remplacant: form.value.id_employe_remplacant,
      date_debut: form.value.date_debut,
      date_fin: form.value.date_fin,
      commentaire: form.value.commentaire,
    })
    message.value = 'Remplaçant affecté et période enregistrée.'
    await charger()
    await rechercher()
  } catch (e) { error.value = e.message }
  finally { busy.value = false }
}

watch(() => [form.value.date_debut, form.value.date_fin], () => { if (congeSelectionne.value) rechercher() })

onMounted(charger)
</script>

<template>
  <div class="app-layout">
    <SidebarMenu nom="Ressources humaines" role="RH" code-role="RH" initiales="RH" :ouvert="menuOuvert" @fermer="menuOuvert = false" />
    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
      <div class="contenu">
        <p class="eyebrow">Ressources humaines</p>
        <h1>Congés prévus & remplacements</h1>
        <p class="subtitle">Consultez les congés prévus, les employés éligibles au congé, recherchez les remplaçants disponibles (poste, compétences, période) et affectez-les.</p>
        <p v-if="message" class="alert success">{{ message }}</p>
        <p v-if="error" class="alert error">{{ error }}</p>

        <div class="grid">
          <section class="card">
            <h2>Employés pouvant partir en congé (solde &gt; 0)</h2>
            <p v-if="loading" class="empty">Chargement...</p>
            <p v-else-if="!eligibles.length" class="empty">Aucun solde disponible trouvé.</p>
            <div v-else class="table-wrap small">
              <table>
                <thead><tr><th>Employé</th><th>Type</th><th>Solde restant</th><th>Période</th></tr></thead>
                <tbody>
                  <tr v-for="e in eligibles" :key="`${e.id_employe}-${e.id_type_conge}-${e.periode_debut}`">
                    <td>{{ e.nom_complet }} <span class="sub">({{ e.matricule }})</span></td>
                    <td>{{ e.type_conge }}</td>
                    <td><strong>{{ Number(e.solde_restant).toFixed(2) }}</strong> j</td>
                    <td class="sub">{{ e.periode_debut }} → {{ e.periode_fin }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <section class="card">
            <h2>Congés prévus (sélectionner pour affecter un remplaçant)</h2>
            <p v-if="loading" class="empty">Chargement...</p>
            <p v-else-if="!absentsActuels.length" class="empty">Aucun congé prévu.</p>
            <div v-else class="list">
              <button v-for="c in absentsActuels" :key="c.id" type="button" class="pick" :class="{active: congeSelectionne?.id===c.id}" @click="choisirConge(c)">
                <strong>{{ c.employe }}</strong> — {{ c.type_conge }}<br>
                <span class="sub">{{ c.date_debut }} → {{ c.date_fin }} · {{ c.statut }} · Remplaçant : {{ c.remplacant || 'à affecter' }}</span>
              </button>
            </div>
          </section>
        </div>

        <section v-if="congeSelectionne" class="card">
          <h2>Remplaçants disponibles pour {{ congeSelectionne.employe }}</h2>
          <p class="sub">Recherche automatique selon poste, compétences et disponibilité (pas de congé/absence/remplacement sur la période).</p>
          <div class="toolbar">
            <label>Date début <input v-model="form.date_debut" type="date" /></label>
            <label>Date fin <input v-model="form.date_fin" type="date" /></label>
            <button class="button secondary" type="button" @click="rechercher">Rechercher</button>
          </div>
          <p v-if="!suggestions.length" class="empty">Aucun remplaçant disponible/suggéré pour cette période.</p>
          <div v-else class="table-wrap">
            <table>
              <thead><tr><th></th><th>Employé</th><th>Poste/Département</th><th>Score</th><th>Compétences communes</th><th>Même poste</th></tr></thead>
              <tbody>
                <tr v-for="r in suggestions" :key="r.id">
                  <td><input type="radio" name="remp" :value="String(r.id)" v-model="form.id_employe_remplacant" /></td>
                  <td>{{ r.nom_complet }} <span class="sub">({{ r.matricule }})</span></td>
                  <td>{{ r.poste || '—' }} <span class="sub">{{ r.departement ? `· ${r.departement}` : '' }}</span></td>
                  <td>{{ r.score_remplacement ?? '—' }}</td>
                  <td>{{ r.competences_communes ?? '—' }}</td>
                  <td>{{ r.meme_poste ? 'Oui' : (r.meme_poste === false ? 'Non' : '—') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <label class="full">Commentaire <input v-model="form.commentaire" type="text" placeholder="Optionnel" /></label>
          <button class="button" type="button" :disabled="busy || !form.id_employe_remplacant" @click="affecter">{{ busy ? 'Enregistrement...' : 'Affecter le remplaçant (enregistrer période)' }}</button>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:1250px;margin:auto;padding:40px 20px}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}h2{margin:0 0 14px;font-size:18px}.subtitle{color:#667085;font-size:14px}.grid{display:grid;grid-template-columns:1.2fr 1fr;gap:18px}.card{margin-top:22px;padding:22px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.table-wrap{overflow:auto}.small table{font-size:12px}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}th{color:#667085;font-size:11px;text-transform:uppercase}.sub{color:#667085;font-size:12px}.list{display:flex;flex-direction:column;gap:8px;max-height:360px;overflow:auto}.pick{border:1px solid #d0d5dd;border-radius:8px;background:#fff;padding:10px;text-align:left;cursor:pointer;font:inherit}.pick.active{border-color:teal;background:#f0fdfa}.toolbar{display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin-bottom:12px}label{display:flex;flex-direction:column;gap:6px;font-size:12px;font-weight:700}input[type=date],input[type=text]{padding:10px;border:1px solid #c8ced8;border-radius:7px;font:inherit}.full{margin-top:12px;max-width:520px}.button{margin-top:14px;padding:11px 20px;border:0;border-radius:20px;background:teal;color:#fff;font-weight:700;cursor:pointer}.button.secondary{margin-top:0;background:#344054}.button:disabled{opacity:.5}.empty{color:#667085;font-size:13px}.alert{padding:12px;border-radius:8px;margin-top:14px}.success{background:#e8f5e9;color:#137333}.error{background:#fdecec;color:#b42318}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}.grid{grid-template-columns:1fr}}
</style>
