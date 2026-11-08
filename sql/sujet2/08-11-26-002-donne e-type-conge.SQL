
BEGIN;

-- 1. Configurer les droits acquis par type de congé
UPDATE config_conges
SET nombre_jour = CASE id_type_conge
    WHEN 1 THEN 2.5
    WHEN 2 THEN 0
    WHEN 3 THEN 0
    WHEN 4 THEN 5
END
WHERE id_type_conge IN (1, 2, 3, 4);

-- 2. Créer les configurations manquantes
INSERT INTO config_conges (id_type_conge, nombre_jour)
VALUES
    (1, 2.5),
    (2, 0),
    (3, 0),
    (4, 5)
ON CONFLICT (id_type_conge)
DO UPDATE SET nombre_jour = EXCLUDED.nombre_jour;

-- 3. Créer les soldes de congés 2027
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
    cc.id_type_conge,
    '2027-01-01',
    '2027-12-31',
    0,
    0,
    0
FROM employes e
CROSS JOIN config_conges cc
WHERE NOT EXISTS (
    SELECT 1
    FROM solde_conges sc
    WHERE sc.id_employe = e.id
      AND sc.id_type_conge = cc.id_type_conge
      AND sc.periode_debut = '2027-01-01'
      AND sc.periode_fin = '2027-12-31'
);

COMMIT;
