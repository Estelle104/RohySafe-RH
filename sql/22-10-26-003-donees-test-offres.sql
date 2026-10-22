-- Données de test pour les offres (à exécuter après table.sql)
-- Les id sont GENERATED ALWAYS : on passe par les libellés avec des sous-requêtes.

-- 1. Référentiels
INSERT INTO departements (libelle) VALUES
    ('Informatique'), ('Technique'), ('Santé'), ('Finance');

INSERT INTO roles (libelle) VALUES
    ('DG'), ('Finance'), ('RH'), ('Manager');

INSERT INTO type_contrats (libelle) VALUES
    ('CDI'), ('CDD'), ('CE');

INSERT INTO statut_demandes (libelle) VALUES
    ('cree'), ('valide'), ('en_attente'), ('refusee'), ('annulee');

INSERT INTO statut_offres (libelle) VALUES
    ('brouillon'), ('publiee'), ('cloturee');

INSERT INTO statut_candidatures (libelle) VALUES
    ('en_attente'), ('preselectionne'), ('ecarte'), ('entretien'), ('retenu'), ('refuse');

INSERT INTO type_documents (libelle) VALUES
    ('cv'), ('lettre_motivation'), ('diplome'), ('cin'), ('photo');

-- 2. Utilisateurs (mdp factice : remplacer par un vrai hash password_hash() de CI4)
INSERT INTO utilisateurs (login, mdp, id_role, id_departement)
SELECT v.login, '$2y$10$hashfactice000000000000000000000000000000000000000000',
       r.id, d.id
FROM (VALUES
    ('dg',            'DG',      NULL),
    ('finance',       'Finance', NULL),
    ('rh',            'RH',      NULL),
    ('manager_info',  'Manager', 'Informatique'),
    ('manager_tech',  'Manager', 'Technique'),
    ('manager_sante', 'Manager', 'Santé')
) AS v(login, role, dept)
JOIN roles r ON r.libelle = v.role
LEFT JOIN departements d ON d.libelle = v.dept;

-- 3. Postes
INSERT INTO categorie_postes (libelle) VALUES
    ('Développement'), ('Réseau'), ('Médical'), ('Comptabilité');

INSERT INTO postes (libelle, id_categorie)
SELECT v.poste, c.id
FROM (VALUES
    ('Développeur Web PHP',             'Développement'),
    ('Développeur Mobile',              'Développement'),
    ('Technicien réseau',               'Réseau'),
    ('Infirmier(ère) d''entreprise',    'Médical'),
    ('Comptable',                       'Comptabilité')
) AS v(poste, categorie)
JOIN categorie_postes c ON c.libelle = v.categorie;

INSERT INTO poste_departement (id_poste, id_departement)
SELECT p.id, d.id
FROM (VALUES
    ('Développeur Web PHP',          'Informatique'),
    ('Développeur Mobile',           'Informatique'),
    ('Technicien réseau',            'Technique'),
    ('Infirmier(ère) d''entreprise', 'Santé'),
    ('Comptable',                    'Finance')
) AS v(poste, dept)
JOIN postes p        ON p.libelle = v.poste
JOIN departements d  ON d.libelle = v.dept;

-- 4. Demandes validées (une par poste/département)
INSERT INTO demandes (
    date_creation, id_poste_departement, id_createur, id_utilisateur_actuel,
    id_statut_actuel, nombre_demande, id_type_contrat, date_prise_poste, salaire_propose
)
SELECT v.date_creation::timestamp, pd.id, u.id, u.id,
       sd.id, v.nb, tc.id, v.date_prise::date, v.salaire
