<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import PageAvecMenu from '../../components/PageAvecMenu.vue'
// Composants déjà présents dans le projet : on les réutilise
import FiltreStatuts from '../../components/FiltreStatuts.vue'
import CompteurNotifications from '../../components/CompteurNotifications.vue'
import { getHistoriqueInfoEmp, urlCopie } from '../../services/infoEmployee'

const route = useRoute()
const idEmploye = route.params.id
const lignes = ref([])
const erreur = ref('')
const chargement = ref(true)

// Filtres
const recherche = ref('')
const statutPro = ref('Tous')       // valeur choisie dans FiltreStatuts
const filtreMatrimonial = ref('')   // libellé ('' = tous)
const dateDebut = ref('')           // format AAAA-MM-JJ (champ date)
const dateFin = ref('')

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

// Les choix des filtres viennent des lignes elles-mêmes : pas d'appel API en plus
const statutsProDisponibles = computed(() => [
  'Tous',
  ...new Set(lignes.value.map((l) => l.statut_professionnelle).filter(Boolean))
])
const statutsMatrimoniauxDisponibles = computed(() => [
  ...new Set(lignes.value.map((l) => l.statut_matrimoniale).filter(Boolean))
])

// La liste affichée : tous les filtres s'appliquent les uns après les autres
const lignesFiltrees = computed(() => {
  const q = recherche.value.trim().toLowerCase()
  return lignes.value.filter((l) => {
    if (q && !(l.residence ?? '').toLowerCase().includes(q)) return false
    if (statutPro.value !== 'Tous' && l.statut_professionnelle !== statutPro.value) return false
    if (filtreMatrimonial.value && l.statut_matrimoniale !== filtreMatrimonial.value) return false
    // "2026-11-08 17:30:00" -> "2026-11-08" : comparaison de textes AAAA-MM-JJ, fiable pour les dates
    const jour = String(l.date ?? '').slice(0, 10)
    if (dateDebut.value && jour < dateDebut.value) return false
    if (dateFin.value && jour > dateFin.value) return false
    return true
  })
})

function reinitialiser() {
  recherche.value = ''
  statutPro.value = 'Tous'
  filtreMatrimonial.value = ''
  dateDebut.value = ''
  dateFin.value = ''
}
</script>

<template>
  <PageAvecMenu
    large
    eyebrow="Ressources humaines"
    titre="Historique des modifications"
    sous-titre="Anciennes valeurs de la fiche, de la plus récente à la plus ancienne."
  >
    <RouterLink :to="`/backoffice/rh/employes/${idEmploye}`" class="retour">← Retour à la fiche</RouterLink>

    <p v-if="chargement" class="message">Chargement de l'historique…</p>
    <p v-else-if="erreur" class="message erreur">{{ erreur }}</p>
    <p v-else-if="!lignes.length" class="message">Aucune modification enregistrée.</p>

    <div v-else class="historique-page">
      <section class="bloc zone-filtres">
        <FiltreStatuts :statuts="statutsProDisponibles" :actif="statutPro" @choisir="statutPro = $event" />

        <div class="champs">
          <label>Recherche dans la résidence
            <input v-model="recherche" placeholder="Ex. Mandrimena">
          </label>
          <label>Statut matrimonial
            <select v-model="filtreMatrimonial">
              <option value="">Tous</option>
              <option v-for="s in statutsMatrimoniauxDisponibles" :key="s" :value="s">{{ s }}</option>
            </select>
          </label>
          <label>Du
            <input v-model="dateDebut" type="date">
          </label>
          <label>Au
            <input v-model="dateFin" type="date">
          </label>
          <button class="btn secondaire" type="button" @click="reinitialiser">Réinitialiser</button>
        </div>
      </section>

      <CompteurNotifications
        :nb-notifications="lignes.length"
        :nb-filtres="lignesFiltrees.length"
        libelle="Modifications"
      />

      <p v-if="!lignesFiltrees.length" class="message">Aucune modification ne correspond aux filtres.</p>

      <section v-else class="bloc table-wrap">
        <table>
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
            <tr v-for="l in lignesFiltrees" :key="l.id">
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
      </section>
    </div>
  </PageAvecMenu>
</template>

<style scoped>
.retour {
  display: inline-block;
  margin-bottom: 16px;
  color: teal;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
}

.retour:hover {
  text-decoration: underline;
}

.historique-page {
  display: grid;
  gap: 16px;
}

.bloc {
  padding: 24px;
  border: 1px solid #d8dddd;
  border-radius: 16px;
  background: #fff;
}

/* Nom volontairement différent de « filtres » : FiltreStatuts utilise déjà cette classe */
.zone-filtres {
  display: grid;
  gap: 16px;
}

.champs {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 12px 16px;
}

label {
  display: grid;
  gap: 6px;
  color: #344054;
  font-size: 13px;
  font-weight: 700;
}

input,
select {
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 9px 11px;
  font: inherit;
  font-weight: 400;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

th,
td {
  padding: 10px;
  border-bottom: 1px solid #edf0f0;
  text-align: left;
  vertical-align: top;
}

th {
  color: #687575;
  font-size: 12px;
}

td a {
  color: teal;
  font-weight: 700;
}

.btn {
  padding: 8px 16px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #fff;
  color: #344054;
  font: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.btn:hover {
  background: #f1f3f6;
}

.message {
  margin: 0 0 16px;
  padding: 18px;
  border-radius: 10px;
  background: #fff;
  color: #667085;
  font-size: 14px;
}

.erreur {
  background: #fdecec;
  color: #b42318;
}
</style>