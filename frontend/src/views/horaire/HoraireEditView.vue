<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SidebarMenu from '../../components/SidebarMenu.vue'
import { chargerUtilisateurInterne } from '../../services/authInterne'
import { getHoraires, updateHoraire } from '../../services/horaireTravail'

const route = useRoute()
const router = useRouter()
const utilisateur = ref(null)
const menuOuvert = ref(false)
const chargement = ref(true)
const erreur = ref('')
const formulaire = ref({
  id: null,
  id_poste: '',
  id_regime: '',
  heure_debut: '',
  heure_fin: '',
  pause_minutes: 0,
})

const codeRole = computed(() => utilisateur.value?.role ?? 'RH')
const libelleRole = computed(() => (['DG', 'Direction'].includes(codeRole.value) ? 'Direction générale' : codeRole.value || 'RH'))
const nomUtilisateur = computed(() => utilisateur.value?.login ?? 'Ressources humaines')
const initiales = computed(() => {
  if (['DG', 'Direction'].includes(codeRole.value)) return 'DG'
  if (codeRole.value === 'RH') return 'RH'
  return nomUtilisateur.value.slice(0, 2).toUpperCase()
})

async function chargerHoraire() {
  try {
    const horaires = await getHoraires()
    const horaire = horaires.find(item => String(item.id) === String(route.params.id))

    if (!horaire) {
      throw new Error('Horaire introuvable.')
    }

    formulaire.value = {
      id: horaire.id,
      id_poste: horaire.id_poste ?? horaire.idPoste ?? '',
      id_regime: horaire.id_regime ?? horaire.idRegime ?? '',
      heure_debut: horaire.heureDebut ?? '',
      heure_fin: horaire.heureFin ?? '',
      pause_minutes: horaire.pause ?? 0,
    }
  } catch (e) {
    erreur.value = e.message || 'Erreur lors du chargement de l’horaire.'
  } finally {
    chargement.value = false
  }
}

async function enregistrer() {
  try {
    await updateHoraire(formulaire.value.id, {
      id_poste: Number(formulaire.value.id_poste),
      id_regime: Number(formulaire.value.id_regime),
      heure_debut: formulaire.value.heure_debut,
      heure_fin: formulaire.value.heure_fin,
      pause_minutes: Number(formulaire.value.pause_minutes || 0),
    })

    router.push('/horaires/postes')
  } catch (e) {
    erreur.value = e.message || 'Erreur lors de la modification.'
  }
}

onMounted(async () => {
  utilisateur.value = await chargerUtilisateurInterne()
  await chargerHoraire()
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
        <p class="eyebrow">Planning RH</p>
        <h1>Horaire poste</h1>

        <section class="module-card">
          <div class="module-head">
            <h2>Modifier l’horaire</h2>
            <button class="btn-secondary" type="button" @click="router.push('/horaires/postes')">Retour</button>
          </div>

          <p v-if="erreur" class="error-box">{{ erreur }}</p>

          <div v-if="chargement" class="loading">Chargement…</div>

          <div v-else class="form-grid">
            <label>
              <span>ID poste</span>
              <input v-model="formulaire.id_poste" type="number" min="1" />
            </label>

            <label>
              <span>ID régime</span>
              <input v-model="formulaire.id_regime" type="number" min="1" />
            </label>

            <label>
              <span>Heure de début</span>
              <input v-model="formulaire.heure_debut" type="time" />
            </label>

            <label>
              <span>Heure de fin</span>
              <input v-model="formulaire.heure_fin" type="time" />
            </label>

            <label>
              <span>Pause (min)</span>
              <input v-model="formulaire.pause_minutes" type="number" min="0" />
            </label>
          </div>

          <div class="actions">
            <button v-if="!chargement" class="btn-primary" type="button" @click="enregistrer">Enregistrer</button>
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
  color: #0b6d6d;
  font-weight: 700;
  cursor: pointer;
  box-shadow: rgba(15, 23, 42, 0.05) 0 10px 24px;
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
  color: #0f172a;
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
  font-size: 1.08rem;
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

input {
  width: 100%;
  box-sizing: border-box;
  padding: 10px 12px;
  border: 1px solid #d0d5dd;
  border-radius: 10px;
  background: #fff;
  color: #111827;
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

.error-box,
.loading {
  margin: 0 0 16px;
  padding: 12px 14px;
  border-radius: 10px;
  font-weight: 700;
}

.error-box {
  background: #fff1f2;
  color: #b42318;
}

.loading {
  background: #f0fdfa;
  color: #0f766e;
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
