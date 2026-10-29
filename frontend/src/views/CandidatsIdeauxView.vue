<script setup>
import { useCandidatsIdeaux } from '../composables/useCandidatsIdeaux'
import CvCard from '../components/CvCard.vue'
import EntretienDialog from '../components/EntretienDialog.vue'

const {
  candidatsFiltres,
  chargement,
  erreur,
  recherche,
  cvCandidat,
  cvDetails,
  cvDocuments,
  erreurAction,
  messageAction,
  entretienCandidat,
  entretienEnCours,
  voirCv,
  fermerCv,
  ouvrirEntretien,
  fermerEntretien,
  planifierEntretien,
  retourner,
  gererExigences,
  peutGererExigences,
} = useCandidatsIdeaux()

function formaterDate(valeur) {
  if (!valeur) {
    return ''
  }

  const date = new Date(String(valeur).replace(' ', 'T'))
  return Number.isNaN(date.getTime())
    ? valeur
    : date.toLocaleString('fr-FR', { dateStyle: 'medium', timeStyle: 'short' })
}
</script>

<template>
    <header class="navbar">
      <span class="brand">RohySafe</span>
      <input
        v-model="recherche"
        type="text"
        class="search"
        placeholder="Rechercher candidat..."
      />
      <div class="nav-right">
        <router-link class="nav-link" :to="{ name: 'entretiens-offres' }">Entretiens</router-link>
        <span class="nav-link">Mes candidatures</span>
        <div class="avatar-me">TH</div>
      </div>
    </header>
  <main class="page">
    <div class="entete-actions">
      <button class="btn secondary back-button" type="button" @click="retourner">
        Retour
      </button>
      <button
        v-if="peutGererExigences"
        class="btn back-button"
        type="button"
        @click="gererExigences"
      >
        Gérer les exigences
      </button>
    </div>

    <p v-if="chargement" class="message">Chargement des candidats...</p>
    <p v-else-if="erreur" class="message error">{{ erreur }}</p>
    <p v-else-if="candidatsFiltres.length === 0" class="message">
      Aucun candidat ne correspond à votre recherche.
    </p>

    <section v-else class="candidats">
      <article
        v-for="(candidat, index) in candidatsFiltres"
        :key="candidat.id_candidature"
        class="card"
      >
        <div class="rank">{{ index + 1 }}</div>
        <div class="identity">
          <h2>{{ candidat.prenom }} {{ candidat.nom }}</h2>
          <p>{{ candidat.email }} · {{ candidat.ville || 'Ville non renseignée' }}</p>
          <small v-if="candidat.competences_validees">
            Compétences validées : {{ candidat.competences_validees }}
          </small>
          <small v-else>Aucun critère de compétence pour ce poste</small>
          <small v-if="candidat.entretien_cree">
            Entretien planifié : {{ formaterDate(candidat.date_entretien) }}
          </small>
        </div>
        <div class="score">
          <strong>{{ candidat.score_total }}</strong>
          <span>/ score</span>
          <small>{{ candidat.nb_competences_validees }} compétence(s)</small>
        </div>
        <div class="actions">
          <button class="btn secondary" @click="voirCv(candidat)">Voir le CV</button>
          <button
            class="btn"
            :disabled="!candidat.id_candidature || candidat.entretien_cree"
            @click="ouvrirEntretien(candidat)"
          >
            {{ candidat.entretien_cree ? 'Entretien planifié' : 'Planifier un entretien' }}
          </button>
        </div>
      </article>
    </section>

    <p v-if="erreurAction" class="message error">{{ erreurAction }}</p>
    <p v-if="messageAction" class="message success">{{ messageAction }}</p>

    <div v-if="cvCandidat" class="modal-backdrop" @click.self="fermerCv">
      <CvCard
        :candidat="cvCandidat"
        :details="cvDetails"
        :documents="cvDocuments"
        @close="fermerCv"
      />
    </div>

    <EntretienDialog
      v-if="entretienCandidat"
      :candidat="entretienCandidat"
      :enregistrement="entretienEnCours"
      :erreur="erreurAction"
      @submit="planifierEntretien"
      @close="fermerEntretien"
    />
  </main>
</template>

<style scoped>
.page {
  min-height: 100vh;
  padding: 40px 20px;
  background: #f1f3f6;
  color: #222;
  font-family: "Lucida Sans", sans-serif;
}

.brand {
  font-size: 22px;
  font-weight: 800;
  color: teal;
}


.navbar {
  position: sticky;
  top: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 20px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.15) 0 2px 8px;
}

.brand {
  font-size: 22px;
  font-weight: 800;
  color: teal;
}

.search {
  flex: 1;
  max-width: 360px;
  border-radius: 20px;
  border: 1px solid #c0c0c0;
  padding: 10px 15px;
  outline: 0;
  font-family: inherit;
  transition: border-color 0.2s;
}

.search:focus {
  border-color: teal;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.nav-link {
  font-size: 13px;
  font-weight: 700;
  color: #747474;
  cursor: pointer;
}

.nav-link:hover {
  color: #000;
}

.entete-actions {
  display: flex;
  max-width: 900px;
  margin: 0 auto 16px;
  gap: 10px;
}

.header,
.candidats {
  max-width: 900px;
  margin: 0 auto;
}

.header {
  margin-bottom: 24px;
}

.header h1 {
  margin: 0 0 8px;
  color: teal;
}

.header p,
.message,
.identity p,
.identity small,
.score span,
.score small {
  color: #667085;
}

.candidats {
  display: grid;
  gap: 14px;
}

.card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 18px;
  background: #fff;
  border-radius: 10px;
  box-shadow: rgba(0, 0, 0, 0.12) 0 3px 12px;
}

.rank {
  min-width: 36px;
  color: teal;
  font-size: 22px;
  font-weight: 800;
  text-align: center;
}

.identity {
  flex: 1;
}

.identity h2 {
  margin: 0 0 6px;
  font-size: 18px;
}

.identity p,
.identity small,
.score span,
.score small {
  display: block;
  margin: 4px 0 0;
  font-size: 12px;
}

.score {
  min-width: 110px;
  color: teal;
  text-align: right;
}

.score strong {
  display: block;
  font-size: 24px;
}

.error {
  color: #b42318;
}

.success {
  color: #137333;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  justify-content: flex-end;
}

.btn {
  padding: 9px 14px;
  border: 0;
  border-radius: 18px;
  background: teal;
  color: #fff;
  cursor: pointer;
}

.btn.secondary {
  background: #fff;
  border: 1px solid teal;
  color: teal;
}

.btn:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 20;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgb(0 0 0 / 45%);
}

</style>
