<script setup>
defineProps({
  notification: {
    type: Object,
    required: true
  },
  compacte: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['ouvrir'])

function formaterDate(date) {
  if (!date) return 'Non définie'

  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  }).format(new Date(date))
}

function formaterSalaire(salaire) {
  if (salaire === null || salaire === undefined || salaire === '') {
    return 'Non renseigné'
  }

  return new Intl.NumberFormat('fr-FR', {
    style: 'decimal',
    maximumFractionDigits: 2
  }).format(Number(salaire)) + ' Ar'
}
</script>

<template>
  <article
    class="card"
    :class="{ inactive: notification.statut !== 'Créée' }"
    @click="notification.statut === 'Créée' && emit('ouvrir', notification)"
  >
    <header class="card-header">
      <div>
        <h2>{{ notification.nombre_poste }}
          {{ notification.nombre_poste > 1 ? 'postes' : 'poste' }}
          — {{ notification.poste }}
        </h2>

        <p class="departement">
          {{ notification.departement }}
        </p>
      </div>

      <span class="badge">
        {{ notification.statut }}
      </span>
    </header>

    <div class="infos-principales">
      <span>
        <strong>Contrat :</strong>
        {{ notification.type_contrat }}
      </span>

      <span>
        <strong>Salaire proposé :</strong>
        {{ formaterSalaire(notification.salaire_propose) }}
      </span>
    </div>

    <div v-if="!compacte" class="details">
      <p>
        <strong>Créée le :</strong>
        {{ formaterDate(notification.date_creation) }}
      </p>

      <p>
        <strong>Prise de poste :</strong>
        {{ formaterDate(notification.date_prise_poste) }}
      </p>

      <p>
        <strong>Créée par :</strong>
        {{ notification.utilisateur || 'Inconnu' }}
      </p>

      <p>
        <strong>Responsable actuel :</strong>
        {{ notification.utilisateur_actuel || 'Non attribuée' }}
      </p>
    </div>
  </article>
</template>

<style scoped>
.card {
  background: #fff;
  border: 1px solid #d5d5d5;
  border-radius: 16px;
  padding: 18px;
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.card:hover {
  border-color: teal;
  box-shadow: 0 3px 12px rgb(0 0 0 / 7%);
}

.card.inactive {
  cursor: default;
  opacity: .72;
}

.card.inactive:hover {
  border-color: #d5d5d5;
  box-shadow: none;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 14px;
}

.card h2 {
  margin: 0 0 5px;
  font-size: 17px;
}

.departement {
  margin: 0;
  color: #747474;
  font-size: 13px;
}

.infos-principales {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 20px;
  font-size: 13px;
}

.details {
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid #eee;
}

.details p {
  margin: 0 0 7px;
  color: #555;
  font-size: 13px;
}

.badge {
  display: inline-block;
  flex-shrink: 0;
  padding: 5px 10px;
  border-radius: 10px;
  background: #e0f2f1;
  color: #00695c;
  font-size: 11px;
  font-weight: 700;
}

@media (max-width: 480px) {
  .card-header {
    flex-direction: column;
  }
}
</style>
