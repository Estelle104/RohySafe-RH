const API = '/api/rh/conges/soldes'
const DROITS_MENSUELS = 2.5
const PLAFOND_CUMUL = 90

async function request(url, options = {}) {
  const response = await fetch(url, {
    credentials: 'include',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    ...options,
  })
  const body = await response.json()
  if (!response.ok) throw new Error(body.message || 'Une erreur est survenue.')
  return body
}

/** Récupère les soldes, éventuellement filtrés par employé et période. */
export const getSoldes = ({ idEmploye, periodeDebut, periodeFin } = {}) => {
  const params = new URLSearchParams()
  if (idEmploye) params.set('id_employe', idEmploye)
  if (periodeDebut) params.set('periode_debut', periodeDebut)
  if (periodeFin) params.set('periode_fin', periodeFin)
  const query = params.toString()
  return request(query ? `${API}?${query}` : API)
}

export const recalculerSoldes = () => request(`${API}/recalculer`, { method: 'POST' })

/** Calcule le nombre de mois entiers compris dans une période d'acquisition. */
export function calculerMoisAcquisition(periodeDebut, periodeFin) {
  const debut = new Date(`${periodeDebut}T00:00:00`)
  const fin = new Date(`${periodeFin}T00:00:00`)
  if (Number.isNaN(debut.getTime()) || Number.isNaN(fin.getTime()) || fin < debut) return 0

  return Math.max(
    0,
    (fin.getFullYear() - debut.getFullYear()) * 12
      + fin.getMonth() - debut.getMonth() + 1,
  )
}

/** Calcule les droits acquis, les jours consommés et le solde disponible. */
export function calculerSolde({ periodeDebut, periodeFin, joursPris = 0, droitsMensuels = DROITS_MENSUELS }) {
  const moisAcquis = calculerMoisAcquisition(periodeDebut, periodeFin)
  const droitsAcquis = Math.min(PLAFOND_CUMUL, Math.max(0, moisAcquis * Number(droitsMensuels)))
  const joursConsommes = Math.max(0, Number(joursPris) || 0)


  return {
    droitsAcquis,
    joursConsommes,
    soldeDisponible: Math.min(PLAFOND_CUMUL, Math.max(0, droitsAcquis - joursConsommes)),
  }
}
