<script setup>
import { formatDateLong, formatDateShort, planningEstTermine } from '../../services/planningService'

defineProps({
  dateCourante: { type: Date, required: true },
  evenements: { type: Array, default: () => [] },
})
</script>

<template>
  <section class="daily-panel">
    <div class="day-header">
      <h2>{{ formatDateLong(dateCourante) }}</h2>
      <span class="header-counter">{{ evenements.length }} planning(s)</span>
    </div>

    <div v-if="evenements.length" class="event-list">
      <article v-for="event in evenements" :key="event.id" class="event-card">
        <div class="event-top">
          <span class="event-tag">{{ event.joursSemaine.join(' • ') }}</span>
          <span :class="['status-badge', { done: planningEstTermine(event.dateFin) }]">
            {{ planningEstTermine(event.dateFin) ? 'Terminé' : 'Actif' }}
          </span>
        </div>

        <div class="event-body">
          <h3>{{ event.libelle }}</h3>
          <div class="meta-grid">
            <div class="meta-item">
              <span class="meta-label">Date début</span>
              <strong>{{ formatDateShort(event.dateDebut) }}</strong>
            </div>
            <div class="meta-item">
              <span class="meta-label">Date fin</span>
              <strong>{{ formatDateShort(event.dateFin) }}</strong>
            </div>
            <div class="meta-item full-width">
              <span class="meta-label">Employés</span>
              <strong>{{ event.employes?.length ? event.employes.join(', ') : 'Aucun employé assigné' }}</strong>
            </div>
          </div>
        </div>

        <div class="event-actions">
          <router-link v-if="!planningEstTermine(event.dateFin)" class="btn-secondary" :to="`/planning/${event.id}/modifier`">Modifier</router-link>
          <span v-else class="btn-disabled">Terminé</span>
        </div>
      </article>
    </div>

    <div v-else class="empty-state">Aucun planning pour cette date.</div>
  </section>
</template>

<style scoped>
.daily-panel {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.day-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 18px;
  border: 1px solid #e5ebf1;
  border-radius: 14px;
  background: #fff;
}

.day-header h2 {
  margin: 0;
  color: #0f172a;
}

.header-counter {
  padding: 6px 10px;
  border-radius: 999px;
  background: #ebfbfb;
  color: #0f766e;
  font-size: 0.76rem;
  font-weight: 800;
}

.event-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.event-card {
  overflow: hidden;
  border: 1px solid #e5ebf1;
  border-radius: 14px;
  background: #fff;
  box-shadow: rgba(15, 23, 42, 0.04) 0 8px 18px;
}

.event-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 14px 18px 10px;
  border-bottom: 1px solid #eef2f7;
  background: linear-gradient(180deg, #f9fdfd 0%, #fff 100%);
}

.event-tag {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  background: #e6f7f7;
  color: #0f766e;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  background: #ecfdf5;
  color: #047857;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.status-badge.done {
  background: #f3f4f6;
  color: #6b7280;
}

.event-body {
  padding: 18px;
}

.event-card h3 {
  margin: 0 0 12px;
  font-size: 1.2rem;
  color: #111827;
}

.meta-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(180px, 1fr));
  gap: 12px;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 12px 14px;
  border: 1px solid #edf2f7;
  border-radius: 12px;
  background: #f9fbfc;
}

.meta-item.full-width {
  grid-column: 1 / -1;
}

.meta-label {
  color: #667085;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  font-weight: 700;
}

.meta-item strong {
  color: #1f2937;
  font-size: 0.92rem;
  line-height: 1.5;
}

.event-actions {
  display: flex;
  justify-content: flex-end;
  padding: 0 18px 18px;
}

.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 16px;
  border-radius: 10px;
  background: #edf8f8;
  color: #0f766e;
  font-weight: 700;
  text-decoration: none;
}

.btn-disabled {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 16px;
  border-radius: 10px;
  background: #f3f4f6;
  color: #6b7280;
  font-weight: 700;
  border: 1px solid #e5e7eb;
}

.empty-state {
  padding: 14px;
  border-radius: 12px;
  background: #f8fafc;
  color: #64748b;
  font-style: italic;
}

@media (max-width: 640px) {
  .day-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .meta-grid {
    grid-template-columns: 1fr;
  }
}
</style>
