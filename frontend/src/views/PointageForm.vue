<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import SidebarMenu from '../components/SidebarMenu.vue'
import { createPointage, getPointageOptions } from '../services/pointage'

const router = useRouter()
const formulaire = ref({ id_employe: '', date_heure: '', id_type_pointage: '' })
const saving = ref(false)
const error = ref('')
const menuOuvert = ref(false)
const employes = ref([])
const types = ref([])
const rechercheEmploye = ref('')
const employesFiltres = computed(() => employes.value.filter((employe) => {
  const recherche = rechercheEmploye.value.trim().toLowerCase()
  if (!recherche) return true
  return `${employe.nom_complet} ${employe.matricule}`.toLowerCase().includes(recherche)
}))

function choisirEmploye(employe) {
  formulaire.value.id_employe = employe.id
  rechercheEmploye.value = `${employe.nom_complet} (${employe.matricule})`
}

getPointageOptions().then((options) => { employes.value = options.employes || []; types.value = options.types || [] }).catch((e) => { error.value = e.message })

async function enregistrer() {
  saving.value = true
  error.value = ''
  try {
    await createPointage({ ...formulaire.value, date_heure: formulaire.value.date_heure.replace('T', ' '), id_employe: Number(formulaire.value.id_employe), id_type_pointage: Number(formulaire.value.id_type_pointage) })
    await router.push('/backoffice/rh/pointages')
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="app-layout"><SidebarMenu nom="Ressources humaines" role="RH" code-role="RH" initiales="RH" :ouvert="menuOuvert" @fermer="menuOuvert = false" />
    <main class="page"><button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button><div class="contenu">
      <p class="eyebrow">Ressources humaines</p><h1>Nouveau pointage</h1><p class="subtitle">Saisissez manuellement un pointage.</p><p v-if="error" class="alert error">{{ error }}</p>
      <section class="card"><form @submit.prevent="enregistrer"><label class="recherche-label">Employé<input v-model="rechercheEmploye" type="search" placeholder="Rechercher un employé" required /><span v-if="rechercheEmploye && !formulaire.id_employe" class="suggestions"><button v-for="employe in employesFiltres" :key="employe.id" type="button" @click="choisirEmploye(employe)">{{ employe.nom_complet }} ({{ employe.matricule }})</button><small v-if="!employesFiltres.length">Aucun employé trouvé.</small></span></label><label>Date et heure<input v-model="formulaire.date_heure" type="datetime-local" required /></label><label>Type de pointage<select v-model="formulaire.id_type_pointage" required><option value="" disabled>Choisir un type</option><option v-for="type in types" :key="type.id" :value="type.id">{{ type.libelle }}</option></select></label><div class="actions"><RouterLink class="cancel" to="/backoffice/rh/pointages">Annuler</RouterLink><button class="button" type="submit" :disabled="saving || !formulaire.id_employe">{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</button></div></form></section>
    </div></main>
  </div>
</template>

<style scoped>
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:800px;margin:auto;padding:40px 20px}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}.subtitle{color:#667085;font-size:14px}.card{margin-top:22px;padding:22px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}form{display:grid;gap:16px}label{display:flex;position:relative;flex-direction:column;gap:7px;color:#344054;font-size:13px;font-weight:700}input,select{padding:10px;border:1px solid #c8ced8;border-radius:7px;background:#fff;font:inherit;font-weight:400}.recherche-label{min-width:230px}.suggestions{position:absolute;top:62px;z-index:2;display:grid;width:100%;max-height:180px;overflow:auto;padding:4px;background:#fff;border:1px solid #c8ced8;border-radius:7px;box-shadow:0 4px 12px #0002}.suggestions button{padding:8px;border:0;background:#fff;text-align:left;cursor:pointer}.suggestions button:hover{background:#eef7f7}.suggestions small{padding:8px;color:#667085}.actions{display:flex;justify-content:flex-end;gap:12px;align-items:center}.button,.cancel{padding:10px 16px;border:0;border-radius:20px;background:teal;color:#fff;font-weight:700;text-decoration:none;cursor:pointer}.button:disabled{opacity:.5}.cancel{background:#eef2f4;color:#344054}.alert{padding:12px;border-radius:8px}.error{background:#fdecec;color:#b42318}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}
</style>
