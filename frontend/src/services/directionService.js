const API = '/api/direction-generale'

async function request(url, options = {}) {
  const response = await fetch(url, { headers: { 'Content-Type': 'application/json' }, ...options })
  const body = await response.json()
  if (!response.ok || !body.success) throw new Error(body.message || 'Une erreur est survenue.')
  return body
}

export const getDirectionNotifications = () => request(`${API}/notifications`)
export const getDirectionFiche = (id) => request(`${API}/demandes/${id}`)
export const validerDirection = (id) => request(`${API}/demandes/${id}/validation`, { method: 'POST' })
export const refuserDirection = (id, motif) => request(`${API}/demandes/${id}/refus`, { method: 'POST', body: JSON.stringify({ motif }) })
