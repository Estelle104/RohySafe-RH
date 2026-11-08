async function lireReponse(reponse, message) {
  const contenu = await reponse.json().catch(() => null)

  if (!reponse.ok) {
    const erreur = contenu?.message || contenu?.error || message
    throw new Error(erreur)
  }

  return contenu?.data ?? contenu
}

export function normaliserEmploye(item) {
  return {
    id: Number(item?.id ?? 0),
    matricule: item?.matricule ?? '',
    nom: item?.nom ?? '',
    prenom: item?.prenom ?? '',
    nomComplet: item?.nom_complet ?? item?.nomComplet ?? `${item?.prenom ?? ''} ${item?.nom ?? ''}`.trim(),
  }
}

export async function getEmployesHeuresSupplementaires() {
  const reponse = await fetch('/api/heures-supplementaires/employes', {
    method: 'GET',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  })

  const donnees = await lireReponse(reponse, 'Erreur lors du chargement des employés.')
  return Array.isArray(donnees) ? donnees.map(normaliserEmploye) : []
}

export async function createHeureSupplementaire(payload) {
  const reponse = await fetch('/api/heures-supplementaires', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
    body: JSON.stringify(payload),
  })

  return lireReponse(reponse, 'Erreur lors de la déclaration des heures supplémentaires.')
}

export async function getHeuresSupplementaires() {
  const reponse = await fetch('/api/heures-supplementaires', {
    method: 'GET',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  })

  const donnees = await lireReponse(reponse, 'Erreur lors du chargement des demandes.')
  return Array.isArray(donnees) ? donnees : []
}

export async function validerHeureSupplementaire(id) {
  const reponse = await fetch(`/api/heures-supplementaires/${id}/validation`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  })

  return lireReponse(reponse, 'Erreur lors de la validation de la demande.')
}
