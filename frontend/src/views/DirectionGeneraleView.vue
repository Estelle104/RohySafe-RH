<script setup>
import { onMounted, ref } from 'vue'
import { getDirectionFiche, getDirectionNotifications, refuserDirection, validerDirection } from '../services/directionService'
import SidebarMenu from '../components/SidebarMenu.vue'

const demandes = ref([]); const nombre = ref(0); const fiche = ref(null); const motif = ref(''); const loading = ref(false); const error = ref(''); const message = ref('')
const menuOuvert = ref(false)
async function charger() { try { const r = await getDirectionNotifications(); nombre.value = r.data.nombre; demandes.value = r.data.demandes } catch (e) { error.value = e.message } }
async function ouvrir(id) { loading.value = true; error.value = ''; try { fiche.value = (await getDirectionFiche(id)).data } catch (e) { error.value = e.message } finally { loading.value = false } }
async function valider() { loading.value = true; try { message.value = (await validerDirection(fiche.value.demande.id)).message; fiche.value = null; await charger() } catch (e) { error.value = e.message } finally { loading.value = false } }
async function refuser() { if (!motif.value.trim()) { error.value = 'Le motif de refus est obligatoire.'; return }; loading.value = true; try { message.value = (await refuserDirection(fiche.value.demande.id, motif.value)).message; fiche.value = null; motif.value = ''; await charger() } catch (e) { error.value = e.message } finally { loading.value = false } }
onMounted(charger)
</script>

<template>
  <div class="app-layout">
    <SidebarMenu
      nom="Direction générale"
      role="Directeur Général"
      codeRole="DG"
      initiales="DG"
      :notifications="nombre"
      :ouvert="menuOuvert"
      @fermer="menuOuvert = false"
    />

    <main class="direction-page">
      <header class="navbar">
        <button class="burger" type="button" @click="menuOuvert = true">☰ Menu</button>
        <div class="header-right"><span class="role">Direction générale</span><button class="bell" @click="charger">🔔 <span>{{ nombre }}</span></button><div class="avatar-me">DG</div></div>
      </header>
      <div class="content"><h1>Demandes à décider</h1><p class="subtitle">Les demandes validées par la Finance attendent votre décision finale.</p>
        <p v-if="message" class="alert success">{{ message }}</p><p v-if="error" class="alert error">{{ error }}</p>
        <section class="layout"><aside class="panel"><h2>Notifications ({{ nombre }})</h2><button v-for="d in demandes" :key="d.id" class="notification" @click="ouvrir(d.id)"><strong>{{ d.poste }}</strong><small>{{ d.departement }} · {{ d.nombre_demande }} personne(s)</small></button><p v-if="!demandes.length" class="empty">Aucune demande en attente.</p></aside>
          <section class="fiche"><p v-if="loading" class="empty">Chargement...</p><p v-else-if="!fiche" class="empty">Sélectionnez une notification.</p><template v-else><div class="fiche-header"><h2>Décision de la Direction</h2><span class="status">À décider</span></div><div class="calculs"><div><small>Poste</small><strong>{{ fiche.demande.poste }}</strong></div><div><small>Département</small><strong>{{ fiche.demande.departement }}</strong></div><div><small>Montant demandé</small><strong>{{ fiche.montant }}</strong></div><div><small>Budget disponible</small><strong>{{ fiche.disponible }}</strong></div></div><label for="motif">Motif obligatoire en cas de refus</label><textarea id="motif" v-model="motif" rows="4" placeholder="Saisissez le motif de refus" /><div class="actions"><button class="btn" :disabled="loading" @click="valider">Valider définitivement</button><button class="btn cancel" :disabled="loading" @click="refuser">Refuser</button></div></template></section>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
/* Structure globale : sidebar + page */
.app-layout { display: grid; grid-template-columns: 260px minmax(0, 1fr); min-height: 100vh; background: #f1f3f6; }

.direction-page { min-height: 100vh; background: #f1f3f6; color: #222; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; letter-spacing: .01em; }
/* La barre du haut n'affiche plus la marque (elle est dans la sidebar) */
.navbar { display:flex;align-items:center;justify-content:flex-end;padding:10px 20px;background:#fff;box-shadow:rgba(0,0,0,.15) 0 2px 8px }
.burger { display:none;margin-right:auto;padding:8px 16px;border:0;border-radius:20px;background:#fff;color:teal;font-family:inherit;font-weight:700;cursor:pointer;box-shadow:rgba(0,0,0,.24) 0 3px 8px }
.header-right{display:flex;align-items:center;gap:12px}.role{color:#747474;font-size:13px;font-weight:600}.bell{position:relative;border:0;background:transparent;font-size:22px;cursor:pointer}.bell span{position:absolute;top:-5px;right:-8px;border-radius:20px;background:teal;color:#fff;padding:2px 6px;font-size:11px}.avatar-me{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:teal;color:#fff;font-weight:700}.content{max-width:1100px;margin:auto;padding:34px 20px}.content h1{margin:0 0 7px;font-size:28px;line-height:1.2;letter-spacing:-.03em}.subtitle{margin:0 0 25px;color:#747474;font-size:14px;line-height:1.6}.layout{display:grid;grid-template-columns:330px 1fr;gap:20px}.panel,.fiche{background:#fff;border-radius:10px;box-shadow:rgba(0,0,0,.12) 0 3px 12px}.panel h2{margin:0;padding:18px 20px;border-bottom:1px solid #e5e5e5;font-size:16px;font-weight:750;letter-spacing:-.01em}.notification{display:block;width:100%;padding:15px 20px;border:0;border-bottom:1px solid #e5e5e5;background:#fff;text-align:left;font-family:inherit;cursor:pointer}.notification strong,.notification small{display:block}.notification strong{margin-bottom:6px;font-size:14px;font-weight:700}.notification small{color:#747474;font-size:12px;line-height:1.5}.notification:hover{background:#e6f2f2}.empty{padding:20px;color:#747474;font-size:13px}.fiche{padding:24px}.fiche-header{display:flex;justify-content:space-between;border-bottom:1px solid #e5e5e5}.fiche h2{font-size:20px;font-weight:750;letter-spacing:-.02em}.status{padding:5px 10px;border-radius:20px;background:#e6f2f2;color:#066;font-size:12px}.calculs{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:20px 0}.calculs div{padding:14px;border-radius:8px;background:#f1f3f6}.calculs small,.calculs strong{display:block}.calculs small{color:#747474;font-size:12px}.calculs strong{margin-top:6px;font-size:15px;line-height:1.35}.fiche label{font-size:13px;font-weight:700}.fiche textarea{display:block;width:100%;margin:8px 0;padding:12px;border:1px solid #c0c0c0;border-radius:8px;font:inherit}.actions{display:flex;gap:10px;margin-top:16px}.btn{padding:11px 22px;border:0;border-radius:20px;background:teal;color:#fff;font-family:inherit;font-size:13px;font-weight:700;cursor:pointer}.btn:disabled{opacity:.45}.cancel{background:#b42318}.alert{padding:12px;border-radius:8px}.success{color:#137333;background:#e8f5e9}.error{color:#b42318;background:#fdecec}

@media (max-width: 960px) {
  .app-layout { grid-template-columns: 1fr; }
  .burger { display: block; }
}

@media (max-width: 720px) {
  .layout{grid-template-columns:1fr}.role{display:none}.calculs{grid-template-columns:1fr}
}
</style>