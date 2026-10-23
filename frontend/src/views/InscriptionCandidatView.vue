<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { estCandidatConnecte, inscrireCandidat } from '../services/candidatAuth'

const route = useRoute()
const router = useRouter()
const formulaire = reactive({
  nom: '',
  prenom: '',
  email: '',
  telephone: '',
  ville: '',
  identifiant: '',
  mot_de_passe: '',
  confirmation: '',
})
const erreur = ref('')
const chargement = ref(false)

const redirection = computed(() => {
  const cible = route.query.redirect
  return typeof cible === 'string' && cible.startsWith('/') && !cible.startsWith('//')
    ? cible
    : '/offres'
})

const lienConnexion = computed(() => ({
  name: 'candidat-login',
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

async function sInscrire() {
  erreur.value = ''

  if (!formulaire.nom.trim() || !formulaire.prenom.trim()) {
    erreur.value = 'Le nom et le prénom sont obligatoires.'
    return
  }

  if (!formulaire.email.trim()) {
    erreur.value = 'L’adresse email est obligatoire.'
    return
  }

  if (!/^[A-Za-z0-9._-]{3,100}$/.test(formulaire.identifiant.trim())) {
    erreur.value = 'L’identifiant doit contenir entre 3 et 100 caractères, sans espace.'
    return
  }

  if (formulaire.mot_de_passe.length < 8) {
    erreur.value = 'Le mot de passe doit contenir au moins 8 caractères.'
    return
  }

  if (formulaire.mot_de_passe !== formulaire.confirmation) {
    erreur.value = 'Les deux mots de passe ne correspondent pas.'
    return
  }

  chargement.value = true

  try {
    await inscrireCandidat({
      nom: formulaire.nom.trim(),
      prenom: formulaire.prenom.trim(),
      email: formulaire.email.trim(),
      telephone: formulaire.telephone.trim(),
      ville: formulaire.ville.trim(),
      identifiant: formulaire.identifiant.trim(),
      mot_de_passe: formulaire.mot_de_passe,
      confirmation: formulaire.confirmation,
    })

    router.replace(redirection.value)
  } catch (e) {
    erreur.value = e.message
  } finally {
    chargement.value = false
  }
}
</script>

<template>
  <div class="page">
    <!-- Moitié gauche : illustration -->
    <section class="illustration">
      <div class="illustration-content">
        <svg class="art" viewBox="0 0 420 340" role="img" aria-label="Illustration de création de compte">
          <!-- cercles décoratifs -->
          <circle cx="55" cy="70" r="40" fill="#ffffff" opacity="0.08" />
          <circle cx="372" cy="270" r="54" fill="#ffffff" opacity="0.08" />
          <circle cx="360" cy="55" r="13" fill="#ffffff" opacity="0.15" />
          <circle cx="45" cy="285" r="10" fill="#ffffff" opacity="0.15" />

          <!-- carte profil -->
          <rect x="105" y="45" width="210" height="240" rx="16" fill="#ffffff" />
          <rect x="105" y="45" width="210" height="70" rx="16" fill="#b2dfdb" />
          <rect x="105" y="90" width="210" height="25" fill="#b2dfdb" />

          <!-- avatar -->
          <circle cx="210" cy="115" r="38" fill="#ffffff" />
          <circle cx="210" cy="115" r="32" fill="#00897b" />
          <circle cx="210" cy="105" r="11" fill="#ffffff" />
          <path d="M190 134 q20 -22 40 0" fill="#ffffff" />

          <!-- lignes d'infos -->
          <rect x="160" y="168" width="100" height="10" rx="5" fill="#004d4d" />
          <rect x="177" y="188" width="66" height="7" rx="3.5" fill="#9ecfcb" />

          <rect x="135" y="215" width="150" height="9" rx="4.5" fill="#d5ebe9" />
          <rect x="135" y="233" width="150" height="9" rx="4.5" fill="#d5ebe9" />
          <rect x="135" y="251" width="104" height="9" rx="4.5" fill="#d5ebe9" />

          <!-- badge + -->
          <circle cx="306" cy="270" r="32" fill="#00897b" stroke="#ffffff" stroke-width="6" />
          <line x1="306" y1="254" x2="306" y2="286" stroke="#ffffff" stroke-width="7" stroke-linecap="round" />
          <line x1="290" y1="270" x2="322" y2="270" stroke="#ffffff" stroke-width="7" stroke-linecap="round" />

          <!-- enveloppe -->
          <rect x="40" y="180" width="76" height="54" rx="9" fill="#ffffff" opacity="0.9" />
          <path d="M44 188 l34 24 l34 -24" fill="none" stroke="#008080" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>

        <h2 class="illustration-title">Rejoignez RohySafe en quelques minutes</h2>
        <p class="illustration-text">
          Créez votre compte, enregistrez votre CV et postulez aux offres qui vous intéressent.
        </p>
      </div>
    </section>

    <!-- Moitié droite : formulaire -->
    <section class="panneau-form">
      <p class="title">Créer un compte</p>
      <p class="sub-title">
        Inscrivez-vous pour postuler aux offres et enregistrer votre CV.
      </p>

      <form class="form" @submit.prevent="sInscrire">
        <div class="grille">
          <label class="field">
            Nom
            <input
              v-model="formulaire.nom"
              class="input"
              type="text"
              autocomplete="family-name"
              placeholder="Rakoto"
            />
          </label>

          <label class="field">
            Prénom
            <input
              v-model="formulaire.prenom"
              class="input"
              type="text"
              autocomplete="given-name"
              placeholder="Miora"
            />
          </label>
        </div>

        <label class="field">
          Adresse email
          <input
            v-model="formulaire.email"
            class="input"
            type="email"
            autocomplete="email"
            placeholder="email@exemple.mg"
          />
        </label>

        <div class="grille">
          <label class="field">
            Téléphone
            <input
              v-model="formulaire.telephone"
              class="input"
              type="tel"
              autocomplete="tel"
              placeholder="034 00 000 00"
            />
          </label>

          <label class="field">
            Ville
            <input
              v-model="formulaire.ville"
              class="input"
              type="text"
              autocomplete="address-level2"
              placeholder="Antananarivo"
            />
          </label>
        </div>

        <label class="field">
          Identifiant
          <input
            v-model="formulaire.identifiant"
            class="input"
            type="text"
            autocomplete="username"
            placeholder="miora_test"
          />
        </label>

        <div class="grille">
          <label class="field">
            Mot de passe
            <input
              v-model="formulaire.mot_de_passe"
              class="input"
              type="password"
              autocomplete="new-password"
              placeholder="8 caractères minimum"
            />
          </label>

          <label class="field">
            Confirmation
            <input
              v-model="formulaire.confirmation"
              class="input"
              type="password"
              autocomplete="new-password"
              placeholder="Répéter le mot de passe"
            />
          </label>
        </div>

        <p v-if="erreur" class="error">{{ erreur }}</p>

        <button class="form-btn" type="submit" :disabled="chargement">
          {{ chargement ? 'Création...' : 'Créer mon compte' }}
        </button>
      </form>

      <p class="bas-de-page">
        Déjà un compte ?
        <router-link :to="lienConnexion">Se connecter</router-link>
      </p>

      <router-link class="page-link" to="/offres">
        Retour aux offres
      </router-link>
    </section>
  </div>
</template>

<style scoped>
.page {
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
  max-width: 480px;
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

.grille {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
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

.bas-de-page a,
.page-link {
  color: teal;
  font-weight: 700;
  text-decoration: underline;
  text-decoration-color: teal;
}

.page-link {
  display: block;
  margin-top: 14px;
  text-align: center;
  font-size: 12px;
}

/* ---------- Mobile / tablette ---------- */
@media (max-width: 900px) {
  .page {
    grid-template-columns: 1fr;
  }

  .illustration {
    display: none;
  }
}

@media (max-width: 520px) {
  .grille {
    grid-template-columns: 1fr;
  }
}
</style>