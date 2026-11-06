<script setup>
import NotificationCard from './NotificationCard.vue'

defineProps({
  notifications: { type: Array, required: true }
})

// la liste ne décide rien : elle transmet l'événement de la carte au parent
const emit = defineEmits(['ouvrir'])
</script>

<template>
  <main class="liste">
    <NotificationCard v-for="n in notifications" :key="n.id"
                      :notification="n" :compacte="false"
                      @ouvrir="emit('ouvrir', $event)" />
  </main>
  <p v-if="notifications.length === 0" class="message">Aucune demande pour ce statut.</p>
</template>

<style scoped>
.liste { display: flex; flex-direction: column; gap: 12px; }
.message { text-align: center; color: #747474; }
</style>
