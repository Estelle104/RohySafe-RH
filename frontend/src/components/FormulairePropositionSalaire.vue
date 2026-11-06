<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  salaireActuel: { type: [Number, String], default: '' },
  enCours: { type: Boolean, default: false }
})
const emit = defineEmits(['soumettre'])
const salaire = ref(props.salaireActuel)
const remarque = ref('')

watch(() => props.salaireActuel, valeur => { salaire.value = valeur })

function soumettre() {
  emit('soumettre', { salaire_propose: salaire.value, remarque: remarque.value })
}
</script>

<template>
  <section class="bloc">
    <h2>Proposition de salaire</h2>
    <form id="proposition-salaire-form" @submit.prevent="soumettre">
      <label for="salaire">Montant proposé (MGA)</label>
      <input id="salaire" v-model="salaire" type="number" min="0" step="1" required :disabled="enCours">
      <label for="remarque">Remarque <span>(facultative)</span></label>
      <textarea id="remarque" v-model="remarque" rows="4" :disabled="enCours" placeholder="Justification ou commentaire"></textarea>
    </form>
  </section>
</template>

<style scoped>
.bloc { background: #fff; border: 1px solid #d8dddd; border-radius: 16px; padding: 24px; } h2 { margin: 0 0 18px; font-size: 18px; }
form { display: grid; gap: 8px; } label { margin-top: 8px; font-size: 13px; font-weight: 700; } label span { color: #687575; font-weight: 400; }
input, textarea { border: 1px solid #aeb8b8; border-radius: 8px; padding: 10px; font: inherit; }
</style>
