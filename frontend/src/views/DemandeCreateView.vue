<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const utilisateur = ref(null)
const postes = ref([])
const types = ref([])
const form = ref({ id_poste_departement: '', nombre_demande: 1, id_type_contrat: '', date_prise_poste: '', salaire_propose: '' })
const confirmation = ref(false)
const erreur = ref('')
const chargement = ref(false)
const posteChoisi = computed(() => postes.value.find((poste) => String(poste.id) === String(form.value.id_poste_departement)))

onMounted(async () => {
  try {
    const meResponse = await fetch('/api/auth/me', { credentials: 'include' })
    const me = await meResponse.json()
    utilisateur.value = me.utilisateur
    if (!utilisateur.value) return router.replace('/login')
    const optionsResponse = await fetch('/api/demandes/options', { credentials: 'include' })
    const options = await optionsResponse.json()
    if (!optionsResponse.ok) throw new Error(options.messages?.error || 'Chargement impossible.')
    postes.value = options.postes
    types.value = options.types_contrat
    const affectation = postes.value.find((p) => String(p.departement) === String(utilisateur.value.departement))
    if (affectation) form.value.id_poste_departement = affectation.id
  } catch (error) { erreur.value = error.message }
})

async function enregistrer() {
  chargement.value = true
  erreur.value = ''
  try {
    const response = await fetch('/api/demandes/', {
      method: 'POST', credentials: 'include', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form.value),
    })
    const result = await response.json()
    if (!response.ok) throw new Error(result.messages?.error || Object.values(result.messages || {}).flat().join(' ') || 'Enregistrement impossible.')
    await router.push({ path: '/demandes/nouvelle', query: { cree: result.id } })
  } catch (error) { erreur.value = error.message } finally { chargement.value = false }
}
</script>

<template>
  <main class="page">
    <header><strong>RohySafe · Demande de recrutement</strong><button @click="router.push('/login')">Déconnexion</button></header>
    <section class="card">
      <template v-if="$route.query.cree">
        <h1>Demande enregistrée</h1><p>La demande #{{ $route.query.cree }} a bien été créée.</p>
        <button @click="router.replace('/demandes/nouvelle')">Créer une autre demande</button>
      </template>
      <template v-else>
        <h1>{{ confirmation ? 'Confirmer la demande' : 'Nouvelle demande de recrutement' }}</h1>
        <p v-if="utilisateur">Connecté : {{ utilisateur.login }} · {{ utilisateur.departement || 'Tous départements' }}</p>
        <p v-if="erreur" class="error">{{ erreur }}</p>
        <form v-if="!confirmation" @submit.prevent="confirmation = true">
          <label>Poste et département<select v-model="form.id_poste_departement" required><option value="" disabled>Choisir…</option><option v-for="p in postes" :key="p.id" :value="p.id">{{ p.poste }} · {{ p.departement }}</option></select></label>
          <label>Nombre de personnes requises<input v-model.number="form.nombre_demande" type="number" min="1" required></label>
          <label>Type de contrat<select v-model="form.id_type_contrat" required><option value="" disabled>Choisir…</option><option v-for="t in types" :key="t.id" :value="t.id">{{ t.libelle }}</option></select></label>
          <label>Date de prise de poste<input v-model="form.date_prise_poste" type="date"></label>
          <label>Salaire proposé (mensuel)<input v-model="form.salaire_propose" type="number" min="0" step="0.01" required></label>
          <button type="submit">Vérifier la demande</button>
        </form>
        <div v-else class="review">
          <p><b>Poste :</b> {{ posteChoisi?.poste }}</p><p><b>Département :</b> {{ posteChoisi?.departement }}</p>
          <p><b>Personnes requises :</b> {{ form.nombre_demande }}</p><p><b>Contrat :</b> {{ types.find(t => String(t.id) === String(form.id_type_contrat))?.libelle }}</p>
          <p><b>Date de prise de poste :</b> {{ form.date_prise_poste || 'Non précisée' }}</p><p><b>Salaire proposé :</b> {{ form.salaire_propose }}</p>
          <button class="secondary" @click="confirmation = false">Modifier</button> <button :disabled="chargement" @click="enregistrer">{{ chargement ? 'Enregistrement…' : 'Confirmer et envoyer' }}</button>
        </div>
      </template>
    </section>
  </main>
</template>

<style scoped>
.page{min-height:100vh;background:#f1f3f6;font-family:Arial,sans-serif;color:#222}header{display:flex;justify-content:space-between;padding:18px 5%;background:white} .card{max-width:620px;margin:40px auto;background:white;padding:30px;border-radius:12px;box-shadow:0 5px 18px #0001}h1{font-size:24px}form{display:grid;gap:16px}label{display:grid;gap:7px;font-weight:600}input,select{padding:11px;border:1px solid #bbb;border-radius:6px;font:inherit}button{padding:11px 18px;background:teal;color:white;border:0;border-radius:6px;cursor:pointer}.secondary{background:#667085}.error{color:#b42318}
</style>
