
-- Creation de 3 candidats qui sont devenues employes => 3 employes

BEGIN;

WITH nouveaux_candidats (
    nom, prenom, email, telephone, ville,
    login, date_embauche, poste, departement
) AS (
    VALUES
    (
        'Randria', 'Aina',
        'aina.randria.planning@example.test',
        '0340000021', 'Antananarivo',
        'aina_planning', DATE '2026-11-06',
        'Développeur Web', 'Informatique'
    ),
    (
        'Rakoto', 'Haja',
        'haja.rakoto.planning@example.test',
        '0340000022', 'Antsirabe',
        'haja_planning', DATE '2026-11-04',
        'Développeur Mobile', 'Informatique'
    ),
    (
        'Andria', 'Fara',
        'fara.andria.planning@example.test',
        '0340000023', 'Toamasina',
        'fara_planning', DATE '2026-11-02',
        'Comptable', 'Finance'
    )
),
candidats_inseres AS (
    INSERT INTO candidats (
        nom, prenom, email, telephone, ville, login, mdp
    )
    SELECT
        n.nom,
        n.prenom,
        n.email,
        n.telephone,
        n.ville,
        n.login,
        (
            SELECT mdp
            FROM candidats
            WHERE email = 'miora.rakoto@example.test'
        )
    FROM nouveaux_candidats n
    ON CONFLICT (email)
    DO UPDATE SET email = EXCLUDED.email
    RETURNING id, email
),
candidats_a_embaucher AS (
    SELECT
        ci.id AS id_candidat,
        n.email,
        n.date_embauche,
        n.poste,
        n.departement
    FROM candidats_inseres ci
    JOIN nouveaux_candidats n ON n.email = ci.email
),
employes_a_creer AS (
    SELECT c.*
    FROM candidats_a_embaucher c
    WHERE NOT EXISTS (
        SELECT 1
        FROM employes e
        WHERE e.id_candidat = c.id_candidat
    )
),
matricules AS (
    SELECT
        e.*,
        ROW_NUMBER() OVER (ORDER BY e.email) AS numero
    FROM employes_a_creer e
),
dernier_matricule AS (
    SELECT COALESCE(
        MAX(SUBSTRING(matricule FROM 'EMP-2026-([0-9]+)$')::INTEGER),
        0
    ) AS maximum
    FROM employes
    WHERE matricule ~ '^EMP-2026-[0-9]+$'
)
INSERT INTO employes (
    id_candidat,
    matricule,
    date_embauche,
    id_statut_actuel,
    id_poste_departement
)
SELECT
    m.id_candidat,
    'EMP-2026-' || LPAD(
        (dm.maximum + m.numero)::TEXT,
        4,
        '0'
    ),
    m.date_embauche,
    se.id,
    pd.id
FROM matricules m
CROSS JOIN dernier_matricule dm
JOIN statut_employes se
    ON se.libelle = 'actif'
JOIN poste_departement pd
    ON pd.id_poste = (
        SELECT p.id
        FROM postes p
        WHERE p.libelle = m.poste
    )
   AND pd.id_departement = (
        SELECT d.id
        FROM departements d
        WHERE d.libelle = m.departement
    );

COMMIT;




-- ajouter les plannings


INSERT INTO planning_edts (
    date_debut,
    date_fin,
    jours_semaine,
    libelle
)
SELECT
    v.date_debut,
    v.date_fin,
    v.jours_semaine,
    v.libelle
FROM (
    VALUES
    (
        DATE '2026-11-06',
        DATE '2026-11-06',
        ARRAY[5]::SMALLINT[],
        'Finalisation du module de recrutement'
    ),
    (
        DATE '2026-11-02',
        DATE '2026-11-08',
        ARRAY[1,2,3,4,5]::SMALLINT[],
        'Suivi des activités de la semaine'
    ),
    (
        DATE '2026-11-02',
        DATE '2026-11-30',
        ARRAY[1,2,3,4,5]::SMALLINT[],
        'Suivi des activités du mois de novembre'
    )
) AS v(date_debut, date_fin, jours_semaine, libelle)
WHERE NOT EXISTS (
    SELECT 1
    FROM planning_edts p
    WHERE p.date_debut = v.date_debut
      AND p.date_fin = v.date_fin
      AND p.libelle = v.libelle
);

-- 2. Affecter les employés au planning d'aujourd'hui
-- Planning d'aujourd'hui : Aina
INSERT INTO planning_edt_employes (id_planning_edt, id_employe)
SELECT p.id, e.id
FROM planning_edts p
JOIN candidats c
    ON c.email = 'aina.randria.planning@example.test'
JOIN employes e ON e.id_candidat = c.id
WHERE p.libelle = 'Finalisation du module de recrutement'
  AND p.date_debut = DATE '2026-11-06'
ON CONFLICT (id_planning_edt, id_employe) DO NOTHING;


-- Planning de la semaine : Haja et Fara
INSERT INTO planning_edt_employes (id_planning_edt, id_employe)
SELECT p.id, e.id
FROM planning_edts p
JOIN candidats c
    ON c.email IN (
        'haja.rakoto.planning@example.test',
        'fara.andria.planning@example.test'
    )
JOIN employes e ON e.id_candidat = c.id
WHERE p.libelle = 'Suivi des activités de la semaine'
  AND p.date_debut = DATE '2026-11-02'
ON CONFLICT (id_planning_edt, id_employe) DO NOTHING;


-- Planning du mois : Haja et Fara
INSERT INTO planning_edt_employes (id_planning_edt, id_employe)
SELECT p.id, e.id
FROM planning_edts p
JOIN candidats c
    ON c.email IN (
        'haja.rakoto.planning@example.test',
        'fara.andria.planning@example.test'
    )
JOIN employes e ON e.id_candidat = c.id
WHERE p.libelle = 'Suivi des activités du mois de novembre'
  AND p.date_debut = DATE '2026-11-02'
ON CONFLICT (id_planning_edt, id_employe) DO NOTHING;