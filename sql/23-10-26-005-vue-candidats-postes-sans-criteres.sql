CREATE OR REPLACE VIEW vue_classement_candidats_postes AS
WITH criteres AS (
    SELECT id_poste, id_competence, note_minimale, obligatoire
    FROM criteres_poste
),
scores AS (
    SELECT
        c.id_poste,
        cc.id_candidat,
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
    GROUP BY c.id_poste, cc.id_candidat
),
postes_sans_criteres AS (
    SELECT p.id AS id_poste
    FROM postes p
    WHERE NOT EXISTS (
        SELECT 1
        FROM criteres c
        WHERE c.id_poste = p.id
    )
),
candidats_sans_criteres AS (
    SELECT
        psc.id_poste,
        cand.id AS id_candidat,
        cand.nom,
        cand.prenom,
        cand.email,
        cand.telephone,
        cand.ville,
        0::NUMERIC AS score_total,
        0::BIGINT AS nb_competences_validees,
        NULL::TEXT AS competences_validees
    FROM postes_sans_criteres psc
    CROSS JOIN candidats cand
)
SELECT
    scores.id_poste,
    cand.id AS id_candidat,
    cand.nom,
    cand.prenom,
    cand.email,
    cand.telephone,
    cand.ville,
    scores.score_total,
    scores.nb_competences_validees,
    scores.competences_validees
FROM scores
JOIN candidats cand ON cand.id = scores.id_candidat
WHERE scores.nb_criteres_obligatoires_valides = (
    SELECT COUNT(*)
    FROM criteres
    WHERE criteres.id_poste = scores.id_poste
      AND criteres.obligatoire = TRUE
)
UNION ALL
SELECT
    id_poste,
    id_candidat,
    nom,
    prenom,
    email,
    telephone,
    ville,
    score_total,
    nb_competences_validees,
    competences_validees
FROM candidats_sans_criteres;
