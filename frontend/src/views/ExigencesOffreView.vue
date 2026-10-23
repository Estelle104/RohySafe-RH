<script setup>
import { useExigencesOffre } from '../composables/useExigencesOffre'

const {
  offre,
  postes,
  competences,
  chargement,
  erreur,
  succes,
  enregistrement,
  experienceForm,
  competenceForm,
  modifierExperience,
  modifierCompetence,
  reinitialiserExperience,
  reinitialiserCompetence,
  enregistrerExperience,
  supprimerExperience,
  enregistrerCompetence,
  supprimerCompetence,
  retourner,
} = useExigencesOffre()
</script>

<template>
  <div class="page">
    <header class="navbar">
      <span class="brand">RohySafe</span>
      <span class="role">Backoffice · Exigences des offres</span>
      <button class="btn secondary" type="button" @click="retourner">
        Retour à la liste
      </button>
    </header>

    <main class="contenu">
      <p v-if="chargement" class="message">Chargement des exigences...</p>
      <p v-else-if="erreur && !offre" class="message error">{{ erreur }}</p>

      <template v-else-if="offre">
        <section class="presentation">
          <p class="eyebrow">Administration des offres</p>
          <h1>{{ offre.titre }}</h1>
          <p>{{ offre.descriptions }}</p>
          <div class="infos">
            <span>{{ offre.departement }}</span>
            <span>{{ offre.type_contrat }}</span>
            <span>{{ offre.nombre_demande }} poste(s)</span>
          </div>
        </section>

        <p v-if="erreur" class="message error">{{ erreur }}</p>
        <p v-if="succes" class="message success">{{ succes }}</p>

        <section class="panneau">
          <div class="panneau-titre">
            <div>
              <h2>Expérience demandée</h2>
              <p>
                Définissez les expériences professionnelles attendues pour cette offre.
                Les durées sont exprimées en mois.
              </p>
            </div>
          </div>

          <p v-if="!offre.exigences_experience?.length" class="message">
            Aucune exigence d’expérience enregistrée.
          </p>

          <ul v-else class="liste">
            <li
              v-for="exigence in offre.exigences_experience"
              :key="exigence.id"
              class="ligne"
            >
              <div>
                <strong>{{ exigence.poste }}</strong>
                <p>
                  {{ exigence.duree_min }} mois minimum
                  <template v-if="exigence.duree_max"> à {{ exigence.duree_max }} mois</template>
                  <span v-if="exigence.obligatoire" class="badge obligatoire">Obligatoire</span>
                </p>
              </div>
              <div class="ligne-actions">
                <button class="mini" type="button" @click="modifierExperience(exigence)">
                  Modifier
                </button>
                <button
                  class="mini danger"
                  type="button"
                  :disabled="enregistrement"
                  @click="supprimerExperience(exigence)"
                >
                  Supprimer
                </button>
              </div>
            </li>
          </ul>

          <form class="formulaire" @submit.prevent="enregistrerExperience">
            <h3>
              {{ experienceForm.id ? 'Modifier une exigence d’expérience' : 'Ajouter une exigence d’expérience' }}
            </h3>

            <div class="grille">
              <label class="pleine-largeur">
                Poste
                <select v-model="experienceForm.id_poste_experience" required>
                  <option value="">Sélectionner un poste</option>
                  <option v-for="poste in postes" :key="poste.id" :value="poste.id">
                    {{ poste.libelle }}
                  </option>
                </select>
              </label>
              <label>
                Durée minimale (mois)
                <input v-model="experienceForm.duree_min" type="number" min="0" step="1" required />
              </label>
              <label>
                Durée maximale (mois)
                <input v-model="experienceForm.duree_max" type="number" min="0" step="1" placeholder="Optionnel" />
              </label>
              <label class="case">
                <input v-model="experienceForm.obligatoire" type="checkbox" />
                Exigence obligatoire pour la présélection
              </label>
            </div>

            <div class="actions">
              <button class="btn" type="submit" :disabled="enregistrement">
                {{ experienceForm.id ? 'Enregistrer les modifications' : 'Ajouter l’exigence' }}
              </button>
              <button
                v-if="experienceForm.id"
                class="btn secondary"
                type="button"
                @click="reinitialiserExperience"
              >
                Annuler
              </button>
            </div>
          </form>
        </section>

        <section class="panneau">
          <div class="panneau-titre">
            <div>
              <h2>Compétences requises</h2>
              <p>
                Définissez les compétences attendues et la note minimale sur 20.
              </p>
            </div>
          </div>

          <p v-if="!offre.competences_requises?.length" class="message">
            Aucune compétence requise enregistrée.
          </p>

          <ul v-else class="liste">
            <li
              v-for="critere in offre.competences_requises"
              :key="critere.id"
              class="ligne"
            >
              <div>
                <strong>{{ critere.competence }}</strong>
                <p>
                  <span v-if="critere.categorie">{{ critere.categorie }} · </span>
                  Note minimale :
                  {{ critere.note_minimale === null ? 'non définie' : `${critere.note_minimale}/20` }}
                  <span v-if="critere.obligatoire" class="badge obligatoire">Obligatoire</span>
                </p>
              </div>
              <div class="ligne-actions">
                <button class="mini" type="button" @click="modifierCompetence(critere)">
                  Modifier
                </button>
                <button
                  class="mini danger"
                  type="button"
                  :disabled="enregistrement"
                  @click="supprimerCompetence(critere)"
                >
                  Supprimer
                </button>
              </div>
            </li>
          </ul>

          <form class="formulaire" @submit.prevent="enregistrerCompetence">
            <h3>
              {{ competenceForm.id ? 'Modifier une compétence requise' : 'Ajouter une compétence requise' }}
            </h3>

            <div class="grille">
              <label class="pleine-largeur">
                Compétence
                <select v-model="competenceForm.id_competence" required>
                  <option value="">Sélectionner une compétence</option>
                  <option v-for="competence in competences" :key="competence.id" :value="competence.id">
                    {{ competence.libelle }} ({{ competence.categorie }})
                  </option>
                </select>
              </label>
              <label>
                Note minimale / 20
                <input
                  v-model="competenceForm.note_minimale"
                  type="number"
                  min="0"
                  max="20"
                  step="0.01"
                  placeholder="Optionnelle"
                />
              </label>
              <label class="case">
                <input v-model="competenceForm.obligatoire" type="checkbox" />
                Compétence obligatoire
              </label>
            </div>

            <div class="actions">
              <button class="btn" type="submit" :disabled="enregistrement">
                {{ competenceForm.id ? 'Enregistrer les modifications' : 'Ajouter la compétence' }}
              </button>
              <button
                v-if="competenceForm.id"
                class="btn secondary"
                type="button"
                @click="reinitialiserCompetence"
              >
                Annuler
              </button>
            </div>
          </form>
        </section>
      </template>
    </main>
  </div>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #f1f3f6;
  color: #222;
  font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
    "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
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
  color: teal;
  font-size: 22px;
  font-weight: 800;
}

