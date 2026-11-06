const API = '/api/rh/pointages'

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

function queryParams({ idEmploye, dateDebut, dateFin } = {}) {
  const params = new URLSearchParams()
  if (idEmploye) params.set('id_employe', idEmploye)
  if (dateDebut) params.set('date_debut', dateDebut)
  if (dateFin) params.set('date_fin', dateFin)
  const query = params.toString()
  return query ? `?${query}` : ''
}

export const getPointages = (filtres = {}) => request(`${API}${queryParams(filtres)}`)
export const createPointage = (data) => request(API, { method: 'POST', body: JSON.stringify(data) })
export const getPointageParEmploye = (idEmploye, filtres = {}) => request(`${API}/employe/${idEmploye}${queryParams(filtres)}`)
