<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import PageAvecMenu from '../../components/PageAvecMenu.vue'
import { getViewInfoEmp, urlCopie } from '../../services/infoEmployee'

// Même valeur que le seuil par défaut de la liste des employés
const SEUIL_ECHEANCE_JOURS = 30

const route = useRoute()
const idEmploye = route.params.id
const fiche = ref(null)
const erreur = ref('')
const chargement = ref(true)

onMounted(async () => {
  try {
    fiche.value = await getViewInfoEmp(idEmploye)
  } catch (e) {
    erreur.value = e.message
  } finally {
    chargement.value = false
  }
})

const fmt = (d) => (d ? new Date(d).toLocaleDateString('fr-FR') : '—')

const initiales = computed(() =>
  fiche.value ? `${fiche.value.prenom?.[0] ?? ''}${fiche.value.nom?.[0] ?? ''}`.toUpperCase() : ''
)

// Nombre de jours avant l'échéance (négatif si dépassée, null si pas de date)
const joursRestants = computed(() => {
  const d = fiche.value?.date_echeance
  if (!d) return null
  const aujourdhui = new Date()
  aujourdhui.setHours(0, 0, 0, 0)
  const echeance = new Date(`${String(d).slice(0, 10)}T00:00:00`)
  return Math.round((echeance - aujourdhui) / 86400000)
})

const echeanceAlerte = computed(() => joursRestants.value !== null && joursRestants.value <= SEUIL_ECHEANCE_JOURS)

const texteEcheance = computed(() => {
  const j = joursRestants.value
  if (j === null) return ''
  if (j < 0) return `Expiré depuis ${-j} j`
  if (j === 0) return "Expire aujourd'hui"
  return `Dans ${j} j`
})

// Les blocs d'informations : on décrit les champs une fois, le template les affiche tous
const blocs = computed(() => {
  const f = fiche.value
  if (!f) return []
  return [
    {
      titre: 'Coordonnées',
      champs: [
        { label: 'Email', valeur: f.email },
        { label: 'Téléphone', valeur: f.telephone },
        { label: 'Ville', valeur: f.ville },
        { label: "Date d'embauche", valeur: f.date_embauche ? fmt(f.date_embauche) : null }
      ]
    },
    {
      titre: 'Situation',
      champs: [
        { label: 'Statut matrimonial', valeur: f.statut_matrimoniale },
        { label: 'Statut professionnel', valeur: f.statut_professionnelle },
        { label: 'Statut actuel', valeur: f.statut_actuel }
      ]
    },
    {
      titre: 'Résidence et justificatif',
      champs: [
        { label: 'Résidence actuelle', valeur: f.residence_actuel, large: true },
        {
          label: 'Copie du justificatif',
          valeur: f.copie_actuel ? 'Voir la copie' : null,
          lien: urlCopie(f.copie_actuel)
        },
        {
          label: 'Échéance',
          valeur: f.date_echeance ? `${fmt(f.date_echeance)}${echeanceAlerte.value ? ` · ${texteEcheance.value}` : ''}` : null,
          alerte: echeanceAlerte.value
        }
      ]
    },
    {
      titre: 'Hiérarchie',
      champs: [
        { label: 'Supérieur hiérarchique', valeur: f.superieur },
        { label: 'Subordonnés', valeur: f.inferieurs }
      ]
    }
  ]
})
</script>

