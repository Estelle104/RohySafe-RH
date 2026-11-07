<script setup>
import { computed, onMounted, ref } from 'vue'
import SidebarMenu from '../../components/SidebarMenu.vue'
import { chargerUtilisateurInterne } from '../../services/authInterne'
import { deleteRegime, getRegimesTravail } from '../../services/horaireTravail'

const menuOuvert = ref(false)
const utilisateur = ref(null)
const regimes = ref([])
const chargement = ref(false)
const erreur = ref('')
const recherche = ref('')
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

const regimesFiltres = computed(() => {
  const terme = recherche.value.trim().toLowerCase()
  const min = volumeMin.value === '' ? null : Number(volumeMin.value)
  const max = volumeMax.value === '' ? null : Number(volumeMax.value)

  return regimes.value.filter((regime) => {
    const texte = `${regime.libelle ?? ''} ${regime.heuresHebdo ?? ''} ${regime.seuilHsJour ?? ''} ${regime.seuilHsSemaine ?? ''}`.toLowerCase()
    const okRecherche = !terme || texte.includes(terme)
    const volume = Number(regime.heuresHebdo ?? 0)
    const okMin = min === null || volume >= min
    const okMax = max === null || volume <= max

    return okRecherche && okMin && okMax
  })
})

async function chargerRegimes() {
  chargement.value = true
  erreur.value = ''

  try {
    regimes.value = await getRegimesTravail()
  } catch (e) {
    erreur.value = e.message || 'Erreur lors du chargement des régimes.'
    regimes.value = []
  } finally {
    chargement.value = false
  }
}

async function supprimerRegime(id) {
  if (!window.confirm('Supprimer ce régime de travail ?')) return

  try {
    await deleteRegime(id)
    await chargerRegimes()
  } catch (e) {
    erreur.value = e.message || 'Erreur lors de la suppression.'
  }
}

onMounted(async () => {
  utilisateur.value = await chargerUtilisateurInterne()
  await chargerRegimes()
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
            <p class="eyebrow">Régimes</p>
            <h1>Régimes de travail</h1>
          </div>
          <router-link class="btn-primary" to="/horaires/postes">Voir les horaires</router-link>
        </section>

        <section v-if="erreur" class="alert-error">{{ erreur }}</section>

        <section class="filter-panel">
          <div class="filter-row">
            <label>
              <span>Recherche</span>
              <input v-model="recherche" type="search" placeholder="Libellé, heures, jours..." />
            </label>

            <label>
              <span>Volume min (h)</span>
              <input v-model="volumeMin" type="number" min="0" step="0.5" placeholder="0" />
            </label>

            <label>
              <span>Volume max (h)</span>
              <input v-model="volumeMax" type="number" min="0" step="0.5" placeholder="50" />
            </label>

            <div class="filter-actions">
              <router-link class="btn-primary" to="/regimes/travail/nouveau">Ajouter</router-link>
            </div>
          </div>
        </section>

        <section v-if="chargement" class="loading-state">Chargement des régimes…</section>

        <section v-else class="cards-grid">
          <article v-for="regime in regimesFiltres" :key="regime.id" class="card">
            <div class="card-main">
              <div class="card-head">
                <span class="badge">{{ regime.libelle }}</span>
              </div>

              <p class="description">{{ regime.description || 'Aucune description ajoutée.' }}</p>
            </div>

            <div class="stats-grid">
              <div>
                <span class="label">Heures / sem.</span>
                <strong>{{ regime.heuresHebdo }}h</strong>
              </div>
              <div>
                <span class="label">HS / jour</span>
                <strong>{{ regime.seuilHsJour }}h</strong>
              </div>
              <div>
                <span class="label">HS / sem.</span>
                <strong>{{ regime.seuilHsSemaine }}h</strong>
              </div>
              <div>
                <span class="label">Tolérance</span>
                <strong>{{ regime.toleranceRetardMax }} min</strong>
              </div>
            </div>

            <div class="card-actions">
              <router-link class="btn-secondary" :to="`/regimes/travail/${regime.id}/modifier`">Modifier</router-link>
              <button class="btn-danger" type="button" @click="supprimerRegime(regime.id)">Supprimer</button>
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
  max-width: 1100px;
  margin: 0 auto;
  padding: 32px 20px 60px;
}

.hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 28px;
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

.filter-panel {
  margin-bottom: 22px;
  padding: 18px;
  border-radius: 16px;
  background: #fff;
  box-shadow: rgba(15, 23, 42, 0.06) 0 10px 22px;
}

.filter-row {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  align-items: end;
}

label {
  display: flex;
  flex-direction: column;
  gap: 8px;
  color: #344054;
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

.filter-actions {
  display: flex;
  justify-content: flex-end;
  align-items: flex-end;
}

.cards-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
}

.card {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.8fr);
  align-items: center;
  gap: 20px;
  padding: 22px 20px;
  border-radius: 16px;
  background: #fff;
  box-shadow: rgba(15, 23, 42, 0.06) 0 8px 18px;
}

.card-main {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.card-head {
  margin-bottom: 16px;
}

.badge {
  display: inline-block;
  padding: 8px 12px;
  border-radius: 999px;
  background: #e6f7f7;
  color: teal;
  font-size: 12px;
  font-weight: 700;
}

.description {
  margin: 0;
  line-height: 1.7;
  color: #475467;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px 12px;
}

.label {
  display: block;
  margin-bottom: 6px;
  color: #667085;
  font-size: 11px;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.stats-grid strong {
  display: block;
  color: #0f172a;
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
  .card {
    display: grid;
    grid-template-columns: 1fr;
  }

  .hero {
    align-items: flex-start;
  }

  .filter-actions {
    justify-content: flex-start;
  }
}
</style>
