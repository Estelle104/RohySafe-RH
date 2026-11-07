<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { getViewInfoEmp, urlCopie } from '../services/infoEmployee'

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

const initiales = computed(() =>
  fiche.value ? `${fiche.value.prenom?.[0] ?? ''}${fiche.value.nom?.[0] ?? ''}`.toUpperCase() : ''
)
const echeanceProche = computed(() => {
  const d = fiche.value?.date_echeance
  if (!d) return false
  return (new Date(d) - new Date()) / 86400000 < 60
})
const fmt = (d) => (d ? new Date(d).toLocaleDateString('fr-FR') : '—')
</script>

<template>
  <div class="dossier-page">
    <p v-if="chargement">Chargement du dossier…</p>
    <p v-else-if="erreur" class="erreur">{{ erreur }}</p>

    <article v-else-if="fiche" class="dossier">
      <header class="dossier-entete">
        <span class="tampon">CONFIDENTIEL</span>
        <span class="ref">MATRICULE {{ fiche.matricule }}</span>
      </header>

      <section class="identite">
        <div class="photo">{{ initiales }}</div>
        <div>
          <h1>{{ fiche.prenom }} <strong>{{ fiche.nom }}</strong></h1>
          <p class="statut">
            {{ fiche.statut_professionnelle || 'Statut professionnel non renseigné' }}
            · {{ fiche.statut_actuel || '—' }}
          </p>
        </div>
      </section>

      <section class="grille">
        <div class="champ"><label>Email</label><span>{{ fiche.email || '—' }}</span></div>
        <div class="champ"><label>Téléphone</label><span>{{ fiche.telephone || '—' }}</span></div>
        <div class="champ"><label>Ville</label><span>{{ fiche.ville || '—' }}</span></div>
        <div class="champ"><label>Date d'embauche</label><span>{{ fmt(fiche.date_embauche) }}</span></div>
        <div class="champ"><label>Statut matrimonial</label><span>{{ fiche.statut_matrimoniale || '—' }}</span></div>
        <div class="champ"><label>Statut professionnel</label><span>{{ fiche.statut_professionnelle || '—' }}</span></div>
      </section>

      <section class="grille">
        <div class="champ large"><label>Résidence actuelle</label><span>{{ fiche.residence_actuel || '—' }}</span></div>
        <div class="champ">
          <label>Copie du justificatif</label>
          <span v-if="fiche.copie_actuel">
            <a :href="urlCopie(fiche.copie_actuel)" target="_blank">Voir la copie</a>
          </span>
          <span v-else>—</span>
        </div>
        <div class="champ" :class="{ alerte: echeanceProche }">
          <label>Échéance</label>
          <span>{{ fmt(fiche.date_echeance) }}</span>
        </div>
      </section>

      <section class="grille">
        <div class="champ"><label>Supérieur hiérarchique</label><span>{{ fiche.superieur || '—' }}</span></div>
        <div class="champ"><label>Subordonnés</label><span>{{ fiche.inferieurs || '—' }}</span></div>
      </section>

      <footer class="actions">
        <RouterLink :to="`/backoffice/rh/employes/${idEmploye}/modifier`" class="btn">Mettre à jour</RouterLink>
        <RouterLink :to="`/backoffice/rh/employes/${idEmploye}/historique`" class="btn secondaire">Historique</RouterLink>
      </footer>
    </article>
  </div>
</template>

<!-- <style scoped>
.dossier-page { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
.dossier { background: #f6f1e4; border: 2px solid #2b2b2b; border-radius: 6px; padding: 1.5rem; box-shadow: 6px 6px 0 #2b2b2b; font-family: 'Courier New', monospace; color: #1d1d1d; }
.dossier-entete { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed #2b2b2b; padding-bottom: .75rem; margin-bottom: 1rem; }
.tampon { border: 3px solid #b3261e; color: #b3261e; padding: .15rem .6rem; font-weight: bold; letter-spacing: 3px; transform: rotate(-3deg); }
.ref { font-weight: bold; }
.identite { display: flex; gap: 1.25rem; align-items: center; margin-bottom: 1.25rem; }
.photo { width: 96px; height: 120px; background: #cfc8b4; border: 2px solid #2b2b2b; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; }
h1 { margin: 0; font-size: 1.6rem; text-transform: uppercase; }
.statut { margin: .25rem 0 0; opacity: .75; }
.grille { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: .75rem 1.25rem; padding: 1rem 0; border-top: 1px solid #b9b19a; }
.champ label { display: block; font-size: .7rem; text-transform: uppercase; letter-spacing: 1px; opacity: .6; }
.champ span { font-weight: bold; }
.champ.large { grid-column: 1 / -1; }
.champ.alerte span { color: #b3261e; }
.actions { display: flex; gap: .75rem; border-top: 2px dashed #2b2b2b; padding-top: 1rem; }
.btn { background: #2b2b2b; color: #f6f1e4; padding: .5rem 1rem; text-decoration: none; border-radius: 3px; }
.btn.secondaire { background: transparent; color: #2b2b2b; border: 2px solid #2b2b2b; }
.erreur { color: #b3261e; }
</style> -->