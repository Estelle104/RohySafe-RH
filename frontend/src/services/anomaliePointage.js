const API = '/api/rh/anomalies-pointage'

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

export const getAnomalies = (filtres = {}) => {
  const params = new URLSearchParams()
  if (filtres.idEmploye) params.set('id_employe', filtres.idEmploye)
  if (filtres.dateDebut) params.set('date_debut', filtres.dateDebut)
  if (filtres.dateFin) params.set('date_fin', filtres.dateFin)
  const query = params.toString()
  return request(query ? `${API}?${query}` : API)
}

export const detecterAnomaliesApi = (filtres = {}) => {
  const params = new URLSearchParams()
  if (filtres.idEmploye) params.set('id_employe', filtres.idEmploye)
  if (filtres.dateDebut) params.set('date_debut', filtres.dateDebut)
  if (filtres.dateFin) params.set('date_fin', filtres.dateFin)
  const query = params.toString()
  return request(`${API}/detecter${query ? `?${query}` : ''}`, { method: 'POST' })
}

/** Détecte les anomalies de séquence dans une liste de pointages triés. */
export function detecterAnomalies(pointages) {
  const anomalies = []
  const groupes = new Map()

  for (const pointage of pointages) {
    const date = String(pointage.date_heure).slice(0, 10)
    const cle = `${pointage.id_employe}-${date}`
    if (!groupes.has(cle)) groupes.set(cle, [])
    groupes.get(cle).push(pointage)
  }

  for (const [cle, journee] of groupes) {
    const tries = [...journee].sort((a, b) => String(a.date_heure).localeCompare(String(b.date_heure)))
    const employe = tries[0]?.id_employe
    const dateJour = String(tries[0]?.date_heure).slice(0, 10)

    for (let i = 1; i < tries.length; i += 1) {
      if (tries[i].type_pointage === tries[i - 1].type_pointage) {
        anomalies.push({ id_employe: employe, date_jour: dateJour, type: 'DOUBLON', id_pointage: tries[i].id })
      }
    }

    const types = tries.map((pointage) => pointage.type_pointage)
    if (!types.includes('ENTREE')) anomalies.push({ id_employe: employe, date_jour: dateJour, type: 'ENTREE_MANQUANTE' })
    if (!types.includes('SORTIE')) anomalies.push({ id_employe: employe, date_jour: dateJour, type: 'SORTIE_MANQUANTE' })

    for (let i = 1; i < types.length; i += 1) {
      if (types[i] === types[i - 1] || (types[i] === 'SORTIE' && types[i - 1] !== 'ENTREE' && types[i - 1] !== 'PAUSE_FIN') || (types[i] === 'PAUSE_FIN' && types[i - 1] !== 'PAUSE_DEBUT')) {
        anomalies.push({ id_employe: employe, date_jour: dateJour, type: 'ECART_EXCESSIF', id_pointage: tries[i].id })
      }
    }
  }

  return anomalies
}

export const traiterAnomalie = (id, data) => request(`${API}/${id}`, { method: 'PATCH', body: JSON.stringify(data) })
