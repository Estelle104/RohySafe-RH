<script setup>
import { dateKey, formatDateCompact, formatDayLabel, idemJour, planningEstTermine } from '../../services/planningService'

const props = defineProps({
  jours: { type: Array, default: () => [] },
  evenementsParJour: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['afficher-jour'])

function getEvenementsPourJour(jour) {
  const key = dateKey(jour)
  return props.evenementsParJour?.[key] ?? []
}
</script>

<template>
  <section class="week-grid">
    <article v-for="jour in jours" :key="dateKey(jour)" class="day-column" :class="{ today: idemJour(jour, new Date()) }">
      <div class="day-column-header">
        <span>{{ formatDayLabel(jour) }}</span>
        <strong>{{ formatDateCompact(jour) }}</strong>
      </div>

      <div v-if="getEvenementsPourJour(jour).length" class="event-stack">
        <div v-for="event in getEvenementsPourJour(jour)" :key="`${event.id}-${dateKey(jour)}`" class="mini-event" @click="emit('afficher-jour', jour)">
          <div class="mini-event-head">
            <strong>{{ event.libelle }}</strong>
            <span :class="['mini-status', { done: planningEstTermine(event.dateFin) }]">
              {{ planningEstTermine(event.dateFin) ? 'Terminé' : 'Actif' }}
            </span>
          </div>
          <span>{{ event.employes?.length ? event.employes.join(', ') : 'Employés non assignés' }}</span>
        </div>
      </div>

      <div v-else class="empty-mini">Libre</div>
    </article>
  </section>
</template>

<style scoped>
.week-grid {
  display: grid;
  grid-template-columns: repeat(7, minmax(140px, 1fr));
  gap: 12px;
}

.day-column {
  min-height: 220px;
  padding: 12px;
  border: 1px solid #e5ebf1;
  border-radius: 14px;
  background: #fff;
}

.day-column.today {
  border-color: #0f766e;
  box-shadow: rgba(20, 184, 166, 0.15) 0 0 0 2px;
}

.day-column-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
  padding-bottom: 8px;
  border-bottom: 1px solid #eef2f7;
  font-size: 0.78rem;
  text-transform: capitalize;
}

.event-stack {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.mini-event {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 10px 12px;
  cursor: pointer;
  border: 1px solid #e5ebf1;
  border-radius: 14px;
  background: linear-gradient(180deg, #f4fbfb 0%, #fff 100%);
  box-shadow: rgba(15, 23, 42, 0.04) 0 8px 18px;
}

.mini-event-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.mini-event strong {
  font-size: 0.9rem;
  color: #0f172a;
}

.mini-status {
  display: inline-flex;
  padding: 4px 7px;
  border-radius: 999px;
  background: #ecfdf5;
  color: #047857;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
}

.mini-status.done {
  background: #f3f4f6;
  color: #6b7280;
}

.mini-event span {
  color: #475467;
  font-size: 0.78rem;
  line-height: 1.5;
}

.empty-mini {
  padding: 14px;
  border-radius: 12px;
  background: #f8fafc;
  color: #64748b;
  font-style: italic;
}

@media (max-width: 1000px) {
  .week-grid {
    grid-template-columns: repeat(2, minmax(180px, 1fr));
  }
}

@media (max-width: 640px) {
  .week-grid {
    grid-template-columns: 1fr;
  }
}
</style>
