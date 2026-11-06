<script setup>
// props : les données qui DESCENDENT du parent vers ce composant
defineProps({
  nbNotifications: { type: Number, required: true },
  nbFiltres: { type: Number, required: true }
})
</script>

<template>
  <article>
    <h2>{{ nbFiltres }} / {{ nbNotifications }} Demandes</h2>
  </article>
</template>