FROM (VALUES
    ('Développeur Web PHP',          'Informatique', 'manager_info',  '2026-10-01', 2, 'CDI', '2026-12-01', 1500000),
    ('Développeur Mobile',           'Informatique', 'manager_info',  '2026-09-25', 1, 'CDD', '2026-12-15', 1400000),
    ('Technicien réseau',            'Technique',    'manager_tech',  '2026-10-03', 1, 'CDD', '2026-12-01',  900000),
    ('Infirmier(ère) d''entreprise', 'Santé',        'manager_sante', '2026-09-28', 3, 'CDI', '2027-01-05', 1100000),
    ('Comptable',                    'Finance',      'finance',       '2026-09-10', 1, 'CDI', '2026-11-01', 1200000)
) AS v(poste, dept, createur, date_creation, nb, contrat, date_prise, salaire)
JOIN postes p              ON p.libelle = v.poste
JOIN departements d        ON d.libelle = v.dept
JOIN poste_departement pd  ON pd.id_poste = p.id AND pd.id_departement = d.id
JOIN utilisateurs u        ON u.login = v.createur
JOIN type_contrats tc      ON tc.libelle = v.contrat
JOIN statut_demandes sd    ON sd.libelle = 'valide';

-- 5. Offres (cas variés pour tester l'affichage et les filtres)
--    Aujourd'hui = 2026-10-22
INSERT INTO offres (id_demande, titre, descriptions, date_publication, date_limite, id_statut)
SELECT dm.id, v.titre, v.descriptions, v.pub::date, v.limite::date, so.id
FROM (VALUES
    ('Développeur Web PHP', 'Informatique',
     'Développeur Web PHP / CodeIgniter',
     'Rejoignez l''équipe informatique pour concevoir nos applications internes. Maîtrise de PHP, CodeIgniter 4 et PostgreSQL souhaitée.',
     '2026-10-20', '2026-11-15', 'publiee'),

    ('Développeur Mobile', 'Informatique',
     'Développeur Mobile Flutter',
     'Création de l''application mobile destinée aux employés. Expérience Flutter ou React Native appréciée.',
     '2026-10-19', '2026-11-20', 'publiee'),

    ('Technicien réseau', 'Technique',
     'Technicien de maintenance réseau',
     'Installation, supervision et maintenance de l''infrastructure réseau. Une première expérience similaire est un atout.',
     '2026-10-18', '2026-11-10', 'publiee'),

    ('Infirmier(ère) d''entreprise', 'Santé',
     'Infirmier(ère) d''entreprise',
     'Suivi médical du personnel, premiers soins et sensibilisation à la sécurité au travail sur site.',
     '2026-10-15', '2026-11-05', 'publiee'),

    -- Offre expirée mais encore "publiee" : pour tester le filtrage par date_limite
    ('Comptable', 'Finance',
     'Comptable',
     'Tenue de la comptabilité générale, rapprochements bancaires et suivi des budgets départementaux.',
     '2026-09-15', '2026-10-15', 'publiee'),

    -- Offre en brouillon : ne doit PAS apparaître dans le fil
    ('Développeur Mobile', 'Informatique',
     'Développeur Mobile (brouillon)',
     'Offre en cours de rédaction, non visible des candidats.',
     NULL, NULL, 'brouillon')
) AS v(poste, dept, titre, descriptions, pub, limite, statut)
JOIN postes p              ON p.libelle = v.poste
JOIN departements d        ON d.libelle = v.dept
JOIN poste_departement pd  ON pd.id_poste = p.id AND pd.id_departement = d.id
JOIN demandes dm           ON dm.id_poste_departement = pd.id
JOIN statut_offres so      ON so.libelle = v.statut;

-- 6. Requête du fil d'offres (format attendu par OffreCard.vue)
--    Seulement les offres publiées et non expirées, les plus récentes d'abord.
-- SELECT o.id,
--        d.libelle  AS departement,
--        o.titre,
--        o.descriptions,
--        o.date_publication,
--        o.date_limite,
--        tc.libelle AS type_contrat,
--        NULL       AS ville,
--        dm.nombre_demande
-- FROM offres o
-- JOIN statut_offres so      ON so.id = o.id_statut
-- JOIN demandes dm           ON dm.id = o.id_demande
-- JOIN poste_departement pd  ON pd.id = dm.id_poste_departement
-- JOIN departements d        ON d.id = pd.id_departement
-- JOIN type_contrats tc      ON tc.id = dm.id_type_contrat
-- WHERE so.libelle = 'publiee'
--   AND (o.date_limite IS NULL OR o.date_limite >= CURRENT_DATE)
-- ORDER BY o.date_publication DESC;q