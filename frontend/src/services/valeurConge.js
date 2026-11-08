const API = '/api/rh/conges/valeurs'

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

export const getValeursConge = ({ idEmploye, periodeDebut, periodeFin } = {}) => {
  const params = new URLSearchParams()
  if (idEmploye) params.set('id_employe', idEmploye)
  if (periodeDebut) params.set('periode_debut', periodeDebut)
  if (periodeFin) params.set('periode_fin', periodeFin)
  const query = params.toString()
  return request(query ? `${API}?${query}` : API)
}

export function calculerValeurConge(salaireBrut, joursAcquis) {
  return Math.round((Number(salaireBrut || 0) * Number(joursAcquis || 0) / 30) * 100) / 100
}

export function calculerIndemniteDepart(salaireBrut, soldeRestant) {
  return calculerValeurConge(salaireBrut, soldeRestant)
}
