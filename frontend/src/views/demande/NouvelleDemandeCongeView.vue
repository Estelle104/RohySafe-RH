<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import SidebarMenu from '../../components/SidebarMenu.vue'
import { creerDemandeConge, getEmployesConge, getTypesConge } from '../../services/demandeConge'
import { suggererRemplacants } from '../../services/remplacement'

const router = useRouter()
const employes = ref([])
const types = ref([])
const suggestions = ref([])
const form = ref({ id_employe: '', id_type_conge: '', date_debut: '', date_fin: '', id_employe_remplacant: '', heure_debut: '08:00', heure_fin: '17:00' })
const recherche = ref('')
const loading = ref(false)
const loadingSug = ref(false)
const erreur = ref('')
const message = ref('')
const menuOuvert = ref(false)

const employesFiltres = computed(() => {
  const terme = recherche.value.trim().toLowerCase()
  if (!terme) return employes.value
  return employes.value.filter((e) =>
    `${e.nom_complet || ''} ${e.matricule || ''}`.toLowerCase().includes(terme))
})

async function charger() {
  try {
    const [employesResponse, typesResponse] = await Promise.all([getEmployesConge(), getTypesConge()])
    employes.value = employesResponse.employes || []
    types.value = typesResponse.types || []
  } catch (e) {
    erreur.value = e.message
  }
}

let sugTimer
async function chargerSuggestions() {
  const { id_employe, date_debut, date_fin } = form.value
  if (!id_employe || !date_debut || !date_fin || date_fin < date_debut) { suggestions.value = []; return }
  loadingSug.value = true
  try {
    const res = await suggererRemplacants({ idEmployeAbsent: id_employe, dateDebut: date_debut, dateFin: date_fin })
    suggestions.value = res.remplacants || res.employes || []
  } catch { suggestions.value = [] }
  finally { loadingSug.value = false }
}
watch(() => [form.value.id_employe, form.value.date_debut, form.value.date_fin], () => {
  clearTimeout(sugTimer)
  sugTimer = setTimeout(chargerSuggestions, 250)
})

async function enregistrer() {
  erreur.value = ''
  message.value = ''
  loading.value = true
  try {
    const result = await creerDemandeConge({
      ...form.value,
      id_employe: Number(form.value.id_employe),
      id_type_conge: Number(form.value.id_type_conge),
      id_employe_remplacant: form.value.id_employe_remplacant ? Number(form.value.id_employe_remplacant) : 0,
    })
    message.value = `${result.message} Vérification solde effectuée ; remplaçant affecté selon disponibilité/poste/compétences.`
    form.value = { id_employe: '', id_type_conge: '', date_debut: '', date_fin: '', id_employe_remplacant: '', heure_debut: '08:00', heure_fin: '17:00' }
    recherche.value = ''
    suggestions.value = []
  } catch (e) {
    erreur.value = e.message
  } finally {
    loading.value = false
  }
}

onMounted(charger)
</script>

<template>
  <div class="app-layout">
    <SidebarMenu nom="Manager" role="Manager" code-role="Manager" initiales="M" :ouvert="menuOuvert" @fermer="menuOuvert = false" />
    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
      <section class="contenu">
        <p class="eyebrow">Gestion des congés</p>
        <h1>Nouvelle demande de congé</h1>
        <p v-if="message" class="success">{{ message }}</p>
        <p v-if="erreur" class="error">{{ erreur }}</p>
        <form class="card" @submit.prevent="enregistrer">
          <label>Employé
            <input v-model="recherche" placeholder="Nom, prénom ou matricule">
            <select v-model="form.id_employe" required>
              <option value="">Sélectionner un employé</option>
              <option v-for="e in employesFiltres" :key="e.id" :value="e.id">{{ e.nom_complet }} — {{ e.matricule }}</option>
            </select>
          </label>
          <label>Type de congé
            <select v-model="form.id_type_conge" required>
              <option value="">Sélectionner un type</option>
              <option v-for="type in types" :key="type.id" :value="type.id">{{ type.libelle }}</option>
            </select>
          </label>
          <label>Date de début<input v-model="form.date_debut" type="date" required></label>
          <label>Date de fin<input v-model="form.date_fin" type="date" required></label>
          <label>Remplaçant (facultatif)
            <select v-model="form.id_employe_remplacant">
              <option value="">Proposition automatique (poste + compétences + disponibilité)</option>
              <option v-for="e in employes.filter(item => Number(item.id) !== Number(form.id_employe))" :key="`all-${e.id}`" :value="e.id">{{ e.nom_complet }} — {{ e.matricule }}</option>
            </select>
          </label>
          <div v-if="loadingSug" class="hint">Recherche des remplaçants disponibles...</div>
          <div v-else-if="suggestions.length" class="suggestions">
            <strong>Remplaçants suggérés (disponibles) :</strong>
            <button v-for="s in suggestions.slice(0,6)" :key="`s-${s.id}`" type="button" class="sug" @click="form.id_employe_remplacant = String(s.id)">
              {{ s.nom_complet }} — {{ s.poste || 'poste' }} <span v-if="s.score_remplacement!=null">(score {{ s.score_remplacement }}, compétences communes {{ s.competences_communes || 0 }})</span>
            </button>
          </div>
          <label>Heure de début du remplacement<input v-model="form.heure_debut" type="time" required></label>
          <label>Heure de fin du remplacement<input v-model="form.heure_fin" type="time" required></label>
          <button type="submit" :disabled="loading">{{ loading ? 'Enregistrement...' : 'Enregistrer la demande' }}</button>
        </form>
      </section>
    </main>
  </div>
</template>

<style scoped>
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6}.page{min-width:0}.contenu{max-width:760px;margin:auto;padding:40px 20px;font-family:Inter,system-ui,sans-serif}.burger{display:none;margin:16px;padding:8px 16px}.eyebrow{color:#667085;text-transform:uppercase;font-size:12px;font-weight:700}h1{color:teal}.card{display:grid;gap:16px;background:#fff;padding:24px;border-radius:10px;box-shadow:0 3px 12px #0001}label{display:grid;gap:6px;color:#344054;font-weight:600}input,select{padding:10px;border:1px solid #c8ced8;border-radius:7px;background:#fff}button{padding:11px;border:0;border-radius:7px;background:teal;color:#fff;font-weight:700}.hint{color:#667085;font-size:13px}.suggestions{display:flex;flex-direction:column;gap:6px;padding:10px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;font-size:13px}.sug{padding:8px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;color:#344054;font:inherit;text-align:left;cursor:pointer}.sug:hover{border-color:teal}.success{padding:12px;background:#e8f7ee;color:#087443}.error{padding:12px;background:#fdecec;color:#b42318}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}
</style>
