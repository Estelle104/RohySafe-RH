<script setup>
import { computed, onMounted, ref } from 'vue'
import { annulerFinance, getFinanceFiche, getFinanceNotifications, validerFinance } from '../services/financeService'
import SidebarMenu from '../components/SidebarMenu.vue'

const demandes = ref([])
const nombre = ref(0)
const fiche = ref(null)
const motif = ref('')
const loading = ref(false)
const error = ref('')
const message = ref('')
const menuOuvert = ref(false)

const peutValider = computed(() => fiche.value?.est_suffisant === true && fiche.value?.demande?.statut_libelle === 'EN_VALIDATION_FINANCE')

async function chargerNotifications() {
  try {
    const result = await getFinanceNotifications()
    nombre.value = result.data.nombre
    demandes.value = result.data.demandes
  } catch (e) { error.value = e.message }
}

async function ouvrirFiche(id) {
  error.value = ''; message.value = ''; loading.value = true
  try { fiche.value = (await getFinanceFiche(id)).data } catch (e) { error.value = e.message }
  finally { loading.value = false }
}

async function valider() {
  if (!fiche.value || !peutValider.value) return
  loading.value = true; error.value = ''
  try {
    const result = await validerFinance(fiche.value.demande.id)
    message.value = result.message; fiche.value = null; await chargerNotifications()
  } catch (e) { error.value = e.message }
  finally { loading.value = false }
}

async function annuler() {
  if (!fiche.value || !motif.value.trim()) { error.value = 'Le motif d’annulation est obligatoire.'; return }
  loading.value = true; error.value = ''
  try {
    const result = await annulerFinance(fiche.value.demande.id, motif.value)
    message.value = result.message; motif.value = ''; fiche.value = null; await chargerNotifications()
  } catch (e) { error.value = e.message }
  finally { loading.value = false }
}

onMounted(chargerNotifications)
</script>

<template>
  <div class="app-layout">
    <SidebarMenu nom="Espace Finance" role="Gestionnaire de finance" codeRole="Finance" initiales="FI"
      :notifications="nombre" :ouvert="menuOuvert" @fermer="menuOuvert = false" />

    <main class="finance-page">
      <header class="navbar">
        <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
        <div class="header-right">
          <span class="role">Espace Finance</span>
          <button class="bell" type="button" @click="chargerNotifications" aria-label="Actualiser les notifications">
            🔔 <span>{{ nombre }}</span>
          </button>
          <div class="avatar-me">FI</div>
        </div>
      </header>

      <div class="content">
        <h1 class="page-title">Demandes financières</h1>
        <p class="subtitle">Vérifiez automatiquement le budget avant de transmettre une demande à la Direction générale.
        </p>
        <p v-if="message" class="alert success">{{ message }}</p>
        <p v-if="error" class="alert error">{{ error }}</p>
        <section class="finance-layout">
          <aside class="panel">
            <h2 class="panel-title">Notifications ({{ nombre }})</h2>
            <button v-for="demande in demandes" :key="demande.id" class="notification" type="button"
              @click="ouvrirFiche(demande.id)">
              <strong>{{ demande.poste }}</strong>
              <small>{{ demande.departement }} · {{ demande.nombre_demande }} personne(s)</small>
              <small>{{ demande.date_creation }}</small>
            </button>
            <p v-if="!demandes.length" class="empty">Aucune demande en attente.</p>
          </aside>

          <section class="fiche">
            <p v-if="loading" class="loading">Chargement...</p>
            <p v-else-if="!fiche" class="empty">Sélectionnez une notification pour afficher sa fiche.</p>
            <template v-else>
              <div class="fiche-header">
                <h2>Fiche de validation</h2><span class="status">À traiter</span>
              </div>
              <div class="calculs">
                <div class="calcul"><span class="calcul-label">Poste</span><span class="calcul-value">{{
                  fiche.demande.poste }}</span></div>
                <div class="calcul"><span class="calcul-label">Département</span><span class="calcul-value">{{
                  fiche.demande.departement }}</span></div>
                <div class="calcul"><span class="calcul-label">Salaire par personne</span><span class="calcul-value">{{
                  fiche.demande.salaire_propose }}</span></div>
                <div class="calcul"><span class="calcul-label">Nombre de personnes</span><span class="calcul-value">{{
                  fiche.demande.nombre_demande }}</span></div>
                <div class="calcul"><span class="calcul-label">Montant total demandé</span><span class="calcul-value">{{
                    fiche.montant }}</span></div>
                <div class="calcul"><span class="calcul-label">Budget disponible</span><span class="calcul-value">{{
                    fiche.disponible }}</span></div>
              </div>
              <p class="budget-result" :class="fiche.est_suffisant ? 'budget-ok' : 'budget-ko'">
                {{ fiche.est_suffisant ? 'Budget suffisant' : 'Budget insuffisant' }}
              </p>
              <label class="motif-label" for="motif">Motif d’annulation</label>
              <textarea id="motif" v-model="motif" placeholder="Saisissez un motif si vous annulez la demande"
                rows="4" />
              <div class="actions">
                <button class="btn" type="button" :disabled="loading || !peutValider" @click="valider">Valider</button>
                <button class="btn cancel" type="button" :disabled="loading" @click="annuler">Annuler</button>
              </div>
            </template>
          </section>
        </section>
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
}

