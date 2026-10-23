
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
                    'id', cc.id,
                    'id_competence', cc.id_competence,
                    'competence', comp.libelle,
                    'categorie', comp.categorie,
                    'niveau', cc.niveau
                )
                ORDER BY comp.libelle
            )
            FROM competences_candidat cc
            JOIN competences comp ON comp.id = cc.id_competence
            WHERE cc.id_candidat = c.id
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
