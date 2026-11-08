<script setup>
import { computed, onMounted, ref } from 'vue'
import SidebarMenu from '../../components/SidebarMenu.vue'
import { chargerUtilisateurInterne } from '../../services/authInterne'
import {
  ajouterJours,
  dateDebutSemaine,
  dateKey,
  estDansPlageDate,
  formatWeekRange,
  getPlanningsEDT,
  jourNom,
  labelDateComplete,
  labelMois,
  premierJourDuMois,
} from '../../services/planningService'
import PlanningJourView from './PlanningJourView.vue'
import PlanningSemaineView from './PlanningSemaineView.vue'
import PlanningMoisView from './PlanningMoisView.vue'

const menuOuvert = ref(false)
const utilisateur = ref(null)
const plannings = ref([])
const chargement = ref(false)
const erreur = ref('')
const vueCourante = ref('jour')
const dateCourante = ref(new Date())
const recherche = ref('')
const periodeFiltre = ref('Tous')

const codeRole = computed(() => utilisateur.value?.role ?? 'RH')
const libelleRole = computed(() => (['DG', 'Direction'].includes(codeRole.value) ? 'Direction générale' : codeRole.value || 'RH'))
const nomUtilisateur = computed(() => utilisateur.value?.login ?? 'Ressources humaines')
const initiales = computed(() => {
  if (['DG', 'Direction'].includes(codeRole.value)) return 'DG'
  if (codeRole.value === 'RH') return 'RH'
  return nomUtilisateur.value.slice(0, 2).toUpperCase()
})

function jourDansPlanning(planning, date) {
  const jourNomCourant = jourNom(date)
  return Array.isArray(planning.joursSemaine) && planning.joursSemaine.includes(jourNomCourant)
}

function getEvenementsPourDate(date) {
  return planningsFiltres.value.filter((planning) => {
    return estDansPlageDate(date, planning.dateDebut, planning.dateFin) && jourDansPlanning(planning, date)
  })
}

const evenementsParJour = computed(() => {
  const map = {}

  for (const planning of planningsFiltres.value) {
    const jours = Array.isArray(planning.joursSemaine) ? planning.joursSemaine : []
    const dateDebut = new Date(planning.dateDebut)
    const dateFin = new Date(planning.dateFin)

    for (let d = new Date(dateDebut); d <= dateFin; d = ajouterJours(d, 1)) {
      const nomJour = jourNom(d)
      if (!jours.includes(nomJour)) continue

      const key = dateKey(d)
      if (!map[key]) map[key] = []
      map[key].push(planning)
    }
  }

  return map
})

const planningsFiltres = computed(() => {
  const terme = recherche.value.trim().toLowerCase()

  return plannings.value.filter((planning) => {
    const texte = `${planning.libelle ?? ''} ${planning.employes?.join(' ') ?? ''} ${planning.joursSemaine?.join(' ') ?? ''}`.toLowerCase()
    const okRecherche = !terme || texte.includes(terme)

    if (!okRecherche) return false

    if (periodeFiltre.value === 'Tous') return true

    const aujourdHui = new Date()
    const dateDebut = new Date(planning.dateDebut)
    const dateFin = new Date(planning.dateFin)

    if (periodeFiltre.value === 'Aujourd’hui') {
      return estDansPlageDate(aujourdHui, planning.dateDebut, planning.dateFin) && Array.isArray(planning.joursSemaine) && planning.joursSemaine.includes(jourNom(aujourdHui))
    }

    if (periodeFiltre.value === 'Cette semaine') {
      const debut = dateDebutSemaine(aujourdHui)
      const fin = ajouterJours(debut, 6)
      return dateDebut >= debut && dateFin <= fin
    }

    if (periodeFiltre.value === 'Ce mois') {
      return dateDebut.getMonth() === aujourdHui.getMonth() && dateDebut.getFullYear() === aujourdHui.getFullYear()
    }

    return true
  })
})

