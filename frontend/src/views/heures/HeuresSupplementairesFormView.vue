<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import SidebarMenu from '../../components/SidebarMenu.vue'
import { chargerUtilisateurInterne } from '../../services/authInterne'
import { createHeureSupplementaire, getEmployesHeuresSupplementaires } from '../../services/heureSupplementaireService'

const router = useRouter()
const utilisateur = ref(null)
const employes = ref([])
const menuOuvert = ref(false)
const soumission = ref(false)
const erreur = ref('')
const formulaire = ref({
  id_employe: '',
  date_debut: '',
  date_fin: '',
  heure_debut: '',
  heure_fin: '',
})
const rechercheEmploye = ref('')
const afficherRechercheEmploye = ref(false)

const codeRole = computed(() => utilisateur.value?.role ?? 'Manager')
const libelleRole = computed(() => (['DG', 'Direction'].includes(codeRole.value) ? 'Direction générale' : codeRole.value || 'Manager'))
const nomUtilisateur = computed(() => utilisateur.value?.login ?? 'Manager')
const initiales = computed(() => {
  if (['DG', 'Direction'].includes(codeRole.value)) return 'DG'
  if (codeRole.value === 'RH') return 'RH'
  return nomUtilisateur.value.slice(0, 2).toUpperCase()
})

const employesFiltres = computed(() => {
  const terme = rechercheEmploye.value.trim().toLowerCase()

  if (!terme) {
    return []
  }

  return employes.value.filter((employe) => {
    const libelle = [
      employe.nomComplet,
      employe.prenom,
      employe.nom,
      employe.matricule,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return libelle.includes(terme)
  })
})

onMounted(async () => {
  utilisateur.value = await chargerUtilisateurInterne()

  try {
    employes.value = await getEmployesHeuresSupplementaires()
  } catch (err) {
    erreur.value = err.message || 'Impossible de charger les employés.'
  }
})

async function enregistrer() {
  soumission.value = true
  erreur.value = ''

  try {
    if (!formulaire.value.id_employe) {
      throw new Error('Sélectionnez un employé.')
    }

    if (!formulaire.value.date_debut || !formulaire.value.date_fin || !formulaire.value.heure_debut || !formulaire.value.heure_fin) {
      throw new Error('Tous les champs sont obligatoires.')
    }

    if (formulaire.value.date_fin < formulaire.value.date_debut) {
      throw new Error('La date de fin doit être postérieure ou égale à la date de début.')
    }

    await createHeureSupplementaire({
      id_employe: Number(formulaire.value.id_employe),
      date_debut: formulaire.value.date_debut,
      date_fin: formulaire.value.date_fin,
      heure_debut: formulaire.value.heure_debut,
      heure_fin: formulaire.value.heure_fin,
    })

    router.push('/planning')
  } catch (e) {
    erreur.value = e.message || 'Erreur lors de l’enregistrement.'
  } finally {
    soumission.value = false
  }
}

function selectionnerEmploye(id) {
  const employe = employes.value.find((item) => Number(item.id) === Number(id))
  formulaire.value.id_employe = Number(id)
  afficherRechercheEmploye.value = false

  if (employe) {
    rechercheEmploye.value = employe.nomComplet || `${employe.prenom} ${employe.nom}`.trim()
  }
}
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
        <p class="eyebrow">Gestion RH</p>
        <h1>Heures supplémentaires</h1>

        <section class="module-card">
          <div class="module-head">
            <h2>Déclaration d’heures supplémentaires</h2>
            <button class="btn-secondary" type="button" @click="router.push('/planning')">Retour</button>
          </div>

          <p v-if="erreur" class="error-box">{{ erreur }}</p>

          <div class="form-grid">
            <label>
              <span>Employé</span>
              <div class="search-select">
                <input
                  v-model="rechercheEmploye"
                  type="text"
                  placeholder="Rechercher un employé..."
                  @focus="afficherRechercheEmploye = true"
                  @blur="setTimeout(() => { afficherRechercheEmploye = false }, 120)"
                />

                <div v-if="afficherRechercheEmploye && rechercheEmploye.trim()" class="search-results">
                  <button
                    v-for="employe in employesFiltres"
                    :key="employe.id"
                    type="button"
                    class="search-result"
                    :class="{ actif: Number(formulaire.id_employe) === Number(employe.id) }"
                    @click="selectionnerEmploye(employe.id)"
                  >
                    <span>{{ employe.nomComplet || `${employe.prenom} ${employe.nom}`.trim() }}</span>
                    <small v-if="employe.matricule">{{ employe.matricule }}</small>
                  </button>

                  <div v-if="!employesFiltres.length" class="search-empty">
                    Aucun employé trouvé
                  </div>
                </div>
              </div>
            </label>

            <label>
              <span>Date de début</span>
              <input v-model="formulaire.date_debut" type="date" />
            </label>

            <label>
              <span>Date de fin</span>
              <input v-model="formulaire.date_fin" type="date" />
            </label>

            <label>
              <span>Heure de début</span>
              <input v-model="formulaire.heure_debut" type="time" />
            </label>

            <label>
              <span>Heure de fin</span>
              <input v-model="formulaire.heure_fin" type="time" />
            </label>
          </div>

          <div class="actions">
            <button class="btn-primary" type="button" :disabled="soumission" @click="enregistrer">
              {{ soumission ? 'Enregistrement...' : 'Déclarer' }}
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
  color: #1d2433;
  font-family: "Segoe UI", Tahoma, sans-serif;
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
  box-shadow: rgba(0, 0, 0, 0.08) 0 6px 18px;
}

