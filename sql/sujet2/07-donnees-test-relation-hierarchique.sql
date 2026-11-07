-- Données de test pour l'écran Relations hiérarchiques et l'organigramme.
-- Prérequis : les tables candidats, employes et relation_hierarchiques existent.
BEGIN;

INSERT INTO candidats (nom, prenom, email, telephone, ville, login, mdp)
VALUES
    ('Test', 'Supérieur', 'test.superieur@rohysafe.local', '0300000001', 'Antananarivo', 'test.superieur', 'test123'),
    ('Test', 'Subordonné', 'test.subordonne@rohysafe.local', '0300000002', 'Antananarivo', 'test.subordonne', 'test123')
ON CONFLICT (email) DO UPDATE
SET nom = EXCLUDED.nom,
    prenom = EXCLUDED.prenom,
    telephone = EXCLUDED.telephone,
    ville = EXCLUDED.ville,
    login = EXCLUDED.login,
    mdp = EXCLUDED.mdp;

INSERT INTO employes (id_candidat, matricule, date_embauche, id_statut_actuel)
SELECT c.id, donnees.matricule, donnees.date_embauche, s.id
FROM (
    VALUES
        ('test.superieur@rohysafe.local', 'TEST-RH-SUP-001', DATE '2026-01-01'),
        ('test.subordonne@rohysafe.local', 'TEST-RH-SUB-001', DATE '2026-02-01')
) AS donnees(email, matricule, date_embauche)
JOIN candidats c ON c.email = donnees.email
JOIN statut_employes s ON s.libelle = 'actif'
ON CONFLICT (matricule) DO UPDATE
SET id_candidat = EXCLUDED.id_candidat,
    date_embauche = EXCLUDED.date_embauche,
    id_statut_actuel = EXCLUDED.id_statut_actuel;

INSERT INTO relation_hierarchiques (id_superieur, id_subordonne, date_debut)
SELECT superieur.id, subordonne.id, DATE '2026-01-01'
FROM employes superieur
JOIN employes subordonne ON subordonne.matricule = 'TEST-RH-SUB-001'
WHERE superieur.id IS NOT NULL
  AND subordonne.id IS NOT NULL
  AND superieur.id <> subordonne.id
  AND superieur.matricule = 'TEST-RH-SUP-001'
  AND NOT EXISTS (
      SELECT 1
      FROM relation_hierarchiques relation
      WHERE relation.id_superieur = superieur.id
        AND relation.id_subordonne = subordonne.id
        AND relation.date_fin IS NULL
  );

COMMIT;
