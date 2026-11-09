const API = '/api/rh/absences'

async function request(url, options = {}) {
  const response = await fetch(url, { credentials: 'include', headers: { Accept: 'application/json', 'Content-Type': 'application/json' }, ...options })
  const body = await response.json()
  if (!response.ok) throw new Error(body.message || 'Une erreur est survenue.')
  return body
}

export const getAbsences = () => request(API)
export const createAbsence = (data) => request(API, { method: 'POST', body: JSON.stringify(data) })
export const modifierStatutAbsence = (id, statut, motif) => request(`${API}/${id}/statut`, { method: 'PATCH', body: JSON.stringify({ statut, motif }) })