.role {
  color: #667085;
  font-size: 13px;
  font-weight: 700;
}

.contenu {
  display: grid;
  gap: 20px;
  width: min(920px, 100%);
  margin: 0 auto;
  padding: 28px 16px 60px;
}

.presentation,
.panneau {
  padding: 24px;
  border-radius: 12px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.12) 0 4px 14px;
}

.eyebrow {
  margin: 0 0 6px;
  color: #667085;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

h1 {
  margin: 0 0 10px;
  color: teal;
  font-size: 24px;
}

h2 {
  margin: 0;
  font-size: 20px;
}

h3 {
  margin: 0 0 14px;
  font-size: 16px;
}

.presentation > p:not(.eyebrow) {
  margin: 0;
  color: #444;
  line-height: 1.5;
}

.infos {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 16px;
}

.infos span,
.badge {
  border-radius: 20px;
  background: #e6f2f2;
  color: #006666;
  font-size: 12px;
  font-weight: 700;
  padding: 5px 12px;
}

.panneau-titre {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.panneau-titre p {
  margin: 6px 0 0;
  color: #667085;
  font-size: 13px;
  line-height: 1.5;
}

.liste {
  display: grid;
  gap: 10px;
  margin: 16px 0;
  padding: 0;
  list-style: none;
}

.ligne {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 14px 16px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fafbfc;
}

.ligne p {
  margin: 5px 0 0;
  color: #667085;
  font-size: 13px;
  line-height: 1.5;
}

.badge {
  display: inline-block;
  margin-left: 8px;
  padding: 3px 9px;
}

.badge.obligatoire {
  background: #fdecec;
  color: #b42318;
}

.ligne-actions {
  display: flex;
  flex: none;
  gap: 8px;
}

.mini {
  border: 1px solid #c0c0c0;
  border-radius: 16px;
  padding: 6px 12px;
  background: #fff;
  color: #475467;
  cursor: pointer;
  font-family: inherit;
  font-size: 12px;
}

.mini:hover {
  border-color: teal;
  color: teal;
}

.mini.danger {
  border-color: #f2b8b5;
  color: #b42318;
}

.mini.danger:hover {
  border-color: #b42318;
}

.formulaire {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.grille {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.pleine-largeur {
  grid-column: 1 / -1;
}

label {
  display: grid;
  gap: 5px;
  color: #475467;
  font-size: 12px;
  font-weight: 700;
}

label.case {
  display: flex;
  align-items: center;
  gap: 8px;
  align-self: end;
  padding-bottom: 9px;
}

input,
select {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #c0c0c0;
  border-radius: 8px;
  padding: 9px 10px;
  background: #fff;
  color: #222;
  font-family: inherit;
  font-size: 14px;
  outline: 0;
}

label.case input {
  width: auto;
}

input:focus,
select:focus {
  border-color: teal;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
}

.btn {
  padding: 10px 20px;
  border: 0;
  border-radius: 20px;
  background: teal;
  color: #fff;
  cursor: pointer;
  font-family: inherit;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.btn:hover {
  background: #006666;
}

.btn.secondary {
  border: 1px solid teal;
  background: #fff;
  color: teal;
  box-shadow: none;
}

.btn:disabled,
.mini:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.message {
  color: #667085;
  font-size: 14px;
}

.error {
  color: #b42318;
}

.success {
  color: #067647;
  font-weight: 700;
}

@media (max-width: 720px) {
  .navbar {
    flex-wrap: wrap;
  }

  .role {
    order: 3;
    width: 100%;
  }

  .ligne {
    flex-direction: column;
    align-items: flex-start;
  }

  .grille {
    grid-template-columns: 1fr;
  }

  .actions {
    flex-direction: column-reverse;
  }
}
</style>
