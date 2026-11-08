export const JOURS_SEMAINE = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']

export function parseDateLocale(dateValue) {
  if (dateValue instanceof Date) {
    return new Date(dateValue.getTime())
  }

  if (dateValue === null || dateValue === undefined || dateValue === '') {
    return new Date(NaN)
  }

  const valeur = String(dateValue).trim()
  if (!valeur) {
    return new Date(NaN)
  }

  if (/^\d{4}-\d{2}-\d{2}$/.test(valeur)) {
    return new Date(`${valeur}T00:00:00`)
  }

  return new Date(valeur)
}

export function dateKey(dateValue) {
  const date = parseDateLocale(dateValue)
  const annee = date.getFullYear()
  const mois = String(date.getMonth() + 1).padStart(2, '0')
  const jour = String(date.getDate()).padStart(2, '0')
  return `${annee}-${mois}-${jour}`
}

function parseJoursSemaine(valeur) {
  if (Array.isArray(valeur)) return valeur

  if (typeof valeur !== 'string') return []

  const nettoye = valeur.trim()
  if (!nettoye || nettoye === '{}') return []

  const sansAccolades = nettoye.replace(/^[{[]|[}]]$/g, '')
  if (!sansAccolades) return []

  return sansAccolades
    .split(',')
    .map((jour) => Number(String(jour).trim()))
    .filter((jour) => Number.isFinite(jour))
}

export function normaliserPlanning(item) {
  const joursSemaine = parseJoursSemaine(item?.joursSemaine ?? item?.jours_semaine ?? [])

  const employes = typeof item?.employes === 'string'
    ? item.employes.split('|').map((nom) => nom.trim()).filter(Boolean)
    : Array.isArray(item?.employes)
      ? item.employes
      : []

  return {
    id: item?.id,
    libelle: item?.libelle ?? 'Planning',
    dateDebut: item?.dateDebut ?? item?.date_debut ?? null,
    dateFin: item?.dateFin ?? item?.date_fin ?? null,
    joursSemaine: joursSemaine.map((jour) => JOURS_SEMAINE[(Number(jour) - 1)] ?? String(jour)),
    nombreEmployes: Number(item?.nombreEmployes ?? item?.nombre_employes ?? employes.length ?? 0),
    employes,
    periodeLabel: item?.periodeLabel ?? null,
  }
}

export function normaliserEmploye(item) {
  const nomComplet = item?.nom_complet ?? item?.nomComplet ?? (((item?.prenom ?? '') + ' ' + (item?.nom ?? '')).trim() || 'Employé')

  return {
    id: Number(item?.id ?? item?.id_employe ?? 0),
    nomComplet,
    prenom: item?.prenom ?? '',
    nom: item?.nom ?? '',
  }
}

export function formatDateLong(dateValue) {
  return parseDateLocale(dateValue).toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

export function formatDateShort(dateValue) {
  return parseDateLocale(dateValue).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

export function formatDateCompact(dateValue) {
  return parseDateLocale(dateValue).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
  })
}

export function formatDayLabel(dateValue) {
  return parseDateLocale(dateValue).toLocaleDateString('fr-FR', { weekday: 'long' })
}

export function formatWeekRange(dateValue) {
  const debut = dateDebutSemaine(dateValue)
  return `Semaine du ${formatDateShort(debut)}`
}

async function lireReponse(reponse, message) {
  const contenu = await reponse.json().catch(() => null)

  if (!reponse.ok) {
    const erreur = contenu?.message || contenu?.error || message
    throw new Error(erreur)
  }

  return contenu?.data ?? contenu
}

export async function getPlanningsEDT() {
  const reponse = await fetch('/api/plannings-edt', {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })

  const resultat = await lireReponse(reponse, 'Erreur lors du chargement des plannings EDT.')
  const liste = Array.isArray(resultat) ? resultat : Array.isArray(resultat?.data) ? resultat.data : []
  return liste.map(normaliserPlanning)
}

export async function getPlanningEDTById(id) {
  if (!id) {
    throw new Error('L’identifiant du planning est requis.')
  }

  const reponse = await fetch(`/api/plannings-edt/${id}`, {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })

  if (!reponse.ok) {
    const erreur = await reponse.json().catch(() => ({}))
    throw new Error(erreur?.message || 'Erreur lors du chargement du planning.')
  }

  const resultat = await reponse.json().catch(() => ({}))
  return normaliserPlanning(resultat?.data ?? resultat)
}

export async function createPlanningEDT(payload) {
  const reponse = await fetch('/api/plannings-edt', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  })

  if (!reponse.ok) {
    const erreur = await reponse.json().catch(() => ({}))
    throw new Error(erreur?.message || 'Erreur lors de la création du planning.')
  }

  return reponse.json().catch(() => ({}))
}

export async function updatePlanningEDT(id, payload) {
  const reponse = await fetch(`/api/plannings-edt/${id}`, {
    method: 'PUT',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  })

  if (!reponse.ok) {
    const erreur = await reponse.json().catch(() => ({}))
    throw new Error(erreur?.message || 'Erreur lors de la modification du planning.')
  }

  return reponse.json().catch(() => ({}))
}

