<script setup>

import { computed } from 'vue'

// Sidebar commune à tous les utilisateurs de l'entreprise.
// Adapter les chemins (`to`) à router/index.js.
const props = defineProps({
  nom: { type: String, default: '' },
  role: { type: String, default: '' },        // texte affiché (ex. "Manager · Informatique")
  codeRole: { type: String, default: '' },    // code utilisé pour le filtre : 'DG', 'FINANCE', 'RH', 'MANAGER'
  initiales: { type: String, default: '' },
  notifications: { type: Number, default: 0 },
  ouvert: { type: Boolean, default: false },
})

defineEmits(['fermer', 'deconnexion'])

const liens = [
  { to: '/dashboard', label: 'Dashboard', icone: 'accueil' },                       // tous
  { groupe: 'Recrutement' },
  { to: '/demandes/nouvelle', label: 'Demandes de personnel', icone: 'demandes', roles: ['Manager','DG'] },
  { to: '/offres/a-publier', label: 'Offres à publier', icone: 'offres', roles: ['RH','DG','Direction'] },
  { groupe: 'Mon espace' },
  { to: '/candidatures', label: 'Liste des candidatures', icone: 'demandes', roles: ['RH'] },
  { to: '/notification', label: 'Notifications', icone: 'notifications', badge: true },  // tous
  { to: '/backoffice', label: 'Gerer les offres d\'emplois', icone: 'demandes', roles: ['RH','DG'] },                          // tous
  { to: '/profil', label: 'Mon profil', icone: 'profil' },
]

const liensVisibles = computed(() => {
  // 1. on garde les liens autorisés (et tous les titres de groupe)
  const autorises = liens.filter(
    l => l.groupe || !l.roles || l.roles.includes(props.codeRole)
  )

  // 2. on retire les titres de groupe qui n'ont plus aucun lien après eux
  return autorises.filter((l, i) => {
    if (!l.groupe) return true
    const suivant = autorises[i + 1]
    return suivant && !suivant.groupe
  })
})
</script>

<template>
  <aside class="sidebar" :class="{ ouvert }">
    <p class="brand">RohySafe</p>

    <nav class="menu">
      <template v-for="(l, i) in liensVisibles" :key="i">
        <p v-if="l.groupe" class="menu-label">{{ l.groupe }}</p>

        <router-link v-else :to="l.to" class="menu-link" @click="$emit('fermer')">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <template v-if="l.icone === 'accueil'">
              <path d="M3 11l9-8 9 8" />
              <path d="M5 10v10h14V10" />
            </template>
            <template v-else-if="l.icone === 'demandes'">
              <rect x="5" y="3" width="14" height="18" rx="2" />
              <path d="M9 8h6M9 12h6M9 16h4" />
            </template>
            <template v-else-if="l.icone === 'offres'">
              <rect x="3" y="7" width="18" height="13" rx="2" />
              <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2" />
            </template>
            <template v-else-if="l.icone === 'notifications'">
              <path d="M6 9a6 6 0 0 1 12 0c0 6 2 7 2 7H4s2-1 2-7" />
              <path d="M10 20a2 2 0 0 0 4 0" />
            </template>
            <template v-else>
              <circle cx="12" cy="8" r="4" />
              <path d="M4 21c0-4 3.6-7 8-7s8 3 8 7" />
            </template>
          </svg>
          {{ l.label }}
          <span v-if="l.badge && notifications > 0" class="badge">{{ notifications }}</span>
        </router-link>
      </template>
    </nav>

    <div class="user-box">
      <div class="avatar">{{ initiales }}</div>
      <div class="user-info">
        <p class="user-name">{{ nom }}</p>
        <p class="user-role">{{ role }}</p>
      </div>
      <a href="/login">
        <button class="logout" type="button" title="Se déconnecter" aria-label="Se déconnecter"
          @click="$emit('deconnexion')">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M9 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4" />
            <path d="M16 8l4 4-4 4" />
            <path d="M20 12H9" />
          </svg>
        </button>
      </a>
    </div>
  </aside>

  <div v-if="ouvert" class="overlay" @click="$emit('fermer')"></div>
</template>

<style scoped>
.sidebar {
  position: sticky;
  top: 0;
  z-index: 20;
  display: flex;
  flex-direction: column;
  height: 100vh;
  padding: 24px 16px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.15) 2px 0 10px;
  /* Police définie ici pour ne pas dépendre de la page parente */
  font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
    "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
  letter-spacing: normal;
}

.brand {
  margin: 0 0 28px;
  padding: 0 12px;
  color: #000;
  font-size: 26px;
  font-weight: 800;
}

.menu {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 6px;
  overflow-y: auto;
}

.menu-label {
  margin: 14px 12px 4px;
  color: #747474;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

.menu-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: 20px;
  color: #555;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  transition: background 0.2s, color 0.2s;
}

.menu-link svg {
  width: 20px;
  height: 20px;
  flex: none;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.menu-link:hover {
  background: #e6f2f2;
  color: teal;
}

.menu-link.router-link-active {
  background: teal;
  color: #fff;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.badge {
  min-width: 22px;
  margin-left: auto;
  padding: 2px 7px;
  border-radius: 20px;
  background: #b42318;
  color: #fff;
  font-size: 11px;
  text-align: center;
}

.user-box {
  display: flex;
  align-items: center;
  gap: 12px;
  padding-top: 16px;
  border-top: 1px solid #e5e5e5;
}

.avatar {
  display: flex;
  flex: none;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: teal;
  color: #fff;
  font-weight: 700;
}

.user-info {
  min-width: 0;
  flex: 1;
}

.user-name {
  margin: 0;
  overflow: hidden;
  font-size: 14px;
  font-weight: 700;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-role {
  margin: 2px 0 0;
  color: #747474;
  font-size: 12px;
}

.logout {
  font-family: inherit;
  padding: 6px;
  border: 0;
  border-radius: 50%;
  background: transparent;
  color: #747474;
  cursor: pointer;
}

.logout:hover {
  background: #f1f3f6;
  color: #000;
}

.logout svg {
  display: block;
  width: 20px;
  height: 20px;
  fill: none;
  stroke: currentColor;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.overlay {
  position: fixed;
  inset: 0;
  z-index: 10;
  background: rgb(0 0 0 / 45%);
}

@media (min-width: 961px) {
  .overlay {
    display: none;
  }
}

@media (max-width: 960px) {
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 260px;
    transform: translateX(-100%);
    transition: transform 0.25s;
  }

  .sidebar.ouvert {
    transform: translateX(0);
  }
}
</style>