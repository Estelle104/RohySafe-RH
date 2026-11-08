<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import PageAvecMenu from '../../components/PageAvecMenu.vue'
import { getListeEmployes, getStatuts } from '../../services/infoEmployee'
// Composants déjà présents dans le projet : on les réutilise
import FiltreStatuts from '../../components/FiltreStatuts.vue'
import CompteurNotifications from '../../components/CompteurNotifications.vue'

const employes = ref([])
const statuts = ref({ matrimoniaux: [], professionnels: [] })
const erreur = ref('')
const chargement = ref(true)

// Filtres
const recherche = ref('')
const statutPro = ref('Tous')          // valeur choisie dans FiltreStatuts
const filtreMatrimonial = ref('')      // libellé du statut matrimonial ('' = tous)
const seulementEcheance = ref(false)
const seuilJours = ref(30)

onMounted(async () => {
  try {
    const [liste, st] = await Promise.all([getListeEmployes(), getStatuts()])
    employes.value = liste
    statuts.value = st
  } catch (e) {
    erreur.value = e.message
  } finally {
    chargement.value = false
  }
})

// FiltreStatuts attend un tableau de textes : « Tous » + les libellés de la base
const boutonsStatutPro = computed(() => ['Tous', ...statuts.value.professionnels.map((s) => s.libelle)])

// Échéance "à surveiller" : dépassée, ou dans les `seuilJours` prochains jours
function echeanceProche(emp) {
  if (emp.jours_restants === null || emp.jours_restants === undefined) return false
  return Number(emp.jours_restants) <= Number(seuilJours.value)
}

function echeanceDepassee(emp) {
  return emp.jours_restants !== null && emp.jours_restants !== undefined && Number(emp.jours_restants) < 0
}

function texteEcheance(emp) {
  const j = Number(emp.jours_restants)
  if (j < 0) return `Expiré depuis ${-j} j`
  if (j === 0) return "Expire aujourd'hui"
  return `Dans ${j} j`
}

// La liste affichée : tous les filtres s'appliquent les uns après les autres
const employesFiltres = computed(() => {
  const q = recherche.value.trim().toLowerCase()
  return employes.value.filter((e) => {
    if (q) {
      const texte = `${e.nom} ${e.prenom} ${e.matricule} ${e.email ?? ''}`.toLowerCase()
      if (!texte.includes(q)) return false
    }
    if (statutPro.value !== 'Tous' && e.statut_professionnelle !== statutPro.value) return false
    // On compare les libellés (comme pour le statut professionnel) : c'est le texte déjà affiché dans le tableau
    if (filtreMatrimonial.value && e.statut_matrimoniale !== filtreMatrimonial.value) return false
    if (seulementEcheance.value && !echeanceProche(e)) return false
    return true
  })
})

const nbAlertes = computed(() => employes.value.filter(echeanceProche).length)

function reinitialiser() {
  recherche.value = ''
  statutPro.value = 'Tous'
  filtreMatrimonial.value = ''
  seulementEcheance.value = false
  seuilJours.value = 30
}

const fmt = (d) => (d ? new Date(d).toLocaleDateString('fr-FR') : '—')
</script>

