const API_URL = '/api/backoffice/offres'

async function envoyer(url, options = {}) {
  const reponse = await fetch(url, {
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...options.headers,
    },
  })

  const resultat = await reponse.json()
  if (!reponse.ok) {
    throw new Error(resultat.message || `Erreur lors de l’opération (${reponse.status})`)
  }

  return resultat
}

export function getOffresBackoffice() {
  return envoyer(API_URL)
}

export function getExigencesOffre(idOffre) {
  return envoyer(`${API_URL}/${idOffre}/exigences`)
}

export function enregistrerExigenceExperience(idOffre, donnees) {
  return envoyer(`${API_URL}/${idOffre}/exigences/experience`, {
    method: 'POST',
    body: JSON.stringify(donnees),
  })
}

export function supprimerExigenceExperience(idOffre, idExigence) {
  return envoyer(`${API_URL}/${idOffre}/exigences/experience/${idExigence}`, {
    method: 'DELETE',
  })
}

export function enregistrerCompetenceRequise(idOffre, donnees) {
  return envoyer(`${API_URL}/${idOffre}/exigences/competence`, {
    method: 'POST',
    body: JSON.stringify(donnees),
  })
}

export function supprimerCompetenceRequise(idOffre, idCritere) {
  return envoyer(`${API_URL}/${idOffre}/exigences/competence/${idCritere}`, {
    method: 'DELETE',
  })
}
