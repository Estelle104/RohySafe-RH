async function lireReponse(reponse, message) {
  const contenu = await reponse.json().catch(() => null)

  if (!reponse.ok) {
    const erreur = contenu?.message || contenu?.error || message
    throw new Error(erreur)
  }

  return contenu?.data ?? contenu
}

export function normaliserHoraire(item) {
  const debut = item?.heure_debut ?? item?.heureDebut ?? '00:00'
  const fin = item?.heure_fin ?? item?.heureFin ?? '00:00'
  const volume = Number(item?.heures_hebdo ?? item?.volumeHoraire ?? 0)
  const pause = Number(item?.pause_minutes ?? item?.pause ?? 0)

  return {
    id: item?.id,
    poste: item?.poste ?? item?.libelle ?? 'Poste',
    regime: item?.regime ?? 'Régime',
    heureDebut: debut,
    heureFin: fin,
    pause: Number.isFinite(pause) ? pause : 0,
    volumeHoraire: Number.isFinite(volume) ? `${volume.toFixed(1)}h` : '0.0h',
    jours: Array.isArray(item?.jours) ? item.jours : [],
  }
}

export function normaliserRegime(item) {
  return {
    id: item?.id,
    libelle: item?.libelle ?? 'Régime',
    heuresHebdo: Number(item?.heures_hebdo ?? item?.heuresHebdo ?? 0),
    seuilHsJour: Number(item?.seuil_hs_jour ?? item?.seuilHsJour ?? 0),
    seuilHsSemaine: Number(item?.seuil_hs_semaine ?? item?.seuilHsSemaine ?? 0),
    toleranceRetardMax: Number(item?.tolerance_retard_max ?? item?.toleranceRetardMax ?? 0),
  }
}

export function calculerTotalHeures(horaires) {
  if (!Array.isArray(horaires)) return 0

  return horaires.reduce((total, item) => {
    const valeur = Number(item?.volumeHoraire?.replace('h', '') ?? item?.heures_hebdo ?? item?.heuresHebdo ?? 0)
    return total + (Number.isFinite(valeur) ? valeur : 0)
  }, 0)
}

export async function getHoraires() {
  const reponse = await fetch('/api/horaires', {
    method: 'GET',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  })

  const donnees = await lireReponse(reponse, 'Erreur lors du chargement des horaires.')
  return Array.isArray(donnees) ? donnees.map(normaliserHoraire) : []
}

export async function getHoraireParPoste(idPoste) {
  if (!idPoste) {
    throw new Error('L’identifiant du poste est requis.')
  }

  const reponse = await fetch(`/api/horaires/postes/${idPoste}`, {
    method: 'GET',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  })

  const donnees = await lireReponse(
    reponse,
    'Erreur lors du chargement de l’horaire du poste.',
  )

  return Array.isArray(donnees) ? donnees.map(normaliserHoraire) : []
}

export async function getPlanningAVenirParPoste(idPoste, dateDebut = null, dateFin = null) {
  if (!idPoste) {
    throw new Error('L’identifiant du poste est requis.')
  }

  const params = new URLSearchParams()

  if (dateDebut) {
    params.set('dateDebut', dateDebut)
  }

  if (dateFin) {
    params.set('dateFin', dateFin)
  }

  const suffixe = params.toString() ? `?${params.toString()}` : ''

  const reponse = await fetch(`/api/horaires/postes/${idPoste}/planning-avenir${suffixe}`, {
    method: 'GET',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  })

  const donnees = await lireReponse(
    reponse,
    'Erreur lors du chargement du planning à venir.',
  )

  return Array.isArray(donnees) ? donnees : []
}

export async function getRegimesTravail() {
  const reponse = await fetch('/api/regimes-travail', {
    method: 'GET',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  })

  const donnees = await lireReponse(reponse, 'Erreur lors du chargement des régimes de travail.')
  return Array.isArray(donnees) ? donnees.map(normaliserRegime) : []
}

export async function createHoraire(payload) {
  const reponse = await fetch('/api/horaires', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  })

  return lireReponse(reponse, 'Erreur lors de la création de l’horaire.')
}

export async function updateHoraire(id, payload) {
  const reponse = await fetch(`/api/horaires/${id}`, {
    method: 'PUT',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  })

  return lireReponse(reponse, 'Erreur lors de la modification de l’horaire.')
}

export async function deleteHoraire(id) {
  const reponse = await fetch(`/api/horaires/${id}`, {
    method: 'DELETE',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  })

  return lireReponse(reponse, 'Erreur lors de la suppression de l’horaire.')
}

export async function createRegime(payload) {
  const reponse = await fetch('/api/regimes-travail', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  })

  return lireReponse(reponse, 'Erreur lors de la création du régime.')
}

export async function updateRegime(id, payload) {
  const reponse = await fetch(`/api/regimes-travail/${id}`, {
    method: 'PUT',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  })

  return lireReponse(reponse, 'Erreur lors de la modification du régime.')
}

export async function deleteRegime(id) {
  const reponse = await fetch(`/api/regimes-travail/${id}`, {
    method: 'DELETE',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  })

  return lireReponse(reponse, 'Erreur lors de la suppression du régime.')
}
