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

export async function getCvCandidat(idCandidat) {
  const reponse = await fetch(`/api/candidats/${idCandidat}/cv`)

  if (!reponse.ok) {
    throw new Error(`Erreur lors du chargement du CV (${reponse.status})`)
  }

  return reponse.json()
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
