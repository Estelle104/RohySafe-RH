const API = '/api/rh/conges/demande'

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

export const getTypesConge = () => request(`${API}/types`)
export const getEmployesConge = () => request(`${API}/employes`)
export const creerDemandeConge = (data) => request(API, {
  method: 'POST',
  body: JSON.stringify(data),
})
