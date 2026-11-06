<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SidebarMenu from '../../components/SidebarMenu.vue'
import { chargerUtilisateurInterne } from '../../services/authInterne'
import {
  assignerEmployesPlanning,
  createPlanningEDT,
  getEmployesDisponibles,
  getEmployesParPlanning,
  getPlanningEDTById,
  getPlanningsEDT,
  planningEstTermine,
  updatePlanningEDT,
} from '../../services/planningService'

const router = useRouter()
const route = useRoute()
const utilisateur = ref(null)
const menuOuvert = ref(false)
const chargement = ref(false)
const soumission = ref(false)
const erreur = ref('')
const success = ref('')
const employesDisponibles = ref([])
const employesSelectionnes = ref([])
const plannings = ref([])
const modeEdition = computed(() => Boolean(route.params?.id))

const formulaire = ref({
  libelle: '',
  date_debut: '',
  date_fin: '',
  jours_semaine: [],
})

const codeRole = computed(() => utilisateur.value?.role ?? 'RH')
const libelleRole = computed(() => (['DG', 'Direction'].includes(codeRole.value) ? 'Direction générale' : codeRole.value || 'RH'))
const nomUtilisateur = computed(() => utilisateur.value?.login ?? 'Ressources humaines')
const initiales = computed(() => {
  if (['DG', 'Direction'].includes(codeRole.value)) return 'DG'
  if (codeRole.value === 'RH') return 'RH'
  return nomUtilisateur.value.slice(0, 2).toUpperCase()
})

const joursDisponibles = [
  { value: 1, label: 'Lundi' },
  { value: 2, label: 'Mardi' },
  { value: 3, label: 'Mercredi' },
  { value: 4, label: 'Jeudi' },
  { value: 5, label: 'Vendredi' },
  { value: 6, label: 'Samedi' },
  { value: 7, label: 'Dimanche' },
]

function toggleJour(jour) {
  const valeurs = new Set(formulaire.value.jours_semaine)
  if (valeurs.has(jour)) {
    valeurs.delete(jour)
  } else {
    valeurs.add(jour)
  }

  formulaire.value.jours_semaine = Array.from(valeurs).sort((a, b) => Number(a) - Number(b))
}

function estJourSelectionne(jour) {
  return formulaire.value.jours_semaine.includes(Number(jour))
}

async function chargerDonnees() {
  chargement.value = true
  erreur.value = ''

  try {
    const [employes, planningsData] = await Promise.all([
      getEmployesDisponibles(),
      getPlanningsEDT(),
    ])

    employesDisponibles.value = employes
    plannings.value = planningsData

    if (modeEdition.value) {
      const id = Number(route.params.id)
      const [planning, employesPlanning] = await Promise.all([
        getPlanningEDTById(id),
        getEmployesParPlanning(id),
      ])

      if (!planning) {
        erreur.value = 'Planning introuvable.'
        return
      }

      const joursSelectionnes = convertirJoursEnNumeros(planning.joursSemaine)
      formulaire.value = {
        libelle: planning.libelle ?? '',
        date_debut: planning.dateDebut ?? '',
        date_fin: planning.dateFin ?? '',
        jours_semaine: joursSelectionnes,
      }

      employesSelectionnes.value = Array.isArray(employesPlanning)
        ? employesPlanning
            .map((employe) => Number(employe.id ?? employe.id_employe ?? 0))
            .filter((value) => Number.isFinite(value) && value > 0)
        : []
    }
  } catch (e) {
    erreur.value = e.message || 'Erreur lors du chargement du formulaire.'
  } finally {
    chargement.value = false
  }
}

function convertirJoursEnNumeros(jours) {
  if (!Array.isArray(jours)) return []

  const mapping = {
    Lundi: 1,
    Mardi: 2,
    Mercredi: 3,
    Jeudi: 4,
    Vendredi: 5,
    Samedi: 6,
    Dimanche: 7,
  }

  return [...new Set(jours.map((jour) => {
    const valeur = String(jour).trim()
    if (!valeur) return null

    if (Number.isFinite(Number(valeur))) {
      return Number(valeur)
    }

    return mapping[valeur] ?? null
  }).filter((jour) => jour !== null))].sort((a, b) => a - b)
}

