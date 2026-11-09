const API = '/api/rh/conges/remplacements'

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

export function calculerRemuneration(heures, salaireHoraire, majoration = 1) {
  return Math.round(Number(heures || 0) * Number(salaireHoraire || 0) * Number(majoration || 1) * 100) / 100
}
