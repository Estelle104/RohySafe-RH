async function lireReponse(reponse, messageDefaut) {
  let resultat = null

  try {
    resultat = await reponse.json()
  } catch {
    resultat = null
  }

  if (!reponse.ok) {
    throw new Error(resultat?.message || resultat?.messages?.error || `${messageDefaut} (${reponse.status})`)
  }

  return resultat
}

export async function creerEntretien(idCandidature, donnees) {
  const reponse = await fetch(`/api/candidatures/${idCandidature}/entretiens`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(donnees),
  })

  return lireReponse(reponse, 'Erreur lors de la création de l’entretien')
}

export async function getOffresEntretiens() {
  const reponse = await fetch('/api/entretiens/offres', {
    headers: { Accept: 'application/json' },
  })

  return lireReponse(reponse, 'Erreur lors du chargement des offres')
}

export async function getCandidatsEntretiens(idOffre) {
  const reponse = await fetch(`/api/entretiens/offres/${idOffre}/candidats`, {
    headers: { Accept: 'application/json' },
  })

  return lireReponse(reponse, 'Erreur lors du chargement des candidats')
}

export async function getDetailEntretien(idCandidature) {
  const reponse = await fetch(`/api/entretiens/candidatures/${idCandidature}`, {
    headers: { Accept: 'application/json' },
  })

  return lireReponse(reponse, 'Erreur lors du chargement de l’entretien')
}

export async function enregistrerEvaluation(idCandidature, donnees) {
  const reponse = await fetch(`/api/entretiens/candidatures/${idCandidature}/evaluation`, {
    method: 'PUT',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(donnees),
  })

  return lireReponse(reponse, 'Erreur lors de l’enregistrement de l’évaluation')
}