.contenu {
  max-width: 980px;
  margin: 0 auto;
  padding: 32px 20px 60px;
}

.eyebrow {
  margin: 0 0 6px;
  color: #7a7a7a;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

h1 {
  margin: 0 0 20px;
  color: teal;
  font-size: 28px;
  font-weight: 800;
}

.module-card {
  padding: 22px 20px;
  border-radius: 18px;
  background: #fff;
  box-shadow: rgba(15, 23, 42, 0.04) 0 10px 24px;
}

.module-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}

.module-head h2 {
  margin: 0;
  font-size: 1.1rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 14px;
}

label {
  display: flex;
  flex-direction: column;
  gap: 8px;
  color: #475467;
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

.search-select {
  position: relative;
}

.search-results {
  position: absolute;
  z-index: 5;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  display: flex;
  flex-direction: column;
  max-height: 220px;
  overflow-y: auto;
  border: 1px solid #d0d5dd;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.search-result {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  width: 100%;
  padding: 10px 12px;
  border: 0;
  border-bottom: 1px solid #eef2f6;
  background: #fff;
  color: #111827;
  text-align: left;
  cursor: pointer;
}

.search-result:last-child {
  border-bottom: none;
}

.search-result:hover,
.search-result.actif {
  background: #edf8f8;
}

.search-result small {
  color: #667085;
}

.recherche-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d0d5dd;
  border-radius: 10px 10px 0 0;
  background: #fff;
  color: #111827;
}

.recherche-input::placeholder {
  color: #a0aec0;
}

.actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 18px;
}

.btn-primary,
.btn-secondary {
  border: none;
  border-radius: 10px;
  padding: 10px 16px;
  font-weight: 700;
  cursor: pointer;
}

.btn-primary {
  background: linear-gradient(135deg, #0f9a9a 0%, #0c7c7c 100%);
  color: #fff;
}

.btn-secondary {
  background: #edf8f8;
  color: #0f766e;
}

.error-box {
  margin: 0 0 16px;
  padding: 12px 14px;
  border-radius: 10px;
  background: #fff1f2;
  color: #b42318;
  font-weight: 700;
}

.search-empty {
  padding: 12px;
  color: #667085;
  font-size: 0.8rem;
  background: #fff;
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
}
</style>