const joursAffiches = computed(() => {
  if (vueCourante.value === 'jour') {
    return [new Date(dateCourante.value)]
  }

  if (vueCourante.value === 'semaine') {
    const debut = dateDebutSemaine(dateCourante.value)
    return Array.from({ length: 7 }, (_, index) => ajouterJours(debut, index))
  }

  const premier = premierJourDuMois(dateCourante.value)
  const debutGrille = ajouterJours(premier, -((premier.getDay() + 6) % 7))
  return Array.from({ length: 42 }, (_, index) => ajouterJours(debutGrille, index))
})

const titreVue = computed(() => {
  if (vueCourante.value === 'jour') return 'Planning du jour'
  if (vueCourante.value === 'semaine') return 'Planning de la semaine'
  return 'Planning du mois'
})

const evenementsDuJour = computed(() => {
  return planningsFiltres.value.filter((planning) => getEvenementsPourDate(dateCourante.value).includes(planning))
})

function afficherJour(date) {
  vueCourante.value = 'jour'
  dateCourante.value = new Date(date)
}

function precedent() {
  if (vueCourante.value === 'jour') {
    dateCourante.value = ajouterJours(dateCourante.value, -1)
    return
  }

  if (vueCourante.value === 'semaine') {
    dateCourante.value = ajouterJours(dateCourante.value, -7)
    return
  }

  dateCourante.value = new Date(dateCourante.value.getFullYear(), dateCourante.value.getMonth() - 1, 1)
}

function suivant() {
  if (vueCourante.value === 'jour') {
    dateCourante.value = ajouterJours(dateCourante.value, 1)
    return
  }

  if (vueCourante.value === 'semaine') {
    dateCourante.value = ajouterJours(dateCourante.value, 7)
    return
  }

  dateCourante.value = new Date(dateCourante.value.getFullYear(), dateCourante.value.getMonth() + 1, 1)
}

function aujourdHui() {
  dateCourante.value = new Date()
  vueCourante.value = 'jour'
}

async function chargerPlannings() {
  chargement.value = true
  erreur.value = ''

  try {
    plannings.value = await getPlanningsEDT()
  } catch (e) {
    erreur.value = e.message || 'Erreur lors du chargement du planning.'
    plannings.value = []
  } finally {
    chargement.value = false
  }
}

