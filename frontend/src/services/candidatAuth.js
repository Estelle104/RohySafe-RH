const CLE_STOCKAGE = 'rohysafe_candidat'

export function getCandidatConnecte() {
  try {
    const valeur = localStorage.getItem(CLE_STOCKAGE)
    if (!valeur) {
      return null
    }

    const candidat = JSON.parse(valeur)
    return Number(candidat?.id) > 0 ? candidat : null
  } catch {
    return null
  }
}

export function estCandidatConnecte() {
  return getCandidatConnecte() !== null
}

export function enregistrerCandidat(candidat) {
  localStorage.setItem(CLE_STOCKAGE, JSON.stringify(candidat))
  window.dispatchEvent(new Event('candidat-auth-change'))
}

export function deconnecterCandidat() {
  localStorage.removeItem(CLE_STOCKAGE)
  window.dispatchEvent(new Event('candidat-auth-change'))
}

export async function inscrireCandidat(donnees) {
  const reponse = await fetch('/api/candidats/inscription', {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(donnees),
  })

  const resultat = await reponse.json()
  if (!reponse.ok) {
    throw new Error(resultat.message || `Erreur d’inscription (${reponse.status})`)
  }

  enregistrerCandidat(resultat.candidat)
  return resultat
}

export async function connecterCandidat(identifiant, motDePasse) {
  const reponse = await fetch('/api/candidats/login', {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      identifiant,
      mot_de_passe: motDePasse,
    }),
  })

  const resultat = await reponse.json()
  if (!reponse.ok) {
    throw new Error(resultat.message || `Erreur de connexion (${reponse.status})`)
  }

  enregistrerCandidat(resultat.candidat)
  return resultat
}
