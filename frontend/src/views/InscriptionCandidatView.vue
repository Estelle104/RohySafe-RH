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
    <div class="form-container">
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
    </div>
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  background: #f1f3f6;
  font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
    "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
}

.form-container {
  width: 100%;
  max-width: 520px;
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

@media (max-width: 520px) {
  .grille {
    grid-template-columns: 1fr;
  }
}
</style>