onMounted(async () => {
  utilisateur.value = await chargerUtilisateurInterne()
  await chargerPlannings()
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
            <h1>{{ titreVue }}</h1>
          </div>

          <div class="toggle-group" aria-label="Choix de la vue du planning">
            <button type="button" :class="['toggle-btn', { active: vueCourante === 'jour' }]" @click="aujourdHui()">Aujourd’hui</button>
            <button type="button" :class="['toggle-btn', { active: vueCourante === 'semaine' }]" @click="vueCourante = 'semaine'">Hebdomadaire</button>
            <button type="button" :class="['toggle-btn', { active: vueCourante === 'mois' }]" @click="vueCourante = 'mois'">Mensuel</button>
          </div>
        </section>

        <section v-if="erreur" class="alert-error">{{ erreur }}</section>

        <section class="toolbar">
          <button class="nav-btn" type="button" @click="precedent">← Précédent</button>
          <strong>{{ vueCourante === 'jour' ? labelDateComplete(dateCourante) : vueCourante === 'semaine' ? formatWeekRange(dateCourante) : labelMois(dateCourante) }}</strong>
          <button class="nav-btn" type="button" @click="suivant">Suivant →</button>
        </section>

        <section class="action-bar">
          <router-link class="btn-primary" to="/planning/nouveau">Créer un planning</router-link>
        </section>

        <section class="filter-panel">
          <div class="filter-row">
            <label class="search-field">
              <span>Recherche</span>
              <input v-model="recherche" type="search" placeholder="Libellé, employés, jours..." />
            </label>

            <label class="select-field">
              <span>Période</span>
              <select v-model="periodeFiltre">
                <option value="Tous">Tous</option>
                <option value="Aujourd’hui">Aujourd’hui</option>
                <option value="Cette semaine">Cette semaine</option>
                <option value="Ce mois">Ce mois</option>
              </select>
            </label>
          </div>
        </section>

        <section v-if="chargement" class="loading-state">Chargement du planning…</section>

        <PlanningJourView
          v-else-if="vueCourante === 'jour'"
          :date-courante="dateCourante"
          :evenements="evenementsDuJour"
        />

        <PlanningSemaineView
          v-else-if="vueCourante === 'semaine'"
          :jours="joursAffiches"
          :evenements-par-jour="evenementsParJour"
          @afficher-jour="afficherJour"
        />

        <PlanningMoisView
          v-else
          :jours="joursAffiches"
          :evenements-par-jour="evenementsParJour"
          :date-courante="dateCourante"
          @afficher-jour="afficherJour"
        />
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
  font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
    "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
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
  font-family: inherit;
  font-weight: 700;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.contenu {
  max-width: 1280px;
  margin: 0 auto;
  padding: 32px 20px 60px;
}

.hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 18px;
  padding: 24px 28px;
  border-radius: 16px;
  background: linear-gradient(135deg, #fff 0%, #edf9f9 100%);
  box-shadow: rgba(15, 23, 42, 0.08) 0 10px 30px;
}

.eyebrow {
  margin: 0 0 8px;
  color: teal;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-size: 12px;
}

h1 {
  margin: 0;
  font-size: clamp(2rem, 2.2vw, 2.8rem);
  color: #111827;
}

.toggle-group {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px;
  border: 1px solid #dfe7eb;
  border-radius: 12px;
  background: #fff;
}

.toggle-btn {
  padding: 10px 14px;
  border: 0;
  border-radius: 10px;
  background: transparent;
  color: #475467;
  font-weight: 700;
  cursor: pointer;
}

.toggle-btn.active {
  background: teal;
  color: #fff;
}

.alert-error {
  margin-bottom: 18px;
  padding: 14px 18px;
  border: 1px solid #f5c2c7;
  border-radius: 12px;
  background: #fff5f5;
  color: #b42318;
  font-weight: 600;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 20px;
  padding: 14px 18px;
  border: 1px solid #e8edf3;
  border-radius: 14px;
  background: #fff;
}

.nav-btn {
  padding: 9px 12px;
  border: 1px solid #d4e7e6;
  border-radius: 10px;
  background: #f5fbfb;
  color: #0f172a;
  font-family: inherit;
  font-weight: 700;
  cursor: pointer;
}

.loading-state {
  padding: 40px 20px;
  border: 1px dashed #cfe6e6;
  border-radius: 16px;
  background: #f4fbfb;
  text-align: center;
  color: #0f766e;
  font-weight: 700;
}

.filter-panel {
  margin: 18px 0 22px;
  padding: 18px 20px;
  border: 1px solid #e5ebf1;
  border-radius: 16px;
  background: linear-gradient(180deg, #fff 0%, #f8fbfb 100%);
  box-shadow: rgba(15, 23, 42, 0.03) 0 8px 22px;
}

.filter-row {
  display: flex;
  flex-wrap: wrap;
  gap: 18px;
}

.search-field,
.select-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 220px;
  color: #344054;
  font-weight: 700;
}

.search-field span,
.select-field span {
  font-size: 0.78rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.search-field input,
.select-field select {
  width: 100%;
  min-height: 44px;
  padding: 0 14px;
  border: 1px solid #d8e2ea;
  border-radius: 10px;
  background: #fff;
  color: #111827;
  font: inherit;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.search-field input:focus,
.select-field select:focus {
  border-color: #0f9a9a;
  box-shadow: 0 0 0 4px rgba(15, 154, 154, 0.1);
  outline: none;
}

.action-bar {
  display: flex;
  justify-content: flex-end;
  margin: 18px 0 0;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 16px;
  border-radius: 10px;
  background: linear-gradient(135deg, #0f9a9a 0%, #0c7c7c 100%);
  color: #fff;
  font-weight: 700;
  text-decoration: none;
  transition: 0.2s ease;
}

@media (max-width: 1000px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .burger {
    display: inline-block;
  }
}

@media (max-width: 640px) {
  .hero,
  .toolbar {
    flex-direction: column;
    align-items: flex-start;
  }

  .toggle-group {
    width: 100%;
    justify-content: space-between;
  }

  .filter-row {
    flex-direction: column;
  }
}
</style>
