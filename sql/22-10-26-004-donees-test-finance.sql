-- Données de test du workflow Finance / Direction générale.
-- À exécuter après la création des tables. Le script est autonome.

-- 0. Référentiels nécessaires aux demandes Finance.
INSERT INTO departements (libelle) VALUES
    ('Informatique'), ('Technique'), ('Santé'), ('Finance')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO roles (libelle) VALUES
    ('DG'), ('Finance'), ('RH'), ('Manager')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO type_contrats (libelle) VALUES
    ('CDI'), ('CDD'), ('CE')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO utilisateurs (login, mdp, id_role, id_departement)
SELECT v.login, '$2y$10$hashfactice000000000000000000000000000000000000000000',
       r.id, d.id
FROM (VALUES
    ('dg', 'DG', NULL),
    ('finance', 'Finance', NULL),
    ('rh', 'RH', NULL),
    ('manager_info', 'Manager', 'Informatique'),
    ('manager_tech', 'Manager', 'Technique')
) AS v(login, role_libelle, departement)
JOIN roles r ON r.libelle = v.role_libelle
LEFT JOIN departements d ON d.libelle = v.departement
ON CONFLICT (login) DO NOTHING;

INSERT INTO categorie_postes (libelle) VALUES
    ('Développement'), ('Réseau')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO postes (libelle, id_categorie)
SELECT v.poste, c.id
FROM (VALUES
    ('Développeur Web PHP', 'Développement'),
    ('Développeur Mobile', 'Développement'),
    ('Technicien réseau', 'Réseau')
) AS v(poste, categorie)
JOIN categorie_postes c ON c.libelle = v.categorie
ON CONFLICT (id_categorie, libelle) DO NOTHING;

INSERT INTO poste_departement (id_poste, id_departement)
SELECT p.id, d.id
FROM (VALUES
    ('Développeur Web PHP', 'Informatique'),
    ('Développeur Mobile', 'Informatique'),
    ('Technicien réseau', 'Technique')
) AS v(poste, departement)
JOIN postes p ON p.libelle = v.poste
JOIN departements d ON d.libelle = v.departement
ON CONFLICT (id_poste, id_departement) DO NOTHING;

-- 1. Statuts nécessaires au workflow.
INSERT INTO statut_demandes (libelle) VALUES
    ('EN_VALIDATION_FINANCE'),
    ('EN_VALIDATION_DIRECTION'),
    ('VALIDEE_DIRECTION'),
    ('REFUSEE_FINANCE'),
    ('REFUSEE_DIRECTION'),
    ('ANNULEE_DIRECTION'),
    ('ANNULEE_FINANCE')
ON CONFLICT (libelle) DO NOTHING;


-- 2. Budgets mensuels 2026.
INSERT INTO budgets (id_departement, annee, montant_mensuel, montant_engage)
SELECT d.id, 2026, v.montant, 0
FROM (VALUES
    ('Informatique', 10000000::numeric),
    ('Technique',     5000000::numeric),
    ('Santé',         8000000::numeric),
    ('Finance',       6000000::numeric)
) AS v(departement, montant)
JOIN departements d ON d.libelle = v.departement
ON CONFLICT (id_departement, annee) DO UPDATE
SET montant_mensuel = EXCLUDED.montant_mensuel;

-- 3. Demandes en attente de validation Finance.
INSERT INTO demandes (
    date_creation, id_poste_departement, id_createur, id_utilisateur_actuel,
    id_statut_actuel, nombre_demande, id_type_contrat, date_prise_poste,
    salaire_propose
)
SELECT v.date_creation::timestamp, pd.id, u.id, u.id, sd.id, v.nombre,
       tc.id, v.date_prise::date, v.salaire
FROM (VALUES
    ('Développeur Web PHP', 'Informatique', 'manager_info', 'CDI', '2026-10-20', '2026-12-01', 1500000::numeric, 1),
    ('Technicien réseau', 'Technique', 'manager_tech', 'CDD', '2026-10-21', '2026-12-01', 900000::numeric, 1)
) AS v(poste, departement, createur, contrat, date_creation, date_prise, salaire, nombre)
JOIN postes p ON p.libelle = v.poste
JOIN departements d ON d.libelle = v.departement
JOIN poste_departement pd ON pd.id_poste = p.id AND pd.id_departement = d.id
JOIN utilisateurs u ON u.login = v.createur
JOIN type_contrats tc ON tc.libelle = v.contrat
JOIN statut_demandes sd ON sd.libelle = 'EN_VALIDATION_FINANCE';

-- 4. Demande déjà validée par la Finance, à traiter par la Direction.
INSERT INTO demandes (
    date_creation, id_poste_departement, id_createur, id_utilisateur_actuel,
    id_statut_actuel, nombre_demande, id_type_contrat, date_prise_poste,
    salaire_propose
)
SELECT '2026-10-18'::timestamp, pd.id, u.id, u.id, sd.id, 1, tc.id,
       '2026-12-15'::date, 1400000
FROM postes p
JOIN departements d ON d.libelle = 'Informatique'
JOIN poste_departement pd ON pd.id_poste = p.id AND pd.id_departement = d.id
JOIN utilisateurs u ON u.login = 'manager_info'
JOIN type_contrats tc ON tc.libelle = 'CDD'
JOIN statut_demandes sd ON sd.libelle = 'EN_VALIDATION_DIRECTION'
WHERE p.libelle = 'Développeur Mobile';

-- 5. Historique initial des demandes de test.
INSERT INTO historique_demandes (
    id_demande, id_statut_demande, id_utilisateur, salaire_propose, remarque
)
SELECT d.id, d.id_statut_actuel, d.id_createur,
       d.salaire_propose * d.nombre_demande,
       'Demande de test créée pour le workflow Finance.'
FROM demandes d
JOIN statut_demandes s ON s.id = d.id_statut_actuel
WHERE s.libelle = 'EN_VALIDATION_FINANCE'
  AND d.date_creation::date = '2026-10-20';

-- 6. Réservation correspondant à la demande déjà passée par la Finance.
UPDATE budgets b
SET montant_engage = montant_engage + x.montant
FROM (
    SELECT pd.id_departement, d.salaire_propose * d.nombre_demande AS montant
    FROM demandes d
    JOIN poste_departement pd ON pd.id = d.id_poste_departement
    JOIN statut_demandes s ON s.id = d.id_statut_actuel
    WHERE s.libelle = 'EN_VALIDATION_DIRECTION'
      AND d.date_creation::date = '2026-10-18'
) x
WHERE b.id_departement = x.id_departement
  AND b.annee = 2026;

