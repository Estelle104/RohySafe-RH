<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import SidebarMenu from '../components/SidebarMenu.vue'

const route = useRoute(), router = useRouter(), offre = ref(null), erreur = ref('')
const menuOuvert = ref(false)

onMounted(async () => {
  try {
    const r = await fetch(`/api/offres/${route.params.id}`)
    if (!r.ok) throw new Error()
    offre.value = await r.json()
  } catch {
    erreur.value = 'Impossible de charger l’offre publiée.'
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

      <section v-if="offre" class="succes">
        <div class="icone" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
        </div>

        <p class="eyebrow">Publication réussie</p>
        <h1>{{ offre.titre }}</h1>
        <p class="texte">
          L’offre pour <strong>{{ offre.poste }}</strong> ({{ offre.departement }}) est maintenant publiée.
        </p>
        <p v-if="offre.date_limite" class="texte">
          Disponible jusqu’au <strong>{{ new Date(offre.date_limite).toLocaleDateString('fr-FR') }}</strong>.
        </p>

        <button class="btn" type="button" @click="router.push('/offres/a-publier')">
          Retour aux demandes
        </button>
      </section>

      <p v-else-if="erreur" class="message erreur">{{ erreur }}</p>
      <p v-else class="message">Chargement…</p>
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
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: #f1f3f6;
}

.burger {
  display: none;
  align-self: flex-start;
  margin-bottom: 16px;
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

.succes {
  box-sizing: border-box;
  width: 100%;
  max-width: 560px;
  padding: 36px 30px;
  border-radius: 10px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
  text-align: center;
}

.icone {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  margin: 0 auto 18px;
  border-radius: 50%;
  background: #e6f2f2;
}

.icone svg {
  width: 32px;
  height: 32px;
  fill: none;
  stroke: teal;
  stroke-width: 2.4;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.eyebrow {
  margin: 0 0 6px;
  color: #747474;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

h1 {
  margin: 0 0 14px;
  color: teal;
  font-size: 26px;
  font-weight: 800;
}

.texte {
  margin: 0 0 8px;
  color: #475467;
  font-size: 14px;
  line-height: 1.6;
}

.btn {
  margin-top: 20px;
  padding: 12px 24px;
  border: 0;
  border-radius: 10px;
  background: teal;
  color: #fff;
  font-family: inherit;
  font-size: 14px;
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

.message {
  margin: 0;
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

  .page {
    justify-content: flex-start;
    padding-top: 16px;
  }

  .burger {
    display: block;
  }
}
</style>