function validerFormulaire() {
  const dateDebut = formulaire.value.date_debut
  const dateFin = formulaire.value.date_fin

  if (!dateDebut || !dateFin) {
    throw new Error('La date de début et la date de fin sont obligatoires.')
  }

  if (dateFin < dateDebut) {
    throw new Error('La date de fin ne peut pas être avant la date de début.')
  }

  if (modeEdition.value && planningEstTermine(dateFin)) {
    throw new Error('Impossible de modifier un planning dont la date de fin est déjà dépassée.')
  }

  const today = new Date()
  today.setHours(0, 0, 0, 0)

  if (new Date(dateFin) < today) {
    throw new Error('Impossible de créer ou modifier un planning dont la date de fin est déjà dépassée.')
  }

  if (new Date(dateDebut) < today && new Date(dateFin) >= today) {
    // autorisé : le planning est déjà commencé mais il continue jusqu’à aujourd’hui ou plus tard
  }

  if (!formulaire.value.jours_semaine.length) {
    throw new Error('Sélectionnez au moins un jour de semaine.')
  }

  const idsUniques = [...new Set(employesSelectionnes.value.map((id) => Number(id)))]
  if (idsUniques.length !== employesSelectionnes.value.length) {
    throw new Error('Un employé ne peut pas être sélectionné deux fois pour le même planning.')
  }
}

async function enregistrer() {
  soumission.value = true
  erreur.value = ''
  success.value = ''

  try {
    validerFormulaire()

    const payload = {
      libelle: formulaire.value.libelle,
      date_debut: formulaire.value.date_debut,
      date_fin: formulaire.value.date_fin,
      jours_semaine: formulaire.value.jours_semaine,
      id_employes: employesSelectionnes.value,
    }

    if (modeEdition.value) {
      const id = Number(route.params.id)
      await updatePlanningEDT(id, payload)
      success.value = 'Planning mis à jour.'
    } else {
      await createPlanningEDT(payload)
      success.value = 'Planning créé.'
    }

    await router.push('/planning')
  } catch (e) {
    erreur.value = e.message || 'Erreur lors de l’enregistrement du planning.'
  } finally {
    soumission.value = false
  }
}

function selectionnerEmploye(idEmploye) {
  const existant = employesSelectionnes.value.includes(Number(idEmploye))
  if (existant) {
    employesSelectionnes.value = employesSelectionnes.value.filter((id) => id !== Number(idEmploye))
    return
  }

  employesSelectionnes.value = [...employesSelectionnes.value, Number(idEmploye)]
}

watch(() => route.params?.id, () => {
  if (route.params?.id) {
    chargerDonnees()
  }
})

onMounted(async () => {
  utilisateur.value = await chargerUtilisateurInterne()
  await chargerDonnees()
})
</script>

<template>
  <div class="app-layout">
    <SidebarMenu
      :nom="nomUtilisateur"
      :role="libelleRole"
      :code-role="codeRole"
      :initiales="initiales"
      :ouvert="menuOuvert"
      @fermer="menuOuvert = false"
    />

    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>

      <div class="contenu">
        <section class="hero">
          <div>
            <p class="eyebrow">Planning RH</p>
            <h1>{{ modeEdition ? 'Modifier le planning' : 'Créer un planning' }}</h1>
          </div>
          <button class="btn-secondary" type="button" @click="router.push('/planning')">Retour</button>
        </section>

        <section v-if="erreur" class="alert-error">{{ erreur }}</section>
        <section v-if="success" class="alert-success">{{ success }}</section>

        <section v-if="chargement" class="loading-state">Chargement du formulaire…</section>
        <section v-else class="module-card">
          <div class="form-grid">
            <label>
              <span>Libellé</span>
              <input v-model="formulaire.libelle" type="text" placeholder="Ex: Aide de cuisine" />
            </label>

            <label>
              <span>Date de début</span>
              <input v-model="formulaire.date_debut" type="date" />
            </label>

            <label>
              <span>Date de fin</span>
              <input v-model="formulaire.date_fin" type="date" />
            </label>
          </div>

          <div class="jours-block">
            <h2>Jours de la semaine</h2>
            <div class="jours-grid">
              <button
                v-for="jour in joursDisponibles"
                :key="jour.value"
                type="button"
                :class="['jour-btn', { actif: estJourSelectionne(jour.value) }]"
                @click="toggleJour(jour.value)"
              >
                {{ jour.label }}
              </button>
            </div>
          </div>

          <div class="panel-employes">
            <div class="panel-head">
              <h2>Employés affectés</h2>
              <span>{{ employesSelectionnes.length }} sélectionné(s)</span>
            </div>

            <div v-if="employesDisponibles.length" class="employee-list">
              <button
                v-for="employe in employesDisponibles"
                :key="employe.id"
                type="button"
                :class="['employee-btn', { actif: employesSelectionnes.includes(employe.id) }]"
                @click="selectionnerEmploye(employe.id)"
              >
                {{ employe.nomComplet }}
              </button>
            </div>
            <p v-else class="empty-text">Aucun employé disponible.</p>
          </div>

          <div class="actions">
            <button class="btn-primary" type="button" :disabled="soumission" @click="enregistrer">
              {{ soumission ? 'Enregistrement...' : (modeEdition ? 'Enregistrer' : 'Créer') }}
            </button>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.app-layout {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  min-height: 100vh;
  background: #f1f3f6;
  color: #222;
  font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande", "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
}

