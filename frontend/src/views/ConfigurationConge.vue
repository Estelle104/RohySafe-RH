<script setup>
import { onMounted, ref } from 'vue'
import SidebarMenu from '../components/SidebarMenu.vue'
import { getConfigurations, updateConfiguration } from '../services/conge'

const configurations = ref([])
const loading = ref(true)
const savingId = ref(null)
const error = ref('')
const message = ref('')
const menuOuvert = ref(false)

async function charger() {
  loading.value = true
  error.value = ''
  try {
    const response = await getConfigurations()
    configurations.value = response.configurations.map((configuration) => ({
      ...configuration,
      nombre_jour: String(configuration.nombre_jour),
    }))
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function enregistrer(configuration) {
  error.value = ''
  message.value = ''
  savingId.value = configuration.id
  try {
    await updateConfiguration(configuration.id, Number(configuration.nombre_jour))
    message.value = `La configuration « ${configuration.libelle} » a été mise à jour.`
  } catch (e) {
    error.value = e.message
  } finally {
    savingId.value = null
  }
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
        <h1>Configuration des congés</h1>
        <p class="subtitle">Configurez le nombre de jours acquis par mois pour chaque type de congé.</p>

        <p v-if="message" class="alert success">{{ message }}</p>
        <p v-if="error" class="alert error">{{ error }}</p>

        <section class="card">
          <p v-if="loading" class="empty">Chargement...</p>
          <p v-else-if="!configurations.length" class="empty">Aucune configuration disponible.</p>
          <div v-else class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Type de congé</th>
                  <th>Jours acquis par mois</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="configuration in configurations" :key="configuration.id">
                  <td>{{ configuration.libelle }}</td>
                  <td>
                    <input v-model="configuration.nombre_jour" type="number" min="0" step="0.01"
                      :aria-label="`Nombre de jours pour ${configuration.libelle}`" />
                  </td>
                  <td>
                    <button class="button" type="button" :disabled="savingId === configuration.id"
                      @click="enregistrer(configuration)">
                      {{ savingId === configuration.id ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                  </td>
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
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:1100px;margin:auto;padding:40px 20px}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}.subtitle{color:#667085;font-size:14px}.card{margin-top:22px;padding:22px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:14px}th,td{padding:14px 10px;border-bottom:1px solid #e5e7eb;text-align:left}th{color:#667085;font-size:11px;text-transform:uppercase}input{width:180px;padding:10px;border:1px solid #c8ced8;border-radius:7px;font:inherit}.button{padding:10px 16px;border:0;border-radius:20px;background:teal;color:#fff;font-weight:700;cursor:pointer}.button:disabled{opacity:.5;cursor:wait}.empty{color:#667085;font-size:13px}.alert{padding:12px;border-radius:8px}.success{background:#e8f5e9;color:#137333}.error{background:#fdecec;color:#b42318}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}@media(max-width:600px){input{width:120px}}
</style>
