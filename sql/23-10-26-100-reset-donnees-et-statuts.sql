-- Réinitialisation des données sans supprimer les tables.
-- Ce script supprime toutes les données, puis réinsère uniquement les statuts.
-- À exécuter après les scripts de création des tables.
-- ATTENTION : les données existantes seront définitivement supprimées.

BEGIN;

TRUNCATE TABLE
    documents_candidat,
    type_documents,
    historique_contrats_statut,
    contrats,
    statut_contrats,
    historique_employes_statut,
    employes,
    statut_employes,
    historique_propositions,
    propositions_embauche,
    statut_propositions_embauche,
    evaluations_candidat,
    historique_entretiens_statut,
    entretiens,
    statut_entretiens,
    criteres_softskill,
    criteres_poste,
    exigences_experience,
    softskills,
    competences_candidat,
    competences,
    formations_candidat,
    experiences_candidat,
    historique_candidature_status,
    candidatures,
    statut_candidatures,
    offres,
    statut_offres,
    budgets,
    histo_grille_salariales,
    grilles_salariales,
    historique_demandes,
    demandes,
    statut_demandes,
    type_contrats,
    poste_departement,
    postes,
    categorie_postes,
    candidats,
    utilisateurs,
    roles,
    departements
RESTART IDENTITY CASCADE;

-- Statuts du workflow des demandes RH.
INSERT INTO statut_demandes (libelle) VALUES
    ('cree'),
    ('en_attente'),
    ('EN_VALIDATION_FINANCE'),
    ('EN_VALIDATION_DIRECTION'),
    ('valide'),
    ('VALIDEE'),
    ('refusee'),
    ('REFUSEE'),
    ('annulee'),
    ('ANNULEE_FINANCE');

-- Statuts des offres.
INSERT INTO statut_offres (libelle) VALUES
    ('brouillon'),
    ('publiee'),
    ('cloturee');

-- Statuts des candidatures.
INSERT INTO statut_candidatures (libelle) VALUES
    ('en_attente'),
    ('preselectionne'),
    ('ecarte'),
    ('entretien'),
    ('retenu'),
    ('refuse'),
    ('annule');

-- Statuts des entretiens.
INSERT INTO statut_entretiens (libelle) VALUES
    ('planifie'),
    ('realise'),
    ('annule');

-- Statuts des propositions d’embauche.
INSERT INTO statut_propositions_embauche (libelle) VALUES
    ('en_attente'),
    ('acceptee'),
    ('refusee'),
    ('annulee');

-- Statuts des employés.
INSERT INTO statut_employes (libelle) VALUES
    ('actif'),
    ('inactif'),
    ('demissionnaire');

-- Statuts des contrats.
INSERT INTO statut_contrats (libelle) VALUES
    ('brouillon'),
    ('actif'),
    ('termine'),
    ('rompu');

COMMIT;

-- Données de référence utilisées par les formulaires et les dropdowns.
BEGIN;

INSERT INTO departements (libelle) VALUES
    ('Informatique'),
    ('Ressources humaines'),
    ('Finance'),
    ('Direction générale');

INSERT INTO categorie_postes (libelle) VALUES
    ('Développement'),
    ('Réseau'),
    ('Ressources humaines'),
    ('Finance'),
    ('Administration');

INSERT INTO postes (libelle, id_categorie)
SELECT v.poste, c.id
FROM (VALUES
    ('Développeur Web PHP', 'Développement'),
    ('Développeur Mobile', 'Développement'),
    ('Technicien réseau', 'Réseau'),
    ('Responsable RH', 'Ressources humaines'),
    ('Comptable', 'Finance'),
    ('Assistant administratif', 'Administration')
) AS v(poste, categorie)
JOIN categorie_postes c ON c.libelle = v.categorie;

