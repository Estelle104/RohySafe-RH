let utilisateurCourant
let chargementEnCours = null

export async function chargerUtilisateurInterne(forcer = false) {
  if (!forcer && utilisateurCourant !== undefined) {
    return utilisateurCourant
  }

  if (chargementEnCours !== null) {
    return chargementEnCours
  }

  chargementEnCours = (async () => {
    try {
      const reponse = await fetch('/api/auth/me', {
        headers: { Accept: 'application/json' },
      })

      if (!reponse.ok) {
        utilisateurCourant = null
        return null
      }

      const resultat = await reponse.json()
      utilisateurCourant = resultat?.utilisateur ?? null
      return utilisateurCourant
    } catch {
      utilisateurCourant = null
      return null
    } finally {
      chargementEnCours = null
    }
  })()

  return chargementEnCours
}

export function reinitialiserUtilisateurInterne(utilisateur = null) {
  utilisateurCourant = utilisateur
}

export function peutAccederAuxEntretiens(utilisateur) {
  return ['Manager', 'RH'].includes(utilisateur?.role)
}
