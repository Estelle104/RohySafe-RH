const API = '/api/rh/relations-hierarchiques'

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

export const getEmployes = () => request(`${API}/employes`)
export const getRelations = (actives = false) => request(`${API}?actives=${actives}`)
export const getOrganigramme = () => request(`${API}/organigramme`)
export const createRelation = (data) => request(API, { method: 'POST', body: JSON.stringify(data) })
export const closeRelation = (id, date_fin) => request(`${API}/${id}/cloture`, { method: 'POST', body: JSON.stringify({ date_fin }) })
