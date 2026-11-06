const API = '/api/finance'

async function request(url, options = {}) {
  const response = await fetch(url, {
    headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
    ...options,
  })
  const body = await response.json()
  if (!response.ok || !body.success) throw new Error(body.message || 'Une erreur est survenue.')
  return body
}

export const getFinanceNotifications = () => request(`${API}/notifications`)
export const getFinanceFiche = (id) => request(`${API}/demandes/${id}`)
export const validerFinance = (id) => request(`${API}/demandes/${id}/validation`, { method: 'POST' })
export const annulerFinance = (id, motif) => request(`${API}/demandes/${id}/annulation`, {
  method: 'POST',
  body: JSON.stringify({ motif }),
})
