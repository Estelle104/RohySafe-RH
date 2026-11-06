<script setup>
defineProps({
  grille: { type: Array, required: true }
})

function formatMontant(valeur) {
  return `${Math.round(Number(valeur || 0)).toLocaleString('fr-FR').replace(/\u202f/g, ' ')} Ar`
}
</script>

<template>
  <section class="bloc">
    <h2>Grille salariale</h2>
    <p class="aide">Fourchettes applicables à ce poste et ce département.</p>
    <p v-if="grille.length === 0" class="vide">Aucune grille salariale n’est définie pour ce poste.</p>
    <div v-else class="table-wrap">
      <table>
        <thead><tr><th>Contrat</th><th>Minimum</th><th>Maximum</th></tr></thead>
        <tbody>
          <tr v-for="ligne in grille" :key="ligne.id">
            <td>{{ ligne.type_contrat }}</td>
            <td>{{ formatMontant(ligne.montant_min) }}</td>
            <td>{{ formatMontant(ligne.montant_max) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<style scoped>
.bloc { background: #fff; border: 1px solid #d8dddd; border-radius: 16px; padding: 24px; }
h2 { margin: 0; font-size: 18px; }.aide, .vide { color: #687575; font-size: 13px; }.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: 14px; } th, td { text-align: left; padding: 10px; border-bottom: 1px solid #edf0f0; } th { color: #687575; font-size: 12px; }
</style>
