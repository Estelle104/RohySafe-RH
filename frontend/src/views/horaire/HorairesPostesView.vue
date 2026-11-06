<script setup>
import { computed, onMounted, ref } from 'vue'
import SidebarMenu from '../../components/SidebarMenu.vue'
import { chargerUtilisateurInterne } from '../../services/authInterne'
import { calculerTotalHeures, deleteHoraire, getHoraires } from '../../services/horaireTravail'

const menuOuvert = ref(false)
const utilisateur = ref(null)
const horaires = ref([])
const chargement = ref(false)
const erreur = ref('')
const recherche = ref('')
const regimeFiltre = ref('Tous')
const jourFiltre = ref('Tous')
const volumeMin = ref('')
const volumeMax = ref('')

const codeRole = computed(() => utilisateur.value?.role ?? 'RH')
const libelleRole = computed(() => (['DG', 'Direction'].includes(codeRole.value) ? 'Direction générale' : codeRole.value || 'RH'))
const nomUtilisateur = computed(() => utilisateur.value?.login ?? 'Ressources humaines')
const initiales = computed(() => {
  if (['DG', 'Direction'].includes(codeRole.value)) return 'DG'
  if (codeRole.value === 'RH') return 'RH'
  return nomUtilisateur.value.slice(0, 2).toUpperCase()
})

const totalHeures = computed(() => calculerTotalHeures(horaires.value))

const regimesDisponibles = computed(() => ['Tous', ...new Set(horaires.value.map((h) => h.regime).filter(Boolean))])
const joursDisponibles = computed(() => ['Tous', ...new Set(horaires.value.flatMap((h) => h.jours ?? []).filter(Boolean))])

function intervalleEnHeures(horaire) {
  const debut = horaire?.heureDebut ?? '00:00'
  const fin = horaire?.heureFin ?? '00:00'
  const [h1, m1] = debut.split(':').map(Number)
  const [h2, m2] = fin.split(':').map(Number)

  let minutes = (h2 * 60 + m2) - (h1 * 60 + m1)
  if (minutes < 0) minutes += 24 * 60

  const pause = Number(horaire?.pause ?? 0) || 0
  return Math.max(0, (minutes - pause) / 60)
}

const horairesFiltres = computed(() => {
  const terme = recherche.value.trim().toLowerCase()
  const volumeMinValue = volumeMin.value === '' ? null : Number(volumeMin.value)
  const volumeMaxValue = volumeMax.value === '' ? null : Number(volumeMax.value)

  return horaires.value.filter((horaire) => {
    const texte = `${horaire.poste ?? ''} ${horaire.regime ?? ''} ${horaire.heureDebut ?? ''} ${horaire.heureFin ?? ''}`.toLowerCase()
    const okRecherche = !terme || texte.includes(terme)
    const okRegime = regimeFiltre.value === 'Tous' || horaire.regime === regimeFiltre.value
    const okJour = jourFiltre.value === 'Tous' || (Array.isArray(horaire.jours) && horaire.jours.includes(jourFiltre.value))
    const volume = intervalleEnHeures(horaire)
    const okMin = volumeMinValue === null || volume >= volumeMinValue
    const okMax = volumeMaxValue === null || volume <= volumeMaxValue

    return okRecherche && okRegime && okJour && okMin && okMax
  })
})

async function chargerHoraires() {
  chargement.value = true
  erreur.value = ''

  try {
    horaires.value = await getHoraires()
  } catch (e) {
    erreur.value = e.message || 'Erreur lors du chargement des horaires.'
    horaires.value = []
  } finally {
    chargement.value = false
  }
}

async function supprimerHoraire(id) {
  if (!window.confirm('Supprimer cet horaire ?')) return

  try {
    await deleteHoraire(id)
    await chargerHoraires()
  } catch (e) {
    erreur.value = e.message || 'Erreur lors de la suppression.'
  }
}

