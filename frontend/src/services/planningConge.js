import { getSoldes } from './soldeConge'

/** Retourne les employés dont le solde couvre la durée demandée. */
export async function trouverEmployesEligibles({ dateDebut, dateFin } = {}) {
  const jours = Math.max(0, Math.floor((new Date(dateFin) - new Date(dateDebut)) / 86400000) + 1)
  if (!dateDebut || !dateFin || jours <= 0) return []
  const { soldes = [] } = await getSoldes({ periodeDebut: dateDebut, periodeFin: dateFin })
  return soldes.filter((solde) => Number(solde.solde_restant) >= jours)
}

/** Classe les remplaçants disponibles par compatibilité de poste puis solde. */
export function proposerRemplacants(employes, indisponibles = []) {
  const ids = new Set(indisponibles.map(Number))
  return [...employes]
    .filter((employe) => !ids.has(Number(employe.id)))
    .sort((a, b) => Number(b.compatibilite || 0) - Number(a.compatibilite || 0))
}
