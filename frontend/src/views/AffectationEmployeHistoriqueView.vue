<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SidebarMenu from '../components/SidebarMenu.vue'

const route = useRoute()
const router = useRouter()
const employe = ref(null)
const affectations = ref([])
const societes = ref([])
const provinces = ref([])
const societeProvinces = ref([])
const formulaire = ref({ id_societe: '', id_province: '', date_debut: new Date().toISOString().slice(0, 10) })
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const message = ref('')
const menuOuvert = ref(false)

const provincesFiltrees = computed(() => {
  const idSociete = String(formulaire.value.id_societe || '')
  if (!idSociete) return provinces.value
  const ids = new Set(societeProvinces.value.filter((sp) => String(sp.id_societe) === idSociete).map((sp) => String(sp.id_province)))
  return provinces.value.filter((p) => ids.has(String(p.id)))
})
const societesFiltrees = computed(() => {
  const idProvince = String(formulaire.value.id_province || '')
  if (!idProvince) return societes.value
  const ids = new Set(societeProvinces.value.filter((sp) => String(sp.id_province) === idProvince).map((sp) => String(sp.id_societe)))
  return societes.value.filter((s) => ids.has(String(s.id)))
})
watch(() => formulaire.value.id_societe, () => {
  const ok = provincesFiltrees.value.some((p) => String(p.id) === String(formulaire.value.id_province || ''))
  if (!ok) formulaire.value.id_province = ''
})
watch(() => formulaire.value.id_province, () => {
  const ok = societesFiltrees.value.some((s) => String(s.id) === String(formulaire.value.id_societe || ''))
  if (!ok) formulaire.value.id_societe = ''
})

async function api(path, options = {}) {
  const res = await fetch(path, { headers: { Accept: 'application/json', 'Content-Type': 'application/json' }, credentials: 'include', ...options })
  const data = await res.json().catch(() => ({}))
  if (!res.ok) throw new Error(data.message || 'Erreur serveur.')
  return data
}

async function charger() {
  loading.value = true
  error.value = ''
  try {
    const [hist, opts] = await Promise.all([
      api(`/api/rh/affectations/employes/${route.params.id}`),
      api('/api/rh/affectations/options'),
    ])
    employe.value = hist.employe
    affectations.value = hist.affectations || []
    societes.value = opts.societes || []
    provinces.value = opts.provinces || []
    societeProvinces.value = opts.societe_provinces || []
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
    await api('/api/rh/affectations', {
      method: 'POST',
      body: JSON.stringify({ id_employe: route.params.id, ...formulaire.value }),
    })
    message.value = "Nouvelle affectation enregistrée. L'affectation précédente a été clôturée (date_fin renseignée) si nécessaire."
    formulaire.value = { id_societe: '', id_province: '', date_debut: new Date().toISOString().slice(0, 10) }
    await charger()
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

function periode(a) {
  return `${a.date_debut} → ${a.date_fin || 'En cours'}`
}

onMounted(charger)
</script>

<template>
  <div class="app-layout">
    <SidebarMenu nom="Ressources humaines" role="RH" code-role="RH" initiales="RH" :ouvert="menuOuvert" @fermer="menuOuvert = false" />
    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
      <div class="contenu">
        <button class="retour" type="button" @click="router.push({ name: 'affectation-employes-module' })">← Retour à la liste</button>
        <p class="eyebrow">Affectations — Employé</p>
        <h1 v-if="employe">{{ employe.nom_complet }} <span class="matricule">({{ employe.matricule }})</span></h1>
        <h1 v-else>Historique des affectations</h1>
        <p class="subtitle">Affectations successives avec périodes. Une nouvelle affectation clôture la précédente en renseignant <code>date_fin</code>.</p>
        <p v-if="message" class="alert success">{{ message }}</p>
        <p v-if="error" class="alert error">{{ error }}</p>

        <section class="card">
          <h2>Nouvelle affectation (changement)</h2>
          <form class="form" @submit.prevent="enregistrer">
            <label>Société
              <select v-model="formulaire.id_societe" required>
                <option value="" disabled>Choisir une société</option>
                <option v-for="s in societesFiltrees" :key="s.id" :value="s.id">{{ s.libelle }}</option>
              </select>
            </label>
            <label>Province
              <select v-model="formulaire.id_province" required>
                <option value="" disabled>Choisir une province</option>
                <option v-for="p in provincesFiltrees" :key="p.id" :value="p.id">{{ p.libelle }}</option>
              </select>
            </label>
            <label>Date de début<input v-model="formulaire.date_debut" type="date" required /></label>
            <button class="button" type="submit" :disabled="saving">{{ saving ? 'Enregistrement...' : 'Affecter' }}</button>
          </form>
        </section>

        <section class="card">
          <h2>Affectations successives</h2>
          <p v-if="loading" class="empty">Chargement...</p>
          <p v-else-if="!affectations.length" class="empty">Aucune affectation enregistrée pour cet employé.</p>
          <div v-else class="table-wrap">
            <table>
              <thead><tr><th>Société</th><th>Province</th><th>Date début</th><th>Date fin</th><th>Période</th></tr></thead>
              <tbody>
                <tr v-for="a in affectations" :key="a.id">
                  <td>{{ a.societe }}</td>
                  <td>{{ a.province }}</td>
                  <td>{{ a.date_debut }}</td>
                  <td>{{ a.date_fin || 'En cours' }}</td>
                  <td>{{ periode(a) }}</td>
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
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:1100px;margin:auto;padding:40px 20px}.retour{margin-bottom:14px;border:1px solid #cbd5e1;border-radius:18px;padding:8px 14px;background:#fff;color:#344054;font:inherit;font-size:13px;font-weight:700;cursor:pointer}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}.matricule{color:#667085;font-size:18px;font-weight:600}code{background:#f1f3f6;padding:1px 5px;border-radius:4px;font-size:12px}.subtitle{color:#667085;font-size:14px}.card{margin-top:22px;padding:22px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.card h2{margin:0 0 18px;font-size:18px}.form{display:grid;grid-template-columns:repeat(3,1fr) auto;gap:14px;align-items:end}label{display:flex;flex-direction:column;gap:7px;font-size:12px;font-weight:700}select,input{padding:10px;border:1px solid #c8ced8;border-radius:7px;background:#fff;font:inherit;font-weight:400}.button{padding:11px 20px;border:0;border-radius:20px;background:teal;color:#fff;font-weight:700}.button:disabled{opacity:.5}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:13px 10px;border-bottom:1px solid #e5e7eb;text-align:left}th{color:#667085;font-size:11px;text-transform:uppercase}.empty{color:#667085;font-size:13px}.alert{padding:12px;border-radius:8px;margin-top:14px}.success{background:#e8f5e9;color:#137333}.error{background:#fdecec;color:#b42318}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}@media(max-width:760px){.form{grid-template-columns:1fr}}
</style>
