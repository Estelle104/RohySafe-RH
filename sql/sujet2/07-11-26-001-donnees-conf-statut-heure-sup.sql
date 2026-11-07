INSERT INTO conf_heure_sups (taux_majoration)
SELECT 1.30
WHERE NOT EXISTS (
    SELECT 1
    FROM conf_heure_sups
);

INSERT INTO statut_heure_sups (libelle)
VALUES
    ('En attente'),
    ('Validée'),
    ('Refusée')
ON CONFLICT (libelle) DO NOTHING;