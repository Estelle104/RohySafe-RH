<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DemandeForm from '../../components/demande/DemandeForm.vue'
import DemandeConfirmation from '../../components/demande/DemandeConfirmation.vue'
import SidebarMenu from '../../components/SidebarMenu.vue'

import { obtenirOptions, creerDemande } from '../../services/demandeService.js'

const router = useRouter()
const route = useRoute()

// Données de l'utilisateur connecté.
const utilisateur = ref(null)

// Menu latéral (affichage mobile).
const menuOuvert = ref(false)

// Options du formulaire.
const postes = ref([])
const types = ref([])

// Données de la demande.
const form = ref({
    id_poste_departement: '',
    nombre_demande: 1,
    id_type_contrat: '',
    date_prise_poste: '',
    salaire_propose: '',
})

// État de la page.
const confirmation = ref(false)
const erreur = ref('')
const chargement = ref(false)

// Initiales affichées dans la sidebar.
const initiales = computed(() =>
    (utilisateur.value?.login ?? '').slice(0, 2).toUpperCase()
)

// Rechercher le poste sélectionné.
const posteChoisi = computed(() =>
    postes.value.find(
        poste =>
            String(poste.id) === String(form.value.id_poste_departement)
    )
)

// Vérifier la session et charger les options.
onMounted(async () => {
    try {
        const meResponse = await fetch('/api/auth/me', {
            credentials: 'include',
        })

        if (!meResponse.ok) {
            await router.replace('/login')
            return
        }

        const me = await meResponse.json()

        utilisateur.value = me.utilisateur

        if (!utilisateur.value) {
            await router.replace('/login')
            return
        }

        const options = await obtenirOptions()

        postes.value = options.postes
        types.value = options.types_contrat

        // Préselectionner un poste du département de l'utilisateur.
        const affectation = postes.value.find(
            poste =>
                String(poste.departement) ===
                String(utilisateur.value.departement)
        )

        if (affectation) {
            form.value.id_poste_departement = affectation.id
        }
    } catch (error) {
        erreur.value = error.message
    }
})

// Passer à l'étape de confirmation.
function verifierDemande() {
    erreur.value = ''
    confirmation.value = true
}

// Revenir au formulaire.
function modifierDemande() {
    confirmation.value = false
}

// Enregistrer la demande.
async function enregistrer() {
    chargement.value = true
    erreur.value = ''

    try {
        const result = await creerDemande(form.value)

        await router.push({
            path: '/demandes/nouvelle',
            query: { cree: result.id },
        })
    } catch (error) {
        erreur.value = error.message
    } finally {
        chargement.value = false
    }
}

// Déconnecter l'utilisateur.
async function deconnecter() {
    try {
        await fetch('/api/auth/logout', {
            method: 'POST',
            credentials: 'include',
        })
    } finally {
        await router.push('/login')
    }
}
</script>

<template>
    <div class="app-layout">
        <SidebarMenu
            :nom="utilisateur?.login ?? ''"
            :role="utilisateur?.departement || 'Tous départements'"
            codeRole="Manager"
            :initiales="initiales"
            :ouvert="menuOuvert"
            @fermer="menuOuvert = false"
            @deconnexion="deconnecter"
        />

        <main class="page">

            
            <button class="burger" type="button" @click="menuOuvert = true">
                ☰ Menu
            </button>

            <!-- Contenu directement dans la page (plus de carte centrée). -->
            <section class="contenu">
                <p class="eyebrow">Demande de recrutement</p>

                <!-- Résultat après l'enregistrement. -->
                <template v-if="route.query.cree">
                    <h1>Demande enregistrée</h1>

                    <p class="texte">
                        La demande #{{ route.query.cree }} a bien été créée.
                    </p>

                    <button class="form-btn" @click="router.replace('/demandes/nouvelle')">
                        Créer une autre demande
                    </button>
                </template>

                <!-- Formulaire et confirmation. -->
                <template v-else>
                    <h1>
                        {{
                            confirmation
                                ? 'Confirmer la demande'
                                : 'Nouvelle demande de recrutement'
                        }}
                    </h1>

                    <p v-if="erreur" class="error">
                        {{ erreur }}
                    </p>

                    <!-- Étape 1 : saisie du formulaire. -->
                    <DemandeForm v-if="!confirmation" :form="form" :postes="postes" :types="types"
                        @submit="verifierDemande" />

                    <!-- Étape 2 : vérification de la demande. -->
                    <DemandeConfirmation v-else :form="form" :poste-choisi="posteChoisi" :types="types"
                        :chargement="chargement" @modifier="modifierDemande" @confirmer="enregistrer" />
                </template>
            </section>
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
    font-family: "Lucida Sans", "Lucida Sans Regular",
        "Lucida Grande", "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
    color: #222;
}

.page {
    min-width: 0;
    min-height: 100vh;
    background: #f1f3f6;
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

/* Contenu intégré à la page, aligné en haut comme les autres pages. */
.contenu {
    box-sizing: border-box;
    width: 100%;
    max-width: 760px;
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

.texte {
    margin: 0 0 18px;
    color: #475467;
    font-size: 14px;
}

.form-btn {
    padding: 12px 22px;
    font-family: inherit;
    font-weight: 700;
    border-radius: 10px;
    border: 0;
    background: teal;
    color: #fff;
    cursor: pointer;
    box-shadow: rgba(0, 0, 0, 0.24) 0 3px 8px;
}

.form-btn:hover {
    background: #006666;
}

.error {
    margin: 0 0 16px;
    padding: 12px 15px;
    border-radius: 8px;
    background: #fdecec;
    color: #b42318;
    font-size: 14px;
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