.finance-page {
  min-width: 0;
  min-height: 100vh;
  background: #f1f3f6;
  color: #222;
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  letter-spacing: .01em;
}

/* La marque est maintenant dans la sidebar */
.navbar {
  position: sticky;
  top: 0;
  z-index: 5;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding: 10px 20px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, .15) 0 2px 8px;
}

.burger {
  display: none;
  margin-right: auto;
  padding: 8px 16px;
  border: 0;
  border-radius: 20px;
  background: #fff;
  color: teal;
  font-family: inherit;
  font-weight: 700;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, .24) 0 3px 8px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.role {
  color: #747474;
  font-size: 13px;
  font-weight: 700;
}

.bell {
  position: relative;
  border: 0;
  background: transparent;
  font-size: 22px;
  cursor: pointer;
}

.bell span {
  position: absolute;
  top: -5px;
  right: -8px;
  min-width: 18px;
  padding: 2px 5px;
  border-radius: 20px;
  background: teal;
  color: #fff;
  font-size: 11px;
}

.avatar-me {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: teal;
  color: #fff;
  font-size: 14px;
  font-weight: 700;
}

.content {
  max-width: 1100px;
  margin: 0 auto;
  padding: 34px 20px 60px;
}

.page-title {
  margin: 0 0 7px;
  font-size: 28px;
  line-height: 1.2;
  letter-spacing: -.03em;
}

.subtitle {
  margin: 0 0 25px;
  color: #747474;
  font-size: 14px;
  line-height: 1.6;
}

.finance-layout {
  display: grid;
  grid-template-columns: 330px 1fr;
  gap: 20px;
  align-items: start;
}

.panel,
.fiche {
  border-radius: 10px;
  background: #fff;
  box-shadow: rgba(0, 0, 0, .12) 0 3px 12px;
}

.panel-title {
  margin: 0;
  padding: 18px 20px;
  border-bottom: 1px solid #e5e5e5;
  font-size: 16px;
  font-weight: 750;
  letter-spacing: -.01em;
}

.notification {
  display: block;
  width: 100%;
  padding: 15px 20px;
  border: 0;
  border-bottom: 1px solid #e5e5e5;
  background: #fff;
  text-align: left;
  font-family: inherit;
  cursor: pointer;
}

.notification:hover,
.notification.selected {
  background: #e6f2f2;
}

.notification strong,
.notification small {
  display: block;
}

.notification strong {
  margin-bottom: 6px;
  color: #222;
  font-size: 14px;
  font-weight: 700;
}

.notification small {
  color: #747474;
  font-size: 12px;
  line-height: 1.5;
}

.empty {
  padding: 20px;
  color: #747474;
  font-size: 13px;
}

.fiche {
  padding: 24px;
}

.fiche h2 {
  margin-top: 0;
  font-size: 20px;
  font-weight: 750;
  letter-spacing: -.02em;
}

.fiche-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-bottom: 1px solid #e5e5e5;
}

.status {
  padding: 5px 10px;
  border-radius: 20px;
  background: #e6f2f2;
  color: #006666;
  font-size: 12px;
  font-weight: 700;
}

.calculs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin: 20px 0;
}

.calcul {
  padding: 14px;
  border-radius: 8px;
  background: #f1f3f6;
}

.calcul-label {
  display: block;
  margin-bottom: 5px;
  color: #747474;
  font-size: 12px;
}

.calcul-value {
  color: #222;
  font-size: 15px;
  font-weight: 750;
  line-height: 1.35;
}

.budget-result {
  margin: 0 0 18px;
  padding: 12px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
}

.budget-ok {
  background: #e8f5e9;
  color: #137333;
}

.budget-ko {
  background: #fdecec;
  color: #b42318;
}

.motif-label {
  display: block;
  margin-bottom: 7px;
  color: #444;
  font-size: 13px;
  font-weight: 700;
}

textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #c0c0c0;
  border-radius: 8px;
  outline: 0;
  font: inherit;
  resize: vertical;
}

textarea:focus {
  border-color: teal;
}

.actions {
  display: flex;
  gap: 10px;
  margin-top: 16px;
}

.btn {
  padding: 11px 22px;
  border: 0;
  border-radius: 20px;
  background: teal;
  color: #fff;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: rgba(0, 0, 0, .24) 0 3px 8px;
}

.btn:hover {
  background: #006666;
}

.btn:disabled {
  opacity: .45;
  cursor: not-allowed;
}

.btn.cancel {
  background: #b42318;
}

.btn.cancel:hover {
  background: #8f1d14;
}

.alert {
  margin: 0 0 18px;
  padding: 12px 15px;
  border-radius: 8px;
  font-size: 13px;
}

.alert.success {
  background: #e8f5e9;
  color: #137333;
}

.alert.error {
  background: #fdecec;
  color: #b42318;
}

.loading {
  color: #747474;
  font-size: 13px;
}

@media (max-width: 960px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .burger {
    display: block;
  }
}

@media (max-width: 720px) {
  .finance-layout {
    grid-template-columns: 1fr;
  }

  .navbar {
    padding: 10px 16px;
  }

  .role {
    display: none;
  }
}

@media (max-width: 480px) {
  .calculs {
    grid-template-columns: 1fr;
  }

  .content {
    padding: 22px 14px 40px;
  }
}
</style>