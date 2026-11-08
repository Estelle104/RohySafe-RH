
BEGIN;

WITH nouvelle_demande AS (
    INSERT INTO heure_supplementaires (
        id_employe,
        heure_debut,
        heure_fin,
        date_debut,
        date_fin,
        id_planning_edt_employes
    )
    VALUES (
        5,
        DATE_TRUNC('day', CURRENT_TIMESTAMP) + INTERVAL '9 hours',
        DATE_TRUNC('day', CURRENT_TIMESTAMP) + INTERVAL '11 hours',
        CURRENT_DATE,
        CURRENT_DATE,
        NULL
    )
    RETURNING id
),
nouvel_historique AS (
    INSERT INTO histo_demande_heure_sups (
        id_statut,
        id_utilisateur,
        id_heure_supplementaire
    )
    SELECT
        1,
        1,
        id
    FROM nouvelle_demande
    RETURNING id_heure_supplementaire
)
SELECT
    id_heure_supplementaire AS id_demande,
    'En attente' AS statut,
    2 AS heures_demandees
FROM nouvel_historique;

COMMIT;


INSERT INTO histo_demande_heure_sups (
    id_statut,
    id_utilisateur,
    id_heure_supplementaire
)
SELECT
    2,
    1,
    1
WHERE EXISTS (
    SELECT 1
    FROM histo_demande_heure_sups
    WHERE id_heure_supplementaire = 1
    AND id_statut = 1
);


UPDATE employes
SET id_poste_departement = (
    SELECT pd.id
    FROM poste_departement pd
    JOIN departements d ON d.id = pd.id_departement
    WHERE d.libelle = 'Informatique'
    LIMIT 1
)
WHERE matricule IN (
    'TEST-RH-SUP-001',
    'TEST-RH-SUB-001',
    'EMP-2026-0001'
);
