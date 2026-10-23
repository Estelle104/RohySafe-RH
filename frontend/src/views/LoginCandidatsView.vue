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
  <div class="login-page">
    <!-- Moitié gauche : illustration -->
    <section class="illustration">
      <div class="illustration-content">
        <svg class="art" viewBox="0 0 420 340" role="img" aria-label="Illustration d'un CV et d'une recherche d'emploi">
          <!-- cercles décoratifs -->
          <circle cx="60" cy="60" r="38" fill="#ffffff" opacity="0.08" />
          <circle cx="370" cy="280" r="52" fill="#ffffff" opacity="0.08" />
          <circle cx="350" cy="50" r="14" fill="#ffffff" opacity="0.15" />
          <circle cx="40" cy="290" r="10" fill="#ffffff" opacity="0.15" />

          <!-- carte CV arrière -->
          <rect x="95" y="52" width="190" height="240" rx="14" fill="#ffffff" opacity="0.25" transform="rotate(-6 190 172)" />

          <!-- carte CV principale -->
          <rect x="120" y="40" width="190" height="250" rx="14" fill="#ffffff" />
          <circle cx="170" cy="92" r="26" fill="#b2dfdb" />
          <circle cx="170" cy="86" r="9" fill="#00796b" />
          <path d="M152 108 q18 -16 36 0" fill="#00796b" />
          <rect x="208" y="76" width="82" height="9" rx="4.5" fill="#004d4d" />
          <rect x="208" y="94" width="56" height="7" rx="3.5" fill="#9ecfcb" />

          <rect x="142" y="140" width="146" height="7" rx="3.5" fill="#d5ebe9" />
          <rect x="142" y="158" width="120" height="7" rx="3.5" fill="#d5ebe9" />
          <rect x="142" y="176" width="136" height="7" rx="3.5" fill="#d5ebe9" />

          <rect x="142" y="204" width="48" height="20" rx="10" fill="#e0f2f1" />
          <rect x="196" y="204" width="56" height="20" rx="10" fill="#e0f2f1" />
          <rect x="142" y="232" width="62" height="20" rx="10" fill="#e0f2f1" />

          <!-- pastille validée -->
          <circle cx="300" cy="258" r="30" fill="#00897b" stroke="#ffffff" stroke-width="6" />
          <path d="M286 258 l10 10 l18 -20" fill="none" stroke="#ffffff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" />

          <!-- loupe -->
          <circle cx="92" cy="206" r="34" fill="none" stroke="#ffffff" stroke-width="9" />
          <circle cx="92" cy="206" r="26" fill="#ffffff" opacity="0.18" />
          <line x1="117" y1="232" x2="146" y2="262" stroke="#ffffff" stroke-width="11" stroke-linecap="round" />
        </svg>

        <h2 class="illustration-title">Trouvez le poste qui vous correspond</h2>
        <p class="illustration-text">
          Déposez votre CV, suivez vos candidatures et soyez présélectionné plus vite.
        </p>
      </div>
    </section>

    <!-- Moitié droite : formulaire -->
    <section class="panneau-form">
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
    </section>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1fr 1fr;
  font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
    "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
}

/* ---------- Gauche : illustration ---------- */
.illustration {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
  background: linear-gradient(145deg, #006666 0%, #008080 55%, #26a69a 100%);
  color: #fff;
}

.illustration-content {
  max-width: 440px;
  text-align: center;
}

.art {
  width: 100%;
  height: auto;
  margin-bottom: 28px;
}

.illustration-title {
  margin: 0 0 10px;
  font-size: 26px;
  font-weight: 800;
  line-height: 1.25;
}

.illustration-text {
  margin: 0;
  font-size: 14px;
  line-height: 1.6;
  opacity: 0.85;
}

/* ---------- Droite : formulaire ---------- */
.panneau-form {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 32px 24px;
  background: #fff;
}

.panneau-form > * {
  width: 100%;
  max-width: 380px;
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

/* ---------- Mobile / tablette : on masque l'illustration ---------- */
@media (max-width: 900px) {
  .login-page {
    grid-template-columns: 1fr;
  }

  .illustration {
    display: none;
  }
}
</style>