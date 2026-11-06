<script setup>
import { computed, onMounted, ref } from 'vue'
import SidebarMenu from '../components/SidebarMenu.vue'
import OrganigrammeNoeud from '../components/OrganigrammeNoeud.vue'
import { getOrganigramme } from '../services/relationHierarchique'

const relations = ref([])
const error = ref('')
const menuOuvert = ref(false)
const relationsUniques = computed(() => {
  const uniques = new Map()
  relations.value.forEach((relation) => {
    const cle = `${relation.id_superieur}-${relation.id_subordonne}`
    if (!uniques.has(cle)) uniques.set(cle, relation)
  })
  return [...uniques.values()]
})
const cycleDetecte = computed(() => relationsUniques.value.length > 0 && racines.value.length === 0)
const enfants = computed(() => relationsUniques.value.reduce((groupes, relation) => {
  ;(groupes[relation.id_superieur] ||= []).push(relation)
  return groupes
}, {}))
const racines = computed(() => {
  const superieursAvecRacine = new Map()
  relationsUniques.value
    .filter((relation) => !relationsUniques.value.some((candidate) => candidate.id_subordonne === relation.id_superieur))
    .forEach((relation) => {
      if (!superieursAvecRacine.has(relation.id_superieur)) {
        superieursAvecRacine.set(relation.id_superieur, relation)
      }
    })
  return [...superieursAvecRacine.values()]
})

onMounted(async () => {
  try {
    relations.value = (await getOrganigramme()).relations
    console.log('relations', relations.value)
    console.log('racines', racines.value)
    console.log('enfants', enfants.value)
  } catch (e) {
    error.value = e.message
  }
})
</script>

<template>
  <div class="app-layout">
    <SidebarMenu nom="Ressources humaines" role="RH" code-role="RH" initiales="RH" :ouvert="menuOuvert" @fermer="menuOuvert = false" />
    <main class="page"><button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
      <div class="contenu"><div class="top"><div><p class="eyebrow">Ressources humaines</p><h1>Organigramme des employés</h1><p class="subtitle">Relations hiérarchiques actives à la date du jour.</p></div><RouterLink to="/backoffice/rh/relations" class="back">Gérer les relations</RouterLink></div>
        <p v-if="error" class="alert error">{{ error }}</p>
        <p v-else-if="cycleDetecte" class="alert error">L’organigramme ne peut pas être affiché : les relations actives forment un cycle hiérarchique. Corrigez les relations dans l’écran de gestion.</p>
        <p v-else-if="!relations.length" class="empty card">Aucune relation hiérarchique active.</p>
        <section v-else class="organigramme card">
          <ul class="tree"><li v-for="racine in racines" :key="racine.id"><div class="person"><strong>{{ racine.superieur }}</strong><small>Supérieur · {{ racine.matricule_superieur }}</small></div>
            <ul v-if="enfants[racine.id_superieur]"><OrganigrammeNoeud v-for="enfant in enfants[racine.id_superieur]" :key="enfant.id" :relation="enfant" :enfants="enfants" /></ul>
          </li></ul>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.app-layout{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh;background:#f1f3f6;color:#222;font-family:Inter,system-ui,sans-serif}.page{min-width:0}.burger{display:none;margin:16px;padding:8px 16px;border:0;border-radius:10px;background:#fff;color:teal;font-weight:700}.contenu{max-width:1100px;margin:auto;padding:40px 20px}.top{display:flex;justify-content:space-between;align-items:flex-end;gap:20px}.eyebrow{margin:0 0 6px;color:#667085;font-size:12px;font-weight:700;text-transform:uppercase}h1{margin:0;color:teal;font-size:28px}.subtitle{color:#667085;font-size:14px}.back{color:teal;font-size:13px;font-weight:700;text-decoration:none}.card{margin-top:22px;padding:28px;background:#fff;border-radius:10px;box-shadow:0 3px 12px #0000001f}.tree,.tree ul{margin:0;padding-left:28px;list-style:none}.tree>li{padding-left:0}.tree ul{border-left:2px solid #b7d4d4}.tree li{position:relative;padding:10px 0 10px 28px}.tree li:before{position:absolute;top:31px;left:0;width:28px;border-top:2px solid #b7d4d4;content:""}.tree>li:before{display:none}.person{display:inline-flex;min-width:230px;flex-direction:column;padding:13px 17px;border-left:4px solid teal;border-radius:8px;background:#e6f2f2}.person small{margin-top:4px;color:#667085;font-size:11px}.subordinate{border-left-color:#667085;background:#f7f8fa}.empty{color:#667085;font-size:13px}.alert{padding:12px;border-radius:8px}.error{background:#fdecec;color:#b42318}@media(max-width:960px){.app-layout{grid-template-columns:1fr}.burger{display:block}}@media(max-width:650px){.top{align-items:flex-start;flex-direction:column}}
</style>
