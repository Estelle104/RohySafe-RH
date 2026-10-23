<script setup>
import { useCvCandidature } from '../composables/useCvCandidature'

const {
  offre,
  candidat,
  experiences,
  experiencesProfil,
  formations,
  competencesCandidat,
  postes,
  competencesDisponibles,
  chargement,
  erreur,
  succes,
  enregistrement,
  aujourdHui,
  dureeJours,
  marquerEnCours,
  ajouterExperienceProfil,
  supprimerExperienceProfil,
  ajouterFormation,
  supprimerFormation,
  ajouterCompetence,
  supprimerCompetence,
  enregistrer,
  retourner,
  seDeconnecter,
  idCandidat,
} = useCvCandidature()
</script>

<template>
  <div class="page">
    <header class="navbar">
      <span class="brand">RohySafe</span>
      <div class="nav-actions">
        <button class="btn secondary" type="button" @click="retourner">
          Retour aux offres
        </button>
        <button class="btn secondary" type="button" @click="seDeconnecter">
          Se déconnecter
        </button>
      </div>
    </header>

    <main class="contenu">
      <p v-if="chargement" class="message">Chargement du CV...</p>
      <p v-else-if="erreur && !offre" class="message error">{{ erreur }}</p>

      <template v-else-if="offre">
        <section class="presentation">
          <p class="eyebrow">{{ offre.departement }} · {{ offre.type_contrat }}</p>
          <h1>{{ offre.titre }}</h1>
          <p>{{ offre.descriptions }}</p>
          <div class="infos">
            <span>{{ offre.nombre_demande }} poste(s)</span>
            <span v-if="offre.date_limite">Limite : {{ offre.date_limite }}</span>
            <span v-if="offre.salaire_propose">Salaire : {{ offre.salaire_propose }}</span>
          </div>
        </section>

        <section class="formulaire">
          <div class="entete-formulaire">
            <div class="identite-entete">
              <div class="avatar">
                {{ candidat.prenom?.charAt(0) }}{{ candidat.nom?.charAt(0) }}
              </div>
              <div>
                <p class="eyebrow">CV en ligne</p>
                <h2>
                  {{ candidat.prenom || 'Prénom' }} {{ candidat.nom || 'Nom' }}
                </h2>
                <p class="contact">
                  {{ candidat.email || 'Email non renseigné' }}
                  · {{ candidat.ville || 'Ville non renseignée' }}
                </p>
              </div>
            </div>
            <span class="badge">Candidat #{{ idCandidat }}</span>
          </div>

          <section class="sous-section">
            <div class="section-titre">
              <div>
                <h3>Informations personnelles</h3>
                <p>Les coordonnées qui apparaîtront sur votre CV.</p>
              </div>
            </div>

            <div class="grille-champs">
              <label>
                Nom
                <input v-model="candidat.nom" type="text" placeholder="Rakoto" />
              </label>
              <label>
                Prénom
                <input v-model="candidat.prenom" type="text" placeholder="Miora" />
              </label>
              <label>
                Email
                <input v-model="candidat.email" type="email" placeholder="email@exemple.mg" />
              </label>
              <label>
                Téléphone
                <input v-model="candidat.telephone" type="tel" placeholder="034 00 000 00" />
              </label>
              <label class="pleine-largeur">
                Ville
                <input v-model="candidat.ville" type="text" placeholder="Antananarivo" />
              </label>
            </div>
          </section>

          <section class="sous-section">
            <div class="section-titre">
              <div>
                <h3>Expériences professionnelles</h3>
                <p>Vos expériences passées, liées aux postes existants.</p>
              </div>
              <button class="btn ajouter" type="button" @click="ajouterExperienceProfil">
                + Ajouter
              </button>
            </div>

            <p v-if="experiencesProfil.length === 0" class="message">
              Aucune expérience professionnelle renseignée.
            </p>

            <article
              v-for="(experience, index) in experiencesProfil"
              :key="`profil-${index}`"
              class="ligne-cv"
            >
              <button
                class="supprimer"
                type="button"
                title="Supprimer"
                @click="supprimerExperienceProfil(index)"
              >
                ×
              </button>

              <div class="grille-champs">
                <label class="pleine-largeur">
                  Poste
                  <select v-model="experience.id_poste">
                    <option value="">Sélectionner un poste</option>
                    <option v-for="poste in postes" :key="poste.id" :value="poste.id">
                      {{ poste.libelle }}
                    </option>
                  </select>
                </label>
                <label>
                  Date de début
                  <input v-model="experience.date_debut" type="date" :max="aujourdHui" />
                </label>
                <label>
                  Date de fin
                  <input
                    v-model="experience.date_fin"
                    type="date"
                    :min="experience.date_debut || undefined"
                    :max="aujourdHui"
                  />
                </label>
                <label class="pleine-largeur">
                  Description
                  <textarea
                    v-model="experience.descriptions"
                    rows="2"
                    placeholder="Missions, réalisations..."
                  ></textarea>
                </label>
              </div>
            </article>
          </section>

          <section class="sous-section">
            <div class="section-titre">
              <div>
                <h3>Formations</h3>
                <p>Vos diplômes et parcours de formation.</p>
              </div>
              <button class="btn ajouter" type="button" @click="ajouterFormation">
                + Ajouter
              </button>
            </div>

            <p v-if="formations.length === 0" class="message">
              Aucune formation renseignée.
            </p>

            <article
              v-for="(formation, index) in formations"
              :key="`formation-${index}`"
              class="ligne-cv"
            >
              <button
                class="supprimer"
                type="button"
                title="Supprimer"
                @click="supprimerFormation(index)"
              >
                ×
              </button>

              <div class="grille-champs">
                <label class="pleine-largeur">
                  Diplôme
                  <input v-model="formation.diplome" type="text" placeholder="Licence en informatique" />
                </label>
                <label class="pleine-largeur">
                  Établissement
                  <input v-model="formation.etablissement" type="text" placeholder="Université" />
                </label>
                <label>
                  Date de début
                  <input v-model="formation.date_debut" type="date" />
                </label>
                <label>
                  Date de fin
                  <input
                    v-model="formation.date_fin"
                    type="date"
                    :min="formation.date_debut || undefined"
                  />
                </label>
                <label class="pleine-largeur">
                  Description
                  <textarea
                    v-model="formation.description"
                    rows="2"
                    placeholder="Mention, spécialité..."
                  ></textarea>
                </label>
              </div>
            </article>
          </section>

          <section class="sous-section">
            <div class="section-titre">
              <div>
                <h3>Compétences</h3>
                <p>Vos compétences et votre niveau sur 20.</p>
              </div>
              <button class="btn ajouter" type="button" @click="ajouterCompetence">
                + Ajouter
              </button>
            </div>

            <p v-if="competencesCandidat.length === 0" class="message">
              Aucune compétence renseignée.
            </p>

            <article
              v-for="(competence, index) in competencesCandidat"
              :key="`competence-${index}`"
              class="ligne-cv"
            >
              <button
                class="supprimer"
                type="button"
                title="Supprimer"
                @click="supprimerCompetence(index)"
              >
                ×
              </button>

              <div class="grille-competence">
                <label>
                  Compétence
                  <select v-model="competence.id_competence">
                    <option value="">Sélectionner une compétence</option>
                    <option
                      v-for="option in competencesDisponibles"
                      :key="option.id"
                      :value="option.id"
                    >
                      {{ option.libelle }} ({{ option.categorie }})
                    </option>
                  </select>
                </label>
                <label>
                  Niveau / 20
                  <input
                    v-model="competence.niveau"
                    type="number"
                    min="0"
                    max="20"
                    step="0.5"
                  />
                </label>
              </div>
            </article>
          </section>

          <section class="sous-section">
            <div class="section-titre">
              <div>
                <h3>Expériences demandées pour cette offre</h3>
                <p>
                  Renseignez les périodes correspondant aux expériences demandées.
                  Une seule période est attendue par expérience.
                </p>
              </div>
            </div>

            <p v-if="experiences.length === 0" class="message">
              Cette offre ne demande aucune expérience particulière.
            </p>

            <article
              v-for="exigence in experiences"
              :key="exigence.id_exigence"
              class="ligne-cv"
            >
              <div class="experience-titre">
                <div>
                  <strong>{{ exigence.poste }}</strong>
                  <p>
                    {{ exigence.duree_min }} mois minimum
                    <template v-if="exigence.duree_max"> à {{ exigence.duree_max }} mois</template>
                    <span v-if="exigence.obligatoire" class="obligatoire">Obligatoire</span>
                  </p>
                </div>
              </div>

              <div class="dates">
                <label>
                  Date de début
                  <input
                    v-model="exigence.date_debut"
                    type="date"
                    :max="exigence.date_fin || aujourdHui"
                  />
                </label>
                <label>
                  Date de fin
                  <input
                    v-model="exigence.date_fin"
                    type="date"
                    :min="exigence.date_debut || undefined"
                    :max="aujourdHui"
                  />
                </label>
                <button class="btn en-cours" type="button" @click="marquerEnCours(exigence)">
                  En cours
                </button>
              </div>

              <p v-if="dureeJours(exigence) !== null" class="duree">
                Durée calculée : {{ dureeJours(exigence) }} jour(s)
                (≈ {{ Math.floor(dureeJours(exigence) / 30) }} mois)
              </p>
            </article>
          </section>

          <p v-if="erreur" class="message error">{{ erreur }}</p>
          <p v-if="succes" class="message success">{{ succes }}</p>

          <div class="actions">
            <button class="btn secondary" type="button" @click="retourner">
              Annuler
            </button>
            <button class="btn" type="button" :disabled="enregistrement" @click="enregistrer">
              {{ enregistrement ? 'Enregistrement...' : 'Enregistrer mon CV et postuler' }}
            </button>
          </div>
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

