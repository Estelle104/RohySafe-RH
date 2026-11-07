<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getViewInfoEmp, updateInfoEmp, getStatuts } from '../services/infoEmployee'

const route = useRoute()
const router = useRouter()
const idEmploye = route.params.id

const form = reactive({
  residence_actuel: '',
  date_echeance: '',
  id_statut_matrimoniale: '',
  id_statut_professionnelle: '',
})
const copie = ref(null)
const statuts = reactive({ matrimoniaux: [], professionnels: [] })
const erreur = ref('')
const envoi = ref(false)

onMounted(async () => {
  try {
    const [fiche, st] = await Promise.all([getViewInfoEmp(idEmploye), getStatuts()])
    statuts.matrimoniaux = st.matrimoniaux
    statuts.professionnels = st.professionnels
    Object.keys(form).forEach((k) => {
      form[k] = fiche[k] ?? ''
    })
  } catch (e) {
    erreur.value = e.message
  }
})

function choisirFichier(e) {
  copie.value = e.target.files[0] || null
}

async function enregistrer() {
  erreur.value = ''
  envoi.value = true
  try {
    await updateInfoEmp(idEmploye, { ...form }, copie.value)
    router.push(`/backoffice/rh/employes/${idEmploye}`)
  } catch (e) {
    erreur.value = e.message
  } finally {
    envoi.value = false
  }
}
</script>

<template>
  <div class="maj-page">
    <h1>Mettre à jour la fiche employé</h1>
    <p v-if="erreur" class="erreur">{{ erreur }}</p>

    <form @submit.prevent="enregistrer" class="formulaire">
      <fieldset>
        <legend>Situation</legend>
        <label>Statut matrimonial
          <select v-model="form.id_statut_matrimoniale">
            <option value="">—</option>
            <option v-for="s in statuts.matrimoniaux" :key="s.id" :value="s.id">{{ s.libelle }}</option>
          </select>
        </label>
        <label>Statut professionnel
          <select v-model="form.id_statut_professionnelle">
            <option value="">—</option>
            <option v-for="s in statuts.professionnels" :key="s.id" :value="s.id">{{ s.libelle }}</option>
          </select>
        </label>
      </fieldset>

      <fieldset>
        <legend>Résidence et justificatif</legend>
        <label class="large">Résidence actuelle <input v-model="form.residence_actuel" maxlength="255" /></label>
        <label>Copie du justificatif (PDF/JPG/PNG)
          <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="choisirFichier" />
        </label>
        <label>Date d'échéance <input type="date" v-model="form.date_echeance" /></label>
      </fieldset>

      <div class="actions">
        <button type="submit" :disabled="envoi">{{ envoi ? 'Enregistrement…' : 'Enregistrer' }}</button>
        <button type="button" class="secondaire" @click="router.back()">Annuler</button>
      </div>
    </form>
  </div>
</template>

<!-- <style scoped>
.maj-page { max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
.formulaire { display: flex; flex-direction: column; gap: 1rem; }
fieldset { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .75rem 1rem; border: 1px solid #ccc; border-radius: 6px; padding: 1rem; }
legend { font-weight: bold; padding: 0 .5rem; }
label { display: flex; flex-direction: column; gap: .25rem; font-size: .9rem; }
label.large { grid-column: 1 / -1; }
input, select { padding: .45rem; border: 1px solid #bbb; border-radius: 4px; font: inherit; }
.actions { display: flex; gap: .75rem; }
button { padding: .55rem 1.2rem; border: 0; border-radius: 4px; background: #2b2b2b; color: #fff; cursor: pointer; }
button.secondaire { background: transparent; color: #2b2b2b; border: 1px solid #2b2b2b; }
.erreur { color: #b3261e; }
</style> -->