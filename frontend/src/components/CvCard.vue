<script setup>
defineProps({
  candidat: { type: Object, required: true },
  details: { type: Object, default: null },
  documents: { type: Array, default: () => [] },
})

defineEmits(['close'])

function experienceSatisfaite(experience) {
  const dureeMin = Number(
    experience.duree_min_jours ?? Number(experience.duree_min ?? 0) * 30
  )

  return Number(experience.duree_jours ?? 0) >= dureeMin
}
</script>

<template>
  <article class="cv-card">
    <button class="close" type="button" @click="$emit('close')">Fermer</button>
    <div class="cv-heading">
      <div class="avatar">{{ candidat.prenom?.charAt(0) }}{{ candidat.nom?.charAt(0) }}</div>
      <div>
        <p class="eyebrow">Curriculum vitae</p>
        <h2>{{ candidat.prenom }} {{ candidat.nom }}</h2>
        <p class="contact">{{ candidat.email }} · {{ candidat.ville || 'Ville non renseignée' }}</p>
      </div>
    </div>

    <section>
      <h3>Documents</h3>
      <p v-if="documents.length === 0" class="empty">Aucun document CV trouvé.</p>
      <ul v-else>
        <li v-for="document in documents" :key="document.id">
          <strong>{{ document.nom_fichier }}</strong>
          <span>{{ document.type_document }} · ajouté le {{ document.date_ajout }}</span>
        </li>
      </ul>
    </section>

    <section>
      <h3>Compétences</h3>
      <p v-if="!details?.competences?.length" class="empty">Aucune compétence renseignée.</p>
      <ul v-else>
        <li v-for="competence in details.competences" :key="competence.id">
          <strong>{{ competence.competence }}</strong>
          <span>{{ competence.categorie }} · niveau {{ competence.niveau }}/20</span>
        </li>
      </ul>
    </section>

    <section>
      <h3>Expériences</h3>
      <p v-if="!details?.experiences?.length" class="empty">Aucune expérience renseignée.</p>
      <ul v-else>
        <li v-for="experience in details.experiences" :key="experience.id">
          <strong>{{ experience.poste }}</strong>
          <span>{{ experience.date_debut }} - {{ experience.date_fin || 'aujourd’hui' }}</span>
          <span>{{ experience.description }}</span>
        </li>
      </ul>
    </section>

    <section v-if="details?.experiences_exigences">
      <h3>Expériences demandées pour cette offre</h3>
      <p v-if="details.experiences_exigences.length === 0" class="empty">
        Aucune expérience renseignée pour cette offre.
      </p>
      <ul v-else>
        <li
          v-for="experience in details.experiences_exigences"
          :key="experience.id"
        >
          <strong>{{ experience.poste }}</strong>
          <span>
            {{ experience.date_debut }} - {{ experience.date_fin }} ·
            {{ experience.duree_jours }} jour(s)
          </span>
          <span
            class="statut"
            :class="experienceSatisfaite(experience) ? 'ok' : 'ko'"
          >
            {{ experienceSatisfaite(experience) ? 'Satisfait' : 'Insuffisant' }} ·
            requis {{ experience.duree_min }} mois
            <template v-if="experience.duree_max"> à {{ experience.duree_max }} mois</template>
            <template v-if="experience.obligatoire === true"> · obligatoire</template>
          </span>
        </li>
      </ul>
    </section>

    <section>
      <h3>Formations</h3>
      <p v-if="!details?.formations?.length" class="empty">Aucune formation renseignée.</p>
      <ul v-else>
        <li v-for="formation in details.formations" :key="formation.id">
          <strong>{{ formation.diplome }}</strong>
          <span>{{ formation.etablissement }}</span>
        </li>
      </ul>
    </section>
  </article>
</template>

<style scoped>
.cv-card {
  position: relative;
  width: min(620px, 100%);
  max-height: 90vh;
  overflow-y: auto;
  padding: 26px;
  border-radius: 12px;
  background: #fff;
}

.cv-heading {
  display: flex;
  align-items: center;
  gap: 14px;
  padding-right: 60px;
}

.avatar {
  display: grid;
  width: 52px;
  height: 52px;
  place-items: center;
  border-radius: 50%;
  background: #e6f2f2;
  color: teal;
  font-weight: 800;
}

.eyebrow,
.contact,
.empty {
  color: #667085;
  font-size: 13px;
}

.eyebrow {
  margin: 0;
}

h2 {
  margin: 3px 0;
  color: teal;
}

.contact {
  margin: 0;
}

h3 {
  margin: 22px 0 8px;
  color: teal;
  font-size: 16px;
}

ul {
  margin: 0;
  padding-left: 20px;
}

li {
  margin-bottom: 8px;
}

li span {
  display: block;
  color: #667085;
  font-size: 13px;
}

li span.statut {
  display: inline-block;
  margin-top: 4px;
  padding: 3px 9px;
  border-radius: 10px;
  font-weight: 700;
}

li span.statut.ok {
  background: #e7f6ec;
  color: #067647;
}

li span.statut.ko {
  background: #fdecec;
  color: #b42318;
}

.close {
  position: absolute;
  top: 14px;
  right: 14px;
  border: 0;
  background: transparent;
  color: #667085;
  cursor: pointer;
}
</style>
