const API = '/api/rh/conges/configurations'

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

export const getConfigurations = () => request(API)

export const updateConfiguration = (id, nombre_jour) => request(`${API}/${id}`, {
  method: 'PUT',
  body: JSON.stringify({ nombre_jour }),
})

/** Vérifie le solde et les dates avant la validation d'une demande. */
export const validerDemandeConge = (demande) => request('/api/rh/conges/valider', {
  method: 'POST',
  body: JSON.stringify(demande),
})
