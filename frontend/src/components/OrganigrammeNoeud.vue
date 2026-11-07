<script setup>
defineProps({
  relation: { type: Object, required: true },
  enfants: { type: Object, required: true },
})
</script>

<template>
  <li>
    <div class="person">
      <strong>{{ relation.subordonne }}</strong>
      <small>Subordonné · {{ relation.matricule_subordonne }}</small>
    </div>
    <ul v-if="enfants[relation.id_subordonne]">
      <OrganigrammeNoeud v-for="enfant in enfants[relation.id_subordonne]" :key="enfant.id" :relation="enfant" :enfants="enfants" />
    </ul>
  </li>
</template>

<style scoped>
li{position:relative;padding:10px 0 10px 28px;list-style:none}li:before{position:absolute;top:31px;left:0;width:28px;border-top:2px solid #b7d4d4;content:""}ul{margin:0;padding-left:28px;border-left:2px solid #b7d4d4}.person{display:inline-flex;min-width:230px;flex-direction:column;padding:13px 17px;border-left:4px solid #667085;border-radius:8px;background:#f7f8fa}.person small{margin-top:4px;color:#667085;font-size:11px}
</style>
