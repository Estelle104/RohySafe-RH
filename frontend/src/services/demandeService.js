const API = '/api/demandes'

async function lireReponse(response) {
  const texte = await response.text()
  try {
    return JSON.parse(texte)
  } catch {
    throw new Error(`Réponse serveur invalide (HTTP ${response.status}). Consultez le journal backend.`)
  }
}

// Récupérer les postes et les types de contrat disponibles.
export async function obtenirOptions() {
  const response = await fetch(`${API}/options`, {
    credentials: 'include',
  })

  const result = await lireReponse(response)

  if (!response.ok) {
    throw new Error(
      result.messages?.error || Object.values(result.messages || {}).flat().join(' ') || 'Chargement des options impossible.'
    )
  }

  return result
}

// Enregistrer une nouvelle demande de recrutement.
export async function creerDemande(donnees) {
  const response = await fetch(API, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(donnees),
  })

  const result = await lireReponse(response)

  if (!response.ok) {
    const messages = Object.values(result.messages || {})
      .flat()
      .join(' ')

    throw new Error(
      result.messages?.error ||
      messages ||
      'Enregistrement impossible.'
    )
  }

  return result
}
