<script setup>
defineProps({
  demande: { type: Object, required: true }
})

function formatDate(valeur) {
  return valeur ? new Date(valeur).toLocaleDateString('fr-FR') : 'Non renseignée'
}

function formatMontant(valeur) {
  return `${Math.round(Number(valeur || 0)).toLocaleString('fr-FR').replace(/\u202f/g, ' ')} Ar`
}
</script>

<template>
  <section class="bloc">
    <div class="titre">
      <div>
        <p class="eyebrow">Demande #{{ demande.id }}</p>
        <h1>{{ demande.nombre_poste }} {{ demande.poste }}</h1>
      </div>
      <span class="statut">{{ demande.statut }}</span>
    </div>

    <dl class="informations">
      <div><dt>Département</dt><dd>{{ demande.departement }}</dd></div>
      <div><dt>Type de contrat</dt><dd>{{ demande.type_contrat }}</dd></div>
      <div><dt>Prise de poste</dt><dd>{{ formatDate(demande.date_prise_poste) }}</dd></div>
      <div><dt>Créée par</dt><dd>{{ demande.createur || 'Inconnu' }}</dd></div>
      <div><dt>Traitée par</dt><dd>{{ demande.utilisateur_actuel || 'Non attribuée' }}</dd></div>
      <div><dt>Proposition actuelle</dt><dd>{{ formatMontant(demande.salaire_propose) }}</dd></div>
    </dl>
  </section>
</template>

<style scoped>
.bloc { background: #fff; border: 1px solid #d8dddd; border-radius: 16px; padding: 24px; }
.titre { display: flex; justify-content: space-between; gap: 16px; align-items: start; }
.eyebrow { margin: 0 0 4px; color: #687575; font-size: 13px; font-weight: 700; }
h1 { margin: 0; font-size: 24px; }
.statut { background: #e0f4ee; color: #006666; padding: 5px 11px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.informations { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin: 24px 0 0; }
.informations div { border-top: 1px solid #edf0f0; padding-top: 10px; }
dt { color: #687575; font-size: 12px; }
dd { margin: 4px 0 0; font-weight: 700; }
@media (max-width: 560px) { .informations { grid-template-columns: 1fr; } .titre { flex-direction: column; } }
</style>
