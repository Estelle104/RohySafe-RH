<script setup>
// Mise en page commune aux pages du backoffice : menu latéral + en-tête de page.
// Le contenu propre à chaque page se met dans le <slot>.
import { computed, onMounted, ref } from 'vue'
import SidebarMenu from './SidebarMenu.vue'
import { chargerUtilisateurInterne } from '../services/authInterne'

defineProps({
  eyebrow: { type: String, default: '' },
  titre: { type: String, required: true },
  sousTitre: { type: String, default: '' },
  // true : page plus large (tableaux)
  large: { type: Boolean, default: false }
})

const menuOuvert = ref(false)
const utilisateur = ref(null)

const codeRole = computed(() => utilisateur.value?.role ?? '')
const estDirection = computed(() => ['DG', 'Direction'].includes(codeRole.value))
const libelleRole = computed(() => estDirection.value ? 'Direction générale' : codeRole.value)
const nomUtilisateur = computed(() => utilisateur.value?.login ?? '')
const initiales = computed(() => {
  if (estDirection.value || codeRole.value === 'RH') return codeRole.value === 'RH' ? 'RH' : 'DG'
  return nomUtilisateur.value.slice(0, 2).toUpperCase()
})

onMounted(async () => {
  utilisateur.value = await chargerUtilisateurInterne()
})
</script>

<template>
  <div class="app-layout">
    <SidebarMenu
      :nom="nomUtilisateur"
      :role="libelleRole"
      :code-role="codeRole"
      :initiales="initiales"
      :ouvert="menuOuvert"
      @fermer="menuOuvert = false"
    />

    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>

      <div class="contenu" :class="{ large }">
        <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
        <h1>{{ titre }}</h1>
        <p v-if="sousTitre" class="sous-titre">{{ sousTitre }}</p>

        <slot />
      </div>
    </main>
  </div>
</template>

<style scoped>
/* Structure globale : sidebar + page (identique à DemandesAPublierView) */
.app-layout {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  min-height: 100vh;
  background: #f1f3f6;
  color: #222;
  font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande",
    "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
}

.page {
  min-width: 0;
  min-height: 100vh;
}

.burger {
  display: none;
  margin: 16px 0 0 16px;
  padding: 8px 16px;
  border: 0;
  border-radius: 10px;
  background: #fff;
  color: teal;
  font-family: inherit;
  font-weight: 700;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.contenu {
  max-width: 850px;
  margin: 0 auto;
  padding: 32px 20px 60px;
}

.contenu.large {
  max-width: 1100px;
}

.eyebrow {
  margin: 0 0 6px;
  color: #747474;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

h1 {
  margin: 0 0 8px;
  color: teal;
  font-size: 28px;
  font-weight: 800;
}

.sous-titre {
  margin: 0 0 24px;
  color: #667085;
  font-size: 14px;
  line-height: 1.5;
}

@media (max-width: 960px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .burger {
    display: block;
  }
}
</style>