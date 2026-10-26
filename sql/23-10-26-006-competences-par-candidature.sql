BEGIN;

DROP VIEW IF EXISTS vue_classement_candidats_postes;
DROP VIEW IF EXISTS vue_cv_candidats;

DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'competences_candidat'
          AND column_name = 'id_candidat'
    ) THEN
        CREATE TEMP TABLE competences_candidat_migration ON COMMIT DROP AS
        SELECT DISTINCT
            ca.id AS id_candidature,
            cc.id_competence,
            cc.niveau
        FROM competences_candidat cc
        JOIN candidatures ca ON ca.id_candidat = cc.id_candidat;

        DROP TABLE competences_candidat;

        CREATE TABLE competences_candidat (
            id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
            id_candidature BIGINT NOT NULL REFERENCES candidatures(id) ON DELETE CASCADE,
            id_competence BIGINT NOT NULL REFERENCES competences(id),
            niveau NUMERIC(5,2) CHECK (niveau BETWEEN 0 AND 20),

            UNIQUE (id_candidature, id_competence)
        );

        INSERT INTO competences_candidat (id_candidature, id_competence, niveau)
        SELECT id_candidature, id_competence, niveau
        FROM competences_candidat_migration;
    END IF;
END
$$;

CREATE OR REPLACE VIEW vue_cv_candidats AS
SELECT
    c.id AS id_candidat,
    c.nom,
    c.prenom,
    c.email,
    c.telephone,
    c.ville,
    COALESCE(
        (
            SELECT jsonb_agg(
                jsonb_build_object(
                    'id', e.id,
                    'id_poste', e.id_poste,
                    'poste', p.libelle,
                    'date_debut', e.date_debut,
                    'date_fin', e.date_fin,
                    'description', e.descriptions
                )
                ORDER BY e.date_debut DESC
            )
            FROM experiences_candidat e
            JOIN postes p ON p.id = e.id_poste
            WHERE e.id_candidat = c.id
        ),
        '[]'::jsonb
    ) AS experiences,
    COALESCE(
        (
            SELECT jsonb_agg(
                jsonb_build_object(
                    'id', f.id,
                    'diplome', f.diplome,
                    'etablissement', f.etablissement,
                    'date_debut', f.date_debut,
                    'date_fin', f.date_fin,
                    'description', f.description
                )
                ORDER BY f.date_debut DESC NULLS LAST
            )
            FROM formations_candidat f
            WHERE f.id_candidat = c.id
        ),
        '[]'::jsonb
    ) AS formations,
    COALESCE(
        (
            SELECT jsonb_agg(
                jsonb_build_object(
                    'id', x.id,
                    'id_competence', x.id_competence,
                    'competence', x.libelle,
                    'categorie', x.categorie,
                    'niveau', x.niveau
                )
                ORDER BY x.libelle
            )
            FROM (
                SELECT DISTINCT ON (cc.id_competence)
                    cc.id,
                    cc.id_competence,
                    comp.libelle,
                    comp.categorie,
                    cc.niveau
                FROM competences_candidat cc
                JOIN candidatures ca ON ca.id = cc.id_candidature
                JOIN competences comp ON comp.id = cc.id_competence
                WHERE ca.id_candidat = c.id
                ORDER BY cc.id_competence, ca.date_candidature DESC, cc.id DESC
            ) x
        ),
        '[]'::jsonb
    ) AS competences,
    COALESCE(
        (
            SELECT jsonb_agg(
                jsonb_build_object(
                    'id', dc.id,
                    'nom_fichier', dc.nom_fichier,
                    'chemin_fichier', dc.chemin_fichier,
                    'type_document', td.libelle,
                    'date_ajout', dc.date_ajout
                )
                ORDER BY dc.date_ajout DESC
            )
            FROM documents_candidat dc
            JOIN type_documents td ON td.id = dc.id_type_document
            WHERE dc.id_candidat = c.id
        ),
        '[]'::jsonb
    ) AS documents
FROM candidats c;

CREATE OR REPLACE VIEW vue_classement_candidats_postes AS
WITH candidatures_postes AS (
    SELECT
        ca.id AS id_candidature,
        ca.id_candidat,
        pd.id_poste
    FROM candidatures ca
    JOIN offres o ON o.id = ca.id_offre
    JOIN demandes dm ON dm.id = o.id_demande
    JOIN poste_departement pd ON pd.id = dm.id_poste_departement
),
criteres AS (
    SELECT id_poste, id_competence, note_minimale, obligatoire
    FROM criteres_poste
),
scores AS (
    SELECT
        c.id_poste,
        cc.id_candidature,
        SUM(cc.niveau) AS score_total,
        COUNT(DISTINCT c.id_competence) AS nb_competences_validees,
        COUNT(DISTINCT CASE
            WHEN c.obligatoire THEN c.id_competence
        END) AS nb_criteres_obligatoires_valides,
        STRING_AGG(DISTINCT comp.libelle, ', ' ORDER BY comp.libelle)
            AS competences_validees
    FROM competences_candidat cc
    JOIN criteres c
        ON c.id_competence = cc.id_competence
       AND cc.niveau >= c.note_minimale
    JOIN competences comp ON comp.id = cc.id_competence
    GROUP BY c.id_poste, cc.id_candidature
),
candidatures_sans_criteres AS (
    SELECT
        cp.id_poste,
        cp.id_candidature,
        cp.id_candidat,
        cand.nom,
        cand.prenom,
        cand.email,
        cand.telephone,
        cand.ville,
        0::NUMERIC AS score_total,
        0::BIGINT AS nb_competences_validees,
        NULL::TEXT AS competences_validees
    FROM candidatures_postes cp
    JOIN candidats cand ON cand.id = cp.id_candidat
    WHERE NOT EXISTS (
        SELECT 1
        FROM criteres c
        WHERE c.id_poste = cp.id_poste
    )
)
SELECT
    scores.id_poste,
    cp.id_candidature,
    cp.id_candidat,
    cand.nom,
    cand.prenom,
    cand.email,
    cand.telephone,
    cand.ville,
    scores.score_total,
    scores.nb_competences_validees,
    scores.competences_validees
FROM scores
JOIN candidatures_postes cp
    ON cp.id_candidature = scores.id_candidature
   AND cp.id_poste = scores.id_poste
JOIN candidats cand ON cand.id = cp.id_candidat
WHERE scores.nb_criteres_obligatoires_valides = (
    SELECT COUNT(*)
    FROM criteres
    WHERE criteres.id_poste = scores.id_poste
      AND criteres.obligatoire = TRUE
)
UNION ALL
SELECT
    id_poste,
    id_candidature,
    id_candidat,
    nom,
    prenom,
    email,
    telephone,
    ville,
    score_total,
    nb_competences_validees,
    competences_validees
FROM candidatures_sans_criteres;

COMMIT;
