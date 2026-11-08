-- Données de test pour le formulaire de demande de congé.
-- À exécuter après les scripts de création des employés, types et soldes.
BEGIN;

-- Les deux employés de test partagent le même poste afin de permettre
-- la proposition automatique d'un remplaçant compatible.
WITH poste_test AS (
    SELECT pd.id
    FROM poste_departement pd
    JOIN departements d ON d.id = pd.id_departement
    WHERE d.libelle = 'Informatique'
    ORDER BY pd.id
    LIMIT 1
)
UPDATE employes e
SET id_poste_departement = poste_test.id
FROM poste_test
WHERE e.matricule IN ('TEST-RH-SUP-001', 'TEST-RH-SUB-001');

-- Garantit que le type annuel et sa règle d'acquisition existent.
INSERT INTO type_conges (libelle)
VALUES ('annuel')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO config_conges (id_type_conge, nombre_jour)
SELECT id, 2.50
FROM type_conges
WHERE libelle = 'annuel'
ON CONFLICT (id_type_conge)
DO UPDATE SET nombre_jour = EXCLUDED.nombre_jour;

-- Crée ou crédite les soldes 2026 et 2027 pour les employés de test.
INSERT INTO solde_conges (
    id_employe,
    id_type_conge,
    periode_debut,
    periode_fin,
    droits_acquis,
    jours_pris,
    solde_restant
)
SELECT
    e.id,
    tc.id,
    annees.annee::date,
    (annees.annee + INTERVAL '1 year - 1 day')::date,
    25.00,
    0.00,
    25.00
FROM employes e
CROSS JOIN type_conges tc
CROSS JOIN (
    VALUES (DATE '2026-01-01'), (DATE '2027-01-01')
) AS annees(annee)
WHERE e.matricule IN ('TEST-RH-SUP-001', 'TEST-RH-SUB-001')
  AND tc.libelle = 'annuel'
ON CONFLICT (id_employe, id_type_conge, periode_debut, periode_fin)
DO UPDATE SET
    droits_acquis = 25.00,
    jours_pris = 0.00,
    solde_restant = 25.00;

COMMIT;

-- Après exécution, tester une demande du 2026-12-01 au 2026-12-03
-- pour TEST-RH-SUB-001 avec le type annuel.
