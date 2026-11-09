const API = '/api/rh/temps-travail'

export const getTempsTravail = ({ idEmploye, dateDebut, dateFin } = {}) => {
  const params = new URLSearchParams()
  if (idEmploye) params.set('id_employe', idEmploye)
  if (dateDebut) params.set('date_debut', dateDebut)
  if (dateFin) params.set('date_fin', dateFin)
  return fetch(`${API}${params.toString() ? `?${params}` : ''}`, { credentials: 'include', headers: { Accept: 'application/json' } })
    .then(async (response) => { const body = await response.json(); if (!response.ok) throw new Error(body.message || 'Une erreur est survenue.'); return body })
}

export const recalculerTempsTravail = ({ idEmploye, dateDebut, dateFin } = {}) => {
  const params = new URLSearchParams()
  if (idEmploye) params.set('id_employe', idEmploye)
  if (dateDebut) params.set('date_debut', dateDebut)
  if (dateFin) params.set('date_fin', dateFin)
  return fetch(`${API}/recalculer${params.toString() ? `?${params}` : ''}`, { method: 'POST', credentials: 'include', headers: { Accept: 'application/json' } }).then(async (response) => { const body = await response.json(); if (!response.ok) throw new Error(body.message || 'Une erreur est survenue.'); return body })
}
