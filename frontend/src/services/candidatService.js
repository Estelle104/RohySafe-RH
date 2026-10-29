export async function getCandidatsIdeauxByOffre(idOffre) {
  const reponse = await fetch(`/api/offres/${idOffre}/candidats-ideaux`)

  if (!reponse.ok) {
    throw new Error(`Erreur lors du chargement des candidats (${reponse.status})`)
  }

  return reponse.json()
}

export async function getCandidatsIdeauxByDemande(idDemande) {
  const reponse = await fetch(`/api/demandes/${idDemande}/candidats-ideaux`)

  if (!reponse.ok) {
    throw new Error(`Erreur lors du chargement des candidats (${reponse.status})`)
  }

  return reponse.json()
}

export async function getCvCandidat(idCandidat, idOffre = null) {
  const url = idOffre
    ? `/api/candidats/${idCandidat}/cv?offre=${idOffre}`
    : `/api/candidats/${idCandidat}/cv`
  const reponse = await fetch(url)

  if (!reponse.ok) {
    throw new Error(`Erreur lors du chargement du CV (${reponse.status})`)
  }

  return reponse.json()
}

export async function getCvCandidature(idCandidat, idOffre) {
  const reponse = await fetch(`/api/candidats/${idCandidat}/offres/${idOffre}/cv`)

  const resultat = await reponse.json()
  if (!reponse.ok) {
    throw new Error(resultat.message || `Erreur lors du chargement (${reponse.status})`)
  }

  return resultat
}

export async function enregistrerCvCandidature(idCandidat, idOffre, donnees) {
  const reponse = await fetch(`/api/candidats/${idCandidat}/offres/${idOffre}/cv`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(donnees),
  })

  const resultat = await reponse.json()
  if (!reponse.ok) {
    throw new Error(resultat.message || `Erreur lors de l’enregistrement (${reponse.status})`)
  }

  return resultat
}

export async function creerProposition(idCandidature) {
  const reponse = await fetch(`/api/candidatures/${idCandidature}/proposition`, {
    method: 'POST',
    headers: { Accept: 'application/json' },
  })

  const resultat = await reponse.json()
  if (!reponse.ok) {
    throw new Error(resultat.message || `Erreur lors de la création (${reponse.status})`)
  }

  return resultat
}
