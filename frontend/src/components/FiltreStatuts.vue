<!--
  Le filtre ne modifie pas lui-même l'état du parent :
  il REMONTE un événement « choisir », et c'est le parent qui décide.
-->
<script setup>
defineProps({
  statuts: { type: Array, required: true },
  actif: { type: String, required: true }
})

// emits : les événements qui REMONTENT vers le parent
const emit = defineEmits(['choisir'])
</script>

<template>
  <nav class="filtres">
    <button v-for="s in statuts" :key="s"
            :class="{ actif: s === actif }"
            @click="emit('choisir', s)">{{ s }}</button>
  </nav>
</template>

<!-- scoped : ce style ne s'applique qu'à ce composant -->
<style scoped>
.filtres { display: flex; gap: 8px; flex-wrap: wrap; }
.filtres button {
  padding: 6px 14px;
  border-radius: 20px;
  border: 1px solid #c0c0c0;
  background: #fff;
  color: #747474;
  font-weight: 700;
  cursor: pointer;
}
.filtres button:hover { text-decoration: underline; }
.filtres button.actif { border-color: teal; color: teal; }
</style>
