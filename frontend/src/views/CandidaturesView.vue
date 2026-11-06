<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import SidebarMenu from '../components/SidebarMenu.vue'

const router = useRouter(), items = ref([]), filtre = ref('Tous'), offreFiltre = ref(''), offres = ref([]), erreur = ref('')
const menuOuvert = ref(false)

const statuts = computed(() => ['Tous', ...new Set(items.value.map(i => i.statut))])

// Liste des offres proposées dans le sélecteur : offres publiées + offres
// déjà choisies par des candidats (même si elles ne sont plus disponibles).
const offresDisponibles = computed(() => {
  const parId = new Map()

  for (const offre of offres.value) {
    if (offre?.id != null && offre.titre) {
      parId.set(String(offre.id), offre.titre)
    }
  }

  for (const candidature of items.value) {
    if (candidature?.id_offre != null && !parId.has(String(candidature.id_offre))) {
      parId.set(String(candidature.id_offre), candidature.offre ?? `Offre #${candidature.id_offre}`)
    }
  }

  return [...parId.entries()]
    .map(([id, titre]) => ({ id, titre }))
    .sort((a, b) => a.titre.localeCompare(b.titre, 'fr'))
})

const liste = computed(() => {
  let resultat = items.value

  if (filtre.value !== 'Tous') {
    resultat = resultat.filter(i => i.statut === filtre.value)
  }

  if (offreFiltre.value) {
    resultat = resultat.filter(i => String(i.id_offre ?? '') === offreFiltre.value)
  }

  return resultat
})

onMounted(async () => {
  try {
    const r = await fetch('/api/candidatures')
    if (!r.ok) throw new Error()
    items.value = await r.json()
  } catch {
    erreur.value = 'Impossible de charger les candidatures.'
  }

  try {
    const r = await fetch('/api/offres')
    if (r.ok) offres.value = await r.json()
  } catch {
    // Le sélecteur reste utilisable avec les offres présentes dans les candidatures.
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
        <h1>Candidatures</h1>

        <div class="filtre-offre">
          <label for="filtre-offre">Filtrer par offre</label>
          <select id="filtre-offre" v-model="offreFiltre">
            <option value="">Toutes les offres</option>
            <option v-for="offre in offresDisponibles" :key="offre.id" :value="offre.id">
              {{ offre.titre }}
            </option>
          </select>
        </div>

        <nav class="filtres">
          <button
            v-for="s in statuts"
            :key="s"
            class="chip"
            :class="{ actif: s === filtre }"
            @click="filtre = s"
          >
            {{ s }}
          </button>
        </nav>

        <p v-if="erreur" class="erreur">{{ erreur }}</p>
        <p v-else-if="liste.length === 0" class="vide">Aucune candidature.</p>

        <article v-for="c in liste" :key="c.id" class="carte">
          <div class="carte-entete">
            <div class="avatar">{{ (c.prenom?.[0] ?? '') + (c.nom?.[0] ?? '') }}</div>
            <div class="infos">
              <h2>{{ c.prenom }} {{ c.nom }}</h2>
              <p class="offre-choisie">
                <span>Offre choisie :</span> {{ c.offre || 'Non renseignée' }}
              </p>
              <p class="email">{{ c.email }}</p>
            </div>
            <span class="statut">{{ c.statut }}</span>
          </div>

          <div v-if="c.statut === 'preselectionne' || c.statut === 'retenu'" class="actions">
            <button
              v-if="c.statut === 'preselectionne'"
              class="btn"
              @click="router.push({ name: 'entretien-formulaire', params: { id: c.id } })"
            >
              Créer l’entretien
            </button>
            <button
              v-if="c.statut === 'retenu'"
              class="btn"
              @click="router.push({ name: 'contrat-fiche', params: { id: c.id } })"
            >
              Générer le contrat
            </button>
          </div>
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
  position: relative;
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

.eyebrow {
  margin: 0 0 6px;
  color: #747474;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}

h1 {
  margin: 0 0 20px;
  color: teal;
  font-size: 28px;
  font-weight: 800;
}

/* Filtres */
.filtre-offre {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  padding: 12px 16px;
  border-radius: 10px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.12) 0 2px 8px;
}

.filtre-offre label {
  flex: none;
  color: #344054;
  font-size: 13px;
  font-weight: 700;
}

.filtre-offre select {
  flex: 1;
  min-width: 0;
  max-width: 380px;
  padding: 9px 14px;
  border: 1px solid #c0c0c0;
  border-radius: 10px;
  background: #fff;
  color: #222;
  font-family: inherit;
  font-size: 13px;
  cursor: pointer;
}

.filtre-offre select:focus {
  border-color: teal;
  outline: 2px solid #b3d9d9;
  outline-offset: 1px;
}

.filtres {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 20px;
}

.chip {
  padding: 8px 16px;
  border: 1px solid #c0c0c0;
  border-radius: 10px;
  background: #fff;
  font-family: inherit;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.chip:hover {
  border-color: teal;
}

.chip.actif {
  border-color: teal;
  background: teal;
  color: #fff;
}

/* Cartes */
.carte {
  margin: 0 0 16px;
  padding: 20px;
  border-radius: 10px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
}

.carte-entete {
  display: flex;
  align-items: center;
  gap: 14px;
}

.avatar {
  display: flex;
  flex: none;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: teal;
  color: #fff;
  font-weight: 800;
  text-transform: uppercase;
}

.infos {
  min-width: 0;
  flex: 1;
}

.infos h2 {
  margin: 0 0 4px;
  font-size: 17px;
  font-weight: 800;
}

.infos p {
  margin: 0;
  overflow: hidden;
  color: #747474;
  font-size: 13px;
  text-overflow: ellipsis;
}

.infos .offre-choisie {
  margin-bottom: 3px;
  color: #006666;
  font-weight: 700;
}

.infos .offre-choisie span {
  color: #747474;
  font-weight: 400;
}

.statut {
  flex: none;
  padding: 4px 12px;
  border-radius: 10px;
  background: #e6f2f2;
  color: #006666;
  font-size: 12px;
  font-weight: 700;
}

.actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e5e5e5;
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

.btn:active {
  box-shadow: none;
}

.erreur {
  padding: 12px 15px;
  border-radius: 8px;
  background: #fdecec;
  color: #b42318;
  font-size: 13px;
}

.vide {
  color: #747474;
  font-size: 13px;
  text-align: center;
}

@media (max-width: 960px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .burger {
    display: block;
  }
}

@media (max-width: 520px) {
  .carte-entete {
    flex-wrap: wrap;
  }

  .filtre-offre {
    align-items: stretch;
    flex-direction: column;
    gap: 6px;
  }

  .filtre-offre select {
    max-width: none;
  }
}
</style>
