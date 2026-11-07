const API_URL = import.meta.env.VITE_API_URL || '/api'

async function handle(res) {
  const json = await res.json().catch(() => ({}))
  if (!res.ok) {
    throw new Error(json.messages?.error || json.message || `Erreur ${res.status}`)
  }
  return json.data ?? json
}

/** Fiche d'un employé : GET /api/rh/employes/:id */
export async function getViewInfoEmp(idEmploye) {
  const res = await fetch(`${API_URL}/rh/employes/${idEmploye}`, { credentials: 'include' })
  return handle(res)
}

/**
 * Met à jour la fiche : POST /api/employes/:id/info
 * `data` = objet simple ; `copie` = File optionnel (justificatif de résidence).
 */
export async function updateInfoEmp(idEmploye, data, copie = null) {
  const form = new FormData()
  Object.entries(data).forEach(([k, v]) => {
    if (v !== null && v !== undefined) form.append(k, v)
  })
  if (copie) form.append('copie', copie)

  const res = await fetch(`${API_URL}/rh/employes/${idEmploye}/info`, {
    method: 'POST',
    credentials: 'include',
    body: form,
  })
  return handle(res)
}

/** Historique : GET /api/employes/:id/historique */
export async function getHistoriqueInfoEmp(idEmploye) {
  const res = await fetch(`${API_URL}/rh/employes/${idEmploye}/historique`, { credentials: 'include' })
  return handle(res)
}

/** Listes des statuts pour les <select> */
export async function getStatuts() {
  const res = await fetch(`${API_URL}/referentiels/statuts`, { credentials: 'include' })
  return handle(res)
}

/** URL d'un justificatif stocké dans public/uploads */
export function urlCopie(chemin) {
  return chemin ? `/${chemin}` : null
}