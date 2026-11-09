<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import PageAvecMenu from '../../components/PageAvecMenu.vue'
import { getViewInfoEmp, updateInfoEmp, getStatuts, urlCopie } from '../../services/infoEmployee'

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
const copieActuelle = ref('')
const nomEmploye = ref('')
const statuts = reactive({ matrimoniaux: [], professionnels: [] })
const erreur = ref('')
const chargement = ref(true)
// true seulement si la fiche a bien été chargée : sinon on n'affiche pas le formulaire
// (on éviterait ainsi d'enregistrer des champs vides par erreur)
const chargee = ref(false)
const envoi = ref(false)

onMounted(async () => {
  try {
    const [fiche, st] = await Promise.all([getViewInfoEmp(idEmploye), getStatuts()])
    statuts.matrimoniaux = st.matrimoniaux
    statuts.professionnels = st.professionnels
    Object.keys(form).forEach((k) => {
      form[k] = fiche[k] ?? ''
    })
    copieActuelle.value = fiche.copie_actuel ?? ''
    nomEmploye.value = `${fiche.prenom ?? ''} ${fiche.nom ?? ''}`.trim()
    chargee.value = true
  } catch (e) {
    erreur.value = e.message
  } finally {
    chargement.value = false
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
  <PageAvecMenu
    eyebrow="Ressources humaines"
    titre="Mettre à jour la fiche"
    :sous-titre="nomEmploye ? `Employé : ${nomEmploye}. L'ancienne version sera conservée dans l'historique.` : ''"
  >
    <RouterLink :to="`/backoffice/rh/employes/${idEmploye}`" class="retour">← Retour à la fiche</RouterLink>

    <p v-if="chargement" class="message">Chargement de la fiche…</p>
    <p v-if="erreur" class="message erreur">{{ erreur }}</p>

    <form v-if="chargee" class="carte" @submit.prevent="enregistrer">
      <section>
        <h3>Situation</h3>
        <div class="champs">
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
        </div>
      </section>

      <section>
        <h3>Résidence et justificatif</h3>
        <div class="champs">
          <label class="large">Résidence actuelle
            <input v-model="form.residence_actuel" maxlength="255">
          </label>
          <label>Copie du justificatif (PDF, JPG ou PNG)
            <input type="file" accept=".pdf,.jpg,.jpeg,.png" @change="choisirFichier">
            <span v-if="copieActuelle" class="aide">
              Copie actuelle :
              <a :href="urlCopie(copieActuelle)" target="_blank">voir la copie</a>
              (laisse vide pour la garder)
            </span>
          </label>
          <label>Date d'échéance
            <input v-model="form.date_echeance" type="date">
          </label>
        </div>
      </section>

      <div class="actions">
        <button class="btn" type="submit" :disabled="envoi">{{ envoi ? 'Enregistrement…' : 'Enregistrer' }}</button>
        <button class="btn secondaire" type="button" :disabled="envoi" @click="router.back()">Annuler</button>
      </div>
    </form>
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

.carte {
  display: grid;
  gap: 8px;
  padding: 24px;
  border-radius: 10px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
}

h3 {
  margin: 8px 0 12px;
  color: teal;
  font-size: 16px;
}

.champs {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px 16px;
}

label {
  display: grid;
  align-content: start;
  gap: 6px;
  color: #344054;
  font-size: 13px;
  font-weight: 700;
}

label.large {
  grid-column: 1 / -1;
}

input,
select {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 11px 12px;
  background: #fff;
  font: inherit;
  font-weight: 400;
}

input:focus,
select:focus {
  border-color: teal;
  outline: 0;
  box-shadow: 0 0 0 3px rgba(0, 128, 128, 0.12);
}

.aide {
  color: #667085;
  font-size: 12px;
  font-weight: 400;
}

.aide a {
  color: teal;
  font-weight: 700;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 16px;
}

.btn {
  padding: 10px 22px;
  border: 0;
  border-radius: 10px;
  background: teal;
  color: #fff;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
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

.btn:disabled {
  opacity: 0.55;
  cursor: default;
}

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
  .champs {
    grid-template-columns: 1fr;
  }

  .actions {
    flex-direction: column;
  }
}
</style>