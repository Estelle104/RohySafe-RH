<script setup>
import { computed, onMounted, ref } from 'vue'
import { getHeuresSupplementaires, validerHeureSupplementaire } from '../../services/heureSupplementaireService'
import SidebarMenu from '../../components/SidebarMenu.vue'

const demandes = ref([])
const erreur = ref('')
const message = ref('')
const loading = ref(false)
const menuOuvert = ref(false)
const role = ref('Manager')

const enAttente = computed(() => demandes.value.filter(item => (item.statut_libelle || '').toLowerCase() === 'en attente'))

async function chargerRole() {
  try {
    const reponse = await fetch('/api/auth/me', { credentials: 'include' })
    if (!reponse.ok) return
    const { utilisateur } = await reponse.json()
    if (utilisateur?.role) {
      role.value = utilisateur.role
    }
  } catch {
    // ignorer, le rôle par défaut est déjà Manager
  }
}

async function chargerDemandes() {
  loading.value = true
  erreur.value = ''
  message.value = ''

  try {
    await chargerRole()
    const reponse = await getHeuresSupplementaires()
    demandes.value = Array.isArray(reponse) ? reponse : []
  } catch (e) {
    erreur.value = e.message
  } finally {
    loading.value = false
  }
}

async function valider(id) {
  if (!id) return
  try {
    const result = await validerHeureSupplementaire(id)
    message.value = result.message || 'Demande validée.'
    await chargerDemandes()
  } catch (e) {
    erreur.value = e.message
  }
}

onMounted(chargerDemandes)
</script>

<template>
  <div class="app-layout">
    <SidebarMenu
      :nom="role === 'RH' ? 'Espace RH' : 'Espace Manager'"
      :role="role === 'RH' ? 'Responsable RH' : 'Manager'"
      :codeRole="role"
      :initiales="role === 'RH' ? 'RH' : 'M'"
      :notificationsHeures="enAttente.length"
      :ouvert="menuOuvert"
      @fermer="menuOuvert = false"
    />

    <main class="page">
      <header class="topbar">
        <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
        <div class="topbar-right">
          <span class="role">{{ role === 'RH' ? 'Ressources humaines' : 'Manager' }}</span>
          <div class="avatar">{{ role === 'RH' ? 'RH' : 'M' }}</div>
        </div>
      </header>

      <div class="content">
        <h1>Demandes d’heures supplémentaires</h1>
        

        <p v-if="message" class="alert success">{{ message }}</p>
        <p v-if="erreur" class="alert error">{{ erreur }}</p>

        <section class="panel">
          <div class="panel-header">
            <h2>Demandes</h2>
            <span class="badge">{{ enAttente.length }} en attente</span>
            <div v-if="role === 'Manager'" class="panel-actions">
                <button class="btn-primary" type="button" @click="$router.push('/heures-supplementaires/nouveau')">
                + Nouvelle demande
                </button>
            </div>
          </div>

          <div v-if="loading" class="empty">Chargement...</div>
          <div v-else-if="!demandes.length" class="empty">Aucune demande pour le moment.</div>

          <div v-else class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Employé</th>
                  <th>Département</th>
                  <th>Période</th>
                  <th>Heures</th>
                  <th>Statut</th>
                  <th v-if="role === 'RH'">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="demande in demandes" :key="demande.id">
                  <td>
                    <strong>{{ demande.prenom || '' }} {{ demande.nom || '' }}</strong>
                  </td>
                  <td>{{ demande.departement || '—' }}</td>
                  <td>{{ demande.date_debut }} → {{ demande.date_fin }}</td>
                  <td>{{ demande.heure_debut }} → {{ demande.heure_fin }}</td>
                  <td>
                    <span class="status" :class="(demande.statut_libelle || '').toLowerCase().replace(/\s+/g, '-')">
                      {{ demande.statut_libelle || '—' }}
                    </span>
                  </td>
                  <td v-if="role === 'RH'">
                    <button
                      v-if="(demande.statut_libelle || '').toLowerCase() === 'en attente'"
                      class="btn"
                      type="button"
                      @click="valider(demande.id)"
                    >
                      Valider
                    </button>
                    <span v-else class="muted">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- <div v-if="role === 'Manager'" class="panel-actions">
            <button class="btn-primary" type="button" @click="$router.push('/heures-supplementaires/nouveau')">
              + Nouvelle demande
            </button>
          </div> -->
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.app-layout {
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  min-height: 100vh;
  background: #f1f3f6;
}

.page {
  min-width: 0;
  min-height: 100vh;
  background: #f1f3f6;
  color: #1f2937;
  font-family: Inter, 'Segoe UI', sans-serif;
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 12px 20px;
  background: white;
  box-shadow: rgba(0, 0, 0, 0.08) 0 2px 10px;
}

.burger {
  display: none;
  margin-right: auto;
  border: 0;
  background: transparent;
  color: teal;
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.role {
  color: #6b7280;
  font-size: 13px;
  font-weight: 700;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: teal;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}

.content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 28px 20px 40px;
}

h1 {
  margin: 0;
  font-size: 30px;
  letter-spacing: -0.03em;
}

.subtitle {
  margin: 8px 0 26px;
  color: #6b7280;
}

.panel {
  background: white;
  border-radius: 12px;
  box-shadow: rgba(0, 0, 0, 0.08) 0 5px 20px;
  overflow: hidden;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.panel-header h2 {
  margin: 0;
  font-size: 18px;
}

.badge {
  padding: 7px 10px;
  border-radius: 999px;
  background: #e6f3f3;
  color: #0f766e;
  font-size: 12px;
  font-weight: 700;
}

.table-wrap {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 15px 18px;
  border-bottom: 1px solid #edf1f5;
  text-align: left;
  vertical-align: middle;
}

th {
  background: #f8fafc;
  color: #475569;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.status {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.status.en-attente { background: #fff7d6; color: #9a6700; }
.status.validée { background: #dcfce7; color: #166534; }
.status.refusée { background: #fee2e2; color: #991b1b; }

.btn {
  border: 0;
  border-radius: 8px;
  background: #0f766e;
  color: white;
  padding: 9px 14px;
  font-weight: 700;
  cursor: pointer;
}

.btn-primary {
  border: 0;
  border-radius: 8px;
  background: #0f766e;
  color: white;
  padding: 9px 14px;
  font-weight: 700;
  cursor: pointer;
}

.panel-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.muted {
  color: #6b7280;
}

.empty {
  padding: 30px 24px;
  color: #6b7280;
}

.alert {
  margin: 0 0 16px;
  padding: 12px 14px;
  border-radius: 8px;
  font-weight: 600;
}

.alert.success { background: #ecfdf5; color: #166534; }
.alert.error { background: #fef2f2; color: #991b1b; }

@media (max-width: 880px) {
  .app-layout { grid-template-columns: 1fr; }
  .burger { display: inline-flex; }
}
</style>
