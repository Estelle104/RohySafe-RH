const BASE = '/api/rh/conges'

async function request(url, options = {}) {
  const response = await fetch(url, {
    credentials: 'include',
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    ...options,
  })
  const body = await response.json().catch(() => ({}))
  if (!response.ok) throw new Error(body.message || 'Une erreur est survenue.')
  return body
}

export const getDemandesConge = ({ statut, idEmploye, periodeDebut, periodeFin } = {}) => {
  const p = new URLSearchParams()
  if (statut) p.set('statut', statut)
  if (idEmploye) p.set('id_employe', idEmploye)
  if (periodeDebut) p.set('periode_debut', periodeDebut)
  if (periodeFin) p.set('periode_fin', periodeFin)
  const q = p.toString()
  return request(`${BASE}/demandes${q ? `?${q}` : ''}`)
}
const idOf = (id) => (id && typeof id === 'object' ? (id.id ?? id.id_conge) : id)
export const getDemandeConge = (id) => request(`${BASE}/demandes/${idOf(id)}`)
export const validerDemandeConge = (id) => request(`${BASE}/demandes/${idOf(id)}/validation`, { method: 'POST' }) // accepte/valide (tâche 6)
export const accepterDemandeConge = validerDemandeConge
export const refuserDemandeConge = (id, motif) => request(`${BASE}/demandes/${idOf(id)}/refus`, { method: 'POST', body: JSON.stringify({ motif }) })
export const annulerDemandeConge = (id, motif) => request(`${BASE}/demandes/${idOf(id)}/annulation`, { method: 'POST', body: JSON.stringify({ motif }) })
export const getCongesPrevus = ({ periodeDebut, periodeFin } = {}) => {
  const p = new URLSearchParams()
  if (periodeDebut) p.set('periode_debut', periodeDebut)
  if (periodeFin) p.set('periode_fin', periodeFin)
  const q = p.toString()
  return request(`${BASE}/conges-prevus${q ? `?${q}` : ''}`)
}
export const getEmployesEligiblesConges = (idTypeConge) => request(`${BASE}/employes-eligibles${idTypeConge ? `?id_type_conge=${idTypeConge}` : ''}`)
