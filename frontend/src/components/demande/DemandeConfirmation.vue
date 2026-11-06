<script setup>
defineProps({
  form: {
    type: Object,
    required: true,
  },
  posteChoisi: {
    type: Object,
    default: null,
  },
  types: {
    type: Array,
    required: true,
  },
  chargement: {
    type: Boolean,
    default: false,
  },
})

defineEmits(['modifier', 'confirmer'])
</script>

<template>
  <div class="review">
    <p>
      <b>Poste :</b>
      {{ posteChoisi?.poste || 'Non sélectionné' }}
    </p>

    <p>
      <b>Département :</b>
      {{ posteChoisi?.departement || 'Non précisé' }}
    </p>

    <p>
      <b>Personnes requises :</b>
      {{ form.nombre_demande }}
    </p>

    <p>
      <b>Contrat :</b>
      {{
        types.find(
          type => String(type.id) === String(form.id_type_contrat)
        )?.libelle || 'Non sélectionné'
      }}
    </p>

    <p>
      <b>Date de prise de poste :</b>
      {{ form.date_prise_poste || 'Non précisée' }}
    </p>

    <p>
      <b>Salaire proposé :</b>
      {{ form.salaire_propose }}
    </p>

    <div class="actions">
      <button
        class="secondary"
        type="button"
        @click="$emit('modifier')"
      >
        Modifier
      </button>

      <button
        class="form-btn"
        type="button"
        :disabled="chargement"
        @click="$emit('confirmer')"
      >
        {{
          chargement
            ? 'Enregistrement...'
            : 'Confirmer et envoyer'
        }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.review p {
  padding: 8px 0;
  border-bottom: 1px solid #eee;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.secondary,
.form-btn {
  padding: 12px 15px;
  border: 0;
  border-radius: 10px;
  color: white;
  cursor: pointer;
}

.secondary {
  background: #747474;
}

.form-btn {
  background: teal;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.form-btn:hover {
  background: #006666;
}

.form-btn:disabled {
  opacity: 0.65;
  cursor: wait;
}
</style>