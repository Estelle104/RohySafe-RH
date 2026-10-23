<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
const router = useRouter(); const demandes = ref([]); const erreur = ref('')
onMounted(async () => { try { const r = await fetch('/api/offres/a-publier'); if (!r.ok) throw new Error(); demandes.value = await r.json() } catch { erreur.value = 'Impossible de charger les demandes à publier.' } })
</script>
<template><main class="page"><h1>Demandes à publier</h1><p>Demandes créées par le DG, validées et attribuées aux RH.</p><p v-if="erreur">{{ erreur }}</p><p v-else-if="!demandes.length">Aucune demande à publier.</p><article v-for="d in demandes" :key="d.id" class="carte"><div><h2>{{ d.nombre_demande }} poste(s) — {{ d.poste }}</h2><p>{{ d.departement }} · {{ d.type_contrat }}</p></div><button @click="router.push({ name: 'offre-formulaire', params: { id: d.id } })">Ouvrir l’offre</button></article></main></template>
<style scoped>.page{max-width:850px;margin:auto;padding:24px}.carte{display:flex;flex-direction:column;gap:16px;border:1px solid #d5d5d5;border-radius:14px;padding:16px;margin:12px 0;background:#fff}.carte h2,.carte p{margin:0 0 6px}.carte p{color:#667}.carte button{align-self:flex-end}button{border:0;border-radius:8px;background:teal;color:#fff;padding:10px 14px;font-weight:bold;cursor:pointer}</style>
