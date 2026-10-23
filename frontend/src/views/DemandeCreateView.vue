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

async function deconnecter() {
  await fetch('/api/auth/logout', { method: 'POST', credentials: 'include' })
  await router.push('/login')
}
</script>

<template>
  <main class="page">
    <section class="form-container">
      <p class="title">RohySafe</p>
      <p class="subtitle">Demande de recrutement</p>
      <button class="logout" @click="deconnecter">Déconnexion</button>
      <template v-if="$route.query.cree">
        <h1>Demande enregistrée</h1><p>La demande #{{ $route.query.cree }} a bien été créée.</p>
        <button @click="router.replace('/demandes/nouvelle')">Créer une autre demande</button>
      </template>
      <template v-else>
        <h1>{{ confirmation ? 'Confirmer la demande' : 'Nouvelle demande de recrutement' }}</h1>
        <p v-if="utilisateur">Connecté : {{ utilisateur.login }} · {{ utilisateur.departement || 'Tous départements' }}</p>
        <p v-if="erreur" class="error">{{ erreur }}</p>
        <form v-if="!confirmation" class="form" @submit.prevent="confirmation = true">
          <label>Poste et département<select v-model="form.id_poste_departement" class="input" required><option value="" disabled>Choisir…</option><option v-for="p in postes" :key="p.id" :value="p.id">{{ p.poste }} · {{ p.departement }}</option></select></label>
          <label>Nombre de personnes requises<input v-model.number="form.nombre_demande" class="input" type="number" min="1" required></label>
          <label>Type de contrat<select v-model="form.id_type_contrat" class="input" required><option value="" disabled>Choisir…</option><option v-for="t in types" :key="t.id" :value="t.id">{{ t.libelle }}</option></select></label>
          <label>Date de prise de poste<input v-model="form.date_prise_poste" class="input" type="date"></label>
          <label>Salaire proposé (mensuel)<input v-model="form.salaire_propose" class="input" type="number" min="0" step="0.01" required></label>
          <button class="form-btn" type="submit">Vérifier la demande</button>
        </form>
        <div v-else class="review">
          <p><b>Poste :</b> {{ posteChoisi?.poste }}</p><p><b>Département :</b> {{ posteChoisi?.departement }}</p>
          <p><b>Personnes requises :</b> {{ form.nombre_demande }}</p><p><b>Contrat :</b> {{ types.find(t => String(t.id) === String(form.id_type_contrat))?.libelle }}</p>
          <p><b>Date de prise de poste :</b> {{ form.date_prise_poste || 'Non précisée' }}</p><p><b>Salaire proposé :</b> {{ form.salaire_propose }}</p>
          <button class="secondary" @click="confirmation = false">Modifier</button> <button class="form-btn" :disabled="chargement" @click="enregistrer">{{ chargement ? 'Enregistrement…' : 'Confirmer et envoyer' }}</button>
        </div>
      </template>
    </section>
  </main>
</template>

<style scoped>
.page{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;background:#f1f3f6;font-family:"Lucida Sans","Lucida Sans Regular","Lucida Grande","Lucida Sans Unicode",Geneva,Verdana,sans-serif;color:#222}.form-container{width:100%;max-width:480px;background:#fff;box-shadow:rgba(0,0,0,.35) 0 5px 15px;border-radius:10px;padding:30px}.title{text-align:center;margin:0 0 8px;font-size:28px;font-weight:800}.subtitle{text-align:center;margin:0 0 18px;color:#747474}.form{display:flex;flex-direction:column;gap:14px}label{display:flex;flex-direction:column;gap:6px;font-size:13px;font-weight:700}.input{box-sizing:border-box;width:100%;border-radius:20px;border:1px solid #c0c0c0;padding:12px 15px;outline:0;transition:border-color .2s;font:inherit;font-weight:400}.input:focus{border-color:teal}.form-btn{padding:12px 15px;font-family:inherit;border-radius:20px;border:0;background:teal;color:#fff;cursor:pointer;box-shadow:rgba(0,0,0,.24) 0 3px 8px}.form-btn:hover{background:#006666}.form-btn:disabled{opacity:.65;cursor:wait}.logout{display:block;margin:0 auto 20px;padding:7px 14px;border:1px solid #c0c0c0;border-radius:20px;background:white;color:#555;cursor:pointer}.secondary{margin:0 8px 0 0;padding:12px 15px;border:0;border-radius:20px;background:#747474;color:#fff;cursor:pointer}.error{color:#b42318;margin:0;font-size:14px}h1{font-size:22px} .review p{padding:8px 0;border-bottom:1px solid #eee}
</style>
