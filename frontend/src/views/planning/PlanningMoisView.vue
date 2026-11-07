<script setup>
import { dateKey, idemJour, planningEstTermine } from '../../services/planningService'

const props = defineProps({
  jours: { type: Array, default: () => [] },
  evenementsParJour: { type: Object, default: () => ({}) },
  dateCourante: { type: Date, required: true },
})

const emit = defineEmits(['afficher-jour'])

function getEvenementsPourJour(jour) {
  const key = dateKey(jour)
  return props.evenementsParJour?.[key] ?? []
}
</script>

<template>
  <section class="month-grid">
    <div v-for="jour in jours" :key="dateKey(jour)" class="month-cell" :class="{ outside: new Date(jour).getMonth() !== new Date(dateCourante).getMonth(), today: idemJour(jour, new Date()) }">
      <div class="month-day-head">
        <strong>{{ new Date(jour).getDate() }}</strong>
      </div>

      <div class="month-event-list">
        <div v-for="event in getEvenementsPourJour(jour)" :key="`${event.id}-${dateKey(jour)}`" class="month-event" @click="emit('afficher-jour', jour)">
          <div class="month-event-head">
            <span>{{ event.libelle }}</span>
            <small :class="['month-status', { done: planningEstTermine(event.dateFin) }]">
              {{ planningEstTermine(event.dateFin) ? 'Terminé' : 'Actif' }}
            </small>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.month-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(110px, 1fr));
  gap: 10px;
  padding: 6px 0;
}

.month-cell {
  min-height: 120px;
  padding: 10px 8px;
  border: 1px solid #e5ebf1;
  border-radius: 12px;
  background: #fff;
}

.month-cell.today {
  border-color: #0f766e;
  box-shadow: rgba(20, 184, 166, 0.15) 0 0 0 2px;
}

.month-cell.outside {
  background: #f9fafb;
  opacity: 0.75;
}

.month-day-head {
  margin-bottom: 8px;
  color: #0f172a;
}

.month-event-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.month-event {
  padding: 6px 8px;
  border: 1px solid #dff7f7;
  border-radius: 10px;
  background: #e6f7f7;
  color: #0f172a;
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
}

.month-event-head {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.month-status {
  display: inline-flex;
  align-self: flex-start;
  padding: 3px 6px;
  border-radius: 999px;
  background: #ecfdf5;
  color: #047857;
  font-size: 8px;
  font-weight: 800;
  text-transform: uppercase;
}

.month-status.done {
  background: #f3f4f6;
  color: #6b7280;
}

@media (max-width: 640px) {
  .month-grid {
    grid-template-columns: 1fr;
  }
}
</style>
