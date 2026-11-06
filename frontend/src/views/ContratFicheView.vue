
<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const d = ref(null)
const erreur = ref('')
const cours = ref(false)
const succes = ref('')

const date = (v) => {
  if (!v) return 'Non renseignée'

  const valeur = String(v).slice(0, 10)
  const [annee, mois, jour] = valeur.split('-')

  if (annee && mois && jour) {
    return `${jour}/${mois}/${annee}`
  }

  return new Date(v).toLocaleDateString('fr-FR')
}

const argent = (v) =>
  `${Math.round(Number(v || 0)).toLocaleString('fr-FR')} Ar`

onMounted(async () => {
  try {
    const r = await fetch(
      `/api/candidatures/${route.params.id}/contrat`
    )

    if (!r.ok) throw new Error()

    d.value = await r.json()
  } catch {
    erreur.value = 'Impossible de préparer ce contrat.'
  }
})

async function generer() {
  erreur.value = ''
  cours.value = true

  try {
    const r = await fetch(
      `/api/candidatures/${route.params.id}/contrat`,
      { method: 'POST' }
    )

    const o = await r.json()

    if (!r.ok) {
      throw new Error(o.message || 'Impossible de générer le contrat.')
    }

    succes.value = 'Contrat généré avec le statut « créé ».'
  } catch (e) {
    erreur.value = e.message || 'Une erreur est survenue.'
  } finally {
    cours.value = false
  }
}
</script>