.page {
  min-width: 0;
  min-height: 100vh;
}

.burger {
  display: none;
  margin: 16px 0 0 16px;
  padding: 8px 16px;
  border: 0;
  border-radius: 10px;
  background: #fff;
  color: teal;
  font-weight: 700;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.contenu {
  max-width: 1100px;
  margin: 0 auto;
  padding: 32px 20px 60px;
}

.hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 20px;
  padding: 24px 28px;
  border-radius: 16px;
  background: linear-gradient(135deg, #fff 0%, #edf9f9 100%);
  box-shadow: rgba(15, 23, 42, 0.08) 0 10px 30px;
}

.eyebrow {
  margin: 0 0 8px;
  color: teal;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

h1 {
  margin: 0;
  font-size: clamp(2rem, 2vw, 2.8rem);
}

.module-card {
  padding: 24px;
  border-radius: 18px;
  background: #fff;
  box-shadow: rgba(15, 23, 42, 0.04) 0 10px 24px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

label {
  display: flex;
  flex-direction: column;
  gap: 8px;
  color: #475467;
  font-size: 0.8rem;
  font-weight: 700;
}

input {
  width: 100%;
  box-sizing: border-box;
  padding: 10px 12px;
  border: 1px solid #d0d5dd;
  border-radius: 10px;
  background: #fff;
  color: #111827;
}

.jours-block,
.panel-employes {
  margin-top: 22px;
}

.jours-block h2,
.panel-head h2 {
  margin: 0 0 12px;
  font-size: 1rem;
}

.jours-grid,
.employee-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.jour-btn,
.employee-btn,
.btn-primary,
.btn-secondary {
  border: 1px solid transparent;
  border-radius: 10px;
  padding: 9px 14px;
  font-weight: 700;
  cursor: pointer;
}

.jour-btn,
.employee-btn {
  background: #f3f6f8;
  color: #344054;
}

.jour-btn.actif,
.employee-btn.actif {
  background: #e6f8f8;
  border-color: teal;
  color: teal;
}

.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}

.panel-head span {
  color: #667085;
  font-size: 0.8rem;
  font-weight: 700;
}

.empty-text {
  margin: 0;
  color: #667085;
}

.alert-error,
.alert-success {
  margin-bottom: 18px;
  padding: 14px 16px;
  border-radius: 12px;
  font-weight: 700;
}

.alert-error {
  border: 1px solid #f5c2c7;
  background: #fff5f5;
  color: #b42318;
}

.alert-success {
  border: 1px solid #bde7d6;
  background: #eefbf4;
  color: #0f7a5d;
}

.actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}

.btn-primary {
  background: linear-gradient(135deg, #0f9a9a 0%, #0c7c7c 100%);
  color: #fff;
}

.btn-secondary {
  background: #edf8f8;
  color: #0f766e;
}

.loading-state {
  padding: 30px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.8);
  text-align: center;
  color: #475467;
  font-weight: 700;
}

@media (max-width: 900px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .burger {
    display: inline-block;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .hero {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