export async function deletePlanningEDT(id) {
  const reponse = await fetch(`/api/plannings-edt/${id}`, {
    method: 'DELETE',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })

  if (!reponse.ok) {
    const erreur = await reponse.json().catch(() => ({}))
    throw new Error(erreur?.message || 'Erreur lors de la suppression du planning.')
  }

  return reponse.json().catch(() => ({}))
}

export async function getEmployesDisponibles() {
  const reponse = await fetch('/api/plannings-edt/employes', {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })

  if (!reponse.ok) {
    const erreur = await reponse.json().catch(() => ({}))
    throw new Error(erreur?.message || 'Erreur lors du chargement des employés.')
  }

  const resultat = await reponse.json().catch(() => ({}))
  const liste = Array.isArray(resultat?.data) ? resultat.data : []
  return liste.map(normaliserEmploye)
}

export async function getEmployesParPlanning(idPlanning) {
  const reponse = await fetch(`/api/plannings-edt/${idPlanning}/employes`, {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })

  if (!reponse.ok) {
    const erreur = await reponse.json().catch(() => ({}))
    throw new Error(erreur?.message || 'Erreur lors du chargement des employés du planning.')
  }

  const resultat = await reponse.json().catch(() => ({}))
  const liste = Array.isArray(resultat?.data) ? resultat.data : []
  return liste.map(normaliserEmploye)
}

export async function assignerEmployesPlanning(idPlanning, idsEmployes) {
  const reponse = await fetch(`/api/plannings-edt/${idPlanning}/employes`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify({ id_employes: idsEmployes }),
  })

  if (!reponse.ok) {
    const erreur = await reponse.json().catch(() => ({}))
    throw new Error(erreur?.message || 'Erreur lors de l’affectation des employés.')
  }

  return reponse.json().catch(() => ({}))
}

export async function retirerEmployePlanning(idPlanning, idEmploye) {
  const reponse = await fetch(`/api/plannings-edt/${idPlanning}/employes/${idEmploye}`, {
    method: 'DELETE',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  })

  if (!reponse.ok) {
    const erreur = await reponse.json().catch(() => ({}))
    throw new Error(erreur?.message || 'Erreur lors du retrait de l’affectation.')
  }

  return reponse.json().catch(() => ({}))
}

export function ajouterJours(date, nbJours) {
  const copy = new Date(date)
  copy.setDate(copy.getDate() + nbJours)
  return copy
}

export function dateDebutSemaine(date) {
  const d = parseDateLocale(date)
  d.setHours(0, 0, 0, 0)
  const day = (d.getDay() + 6) % 7
  d.setDate(d.getDate() - day)
  return d
}

export function premierJourDuMois(date) {
  return new Date(date.getFullYear(), date.getMonth(), 1)
}

export function dernierJourDuMois(date) {
  return new Date(date.getFullYear(), date.getMonth() + 1, 0)
}

export function estDansPlageDate(date, dateDebut, dateFin) {
  if (!dateDebut || !dateFin) return false

  const d = parseDateLocale(date)
  const debut = parseDateLocale(dateDebut)
  const fin = parseDateLocale(dateFin)
  d.setHours(0, 0, 0, 0)
  debut.setHours(0, 0, 0, 0)
  fin.setHours(23, 59, 59, 999)

  return d >= debut && d <= fin
}

export function idemJour(dateA, dateB) {
  return dateA.getFullYear() === dateB.getFullYear() && dateA.getMonth() === dateB.getMonth() && dateA.getDate() === dateB.getDate()
}

export function jourNom(date) {
  return JOURS_SEMAINE[(parseDateLocale(date).getDay() + 6) % 7]
}

export function labelDateComplete(date) {
  return formatDateLong(date)
}

export function labelMois(date) {
  return parseDateLocale(date).toLocaleDateString('fr-FR', {
    month: 'long',
    year: 'numeric',
  })
}

export function filtrerPlannings(plannings, options = {}) {
  const recherche = String(options.recherche ?? '').trim().toLowerCase()
  const jour = options.jour ?? 'Tous'
  const employe = options.employe ?? 'Tous'

  return (Array.isArray(plannings) ? plannings : []).filter((planning) => {
    const libelle = (planning?.libelle ?? '').toLowerCase()
    const texte = `${libelle} ${Array.isArray(planning?.employes) ? planning.employes.join(' ') : ''} ${planning?.periodeLabel ?? ''}`.toLowerCase()
    const okRecherche = !recherche || texte.includes(recherche)
    const okJour = jour === 'Tous' || (Array.isArray(planning?.joursSemaine) && planning.joursSemaine.includes(jour))
    const okEmploye = employe === 'Tous' || (Array.isArray(planning?.employes) && planning.employes.includes(employe))

    return okRecherche && okJour && okEmploye
  })
}

export function planningEstTermine(dateFin) {
  if (!dateFin) return false

  const fin = parseDateLocale(dateFin)
  const now = new Date()
  fin.setHours(23, 59, 59, 999)
  now.setHours(0, 0, 0, 0)

  return fin < now
}