<template>
  <PageAvecMenu
    :eyebrow="fiche ? `Matricule ${fiche.matricule}` : 'Ressources humaines'"
    titre="Fiche employé"
    sous-titre="Informations personnelles, situation, justificatif de résidence et hiérarchie."
  >
    <RouterLink to="/backoffice/rh/employes" class="retour">← Retour à la liste</RouterLink>

    <p v-if="chargement" class="message">Chargement de la fiche…</p>
    <p v-else-if="erreur" class="message erreur">{{ erreur }}</p>

    <article v-else-if="fiche" class="carte">
      <div class="entete">
        <div class="identite">
          <span class="avatar">{{ initiales }}</span>
          <div>
            <h2>{{ fiche.prenom }} {{ fiche.nom }}</h2>
            <div class="tags">
              <span class="tag">{{ fiche.statut_professionnelle || 'Statut professionnel non renseigné' }}</span>
              <span v-if="fiche.statut_actuel" class="tag">{{ fiche.statut_actuel }}</span>
            </div>
          </div>
        </div>

        <div class="actions">
          <RouterLink :to="`/backoffice/rh/employes/${idEmploye}/modifier`" class="btn">Mettre à jour</RouterLink>
          <RouterLink :to="`/backoffice/rh/employes/${idEmploye}/historique`" class="btn secondaire">Historique</RouterLink>
        </div>
      </div>

      <p v-if="echeanceAlerte" class="alerte-echeance">
        Justificatif de résidence : {{ texteEcheance.toLowerCase() }}. Pense à le renouveler.
      </p>

      <section v-for="bloc in blocs" :key="bloc.titre">
        <h3>{{ bloc.titre }}</h3>
        <dl class="informations">
          <div v-for="c in bloc.champs" :key="c.label" :class="{ large: c.large }">
            <dt>{{ c.label }}</dt>
            <dd :class="{ rouge: c.alerte }">
              <a v-if="c.lien && c.valeur" :href="c.lien" target="_blank">{{ c.valeur }}</a>
              <template v-else>{{ c.valeur || '—' }}</template>
            </dd>
          </div>
        </dl>
      </section>
    </article>
  </PageAvecMenu>
</template>

<style scoped>
.retour {
  display: inline-block;
  margin-bottom: 16px;
  color: teal;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
}

.retour:hover {
  text-decoration: underline;
}

/* Carte principale */
.carte {
  padding: 24px;
  border-radius: 10px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
}

.entete {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.identite {
  display: flex;
  align-items: center;
  gap: 14px;
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

h2 {
  margin: 0 0 8px;
  color: teal;
  font-size: 20px;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tag {
  padding: 4px 12px;
  border-radius: 10px;
  background: #e6f2f2;
  color: #006666;
  font-size: 12px;
  font-weight: 700;
}

h3 {
  margin: 24px 0 8px;
  color: teal;
  font-size: 16px;
}

/* Liste d'informations : même présentation que FicheDemande */
.informations {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  margin: 0;
}

.informations > div {
  border-top: 1px solid #edf0f0;
  padding-top: 10px;
}

.informations > div.large {
  grid-column: 1 / -1;
}

dt {
  color: #687575;
  font-size: 12px;
}

dd {
  margin: 4px 0 0;
  font-weight: 700;
}

dd a {
  color: teal;
}

dd.rouge {
  color: #b42318;
}

.alerte-echeance {
  margin: 20px 0 0;
  padding: 10px 14px;
  border: 1px solid #b42318;
  border-radius: 10px;
  background: #fdecec;
  color: #b42318;
  font-size: 13px;
  font-weight: 700;
}

/* Boutons */
.actions {
  display: flex;
  gap: 10px;
}

.btn {
  display: inline-block;
  padding: 10px 22px;
  border: 0;
  border-radius: 10px;
  background: teal;
  color: #fff;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.btn:hover {
  background: #006666;
}

.btn.secondaire {
  border: 1px solid #cbd5e1;
  background: #fff;
  color: #344054;
  box-shadow: none;
}

.btn.secondaire:hover {
  background: #f1f3f6;
}

/* Messages */
.message {
  margin: 0 0 16px;
  padding: 18px;
  border-radius: 10px;
  background: #fff;
  color: #667085;
  font-size: 14px;
}

.erreur {
  background: #fdecec;
  color: #b42318;
}

@media (max-width: 560px) {
  .informations {
    grid-template-columns: 1fr;
  }

  .actions {
    width: 100%;
  }
}
</style>