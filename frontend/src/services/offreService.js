const API_URL = '/api/offres'

export async function getOffres() {
  const reponse = await fetch(API_URL)

  if (!reponse.ok) {
    throw new Error(`Erreur lors du chargement des offres (${reponse.status})`)
  }

  return reponse.json()
}
