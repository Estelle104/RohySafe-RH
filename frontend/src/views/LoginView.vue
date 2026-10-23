<template>
  <div class="login-container">
    <div class="form-container">
      <p class="title">RohySafe</p>
      <form class="form" @submit.prevent="connecter">
        <input v-model="login" type="text" class="input" placeholder="Identifiant" autocomplete="username" required>
        <input v-model="mdp" type="password" class="input" placeholder="Mot de passe" autocomplete="current-password" required>
        <p v-if="erreur" class="error">{{ erreur }}</p>
        <button class="form-btn" :disabled="chargement">{{ chargement ? 'Connexion…' : 'Se connecter' }}</button>
      </form>
      <router-link class="candidate-link" to="/candidats/login">
        Vous êtes candidat ? Se connecter
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const login = ref('')
const mdp = ref('')
const erreur = ref('')
const chargement = ref(false)

async function connecter() {
  erreur.value = ''
  chargement.value = true
  try {
    const response = await fetch('/api/auth/login', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      credentials: 'include', body: JSON.stringify({ login: login.value, mdp: mdp.value }),
    })
    const result = await response.json()
    if (!response.ok) throw new Error(result.messages?.error || 'Identifiant ou mot de passe incorrect.')
    await router.push('/demandes/nouvelle')
  } catch (error) {
    erreur.value = error.message
  } finally {
    chargement.value = false
  }
}
</script>

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
      max-width: 350px;
      background-color: #fff;
      box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
      border-radius: 10px;
      padding: 30px;
    }

    .title {
      text-align: center;
      margin: 0 0 30px;
      font-size: 28px;
      font-weight: 800;
    }

    .form {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .input {
      width: 100%;
      border-radius: 20px;
      border: 1px solid #c0c0c0;
      padding: 12px 15px;
      outline: 0;
      transition: border-color 0.2s;
    }

    .input:focus {
      border-color: teal;
    }

    .page-link {
      margin: 0;
      text-align: end;
      color: #747474;
      text-decoration: underline;
      text-decoration-color: #747474;
    }

    .page-link-label {
      cursor: pointer;
      font-size: 12px;
      font-weight: 700;
    }

    .page-link-label:hover {
      color: #000;
    }

    .form-btn {
      padding: 12px 15px;
      font-family: inherit;
      border-radius: 20px;
      border: 0;
      outline: 0;
      background: teal;
      color: #fff;
      cursor: pointer;
      box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
    }

    .form-btn:hover {
      background: #006666;
    }

    .form-btn:active {
      box-shadow: none;
    }

    .candidate-link {
      display: block;
      margin-top: 20px;
      text-align: center;
      color: teal;
      font-size: 12px;
      font-weight: 700;
      text-decoration: underline;
      text-decoration-color: teal;
    }
    .error { color: #b42318; margin: 0; font-size: 14px; }
</style>
