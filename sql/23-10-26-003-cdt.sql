CREATE OR REPLACE VIEW v_donnees_contrat_travail AS
SELECT
    -- Contrat
    ct.id AS id_contrat,
    ct.id_type_contrat,
    tc.libelle AS type_contrat,
    ct.date_debut,
    ct.date_fin,
    ct.salaire AS salaire_mensuel,
    ct.date_signature,
    ct.id_statut_actuel,

    -- Employé
    e.id AS id_employe,
    e.matricule,
    e.date_embauche,

    -- Informations personnelles
    c.id AS id_candidat,
    c.nom,
    c.prenom,
    CONCAT(c.prenom, ' ', c.nom) AS nom_complet,
    c.email,
    c.telephone,
    c.ville,

    -- Candidature et offre
    ca.id AS id_candidature,
    o.id AS id_offre,
    o.titre AS titre_offre,

    -- Demande de recrutement
    d.id AS id_demande,
    d.date_prise_poste,
    p_d.id_poste,
    p.libelle AS poste,
    p_d.id_departement,
    dep.libelle AS departement

FROM contrats ct
JOIN employes e
    ON e.id = ct.id_employe
JOIN candidats c
    ON c.id = e.id_candidat
LEFT JOIN type_contrats tc
    ON tc.id = ct.id_type_contrat
LEFT JOIN candidatures ca
    ON ca.id_candidat = c.id
LEFT JOIN offres o
    ON o.id = ca.id_offre
LEFT JOIN demandes d
    ON d.id = o.id_demande
-- LEFT JOIN postes p
--     ON p.id = d.id_poste
-- LEFT JOIN departements dep
--     ON dep.id = d.id_departement
LEFT JOIN poste_departement p_d
    ON d.id_poste_departement = p_d.id
LEFT JOIN postes p
    ON p.id = p_d.id_poste
LEFT JOIN departements dep
    ON dep.id = p_d.id_departement;




-- donnee test sataut contrats
-- INSERT INTO statut_contrats (libelle)
-- VALUES
--     ('cree'),
--     ('en attente'),
--     ('signe'),
--     ('suspendu'),
--     ('annule');