<template>
  <main class="page">
    <div class="barre-actions">
      <button class="retour" @click="router.back()">← Retour</button>
      <button class="imprimer" @click="window.print()">Imprimer / PDF</button>
    </div>

    <p v-if="erreur" class="message-erreur">{{ erreur }}</p>
    <p v-if="succes" class="message-succes">{{ succes }}</p>
    <p v-if="!d && !erreur" class="chargement">Chargement du contrat…</p>

    <template v-if="d">
      <article class="document">
        <header class="entete">
          <div>
            <p class="entreprise">RohySafe RH</p>
            <p class="adresse">Antananarivo, Madagascar</p>
          </div>

          <div class="date-document">
            <p>Antananarivo, le {{ date(new Date()) }}</p>
            <p v-if="d.id_contrat || d.id">
              Référence : CDT-{{ d.id_contrat || d.id }}
            </p>
          </div>
        </header>

        <div class="titre-document">
          <h1>CONTRAT DE TRAVAIL</h1>
          <p>Poste : <strong>{{ d.poste || 'Non renseigné' }}</strong></p>
        </div>

        <p class="introduction">
          Entre les soussignés, les parties conviennent des conditions
          de travail définies dans le présent contrat.
        </p>

        <section>
          <h2>Article 1 — Identification des parties</h2>

          <p>
            <strong>L'employeur :</strong> RohySafe RH, sise à
            Antananarivo, Madagascar, représentée par la Direction générale,
            ci-après dénommée « l'Employeur ».
          </p>

          <p>
            <strong>Le salarié :</strong>
            {{ d.prenom }} {{ d.nom }},
            domicilié(e) à {{ d.ville || 'adresse non renseignée' }},
            matricule {{ d.matricule || 'à attribuer' }},
            ci-après dénommé(e) « le Salarié ».
          </p>

          <p>Les parties conviennent de ce qui suit.</p>
        </section>

        <section>
          <h2>Article 2 — Objet et fonctions</h2>

          <p>
            Le Salarié est engagé en qualité de
            <strong>{{ d.poste || 'poste à préciser' }}</strong>,
            au sein du département
            <strong>{{ d.departement || 'à préciser' }}</strong>.
          </p>

          <p>
            Le lieu habituel de travail est fixé à Antananarivo,
            Madagascar. Le Salarié exercera les fonctions correspondant
            à son poste, conformément aux directives de l'Employeur.
          </p>
        </section>

        <section>
          <h2>Article 3 — Durée et prise de fonction</h2>

          <p>
            Le présent contrat est conclu sous la forme d'un
            <strong>{{ d.type_contrat || 'type à préciser' }}</strong>.
          </p>

          <p>
            La date de début du contrat est fixée au
            <strong>{{ date(d.date_debut || d.date_prise_poste) }}</strong>.
            La prise de fonction est prévue le
            <strong>{{ date(d.date_prise_poste) }}</strong>.
          </p>

          <p v-if="d.date_fin">
            Le contrat prend fin le <strong>{{ date(d.date_fin) }}</strong>,
            sous réserve des dispositions légales applicables.
          </p>

          <p v-else>
            La durée du contrat est déterminée conformément au type
            de contrat et aux dispositions légales applicables.
          </p>

          <p>
            La période d'essai, lorsqu'elle est applicable, est fixée
            conformément aux dispositions légales et conventionnelles
            en vigueur.
          </p>
        </section>

        <section>
          <h2>Article 4 — Durée et horaires de travail</h2>

          <p>
            Le temps de travail est fixé à quarante (40) heures
            par semaine, du lundi au vendredi, selon les horaires suivants :
            de 08 h 00 à 12 h 00 et de 13 h 00 à 17 h 00.
          </p>

          <p>
            Toute heure supplémentaire et toute modification des horaires
            sont soumises aux règles applicables au sein de l'entreprise
            et à la législation en vigueur.
          </p>
        </section>

        <section>
          <h2>Article 5 — Rémunération</h2>

          <p>
            En contrepartie de son travail, le Salarié percevra un salaire
            de base mensuel brut de
            <strong>{{ argent(d.salaire_mensuel ?? d.salaire_propose) }}</strong>.
          </p>

          <p>
            Les primes, indemnités, retenues et modalités de paiement,
            lorsqu'elles sont applicables, sont déterminées conformément
            aux dispositions légales et aux accords en vigueur.
          </p>
        </section>

        <section>
          <h2>Article 6 — Obligations des parties</h2>

          <p>
            L'Employeur s'engage à respecter les obligations qui lui
            incombent, notamment en matière de rémunération, de conditions
            de travail et de respect des droits du Salarié.
          </p>

          <p>
            Le Salarié s'engage à accomplir ses fonctions avec diligence,
            à respecter les instructions professionnelles, le règlement
            intérieur et les obligations de confidentialité applicables.
          </p>
        </section>

        <section>
          <h2>Article 7 — Congés et cessation du contrat</h2>

          <p>
            Les congés, le préavis, les conditions de rupture et les
            éventuelles indemnités sont régis par les dispositions légales,
            réglementaires et conventionnelles applicables au présent contrat.
          </p>
        </section>

        <section>
          <h2>Article 8 — Dispositions finales</h2>

          <p>
            Le présent contrat est établi en deux exemplaires originaux,
            dont un est remis à chacune des parties après signature.
            Les parties déclarent avoir pris connaissance de son contenu
            et s'engagent à en respecter les dispositions.
          </p>
        </section>

        <div class="signatures">
          <p class="date-signature">
            Fait à Antananarivo, le {{ date(new Date()) }}.
          </p>

          <div class="blocs-signature">
            <div>
              <p><strong>Pour l'Employeur</strong></p>
              <p>Nom et qualité du représentant</p>
              <div class="espace-signature"></div>
              <p class="mention">Signature et cachet</p>
            </div>

            <div>
              <p><strong>Le Salarié</strong></p>
              <p>{{ d.prenom }} {{ d.nom }}</p>
              <div class="espace-signature"></div>
              <p class="mention">Signature précédée de la mention « Lu et approuvé »</p>
            </div>
          </div>
        </div>

        <footer class="pied-document">
          RohySafe RH — Contrat de travail
        </footer>
      </article>

      <div class="actions-finales">
        <button
          class="generer"
          :disabled="cours || !!succes"
          @click="generer"
        >
          {{ cours ? 'Génération en cours…' : 'Générer le contrat' }}
        </button>
      </div>
    </template>
  </main>
</template>

<style scoped>
.page {
  min-height: 100vh;
  padding: 28px 16px 40px;
  background: #f0f1f2;
  color: #222;
  font-family: Arial, Helvetica, sans-serif;
}

