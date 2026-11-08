const API = '/api/rh/conges/remplacements'

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

export const getRemplacements = ({ idEmploye, periodeDebut, periodeFin } = {}) => {
  const params = new URLSearchParams()
  if (idEmploye) params.set('id_employe', idEmploye)
  if (periodeDebut) params.set('periode_debut', periodeDebut)
  if (periodeFin) params.set('periode_fin', periodeFin)
  const query = params.toString()
  return request(query ? `${API}?${query}` : API)
}

export const creerRemplacement = (remplacement) => request(API, {
  method: 'POST',
  body: JSON.stringify(remplacement),
})

export const affecterRemplacant = (data) => request(`${API}/affecter`, { method: 'POST', body: JSON.stringify(data) })

export const getEmployesDisponiblesRemplacement = ({ dateDebut, dateFin, idEmployeAbsent, idConge } = {}) => {
  const p = new URLSearchParams()
  if (dateDebut) p.set('date_debut', dateDebut)
  if (dateFin) p.set('date_fin', dateFin)
  if (idEmployeAbsent) p.set('id_employe_absent', idEmployeAbsent)
  if (idConge) p.set('id_conge', idConge)
  const q = p.toString()
  return request(`${API}/disponibles${q ? `?${q}` : ''}`)
}

export const suggererRemplacants = ({ idEmployeAbsent, dateDebut, dateFin } = {}) => {
  const p = new URLSearchParams()
  if (idEmployeAbsent) p.set('id_employe_absent', idEmployeAbsent)
  if (dateDebut) p.set('date_debut', dateDebut)
  if (dateFin) p.set('date_fin', dateFin)
  const q = p.toString()
  return request(`${API}/suggerer${q ? `?${q}` : ''}`)
}

export function calculerRemuneration(heures, salaireHoraire, majoration = 1) {
  return Math.round(Number(heures || 0) * Number(salaireHoraire || 0) * Number(majoration || 1) * 100) / 100
}
