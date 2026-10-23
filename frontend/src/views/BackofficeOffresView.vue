<script setup>
import { useBackofficeOffres } from '../composables/useBackofficeOffres'

const {
  offresFiltrees,
  chargement,
  erreur,
  recherche,
  charger,
  ajouterExigences,
  retourner,
} = useBackofficeOffres()

function formatDate(date) {
  return date ? new Date(date).toLocaleDateString('fr-FR') : '-'
}
</script>

<template>
  <div class="page">
    <header class="navbar">
      <span class="brand">RohySafe</span>
      <span class="role">Backoffice · Exigences des offres</span>
      <button class="btn secondary" type="button" @click="retourner">
        Retour aux offres
      </button>
    </header>

    <main class="contenu">
      <section class="entete">
        <div>
          <p class="eyebrow">Administration des offres</p>
          <h1>Liste des offres</h1>
          <p class="sous-titre">
            Sélectionnez une offre pour ajouter ou modifier ses exigences
            d’expérience et ses compétences requises.
          </p>
        </div>
        <input
          v-model="recherche"
          type="search"
          class="search"
          placeholder="Rechercher une offre..."
        />
      </section>

      <p v-if="chargement" class="message">Chargement des offres...</p>

      <template v-else-if="erreur">
        <p class="message error">{{ erreur }}</p>
        <button class="btn retry" type="button" @click="charger">Réessayer</button>
      </template>

      <template v-else>
        <p v-if="offresFiltrees.length === 0" class="message">
          Aucune offre ne correspond à votre recherche.
        </p>

        <section v-else class="grille">
          <article v-for="offre in offresFiltrees" :key="offre.id" class="carte">
            <div class="carte-entete">
              <div>
                <p class="departement">{{ offre.departement }}</p>
                <h2>{{ offre.titre }}</h2>
              </div>
              <span class="statut" :class="offre.statut">{{ offre.statut }}</span>
            </div>

            <p class="description">{{ offre.descriptions }}</p>

            <div class="tags">
              <span>{{ offre.type_contrat }}</span>
              <span>{{ offre.nombre_demande }} poste(s)</span>
              <span v-if="offre.date_limite">Limite : {{ formatDate(offre.date_limite) }}</span>
            </div>

            <div class="compteurs">
              <span>{{ Number(offre.nb_exigences_experience) }} expérience(s)</span>
              <span>{{ Number(offre.nb_competences_requises) }} compétence(s)</span>
            </div>

            <div class="actions">
              <button class="btn" type="button" @click="ajouterExigences(offre)">
                Ajouter des exigences
              </button>
            </div>
          </article>
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
  width: min(1100px, 100%);
  margin: 0 auto;
  padding: 28px 16px 60px;
}

.entete {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 20px;
}

.eyebrow {
  margin: 0 0 6px;
  color: #667085;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

h1 {
  margin: 0;
  color: teal;
  font-size: 28px;
}

.sous-titre {
  max-width: 650px;
  margin: 8px 0 0;
  color: #667085;
  font-size: 14px;
  line-height: 1.5;
}

.search {
  width: min(320px, 100%);
  flex: none;
  border: 1px solid #c0c0c0;
  border-radius: 20px;
  padding: 10px 16px;
  outline: 0;
  font-family: inherit;
}

.search:focus {
  border-color: teal;
}

.grille {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.carte {
  display: flex;
  flex-direction: column;
  padding: 20px;
  border-radius: 12px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.12) 0 4px 14px;
}

.carte-entete {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.departement {
  margin: 0 0 4px;
  color: #667085;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

h2 {
  margin: 0;
  font-size: 18px;
}

.statut {
  flex: none;
  border-radius: 20px;
  padding: 5px 10px;
  background: #e6f2f2;
  color: #006666;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

.statut.brouillon {
  background: #fef3e6;
  color: #b54708;
}

.statut.cloturee {
  background: #f2f4f7;
  color: #667085;
}

.description {
  margin: 14px 0;
  color: #475467;
  font-size: 14px;
  line-height: 1.5;
}

.tags,
.compteurs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tags span {
  border-radius: 20px;
  padding: 4px 10px;
  background: #f1f3f6;
  color: #475467;
  font-size: 12px;
  font-weight: 700;
}

.compteurs {
  margin-top: 14px;
}

.compteurs span {
  color: #667085;
  font-size: 12px;
  font-weight: 700;
}

.compteurs span + span::before {
  content: "·";
  margin-right: 8px;
}

.actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 18px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
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

.retry {
  display: block;
  margin: 0 auto;
}

.message {
  color: #667085;
  font-size: 14px;
}

.error {
  color: #b42318;
}

@media (max-width: 820px) {
  .entete {
    flex-direction: column;
    align-items: stretch;
  }

  .search {
    width: 100%;
  }

  .grille {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 620px) {
  .navbar {
    flex-wrap: wrap;
  }

  .role {
    order: 3;
    width: 100%;
  }
}
</style>
