<template>
  <div class="page">
    <header class="navbar">
      <span class="brand">RohySafe</span>
      <div class="nav-actions">
        <button class="btn secondary" type="button" @click="retourOffres">Retour aux offres</button>
        <button class="btn secondary" type="button" @click="seDeconnecter">Se déconnecter</button>
      </div>
    </header>

    <main class="contenu">
      <div class="entete">
        <h1>Mes notifications</h1>
        <p>Suivi de vos candidatures (entretiens, engagement...)</p>
      </div>

      <p v-if="chargement" class="message">Chargement...</p>
      <p v-else-if="erreur" class="message erreur">{{ erreur }}</p>
      <p v-else-if="notifications.length === 0" class="message">Aucune notification.</p>

      <section v-else class="liste">
        <article v-for="notif in notifications" :key="notif.id" class="carte" :class="{ lu: notif.lu }">
          <div class="entete-carte">
            <span class="type" :class="notif.type">{{ libelleType(notif.type) }}</span>
            <span class="date">{{ formaterDate(notif.date_creation) }}</span>
          </div>
          <p class="message-notif">{{ notif.message }}</p>
          <div class="actions">
            <button v-if="!notif.lu" class="btn" type="button" @click="marquerLu(notif)">Marquer comme lu</button>
            <button class="btn secondary" type="button" @click="retourOffres">Voir les offres</button>
          </div>
        </article>
      </section>
    </main>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { deconnecterCandidat, getCandidatConnecte } from '../services/candidatAuth'

const router = useRouter()
const candidat = ref(getCandidatConnecte())
const notifications = ref([])
const chargement = ref(true)
const erreur = ref('')

function formaterDate(valeur) {
  if (!valeur) return '-'
  const date = new Date(valeur.replace(' ', 'T'))
  if (Number.isNaN(date.getTime())) return valeur
  return date.toLocaleString('fr-FR')
}

function libelleType(type) {
  if (type === 'engage') return 'Engagé'
  if (type === 'entretien') return 'Entretien'
  if (type === 'proposition') return 'Proposition'
  return type
}

async function charger() {
  chargement.value = true
  erreur.value = ''
  try {
    if (!candidat.value?.id) {
      router.push({ name: 'candidat-login' })
      return
    }
    const res = await fetch(`/api/candidats/${candidat.value.id}/notifications`, { headers: { Accept: 'application/json' } })
    if (!res.ok) throw new Error('Erreur lors du chargement des notifications.')
    notifications.value = await res.json()
  } catch (e) {
    erreur.value = e.message
  } finally {
    chargement.value = false
  }
}

async function marquerLu(notif) {
  try {
    await fetch(`/api/candidats/notifications/${notif.id}/lu`, { method: 'POST', headers: { Accept: 'application/json' } })
    notif.lu = 1
  } catch (e) {
    erreur.value = e.message
  }
}

function retourOffres() {
  router.push({ name: 'offres' })
}

function seDeconnecter() {
  deconnecterCandidat()
  router.push({ name: 'candidat-login' })
}

onMounted(charger)
</script>

<style scoped>
.page {
  min-height: 100vh;
  background: #f7f9fb;
  font-family: "Lucida Sans", "Lucida Sans Regular", "Lucida Grande", "Lucida Sans Unicode", Geneva, Verdana, sans-serif;
}
.navbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 32px;
  background: #fff;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}
.brand {
  font-weight: 800;
  color: teal;
  font-size: 18px;
}
.nav-actions {
  display: flex;
  gap: 12px;
}
.btn {
  padding: 9px 18px;
  border-radius: 10px;
  border: 0;
  background: teal;
  color: #fff;
  font-family: inherit;
  cursor: pointer;
}
.btn.secondary {
  background: #fff;
  color: teal;
  border: 1px solid teal;
}
.contenu {
  max-width: 880px;
  margin: 0 auto;
  padding: 32px 16px 60px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.entete h1 {
  margin: 0;
  color: teal;
}
.entete p {
  margin: 6px 0 0;
  color: #555;
}
.liste {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.carte {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
  padding: 18px 22px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.carte.lu {
  opacity: 0.7;
}
.entete-carte {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.type {
  padding: 4px 10px;
  border-radius: 999px;
  background: #e6f2f2;
  color: #006666;
  font-size: 12px;
  font-weight: 700;
}
.type.engage {
  background: #e7f7ee;
  color: #1f7a3e;
}
.type.entretien {
  background: #fff4e5;
  color: #b15c00;
}
.date {
  color: #747474;
  font-size: 13px;
}
.message-notif {
  margin: 0;
  line-height: 1.6;
}
.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.message {
  text-align: center;
  color: #555;
}
.message.erreur {
  color: #b42318;
}
</style>