<template>
  <PageAvecMenu
    large
    eyebrow="Ressources humaines"
    titre="Employés"
    sous-titre="Liste des employés, filtres et alertes sur les échéances des justificatifs."
  >
    <p v-if="chargement" class="message">Chargement des employés…</p>
    <p v-else-if="erreur" class="message erreur">{{ erreur }}</p>

    <div v-else class="liste-page">
      <p v-if="nbAlertes" class="resume-alerte">
        {{ nbAlertes }} employé(s) avec un justificatif expiré ou qui expire dans {{ seuilJours }} jours ou moins.
      </p>

      <section class="bloc filtres">
        <FiltreStatuts :statuts="boutonsStatutPro" :actif="statutPro" @choisir="statutPro = $event" />

        <div class="champs">
          <label>Recherche
            <input v-model="recherche" placeholder="Nom, prénom, matricule, email">
          </label>
          <label>Statut matrimonial
            <select v-model="filtreMatrimonial">
              <option value="">Tous</option>
              <option v-for="s in statuts.matrimoniaux" :key="s.id" :value="s.libelle">{{ s.libelle }}</option>
            </select>
          </label>
          <!-- <label>Échéance dans
            <select v-model="seuilJours">
              <option :value="15">15 jours</option>
              <option :value="30">30 jours</option>
              <option :value="60">60 jours</option>
              <option :value="90">90 jours</option>
            </select>
          </label> -->
          <label class="case">
            <input v-model="seulementEcheance" type="checkbox">
            Échéance proche ou dépassée seulement
          </label>
          <button class="btn secondaire" type="button" @click="reinitialiser">Réinitialiser</button>
        </div>
      </section>

      <CompteurNotifications
        :nb-notifications="employes.length"
        :nb-filtres="employesFiltres.length"
        libelle="Employés"
      />

      <p v-if="!employesFiltres.length" class="message">Aucun employé ne correspond aux filtres.</p>

      <section v-else class="bloc table-wrap">
        <table>
          <thead>
            <tr>
              <th>Matricule</th>
              <th>Nom</th>
              <th>Prénom</th>
              <th>Statut matrimonial</th>
              <th>Statut professionnel</th>
              <th>Échéance</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="e in employesFiltres"
              :key="e.id_employe"
              :class="{ alerte: echeanceProche(e), depasse: echeanceDepassee(e) }"
            >
              <td>{{ e.matricule }}</td>
              <td>{{ e.nom }}</td>
              <td>{{ e.prenom }}</td>
              <td>{{ e.statut_matrimoniale || '—' }}</td>
              <td>{{ e.statut_professionnelle || '—' }}</td>
              <td>
                {{ fmt(e.date_echeance) }}
                <span v-if="echeanceProche(e)" class="badge">{{ texteEcheance(e) }}</span>
              </td>
              <td>
                <RouterLink :to="`/backoffice/rh/employes/${e.id_employe}`" class="btn">Voir la fiche</RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  </PageAvecMenu>
</template>

<style scoped>
.liste-page { display: grid; gap: 16px; }
.bloc { background: #fff; border: 1px solid #d8dddd; border-radius: 16px; padding: 24px; }
.filtres { display: grid; gap: 16px; }
.champs { display: flex; flex-wrap: wrap; gap: 12px 16px; align-items: flex-end; }
label { display: grid; gap: 6px; color: #344054; font-size: 13px; font-weight: 700; }
label.case { display: flex; align-items: center; gap: 8px; font-weight: 400; }
input, select { border: 1px solid #cbd5e1; border-radius: 10px; padding: 9px 11px; font: inherit; font-weight: 400; }
input[type='checkbox'] { padding: 0; }

.message { margin: 0; padding: 18px; border-radius: 10px; background: #fff; color: #667085; font-size: 14px; }
.erreur { background: #fdecec; color: #b42318; }
.resume-alerte { margin: 0; padding: 10px 14px; border: 1px solid #b42318; border-radius: 10px; background: #fdecec; color: #b42318; font-size: 13px; font-weight: 700; }

.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
th, td { text-align: left; padding: 10px; border-bottom: 1px solid #edf0f0; }
th { color: #687575; font-size: 12px; }
tr.alerte td { background: #fdecec; color: #b42318; }
tr.depasse td { background: #fbd5d1; font-weight: 700; }

.badge { display: inline-block; margin-left: 8px; padding: 3px 9px; border-radius: 10px; background: #b42318; color: #fff; font-size: 11px; font-weight: 700; }
.btn { display: inline-block; border: 0; border-radius: 10px; padding: 8px 16px; background: teal; color: #fff; font: inherit; font-size: 13px; font-weight: 700; text-decoration: none; white-space: nowrap; cursor: pointer; }
.btn:hover { background: #006666; }
.btn.secondaire { border: 1px solid #cbd5e1; background: #fff; color: #344054; }
.btn.secondaire:hover { background: #f1f3f6; }
</style>