.barre-actions,
.actions-finales {
  width: 100%;
  max-width: 850px;
  margin: 0 auto 18px;
  display: flex;
  justify-content: space-between;
  gap: 12px;
}

.barre-actions button,
.generer {
  padding: 10px 16px;
  border-radius: 4px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.retour,
.imprimer {
  border: 1px solid #d1d5d8;
  background: #fff;
  color: #344b52;
}

.document {
  width: 100%;
  max-width: 850px;
  min-height: 1120px;
  margin: 0 auto;
  padding: 60px 68px 40px;
  background: #fff;
  border: 1px solid #dedede;
  box-shadow: 0 2px 10px rgb(0 0 0 / 5%);
}

.entete {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  padding-bottom: 20px;
  border-bottom: 1px solid #b8c4c7;
}

.entreprise {
  margin: 0;
  color: #225e68;
  font-size: 19px;
  font-weight: 700;
}

.adresse,
.date-document p {
  margin: 7px 0 0;
  color: #666;
  font-size: 11px;
  line-height: 1.6;
}

.date-document {
  text-align: right;
}

.titre-document {
  margin: 35px 0 25px;
  text-align: center;
}

.titre-document h1 {
  margin: 0 0 12px;
  color: #225e68;
  font-family: Georgia, 'Times New Roman', serif;
  font-size: 22px;
  letter-spacing: 0.5px;
}

.titre-document p {
  margin: 0;
  font-size: 12px;
}

.introduction {
  margin: 0 0 26px;
  line-height: 1.8;
  text-align: justify;
}

.document section {
  margin-top: 24px;
  break-inside: avoid;
}

.document h2 {
  margin: 0 0 12px;
  padding-bottom: 7px;
  border-bottom: 1px solid #e2e5e6;
  color: #225e68;
  font-family: Georgia, 'Times New Roman', serif;
  font-size: 14px;
}

.document section p {
  margin: 8px 0;
  font-size: 12px;
  line-height: 1.8;
  text-align: justify;
}

.signatures {
  margin-top: 35px;
  break-inside: avoid;
}

.date-signature {
  margin-bottom: 24px;
  font-size: 12px;
}

.blocs-signature {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 45px;
}

.blocs-signature p {
  margin: 5px 0;
  font-size: 11px;
  line-height: 1.6;
}

.espace-signature {
  height: 75px;
  border-bottom: 1px solid #999;
}

.mention {
  color: #555;
  font-size: 10px !important;
  font-style: italic;
}

.pied-document {
  margin-top: 35px;
  padding-top: 10px;
  border-top: 1px solid #ddd;
  color: #777;
  font-size: 10px;
  text-align: center;
}

.actions-finales {
  justify-content: center;
  margin-top: 20px;
}

.generer {
  border: 0;
  background: #225e68;
  color: #fff;
}

.generer:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}

.message-erreur,
.message-succes,
.chargement {
  max-width: 850px;
  margin: 0 auto 16px;
  padding: 12px;
  font-size: 13px;
}

.message-erreur {
  background: #fff0f0;
  color: #a52828;
}

.message-succes {
  background: #edf8f0;
  color: #22663a;
}

.chargement {
  text-align: center;
}

@media (max-width: 640px) {
  .page {
    padding: 12px 8px;
  }

  .document {
    min-height: auto;
    padding: 28px 20px;
  }

  .entete {
    flex-direction: column;
  }

  .date-document {
    text-align: left;
  }

  .blocs-signature {
    gap: 18px;
  }
}

@media print {
  @page {
    size: A4;
    margin: 18mm 20mm;
  }

  .page {
    padding: 0;
    background: #fff;
  }

  .barre-actions,
  .actions-finales,
  .message-erreur,
  .message-succes,
  .chargement {
    display: none !important;
  }

  .document {
    width: 100%;
    max-width: none;
    min-height: 0;
    margin: 0;
    padding: 0;
    border: 0;
    box-shadow: none;
  }

  .document section,
  .signatures {
    break-inside: avoid;
  }
}
</style>
