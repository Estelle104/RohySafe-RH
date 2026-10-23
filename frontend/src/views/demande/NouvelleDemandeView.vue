<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import DemandeForm from '../../components/demande/DemandeForm.vue'
import DemandeConfirmation from '../../components/demande/DemandeConfirmation.vue'

import { obtenirOptions, creerDemande } from '../../services/demandeService.js'

const router = useRouter()
const route = useRoute()

// Données de l'utilisateur connecté.
const utilisateur = ref(null)

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
    <main class="page">
        <section class="form-container">
            <p class="title">RohySafe</p>
            <p class="subtitle">Demande de recrutement</p>

            <button class="logout" @click="deconnecter">
                Déconnexion
            </button>

            <!-- Résultat après l'enregistrement. -->
            <template v-if="route.query.cree">
                <h1>Demande enregistrée</h1>

                <p>
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

                <!-- <p v-if="utilisateur">
                    Connecté : {{ utilisateur.login }}
                    ·
                    {{ utilisateur.departement || 'Tous départements' }}
                </p> -->

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
</template>

<style scoped>
.page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: #f1f3f6;
    font-family: "Lucida Sans", "Lucida Sans Regular",
        "Lucida Grande", "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
    color: #222;
}

.form-container {
    box-sizing: border-box;
    width: 100%;
    max-width: 480px;
    background: #fff;
    box-shadow: rgba(0, 0, 0, 0.35) 0 5px 15px;
    border-radius: 10px;
    padding: 30px;
}

.title {
    text-align: center;
    margin: 0 0 8px;
    font-size: 28px;
    font-weight: 800;
}

.subtitle {
    text-align: center;
    margin: 0 0 18px;
    color: #747474;
}

.logout {
    display: block;
    margin: 0 auto 20px;
    padding: 7px 14px;
    border: 1px solid #c0c0c0;
    border-radius: 20px;
    background: white;
    color: #555;
    cursor: pointer;
}

.form-btn {
    padding: 12px 15px;
    font-family: inherit;
    border-radius: 20px;
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
    color: #b42318;
    margin: 0;
    font-size: 14px;
}

h1 {
    font-size: 22px;
}
</style>