CREATE OR REPLACE VIEW v_info_employes AS
SELECT
    e.id                       AS id_employe,
    e.matricule,
    e.date_embauche,
    se.libelle                 AS statut_actuel,
    c.nom,
    c.prenom,
    c.email,
    c.telephone,
    c.ville,
    ie.residence_actuel,
    ie.copie_actuel,
    ie.date_echeance,
    ie.id_statut_matrimoniale,
    sm.libelle                 AS statut_matrimoniale,
    ie.id_statut_professionnelle,
    sp.libelle                 AS statut_professionnelle,
    (SELECT string_agg(cs.nom || ' ' || cs.prenom, ', ' ORDER BY cs.nom)
       FROM relation_hierarchiques r
       JOIN employes es  ON es.id = r.id_superieur
       JOIN candidats cs ON cs.id = es.id_candidat
      WHERE r.id_subordonne = e.id
        AND r.date_debut <= CURRENT_DATE
        AND (r.date_fin IS NULL OR r.date_fin >= CURRENT_DATE)) AS superieur,
    (SELECT string_agg(ci.nom || ' ' || ci.prenom, ', ' ORDER BY ci.nom)
       FROM relation_hierarchiques r
       JOIN employes ei  ON ei.id = r.id_subordonne
       JOIN candidats ci ON ci.id = ei.id_candidat
      WHERE r.id_superieur = e.id
        AND r.date_debut <= CURRENT_DATE
        AND (r.date_fin IS NULL OR r.date_fin >= CURRENT_DATE)) AS inferieurs
FROM employes e
JOIN candidats c                        ON c.id = e.id_candidat
LEFT JOIN statut_employes se            ON se.id = e.id_statut_actuel
LEFT JOIN info_employes ie              ON ie.id_employe = e.id
LEFT JOIN statut_matrimoniales sm       ON sm.id = ie.id_statut_matrimoniale
LEFT JOIN statut_professionnelles sp    ON sp.id = ie.id_statut_professionnelle;