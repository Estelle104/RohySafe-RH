<script setup>
defineProps({
  offre: { type: Object, required: true },
})
</script>

<template>
  <article class="contract-card">
    <div class="card-heading">
      <span class="card-icon">RS</span>
      <div>
        <p class="eyebrow">Détail du contrat</p>
        <h2>{{ offre.titre }}</h2>
      </div>
    </div>

    <p class="description">{{ offre.descriptions }}</p>

    <div class="contract-grid">
      <div>
        <span class="label">Type de contrat</span>
        <strong>{{ offre.type_contrat || '-' }}</strong>
      </div>
      <div>
        <span class="label">Salaire proposé</span>
        <strong>{{ offre.salaire_propose || '-' }}</strong>
      </div>
      <div>
        <span class="label">Postes à pourvoir</span>
        <strong>{{ offre.nombre_demande || '-' }}</strong>
      </div>
      <div>
        <span class="label">Prise de poste</span>
        <strong>{{ offre.date_prise_poste || '-' }}</strong>
      </div>
    </div>

    <section>
      <h3>Compétences requises</h3>
      <p v-if="!offre.competences_requises?.length" class="empty">
        Aucune compétence requise renseignée.
      </p>
      <ul v-else>
        <li v-for="competence in offre.competences_requises" :key="competence.id">
          <strong>{{ competence.competence }}</strong>
          <span v-if="competence.categorie"> ({{ competence.categorie }})</span>
          : niveau minimal {{ competence.note_minimale }}/20
          <strong v-if="competence.obligatoire"> (obligatoire)</strong>
        </li>
      </ul>
    </section>

    <section>
      <h3>Expérience demandée</h3>
      <p v-if="!offre.exigences_experience?.length" class="empty">
        Aucune exigence d’expérience renseignée.
      </p>
      <ul v-else>
        <li v-for="exigence in offre.exigences_experience" :key="exigence.id">
          {{ exigence.poste }} :
          {{ exigence.duree_min }} mois
          <template v-if="exigence.duree_max"> à {{ exigence.duree_max }} mois</template>
          <strong v-if="exigence.obligatoire"> (obligatoire)</strong>
        </li>
      </ul>
    </section>
  </article>
</template>

<style scoped>
.contract-card {
  padding: 22px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #fff;
}

.card-heading {
  display: flex;
  align-items: center;
  gap: 12px;
}

.card-icon {
  display: grid;
  width: 42px;
  height: 42px;
  place-items: center;
  border-radius: 50%;
  background: teal;
  color: #fff;
  font-weight: 800;
}

.eyebrow,
.label {
  display: block;
  margin: 0;
  color: #667085;
  font-size: 12px;
}

h2 {
  margin: 3px 0 0;
  color: teal;
  font-size: 20px;
}

.description {
  margin: 18px 0;
  color: #444;
  line-height: 1.5;
}

.contract-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-bottom: 20px;
}

.contract-grid > div {
  display: grid;
  gap: 4px;
  padding: 12px;
  border-radius: 8px;
  background: #f1f3f6;
}

h3 {
  margin: 20px 0 8px;
  color: teal;
  font-size: 16px;
}

ul {
  margin: 0;
  padding-left: 20px;
  line-height: 1.7;
}

.empty {
  color: #667085;
  font-size: 13px;
}

@media (max-width: 520px) {
  .contract-grid {
    grid-template-columns: 1fr;
  }
}
</style>