.nav-actions {
  display: flex;
  gap: 8px;
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

.contenu {
  display: grid;
  gap: 20px;
  width: min(860px, 100%);
  margin: 0 auto;
  padding: 28px 16px 60px;
}

.presentation,
.formulaire {
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
  color: teal;
  font-size: 22px;
}

h3 {
  margin: 0;
  color: #222;
  font-size: 17px;
}

.identite-entete {
  display: flex;
  align-items: center;
  gap: 14px;
}

.avatar {
  display: grid;
  width: 56px;
  height: 56px;
  flex: none;
  place-items: center;
  border-radius: 50%;
  background: #e6f2f2;
  color: teal;
  font-size: 18px;
  font-weight: 800;
}

.contact {
  margin: 4px 0 0;
  color: #667085;
  font-size: 13px;
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
.badge,
.obligatoire {
  border-radius: 20px;
  background: #e6f2f2;
  color: #006666;
  font-size: 12px;
  font-weight: 700;
  padding: 5px 12px;
}

.entete-formulaire {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding-bottom: 18px;
  border-bottom: 1px solid #e5e7eb;
}

.sous-section {
  padding: 20px 0;
  border-bottom: 1px solid #e5e7eb;
}

.sous-section:last-of-type {
  border-bottom: 0;
}

.section-titre {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
}

.section-titre p {
  margin: 5px 0 0;
  color: #667085;
  font-size: 13px;
  line-height: 1.5;
}

.grille-champs {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.grille-competence {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 140px;
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

input,
select,
textarea {
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

textarea {
  resize: vertical;
}

input:focus,
select:focus,
textarea:focus {
  border-color: teal;
}

.ligne-cv {
  position: relative;
  margin-bottom: 12px;
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fafbfc;
}

.experience-titre p {
  margin: 5px 0 0;
  color: #667085;
  font-size: 13px;
}

.obligatoire {
  display: inline-block;
  margin-left: 8px;
  padding: 3px 9px;
  background: #fdecec;
  color: #b42318;
}

.dates {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr)) auto;
  align-items: end;
  gap: 10px;
  margin-top: 14px;
}

.duree {
  margin: 10px 0 0;
  color: #667085;
  font-size: 13px;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 22px;
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
}

.btn.ajouter {
  flex: none;
  padding: 8px 14px;
  border: 1px dashed teal;
  background: #fff;
  color: teal;
  box-shadow: none;
}

.btn.en-cours {
  border: 1px solid #c0c0c0;
  background: #fff;
  color: #475467;
  box-shadow: none;
}

.btn:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.supprimer {
  position: absolute;
  top: 8px;
  right: 10px;
  border: 0;
  background: transparent;
  color: #b42318;
  cursor: pointer;
  font-size: 22px;
  line-height: 1;
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

@media (max-width: 620px) {
  .entete-formulaire,
  .section-titre {
    flex-direction: column;
  }

  .grille-champs,
  .grille-competence,
  .dates {
    grid-template-columns: 1fr;
  }

  .actions {
    flex-direction: column-reverse;
  }
}
</style>
