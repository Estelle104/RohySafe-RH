<!--
  Le PARENT : c'est lui qui possède l'état (données, filtre actif)
  et qui décide quoi faire des événements remontés par les enfants.
-->
<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import CompteurNotifications from './CompteurNotifications.vue'
import FiltreStatuts from './FiltreStatuts.vue'
import ListeNotifications from './ListeNotifications.vue'
import SidebarMenu from '../components/SidebarMenu.vue'

// Adapte l'URL à ta route CodeIgniter 4
const API_URL = '/api/demandes/notifications'

const statuts = [
  'Tous',
  'Créée',
  'En attente',
  'En validation Finance',
  'En validation Direction générale',
  'Validée',
  'Refusée',
  'Annulée',
  'Annulée par la Finance',
]
const statutActif = ref('Tous')
const notifications = ref([])
const menuOuvert = ref(false)
const router = useRouter()

const notificationsFiltrees = computed(() =>
  statutActif.value === 'Tous'
    ? notifications.value
    : notifications.value.filter(n => n.statut === statutActif.value)
)

onMounted(async () => {
  const res = await fetch(API_URL, { headers: { Accept: 'application/json' }, credentials: 'include' })
  if (!res.ok) throw new Error('Impossible de charger les notifications.')
  notifications.value = await res.json()
})

function ouvrir(notification) {
  if (notification.statut !== 'Créée') return
  router.push({ name: 'demande-detail', params: { id: notification.id } })
}
</script>

<template>
  <div class="app-layout">
    <!-- Nom / rôle / initiales : à remplacer par l'utilisateur connecté -->
    <SidebarMenu
      nom="Utilisateur"
      role="Manager"
      codeRole="RH"
      initiales="U"
      :notifications="notifications.length"
      :ouvert="menuOuvert"
      @fermer="menuOuvert = false"
    />

    <main class="contenu">
      <header class="topbar">
        <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
      </header>

      <section class="page">
        <div class="entete">
          <p class="eyebrow">Mon espace</p>
          <h1>Notifications</h1>
        </div>

        <CompteurNotifications :nbNotifications="notifications.length"
                               :nbFiltres="notificationsFiltrees.length" />
        <FiltreStatuts :statuts="statuts" :actif="statutActif"
                       @choisir="statutActif = $event" />
        <ListeNotifications :notifications="notificationsFiltrees"
                            @ouvrir="ouvrir" />
      </section>
    </main>
  </div>
</template>

<style scoped>
.app-layout {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  min-height: 100vh;
  background: #f1f3f6;
  color: #222;
  font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
    "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
}

.contenu {
  min-width: 0;
}

.topbar {
  display: none;
  padding: 12px 16px 0;
}

.burger {
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

.page {
  max-width: 720px;
  margin: 0 auto;
  padding: 32px 16px 60px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.eyebrow {
  margin: 0 0 6px;
  color: #747474;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

h1 {
  margin: 0;
  color: teal;
  font-size: 28px;
  font-weight: 800;
}

@media (max-width: 960px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .topbar {
    display: block;
  }
}
</style>