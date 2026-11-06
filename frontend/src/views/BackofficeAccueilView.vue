<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import SidebarMenu from '../components/SidebarMenu.vue'

const router = useRouter()
const utilisateur = ref(null)
const menuOuvert = ref(false)

// Rôles autorisés sur cette page.
const ROLES_AUTORISES = ['DG', 'RH']

const initiales = computed(() =>
  (utilisateur.value?.login ?? '').slice(0, 2).toUpperCase()
)

// Récupère l'utilisateur connecté pour la sidebar.
onMounted(async () => {
  try {
    const r = await fetch('/api/auth/me', { credentials: 'include' })
    if (!r.ok) throw new Error()
    const me = await r.json()
    if (!me.utilisateur) throw new Error()
    utilisateur.value = me.utilisateur

    // Masquage côté interface seulement : la vraie protection reste côté serveur.
    const role = String(me.utilisateur.role ?? '').toUpperCase()
    if (!ROLES_AUTORISES.includes(role)) {
      router.replace('/dashboard')
    }
  } catch {
    router.replace('/login')
  }
})

// Liens de navigation du backoffice : adapter les chemins à ton router/index.js
const liens = [
  {
    to: '/backoffice/candidats-ideaux',
    titre: 'Candidats idéaux',
    description:
      'Consultez les candidats les mieux classés pour chaque offre, selon leur score de présélection.',
    icone: 'candidats',
  },
  {
    to: '/backoffice/offres',
    titre: 'Exigences offres',
    description:
      'Définissez pour chaque offre les expériences et les compétences requises.',
    icone: 'exigences',
  },
]
</script>

<template>
  <div class="app-layout">
    <SidebarMenu
      :nom="utilisateur?.login ?? ''"
      :role="utilisateur?.departement || utilisateur?.role || ''"
      :code-role="utilisateur?.role ?? ''"
      :initiales="initiales"
      :ouvert="menuOuvert"
      @fermer="menuOuvert = false"
    />

    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>

      <div class="contenu">
        <section class="entete">
          <p class="eyebrow">Administration</p>
          <h1>Espace de gestion</h1>
          <p class="sous-titre">
            Choisissez une section pour gérer la présélection des candidats et les
            critères des offres.
          </p>
        </section>

        <section class="grille">
          <router-link
            v-for="lien in liens"
            :key="lien.to"
            :to="lien.to"
            class="carte"
          >
            <div class="pastille">
              <!-- Candidats -->
              <svg v-if="lien.icone === 'candidats'" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="9" cy="8" r="3.5" />
                <path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6" />
                <path d="M16 11.5l2 2 4-4.5" />
              </svg>
              <!-- Exigences -->
              <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                <rect x="4" y="3" width="16" height="18" rx="2.5" />
                <path d="M8 8h8M8 12h8M8 16h5" />
              </svg>
            </div>

            <h2>{{ lien.titre }}</h2>
            <p class="description">{{ lien.description }}</p>

            <span class="acces">Accéder →</span>
          </router-link>
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
  width: min(900px, 100%);
  margin: 0 auto;
  padding: 40px 16px 60px;
}

.entete {
  margin-bottom: 28px;
}

.eyebrow {
  margin: 0 0 6px;
  color: #667085;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

h1 {
  margin: 0;
  color: teal;
  font-size: 28px;
}

.sous-titre {
  max-width: 650px;
  margin: 8px 0 0;
  color: #667085;
  font-size: 14px;
  line-height: 1.5;
}

.grille {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.carte {
  display: flex;
  flex-direction: column;
  padding: 24px;
  border-radius: 12px;
  background: #fff;
  color: inherit;
  text-decoration: none;
  box-shadow: rgba(0, 0, 0, 0.12) 0 4px 14px;
  transition: transform 0.15s, box-shadow 0.15s;
}

.carte:hover {
  transform: translateY(-3px);
  box-shadow: rgba(0, 0, 0, 0.2) 0 8px 20px;
}

.pastille {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 52px;
  height: 52px;
  margin-bottom: 16px;
  border-radius: 14px;
  background: #e6f2f2;
}

.pastille svg {
  width: 26px;
  height: 26px;
  fill: none;
  stroke: teal;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

h2 {
  margin: 0;
  font-size: 18px;
}

.description {
  margin: 10px 0 18px;
  color: #475467;
  font-size: 14px;
  line-height: 1.5;
}

.acces {
  margin-top: auto;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
  color: teal;
  font-size: 13px;
  font-weight: 700;
}

.carte:hover .acces {
  color: #006666;
}

@media (max-width: 960px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .burger {
    display: block;
  }
}

@media (max-width: 720px) {
  .grille {
    grid-template-columns: 1fr;
  }
}
</style>