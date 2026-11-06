const API_URL = '/api/offres'

export async function getOffres() {
  const reponse = await fetch(API_URL)

  if (!reponse.ok) {
    throw new Error(`Erreur lors du chargement des offres (${reponse.status})`)
  }

  return reponse.json()
}

export async function getOffreDetail(idOffre) {
  const reponse = await fetch(`/api/offres/${idOffre}`)

  if (!reponse.ok) {
    throw new Error(`Erreur lors du chargement du détail (${reponse.status})`)
  }

  return reponse.json()
}
