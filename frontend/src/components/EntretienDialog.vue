<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  candidat: { type: Object, required: true },
  enregistrement: { type: Boolean, default: false },
  erreur: { type: String, default: '' },
})

const emit = defineEmits(['submit', 'close'])

const form = ref({
  date_entretien: '',
  lieu_entretien: '',
  commentaire: '',
})

const dateMinimum = computed(() => {
  const maintenant = new Date(Date.now() - new Date().getTimezoneOffset() * 60000)
  return maintenant.toISOString().slice(0, 16)
})

function envoyer() {
  emit('submit', { ...form.value })
}
</script>

<template>
  <div class="modal-backdrop" @click.self="!enregistrement && emit('close')">
    <section class="dialogue">
      <div class="entete">
        <div>
          <p class="surtitre">Planifier un entretien</p>
          <h2>{{ candidat.prenom }} {{ candidat.nom }}</h2>
        </div>
        <button class="fermer" type="button" :disabled="enregistrement" @click="emit('close')">
          ×
        </button>
      </div>

      <form @submit.prevent="envoyer">
        <label>
          Date et heure
          <input v-model="form.date_entretien" type="datetime-local" :min="dateMinimum" required>
        </label>
        <label>
          Lieu
          <input v-model="form.lieu_entretien" type="text" placeholder="Salle RH, visioconférence..." required>
        </label>
        <label>
          Commentaire
          <textarea v-model="form.commentaire" rows="4" placeholder="Informations utiles pour le jury..." />
        </label>

        <p v-if="erreur" class="erreur">{{ erreur }}</p>

        <div class="actions">
          <button class="btn secondaire" type="button" :disabled="enregistrement" @click="emit('close')">
            Annuler
          </button>
          <button class="btn" type="submit" :disabled="enregistrement">
            {{ enregistrement ? 'Planification…' : 'Planifier l’entretien' }}
          </button>
        </div>
      </form>
    </section>
  </div>
</template>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(20, 32, 40, 0.45);
}

.dialogue {
  width: 100%;
  max-width: 480px;
  padding: 24px;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 18px 50px rgba(0, 0, 0, 0.25);
}

.entete {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}

.surtitre {
  margin: 0 0 4px;
  color: #667085;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.entete h2 {
  margin: 0;
  color: #00534f;
  font-size: 22px;
}

.fermer {
  border: 0;
  background: transparent;
  color: #667085;
  font-size: 28px;
  line-height: 1;
  cursor: pointer;
}

form {
  display: grid;
  gap: 14px;
}

label {
  display: grid;
  gap: 6px;
  color: #344054;
  font-size: 13px;
  font-weight: 700;
}

input,
textarea {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 11px 12px;
  font: inherit;
  font-weight: 400;
}

input:focus,
textarea:focus {
  border-color: teal;
  outline: 0;
  box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.12);
}

textarea {
  resize: vertical;
}

.erreur {
  margin: 0;
  color: #b42318;
  font-size: 13px;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 6px;
}

.btn {
  border: 0;
  border-radius: 10px;
  padding: 10px 18px;
  background: teal;
  color: #fff;
  font: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.btn.secondaire {
  border: 1px solid #cbd5e1;
  background: #fff;
  color: #344054;
}

.btn:disabled {
  opacity: 0.55;
  cursor: default;
}
</style>
