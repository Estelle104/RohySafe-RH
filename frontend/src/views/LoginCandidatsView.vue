<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { connecterCandidat, estCandidatConnecte } from '../services/candidatAuth'

const route = useRoute()
const router = useRouter()
const identifiant = ref('')
const motDePasse = ref('')
const erreur = ref('')
const chargement = ref(false)

const redirection = computed(() => {
  const cible = route.query.redirect
  return typeof cible === 'string' && cible.startsWith('/') && !cible.startsWith('//')
    ? cible
    : '/offres'
})

const lienInscription = computed(() => ({
  name: 'candidat-inscription',
  query:
    typeof route.query.redirect === 'string'
      ? { redirect: route.query.redirect }
      : {},
}))

onMounted(() => {
  if (estCandidatConnecte()) {
    router.replace(redirection.value)
  }
})

async function seConnecter() {
  erreur.value = ''

  if (!identifiant.value.trim() || !motDePasse.value) {
    erreur.value = 'Renseignez votre identifiant et votre mot de passe.'
    return
  }

  chargement.value = true

  try {
    await connecterCandidat(identifiant.value.trim(), motDePasse.value)
    router.replace(redirection.value)
  } catch (e) {
    erreur.value = e.message
  } finally {
    chargement.value = false
  }
}
</script>

<template>
  <div class="login-container">
    <div class="form-container">
      <p class="title">RohySafe</p>
      <p class="sub-title">Connectez-vous pour postuler et gérer votre CV.</p>

      <form class="form" @submit.prevent="seConnecter">
        <label class="field">
          Identifiant ou email
          <input
            v-model="identifiant"
            class="input"
            type="text"
            autocomplete="username"
            placeholder="miora_test"
          />
        </label>

        <label class="field">
          Mot de passe
          <input
            v-model="motDePasse"
            class="input"
            type="password"
            autocomplete="current-password"
            placeholder="Mot de passe"
          />
        </label>

        <p v-if="erreur" class="error">{{ erreur }}</p>

        <button class="form-btn" type="submit" :disabled="chargement">
          {{ chargement ? 'Connexion...' : 'Se connecter' }}
        </button>
      </form>

      <p class="bas-de-page">
        Pas encore de compte ?
        <router-link :to="lienInscription">Créer un compte</router-link>
      </p>

      <router-link class="page-link" to="/offres">
        Retour aux offres
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: #f1f3f6;
  font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
    "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
}

.form-container {
  width: 100%;
  max-width: 380px;
  background-color: #fff;
  box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
  border-radius: 10px;
  box-sizing: border-box;
  padding: 30px;
}

.title {
  text-align: center;
  margin: 0 0 8px;
  font-size: 28px;
  font-weight: 800;
  color: #1f2937;
}

.sub-title {
  margin: 0 0 28px;
  text-align: center;
  color: #667085;
  font-size: 13px;
  line-height: 1.5;
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
  border-radius: 20px;
  border: 1px solid #c0c0c0;
  outline: 0;
  box-sizing: border-box;
  padding: 12px 15px;
  font-family: inherit;
  font-size: 14px;
  transition: border-color 0.2s;
}

.input:focus {
  border-color: teal;
}

.form-btn {
  margin-top: 4px;
  padding: 12px 15px;
  font-family: inherit;
  font-size: 14px;
  font-weight: 700;
  border-radius: 20px;
  border: 0;
  outline: 0;
  background: teal;
  color: #fff;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.form-btn:hover:not(:disabled) {
  background: #006666;
}

.form-btn:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.error {
  margin: 0;
  padding: 10px 12px;
  border-radius: 8px;
  background: #fef3f2;
  color: #b42318;
  font-size: 12px;
}

.bas-de-page {
  margin: 20px 0 0;
  text-align: center;
  color: #667085;
  font-size: 12px;
}

.bas-de-page a {
  color: teal;
  font-weight: 700;
  text-decoration: underline;
  text-decoration-color: teal;
}

.page-link {
  display: block;
  margin-top: 14px;
  text-align: center;
  color: teal;
  font-size: 12px;
  font-weight: 700;
  text-decoration: underline;
  text-decoration-color: teal;
}
</style>
