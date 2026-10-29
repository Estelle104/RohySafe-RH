<script setup>
defineProps({
  form: {
    type: Object,
    required: true,
  },
  postes: {
    type: Array,
    required: true,
  },
  types: {
    type: Array,
    required: true,
  },
})

const emit = defineEmits(['submit'])
</script>

<template>
  <form class="form" @submit.prevent="emit('submit')">
    <label>
      Poste et département

      <select
        v-model="form.id_poste_departement"
        class="input"
        required
      >
        <option value="" disabled>Choisir...</option>

        <option
          v-for="poste in postes"
          :key="poste.id"
          :value="poste.id"
        >
          {{ poste.poste }} · {{ poste.departement }}
        </option>
      </select>
    </label>

    <label>
      Nombre de personnes requises

      <input
        v-model.number="form.nombre_demande"
        class="input"
        type="number"
        min="1"
        required
      >
    </label>

    <label>
      Type de contrat

      <select
        v-model="form.id_type_contrat"
        class="input"
        required
      >
        <option value="" disabled>Choisir...</option>

        <option
          v-for="type in types"
          :key="type.id"
          :value="type.id"
        >
          {{ type.libelle }}
        </option>
      </select>
    </label>

    <label>
      Date de prise de poste

      <input
        v-model="form.date_prise_poste"
        class="input"
        type="date"
      >
    </label>

    <label>
      Salaire proposé (mensuel)

      <input
        v-model="form.salaire_propose"
        class="input"
        type="number"
        min="0"
        step="0.01"
        required
      >
    </label>

    <button class="form-btn" type="submit">
      Vérifier la demande
    </button>
  </form>
</template>

<style scoped>
.form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

label {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 13px;
  font-weight: 700;
}

.input {
  box-sizing: border-box;
  width: 100%;
  border-radius: 10px;
  border: 1px solid #c0c0c0;
  padding: 12px 15px;
  outline: 0;
  transition: border-color 0.2s;
  font: inherit;
  font-weight: 400;
}

.input:focus {
  border-color: teal;
}

.form-btn {
  padding: 12px 15px;
  font-family: inherit;
  border-radius: 10px;
  border: 0;
  background: teal;
  color: white;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.form-btn:hover {
  background: #006666;
}
</style>