INSERT INTO poste_departement (id_poste, id_departement)
SELECT p.id, d.id
FROM (VALUES
    ('Développeur Web PHP', 'Informatique'),
    ('Développeur Mobile', 'Informatique'),
    ('Technicien réseau', 'Informatique'),
    ('Responsable RH', 'Ressources humaines'),
    ('Comptable', 'Finance'),
    ('Assistant administratif', 'Direction générale')
) AS v(poste, departement)
JOIN postes p ON p.libelle = v.poste
JOIN departements d ON d.libelle = v.departement;

INSERT INTO type_contrats (libelle) VALUES
    ('CDI'),
    ('CDD'),
    ('Stage'),
    ('Alternance');

-- Budgets mensuels 2026 par département.
INSERT INTO budgets (id_departement, annee, montant_mensuel, montant_engage)
SELECT d.id, 2026, v.montant, 0
FROM (VALUES
    ('Informatique', 15000000::numeric),
    ('Ressources humaines', 6000000::numeric),
    ('Finance', 8000000::numeric),
    ('Direction générale', 5000000::numeric)
) AS v(departement, montant)
JOIN departements d ON d.libelle = v.departement;

-- Grilles salariales par poste et type de contrat.
INSERT INTO grilles_salariales (
    id_poste_departement, montant_min, montant_max, id_type_contrat
)
SELECT pd.id, v.montant_min, v.montant_max, tc.id
FROM (VALUES
    ('Développeur Web PHP', 'Informatique', 'CDI', 1800000::numeric, 3500000::numeric),
    ('Développeur Web PHP', 'Informatique', 'CDD', 1500000::numeric, 3000000::numeric),
    ('Développeur Web PHP', 'Informatique', 'Stage', 400000::numeric, 800000::numeric),
    ('Développeur Web PHP', 'Informatique', 'Alternance', 700000::numeric, 1200000::numeric),
    ('Développeur Mobile', 'Informatique', 'CDI', 2000000::numeric, 4000000::numeric),
    ('Développeur Mobile', 'Informatique', 'CDD', 1700000::numeric, 3500000::numeric),
    ('Technicien réseau', 'Informatique', 'CDI', 1300000::numeric, 2500000::numeric),
    ('Technicien réseau', 'Informatique', 'CDD', 1100000::numeric, 2200000::numeric),
    ('Responsable RH', 'Ressources humaines', 'CDI', 1800000::numeric, 3500000::numeric),
    ('Responsable RH', 'Ressources humaines', 'CDD', 1500000::numeric, 3000000::numeric),
    ('Comptable', 'Finance', 'CDI', 1600000::numeric, 3200000::numeric),
    ('Comptable', 'Finance', 'CDD', 1300000::numeric, 2700000::numeric),
    ('Assistant administratif', 'Direction générale', 'CDI', 1000000::numeric, 2000000::numeric),
    ('Assistant administratif', 'Direction générale', 'CDD', 800000::numeric, 1600000::numeric)
) AS v(poste, departement, contrat, montant_min, montant_max)
JOIN postes p ON p.libelle = v.poste
JOIN departements d ON d.libelle = v.departement
JOIN poste_departement pd ON pd.id_poste = p.id AND pd.id_departement = d.id
JOIN type_contrats tc ON tc.libelle = v.contrat;

COMMIT;

-- Utilisateurs de test pour le workflow RH.
-- Mot de passe commun de développement : password
BEGIN;

INSERT INTO roles (libelle) VALUES
    ('Manager'),
    ('RH'),
    ('Finance'),
    ('DG');

INSERT INTO utilisateurs (login, mdp, id_role, id_departement)
SELECT v.login,
       '$2y$12$hiKj0F2T47M75aXssiUZvubXrD8xB890H.XvrsxN.XuJ37VHY4LYm',
       r.id,
       d.id
FROM (VALUES
    ('manager', 'Manager', 'Informatique'),
    ('rh', 'RH', 'Ressources humaines'),
    ('finance', 'Finance', 'Finance'),
    ('direction', 'DG', 'Direction générale')
) AS v(login, role_libelle, departement)
JOIN roles r ON r.libelle = v.role_libelle
JOIN departements d ON d.libelle = v.departement;

COMMIT;

