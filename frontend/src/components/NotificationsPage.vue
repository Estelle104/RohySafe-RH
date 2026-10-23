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
  <section class="page">
    <h1>Notifications</h1>
    <CompteurNotifications :nbNotifications="notifications.length"
                           :nbFiltres="notificationsFiltrees.length" />
    <FiltreStatuts :statuts="statuts" :actif="statutActif"
                   @choisir="statutActif = $event" />
    <ListeNotifications :notifications="notificationsFiltrees"
                        @ouvrir="ouvrir" />
  </section>
</template>

<style scoped>
.page {
  max-width: 520px;
  margin: 0 auto;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}
</style>
