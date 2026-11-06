<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SidebarMenu from '../components/SidebarMenu.vue'

const route = useRoute(), router = useRouter(), form = ref({ date_entretien: '', lieu_entretien: '', commentaire: '' }), erreur = ref(''), enCours = ref(false)
const menuOuvert = ref(false)

async function envoyer() {
  enCours.value = true
  try {
    const r = await fetch(`/api/candidatures/${route.params.id}/entretiens`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(form.value) })
    const d = await r.json()
    if (!r.ok) throw new Error(d.message)
    router.push({ name: 'entretien-confirme', params: { id: route.params.id }, query: { entretien: d.id } })
  } catch (e) {
    erreur.value = e.message
  } finally {
    enCours.value = false
  }
}
</script>

<template>
  <div class="app-layout">
    <!-- Nom / initiales : à remplacer par l'utilisateur connecté -->
    <SidebarMenu
      nom="Ressources humaines"
      role="RH"
      code-role="RH"
      initiales="RH"
      :ouvert="menuOuvert"
      @fermer="menuOuvert = false"
    />

    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>

      <div class="contenu">
        <button class="retour" type="button" @click="router.back()">← Retour</button>

        <section class="carte">
          <p class="eyebrow">Recrutement</p>
          <h1>Créer un entretien</h1>

          <form class="form" @submit.prevent="envoyer">
            <label class="field">
              Date et heure
              <input v-model="form.date_entretien" class="input" type="datetime-local" required />
            </label>

            <label class="field">
              Lieu
              <input v-model="form.lieu_entretien" class="input" type="text" placeholder="Salle de réunion, adresse..." required />
            </label>

            <label class="field">
              Commentaire
              <textarea v-model="form.commentaire" class="input zone" rows="5" placeholder="Informations utiles pour l'entretien" />
            </label>

            <p v-if="erreur" class="erreur">{{ erreur }}</p>

            <button class="btn" :disabled="enCours">
              {{ enCours ? 'Création…' : 'Valider l’entretien' }}
            </button>
          </form>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
/* Structure globale : sidebar + page */
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
  max-width: 600px;
  margin: 0 auto;
  padding: 32px 20px 60px;
}

.retour {
  margin-bottom: 16px;
  padding: 8px 16px;
  border: 1px solid teal;
  border-radius: 10px;
  background: #fff;
  color: teal;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.retour:hover {
  background: #e6f2f2;
}

.carte {
  padding: 30px;
  border-radius: 10px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
}

.eyebrow {
  margin: 0 0 6px;
  color: #747474;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

h1 {
  margin: 0 0 24px;
  color: teal;
  font-size: 26px;
  font-weight: 800;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  color: #344054;
  font-size: 12px;
  font-weight: 700;
}

.input {
  width: 100%;
  box-sizing: border-box;
  padding: 12px 15px;
  border: 1px solid #c0c0c0;
  border-radius: 10px;
  outline: 0;
  font-family: inherit;
  font-size: 14px;
  transition: border-color 0.2s;
}

.input:focus {
  border-color: teal;
}

.zone {
  border-radius: 14px;
  resize: vertical;
}

.btn {
  margin-top: 4px;
  padding: 12px 15px;
  border: 0;
  border-radius: 10px;
  background: teal;
  color: #fff;
  font-family: inherit;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.btn:hover:not(:disabled) {
  background: #006666;
}

.btn:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.erreur {
  margin: 0;
  padding: 10px 12px;
  border-radius: 8px;
  background: #fef3f2;
  color: #b42318;
  font-size: 12px;
}

@media (max-width: 960px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .burger {
    display: block;
  }
}
</style>