onMounted(async () => {
  utilisateur.value = await chargerUtilisateurInterne()
  await chargerHoraires()
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
            <h1>Horaire poste</h1>
          </div>
          <router-link class="btn-primary" to="/regimes/travail">Voir les régimes</router-link>
        </section>

        <section v-if="erreur" class="alert-error">{{ erreur }}</section>

        <section class="stats-grid">
          <article class="stat-card accent">
            <span class="label">Postes</span>
            <strong>{{ horaires.length }}</strong>
          </article>
          <article class="stat-card">
            <span class="label">Volume total</span>
            <strong>{{ totalHeures.toFixed(1) }}h</strong>
          </article>
          <article class="stat-card">
            <span class="label">Régimes</span>
            <strong>{{ new Set(horaires.map((h) => h.regime)).size }}</strong>
          </article>
          <article class="stat-card">
            <span class="label">Pause moyenne</span>
            <strong>{{ horaires.length ? (horaires.reduce((s, h) => s + Number(h.pause || 0), 0) / horaires.length).toFixed(0) : 0 }} min</strong>
          </article>
        </section>

        <section class="filter-panel">
          <div class="filter-row">
            <label>
              <span>Recherche</span>
              <input v-model="recherche" type="search" placeholder="Nom du poste, régime..." />
            </label>

            <label>
              <span>Régime</span>
              <select v-model="regimeFiltre">
                <option v-for="regime in regimesDisponibles" :key="regime" :value="regime">{{ regime }}</option>
              </select>
            </label>

            <label>
              <span>Jour</span>
              <select v-model="jourFiltre">
                <option v-for="jour in joursDisponibles" :key="jour" :value="jour">{{ jour }}</option>
              </select>
            </label>
          </div>

          <div class="filter-row">
            <label>
              <span>Volume min (h)</span>
              <input v-model="volumeMin" type="number" min="0" step="0.5" placeholder="0" />
            </label>

            <label>
              <span>Volume max (h)</span>
              <input v-model="volumeMax" type="number" min="0" step="0.5" placeholder="12" />
            </label>

            <div class="filter-actions">
              <router-link class="btn-primary" to="/horaires/postes/nouveau">Ajouter</router-link>
            </div>
          </div>
        </section>

        <section v-if="chargement" class="loading-state">Chargement des horaires…</section>

        <section v-else class="cards-grid">
          <article v-for="horaire in horairesFiltres" :key="horaire.id" class="card">
            <div class="card-head">
              <div>
                <h2>{{ horaire.poste }}</h2>
              </div>
              <span class="badge">{{ horaire.regime }}</span>
            </div>

            <div class="meta-grid">
              <div>
                <span class="meta-label">Horaire</span>
                <strong>{{ horaire.heureDebut }} - {{ horaire.heureFin }}</strong>
              </div>
              <div>
                <span class="meta-label">Pause</span>
                <strong>{{ horaire.pause }} min</strong>
              </div>
              <div>
                <span class="meta-label">Volume</span>
                <strong>{{ intervalleEnHeures(horaire).toFixed(1) }}h</strong>
              </div>
            </div>

            <div class="chip-row">
              <span v-for="jour in horaire.jours" :key="jour" class="chip">{{ jour }}</span>
            </div>

            <div class="card-actions">
              <router-link class="btn-secondary" :to="`/horaires/postes/${horaire.id}/modifier`">Modifier</router-link>
              <button class="btn-danger" type="button" @click="supprimerHoraire(horaire.id)">Supprimer</button>
            </div>
          </article>
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
  max-width: 1200px;
  margin: 0 auto;
  padding: 32px 20px 60px;
}

.hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 24px;
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
  font-size: clamp(28px, 4vw, 38px);
  line-height: 1.1;
  color: #082a2a;
}

.btn-primary,
.btn-secondary,
.btn-danger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42px;
  padding: 10px 16px;
  border: 0;
  border-radius: 10px;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
}

.btn-primary {
  background: teal;
  color: #fff;
  box-shadow: rgba(0, 0, 0, 0.15) 0 8px 24px;
}

.btn-secondary {
  background: #e8f7f7;
  color: #0b6d6d;
}

.btn-danger {
  background: #fee2e2;
  color: #b42318;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  padding: 20px 18px;
  border-radius: 14px;
  background: #fff;
  box-shadow: rgba(15, 23, 42, 0.06) 0 6px 18px;
}

.stat-card.accent {
  background: linear-gradient(135deg, #0f9b9b 0%, #38b2ac 100%);
  color: #fff;
}

.label {
  display: block;
  margin-bottom: 10px;
  font-size: 12px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  opacity: 0.9;
}

.stat-card strong {
  font-size: clamp(24px, 3vw, 30px);
}

.filter-panel {
  margin-bottom: 22px;
  padding: 18px;
  border-radius: 16px;
  background: #fff;
  box-shadow: rgba(15, 23, 42, 0.06) 0 10px 22px;
}

.filter-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  align-items: end;
}

.filter-row + .filter-row {
  margin-top: 14px;
}

label {
  display: flex;
  flex-direction: column;
  gap: 8px;
  color: #344054;
  font-size: 0.8rem;
  font-weight: 700;
}

input,
select {
  width: 100%;
  box-sizing: border-box;
  padding: 10px 12px;
  border: 1px solid #d0d5dd;
  border-radius: 10px;
  background: #fff;
  color: #111827;
}

.filter-actions {
  display: flex;
  justify-content: flex-end;
  align-items: flex-end;
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 18px;
}

.card {
  padding: 22px 20px;
  border-radius: 18px;
  background: #fff;
  box-shadow: rgba(15, 23, 42, 0.06) 0 10px 22px;
}

.card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

h2 {
  margin: 0;
  font-size: 1.2rem;
  color: #0f172a;
}

.badge {
  display: inline-flex;
  align-items: center;
  padding: 8px 12px;
  border-radius: 999px;
  background: #e6f7f7;
  color: teal;
  font-size: 12px;
  font-weight: 700;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.meta-label {
  display: block;
  margin-bottom: 6px;
  color: #667085;
  font-size: 11px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.meta-grid strong {
  display: block;
  color: #0f172a;
}

.chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 18px;
}

.chip {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  background: #edf2f7;
  color: #334155;
  font-size: 11px;
  font-weight: 700;
}

.card-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.alert-error,
.loading-state {
  margin-bottom: 18px;
  padding: 14px 16px;
  border-radius: 12px;
}

.alert-error {
  background: #fff3f3;
  color: #b42318;
  font-weight: 600;
}

.loading-state {
  background: #f0fdfa;
  color: #0f766e;
  font-weight: 600;
}

@media (max-width: 900px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .burger {
    display: inline-block;
  }

  .hero,
  .filter-row,
  .card-head,
  .meta-grid {
    grid-template-columns: 1fr;
    display: grid;
  }

  .hero {
    align-items: flex-start;
  }

  .filter-actions {
    justify-content: flex-start;
  }
}
</style>
