<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import SidebarMenu from '../components/SidebarMenu.vue'

const router = useRouter()
const employes = ref([])
const loading = ref(true)
const error = ref('')
const menuOuvert = ref(false)
const recherche = ref('')

async function api(path) {
  const res = await fetch(path, { headers: { Accept: 'application/json' }, credentials: 'include' })
  const data = await res.json().catch(() => ({}))
  if (!res.ok) throw new Error(data.message || 'Erreur serveur.')
  return data
}

async function charger() {
  loading.value = true
  error.value = ''
  try {
    const data = await api('/api/rh/affectations/employes')
    employes.value = data.employes || []
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

function filtrer() {
  const q = recherche.value.trim().toLowerCase()
  if (!q) return employes.value
  return employes.value.filter((e) =>
    `${e.nom_complet} ${e.matricule} ${e.affectation_courante?.societe || ''} ${e.affectation_courante?.province || ''}`.toLowerCase().includes(q),
  )
}

onMounted(charger)
</script>

<template>
  <div class="app-layout">
    <SidebarMenu nom="Ressources humaines" role="RH" code-role="RH" initiales="RH" :ouvert="menuOuvert" @fermer="menuOuvert = false" />
    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
      <div class="contenu">
        <div class="entete-ligne">
          <div>
            <p class="eyebrow">Ressources humaines</p>
            <h1>Affectations — Liste des employés</h1>
            <p class="subtitle">Module affectation : consultez l’affectation courante et l’historique de chaque employé.</p>
          </div>
          <button class="button" type="button" @click="router.push({ name: 'affectation-employe' })">Nouvelle affectation</button>
        </div>
        <p v-if="error" class="alert error">{{ error }}</p>

        <section class="card">
          <div class="barre">
            <input v-model="recherche" class="search" type="search" placeholder="Rechercher (nom, matricule, société, province)..." />
          </div>
          <p v-if="loading" class="empty">Chargement...</p>
          <p v-else-if="!filtrer().length" class="empty">Aucun employé.</p>
          <div v-else class="table-wrap">
            <table>
              <thead><tr><th>Employé</th><th>Matricule</th><th>Affectation courante</th><th>Période</th><th></th></tr></thead>
              <tbody>
                <tr v-for="e in filtrer()" :key="e.id">
                  <td>{{ e.nom_complet }}</td>
                  <td>{{ e.matricule }}</td>
                  <td>
                    <template v-if="e.affectation_courante">
                      {{ e.affectation_courante.societe }} — {{ e.affectation_courante.province }}
                    </template>
                    <span v-else class="muted">Non affecté</span>
                  </td>
                  <td>
                    <template v-if="e.affectation_courante">
                      {{ e.affectation_courante.date_debut }} → {{ e.affectation_courante.date_fin || 'En cours' }}
                    </template>
                    <span v-else>—</span>
                  </td>
                  <td><button class="link-button" type="button" @click="router.push({ name: 'affectation-employe-historique', params: { id: e.id } })">Historique</button></td>
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
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:1100px;margin:auto;padding:40px 20px}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}.subtitle{color:#667085;font-size:14px}.entete-ligne{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap}.button{padding:11px 20px;border:0;border-radius:20px;background:teal;color:#fff;font-weight:700;cursor:pointer}.card{margin-top:22px;padding:22px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.barre{margin-bottom:14px}.search{width:100%;max-width:420px;padding:10px 12px;border:1px solid #c8ced8;border-radius:7px;font:inherit}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}th,td{padding:13px 10px;border-bottom:1px solid #e5e7eb;text-align:left}th{color:#667085;font-size:11px;text-transform:uppercase}.link-button{border:0;background:none;color:teal;cursor:pointer;font-weight:700}.empty{color:#667085;font-size:13px}.muted{color:#667085}.alert{padding:12px;border-radius:8px;margin-top:14px}.error{background:#fdecec;color:#b42318}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}
</style>
