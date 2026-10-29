<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import SidebarMenu from '../components/SidebarMenu.vue'

const router = useRouter()
const demandes = ref([])
const erreur = ref('')
const chargement = ref(true)
const menuOuvert = ref(false)

onMounted(async () => {
  try {
    const r = await fetch('/api/offres/a-publier', { credentials: 'include' })
    if (!r.ok) throw new Error()
    demandes.value = await r.json()
  } catch {
    erreur.value = 'Impossible de charger les demandes à publier.'
  } finally {
    chargement.value = false
  }
})
</script>

<template>
  <div class="app-layout">
    <!-- Nom / initiales : à remplacer par l'utilisateur connecté -->
    <SidebarMenu
      nom="Ressources humaines"
      role="RH"
      code-role="RH"
      initiales="RH"
      :ouvert="menuOuvert"
      @fermer="menuOuvert = false"
    />

    <main class="page">
      <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>

      <div class="contenu">
        <p class="eyebrow">Recrutement</p>
        <h1>Demandes à publier</h1>
        <p class="sous-titre">Demandes créées par le DG, validées et attribuées aux RH.</p>

        <p v-if="chargement" class="message">Chargement des demandes…</p>
        <p v-else-if="erreur" class="message erreur">{{ erreur }}</p>
        <p v-else-if="!demandes.length" class="message">Aucune demande à publier.</p>

        <article v-for="d in demandes" :key="d.id" class="carte">
          <div class="infos">
            <p class="compteur">{{ d.nombre_demande }} poste(s)</p>
            <h2>{{ d.poste }}</h2>
            <div class="tags">
              <span class="tag">{{ d.departement }}</span>
              <span class="tag">{{ d.type_contrat }}</span>
            </div>
          </div>

          <button class="btn" type="button" @click="router.push({ name: 'offre-formulaire', params: { id: d.id } })">
            Ouvrir l’offre
          </button>
        </article>
      </div>
    </main>
  </div>
</template>

<style scoped>
/* Structure globale : sidebar + page */
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
  border-radius: 20px;
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

/* Cartes */
.carte {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin: 0 0 16px;
  padding: 20px;
  border-radius: 10px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
}

.infos {
  min-width: 0;
}

.compteur {
  margin: 0 0 6px;
  color: teal;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

.carte h2 {
  margin: 0 0 10px;
  font-size: 18px;
  font-weight: 800;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tag {
  padding: 4px 12px;
  border-radius: 20px;
  background: #e6f2f2;
  color: #006666;
  font-size: 12px;
  font-weight: 700;
}

.btn {
  flex: none;
  padding: 10px 22px;
  border: 0;
  border-radius: 20px;
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

.btn:active {
  box-shadow: none;
}

/* Messages */
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

@media (max-width: 960px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .burger {
    display: block;
  }
}

@media (max-width: 560px) {
  .carte {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>