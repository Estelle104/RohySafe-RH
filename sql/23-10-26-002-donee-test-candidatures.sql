
INSERT INTO candidats (
    nom, prenom, email, telephone, ville, date_creation, login, mdp
)
VALUES
    ('Rakoto', 'Jean', 'jean.rakoto.test@example.com', '0340000001', 'Antananarivo', CURRENT_TIMESTAMP, 'candidat_test1', 'Test123!'),
    ('Rabe', 'Marie', 'marie.rabe.test@example.com', '0340000002', 'Antsirabe', CURRENT_TIMESTAMP, 'candidat_test2', 'Test123!'),
    ('Andria', 'Hery', 'hery.andria.test@example.com', '0340000003', 'Toamasina', CURRENT_TIMESTAMP, 'candidat_test3', 'Test123!'),
    ('Rasoa', 'Lina', 'lina.rasoa.test@example.com', '0340000004', 'Antananarivo', CURRENT_TIMESTAMP, 'candidat_test4', 'Test123!'),
    ('Randria', 'Paul', 'paul.randria.test@example.com', '0340000005', 'Fianarantsoa', CURRENT_TIMESTAMP, 'candidat_test5', 'Test123!'),
    ('Rakotoarisoa', 'Sarah', 'sarah.rakoto.test@example.com', '0340000006', 'Mahajanga', CURRENT_TIMESTAMP, 'candidat_test6', 'Test123!'),
    ('Ramanana', 'Kevin', 'kevin.ramanana.test@example.com', '0340000007', 'Antananarivo', CURRENT_TIMESTAMP, 'candidat_test7', 'Test123!'),
    ('Razafy', 'Nadia', 'nadia.razafy.test@example.com', '0340000008', 'Toliara', CURRENT_TIMESTAMP, 'candidat_test8', 'Test123!')
RETURNING id, nom, prenom, email;


WITH candidats_test AS (
    SELECT
        id,
        ROW_NUMBER() OVER (ORDER BY id) AS numero
    FROM candidats
    WHERE email IN (
        'jean.rakoto.test@example.com',
        'marie.rabe.test@example.com',
        'hery.andria.test@example.com',
        'lina.rasoa.test@example.com',
        'paul.randria.test@example.com',
        'sarah.rakoto.test@example.com',
        'kevin.ramanana.test@example.com',
        'nadia.razafy.test@example.com'
    )
),
offres_test AS (
    SELECT
        id,
        ROW_NUMBER() OVER (ORDER BY id) AS numero
    FROM offres
),
donnees_test AS (
    SELECT *
    FROM (VALUES
        (1, 1, 15, 1),
        (2, 1, 14, 2),
        (3, 1, 13, 3),
        (4, 2, 12, 4),
        (5, 2, 11, 5),
        (6, 2, 10, 6),
        (7, 3,  8, 2),
        (8, 4,  5, 4)
    ) AS v(numero_candidat, numero_offre, jours, id_statut)
)
INSERT INTO candidatures (
    id_candidat,
    id_offre,
    date_candidature,
    id_statut_actuel
)
SELECT
    c.id,
    o.id,
    CURRENT_DATE - dt.jours,
    dt.id_statut
FROM donnees_test dt
JOIN candidats_test c
    ON c.numero = dt.numero_candidat
JOIN offres_test o
    ON o.numero = dt.numero_offre
JOIN statut_candidatures sc
    ON sc.id = dt.id_statut
RETURNING *;

INSERT INTO statut_candidatures (libelle) VALUES ('selectionne');

INSERT INTO candidats (
    nom,
    prenom,
    email,
    telephone,
    ville,
    date_creation,
    login,
    mdp
)
VALUES
(
    'Razanamasy', 'Toky',
    'toky.razanamasy.test@example.com',
    '0340000011', 'Antananarivo',
    CURRENT_TIMESTAMP, 'candidat_test11', 'Test123!'
),
(
    'Andriamihaja', 'Miora',
    'miora.andriamihaja.test@example.com',
    '0340000012', 'Toamasina',
    CURRENT_TIMESTAMP, 'candidat_test12', 'Test123!'
),
(
    'Rakotomalala', 'Fanja',
    'fanja.rakotomalala.test@example.com',
    '0340000013', 'Antsirabe',
    CURRENT_TIMESTAMP, 'candidat_test13', 'Test123!'
)
RETURNING id, nom, prenom, email;


WITH nouveaux_candidats AS (
    SELECT id, ROW_NUMBER() OVER (ORDER BY id) AS numero
    FROM candidats
    WHERE email IN (
        'toky.razanamasy.test@example.com',
        'miora.andriamihaja.test@example.com',
        'fanja.rakotomalala.test@example.com'
    )
),
offres_test AS (
    SELECT id, ROW_NUMBER() OVER (ORDER BY id) AS numero
    FROM offres
)
INSERT INTO candidatures (
    id_candidat,
    id_offre,
    date_candidature,
    id_statut_actuel
)
SELECT
    c.id,
    o.id,
    CURRENT_DATE - c.numero::INTEGER,
    7
FROM nouveaux_candidats c
JOIN offres_test o ON o.numero = c.numero
WHERE EXISTS (
    SELECT 1
    FROM statut_candidatures
    WHERE id = 7
)
RETURNING *;