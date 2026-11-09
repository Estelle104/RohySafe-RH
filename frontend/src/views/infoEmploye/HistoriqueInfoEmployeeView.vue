<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { getHistoriqueInfoEmp, urlCopie } from '../../services/infoEmployee'

const route = useRoute()
const idEmploye = route.params.id
const lignes = ref([])
const erreur = ref('')
const chargement = ref(true)

onMounted(async () => {
  try {
    lignes.value = await getHistoriqueInfoEmp(idEmploye)
  } catch (e) {
    erreur.value = e.message
  } finally {
    chargement.value = false
  }
})

const fmt = (d) => (d ? new Date(d.replace(' ', 'T')).toLocaleString('fr-FR') : '—')
</script>

<template>
  <div class="histo-page">
    <h1>Historique des modifications</h1>
    <RouterLink :to="`/backoffice/rh/employes/${idEmploye}`">← Retour à la fiche</RouterLink>

    <p v-if="chargement">Chargement…</p>
    <p v-else-if="erreur" class="erreur">{{ erreur }}</p>
    <p v-else-if="!lignes.length">Aucune modification enregistrée.</p>

    <table v-else>
      <thead>
        <tr>
          <th>Date</th>
          <th>Ancienne résidence</th>
          <th>Ancienne copie</th>
          <th>Ancien statut matrimonial</th>
          <th>Ancien statut professionnel</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="l in lignes" :key="l.id">
          <td>{{ fmt(l.date) }}</td>
          <td>{{ l.residence || '—' }}</td>
          <td>
            <a v-if="l.copie" :href="urlCopie(l.copie)" target="_blank">Voir</a>
            <span v-else>—</span>
          </td>
          <td>{{ l.statut_matrimoniale || '—' }}</td>
          <td>{{ l.statut_professionnelle || '—' }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.histo-page { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
th, td { border: 1px solid #ccc; padding: .5rem .7rem; text-align: left; vertical-align: top; }
th { background: #f0f0f0; }
.erreur { color: #b3261e; }
</style>