<script setup>
defineProps({
  offre: { type: Object, required: true },
})
defineEmits(['details', 'postuler'])

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('fr-FR') : '-'
}
</script>

<template>
  <article class="card">
    <div class="card-header">
      <div class="logo">RS</div>
      <div>
        <p class="company">{{ offre.departement }}</p>
        <p class="meta">Publiée le {{ formatDate(offre.date_publication) }}</p>
      </div>
    </div>

    <h2 class="offer-title">{{ offre.titre }}</h2>
    <p class="offer-desc">{{ offre.descriptions }}</p>

    <div class="tags">
      <span class="tag">{{ offre.type_contrat }}</span>
      <span v-if="offre.ville" class="tag">{{ offre.ville }}</span>
      <span v-if="offre.date_limite" class="tag deadline">
        Limite : {{ formatDate(offre.date_limite) }}
      </span>
    </div>

    <div class="card-footer">
      <span class="places">
        {{ offre.nombre_demande }} poste{{ offre.nombre_demande > 1 ? 's' : '' }} à pourvoir
      </span>
      <div class="actions">
        <button class="btn secondary" @click="$emit('details', offre)">Détails</button>
        <button class="btn" @click="$emit('postuler', offre)">Postuler</button>
      </div>
    </div>
  </article>
</template>

<style scoped>
.card {
  background: #fff;
  border-radius: 10px;
  box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
  padding: 20px;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 14px;
}

.logo {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: teal;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  flex: none;
}

.company {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
}

.meta {
  margin: 2px 0 0;
  font-size: 12px;
  color: #747474;
}

.offer-title {
  margin: 0 0 8px;
  font-size: 18px;
  font-weight: 800;
}

.offer-desc {
  margin: 0 0 14px;
  font-size: 14px;
  line-height: 1.5;
  color: #444;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 14px;
}

.tag {
  padding: 4px 12px;
  border-radius: 20px;
  background: #e6f2f2;
  color: #006666;
  font-size: 12px;
  font-weight: 700;
}

.tag.deadline {
  background: #fdecec;
  color: #b42318;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-top: 14px;
  border-top: 1px solid #e5e5e5;
}

.places {
  font-size: 12px;
  color: #747474;
}

.actions {
  display: flex;
  gap: 8px;
}

.btn {
  padding: 10px 22px;
  font-family: inherit;
  border-radius: 20px;
  border: 0;
  outline: 0;
  background: teal;
  color: #fff;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.btn:hover {
  background: #006666;
}

.btn:active {
  box-shadow: none;
}

.btn.secondary {
  background: #fff;
  color: teal;
  border: 1px solid teal;
  box-shadow: none;
}

.btn.secondary:hover {
  background: #e6f2f2;
}
</style>