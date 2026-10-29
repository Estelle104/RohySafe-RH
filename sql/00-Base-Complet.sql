-- Arsenoh

CREATE TABLE departements (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE roles (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE utilisateurs (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    login VARCHAR(100) NOT NULL UNIQUE,
    mdp TEXT NOT NULL,
    id_role BIGINT NOT NULL REFERENCES roles(id),
    -- renseigné pour les managers de département, NULL pour DG / RH / Finance
    id_departement BIGINT REFERENCES departements(id)
);

CREATE TABLE categorie_postes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE postes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(120) NOT NULL,
    id_categorie BIGINT NOT NULL REFERENCES categorie_postes(id),
    UNIQUE (id_categorie, libelle)
);

CREATE TABLE poste_departement (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES postes(id),
    id_departement BIGINT NOT NULL REFERENCES departements(id),

    UNIQUE (id_poste, id_departement)
);

-- CDD, CDI, CE
CREATE TABLE type_contrats (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL UNIQUE
);

-- Demande cree, valide, en_attente, refusee, annulee
CREATE TABLE statut_demandes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE demandes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_poste_departement BIGINT NOT NULL REFERENCES poste_departement(id),
    id_createur BIGINT NOT NULL REFERENCES utilisateurs(id),
    id_utilisateur_actuel BIGINT REFERENCES utilisateurs(id),
    id_statut_actuel BIGINT NOT NULL REFERENCES statut_demandes(id),
    nombre_demande INTEGER NOT NULL CHECK (nombre_demande > 0),
    id_type_contrat BIGINT NOT NULL REFERENCES type_contrats(id),
    date_prise_poste DATE,
    salaire_propose NUMERIC(12, 2) NOT NULL CHECK (salaire_propose >= 0)
);

CREATE TABLE historique_demandes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_demande BIGINT NOT NULL REFERENCES demandes(id),
    id_statut_demande BIGINT NOT NULL REFERENCES statut_demandes(id),
    id_utilisateur BIGINT REFERENCES utilisateurs(id),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    salaire_propose NUMERIC(12, 2) NOT NULL CHECK (salaire_propose >= 0),
    remarque TEXT
);

CREATE TABLE grilles_salariales (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_poste_departement BIGINT NOT NULL REFERENCES poste_departement(id),
    montant_min NUMERIC(12, 2) NOT NULL CHECK (montant_min >= 0),
    montant_max NUMERIC(12, 2) NOT NULL,
    id_type_contrat BIGINT NOT NULL REFERENCES type_contrats(id),
    CHECK (montant_max >= montant_min),
    UNIQUE (id_poste_departement, id_type_contrat)
);

CREATE TABLE histo_grille_salariales (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_grille BIGINT NOT NULL REFERENCES grilles_salariales(id),
    montant_min NUMERIC(12, 2) NOT NULL CHECK (montant_min >= 0),
    montant_max NUMERIC(12, 2) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE,
    CHECK (montant_max >= montant_min),
    CHECK (
        date_fin IS NULL
        OR date_fin >= date_debut
    )
);

CREATE TABLE budgets (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_departement BIGINT NOT NULL REFERENCES departements(id),
    annee INTEGER NOT NULL CHECK (
        annee BETWEEN 2000 AND 2200
    ),
    montant_mensuel NUMERIC(14, 2) NOT NULL CHECK (montant_mensuel >= 0),
    montant_engage NUMERIC(14, 2) NOT NULL DEFAULT 0 CHECK (montant_engage >= 0),
    UNIQUE (id_departement, annee)
);

CREATE TABLE statut_offres (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE offres (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_demande BIGINT NOT NULL REFERENCES demandes(id),
    titre VARCHAR(150) NOT NULL,
    descriptions TEXT,
    date_publication DATE,
    date_limite DATE,
    id_statut BIGINT REFERENCES statut_offres(id),
    CHECK (
        date_limite IS NULL
        OR date_publication IS NULL
        OR date_limite >= date_publication
    )
);

CREATE TABLE candidats (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telephone VARCHAR(30),
    ville VARCHAR(100),
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (email)
);

-- selection
CREATE TABLE statut_candidatures (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE candidatures (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidat BIGINT NOT NULL REFERENCES candidats(id),
    id_offre BIGINT NOT NULL REFERENCES offres(id),
    date_candidature TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_statut_actuel BIGINT NOT NULL REFERENCES statut_candidatures(id),
    UNIQUE (id_candidat, id_offre)
);

CREATE TABLE historique_candidature_status (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidatures(id),
    id_statut_candidature BIGINT NOT NULL
        REFERENCES statut_candidatures(id),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE experiences_candidat (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidat BIGINT NOT NULL REFERENCES candidats(id),
    id_poste BIGINT NOT NULL REFERENCES postes(id),
    date_debut DATE NOT NULL,
    date_fin DATE,
    descriptions TEXT,
    CHECK (date_fin IS NULL OR date_fin >= date_debut)
);

CREATE TABLE formations_candidat (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidat BIGINT NOT NULL REFERENCES candidats(id),
    diplome VARCHAR(150) NOT NULL,
    etablissement VARCHAR(150),
    date_debut DATE,
    date_fin DATE,
    description TEXT,

    CHECK (
        date_debut IS NULL OR date_fin IS NULL
        OR date_fin >= date_debut
    )
);

CREATE TABLE competences (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(120) NOT NULL,
    categorie VARCHAR(80),

    UNIQUE (libelle, categorie)
);

CREATE TABLE competences_candidat (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidat BIGINT NOT NULL REFERENCES candidats(id),
    id_competence BIGINT NOT NULL REFERENCES competences(id),
    niveau NUMERIC(5,2) CHECK (niveau BETWEEN 0 AND 20),

    UNIQUE (id_candidat, id_competence)
);

CREATE TABLE softskills (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE exigences_experience (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_poste_departement BIGINT NOT NULL REFERENCES poste_departement(id),
    id_poste_experience BIGINT NOT NULL REFERENCES postes(id),
    duree_min INTEGER NOT NULL DEFAULT 0 CHECK (duree_min >= 0),
    duree_max INTEGER,
    obligatoire BOOLEAN NOT NULL DEFAULT FALSE,

    CHECK (duree_max IS NULL OR duree_max >= duree_min)
);

-- anaovana anle calcul automatique
CREATE TABLE criteres_poste (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES postes(id),
    id_competence BIGINT NOT NULL REFERENCES competences(id),
    note_minimale NUMERIC(5,2) CHECK (note_minimale BETWEEN 0 AND 20),
    obligatoire BOOLEAN NOT NULL DEFAULT FALSE,

    UNIQUE (id_poste, id_competence)
);

CREATE TABLE criteres_softskill (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES postes(id),
    id_softskill BIGINT NOT NULL REFERENCES softskills(id),
    note_minimale NUMERIC(5,2) CHECK (note_minimale BETWEEN 0 AND 20),
    obligatoire BOOLEAN NOT NULL DEFAULT FALSE,

    UNIQUE (id_poste, id_softskill)
);

CREATE TABLE statut_entretiens (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE entretiens (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidatures(id),
    date_entretien TIMESTAMP NOT NULL,
    lieu_entretien VARCHAR(150),
    id_statut_actuel BIGINT REFERENCES statut_entretiens(id),
    commentaire TEXT
);

CREATE TABLE historique_entretiens_statut (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_entretien BIGINT NOT NULL REFERENCES entretiens(id),
    id_statut_entretien BIGINT NOT NULL REFERENCES statut_entretiens(id),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


-- Ity ilay fenoina rehefa entretien amzay
CREATE TABLE evaluations_candidat (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_entretien BIGINT NOT NULL REFERENCES entretiens(id),
    id_softskill BIGINT NOT NULL REFERENCES softskills(id),
    id_evaluateur BIGINT NOT NULL REFERENCES utilisateurs(id),
    note NUMERIC(5,2) NOT NULL CHECK (note BETWEEN 0 AND 20),
    commentaire TEXT,
    date_evaluation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE (id_entretien, id_softskill, id_evaluateur)
);

-- validee_final, refusee, annulee, en_attente, en_negociation, refusee_candidat, cree (eken'ny dg fa tsy hay na mety am candidat)
CREATE TABLE statut_propositions_embauche (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

-- rehefa avy preselection, dia alefa any amin'ny candidat ny proposition d'embauche
CREATE TABLE propositions_embauche (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidatures(id),
    date_proposition TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_statut_actuel BIGINT NOT NULL REFERENCES statut_propositions_embauche(id)
);

CREATE TABLE historique_propositions (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_proposition BIGINT NOT NULL REFERENCES propositions_embauche(id),
    salaire_propose NUMERIC(12,2) CHECK (salaire_propose >= 0),
    id_type_contrat BIGINT REFERENCES type_contrats(id),
    id_statut_proposition BIGINT NOT NULL REFERENCES statut_propositions_embauche(id),
    motif TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- actif, conge, suspendu, demissionne, licencie, retraite
CREATE TABLE statut_employes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE employes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidat BIGINT NOT NULL UNIQUE REFERENCES candidats(id),
    matricule VARCHAR(50) NOT NULL UNIQUE,
    date_embauche DATE NOT NULL,
    id_statut_actuel BIGINT REFERENCES statut_employes(id)
);


CREATE TABLE historique_employes_statut (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    id_statut_employe BIGINT NOT NULL REFERENCES statut_employes(id),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- signe, rompu, cree
CREATE TABLE statut_contrats (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE contrats (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    id_type_contrat BIGINT NOT NULL REFERENCES type_contrats(id),
    date_debut DATE NOT NULL,
    date_fin DATE,
    salaire NUMERIC(12,2) NOT NULL CHECK (salaire >= 0),
    id_statut_actuel BIGINT REFERENCES statut_contrats(id),
    date_signature DATE,

    CHECK (date_fin IS NULL OR date_fin >= date_debut)
);

CREATE TABLE historique_contrats_statut (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_contrat BIGINT NOT NULL REFERENCES contrats(id),
    id_statut_contrat BIGINT NOT NULL REFERENCES statut_contrats(id),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Ajouter les colonnes login et mdp à candidats
ALTER TABLE candidats
ADD COLUMN login VARCHAR(100) UNIQUE;

ALTER TABLE candidats
ADD COLUMN mdp TEXT NOT NULL;

-- Créer la table type_documents
CREATE TABLE type_documents (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

-- Créer la table documents_candidat
CREATE TABLE documents_candidat (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidat BIGINT NOT NULL REFERENCES candidats(id),
    id_type_document BIGINT NOT NULL REFERENCES type_documents(id),
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier TEXT NOT NULL,
    date_ajout TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Rendre login et mdp obligatoires après avoir renseigné
-- les valeurs pour les candidats déjà existants
ALTER TABLE candidats
ALTER COLUMN login SET NOT NULL;

ALTER TABLE candidats
ALTER COLUMN mdp SET NOT NULL;



BEGIN;

CREATE TABLE IF NOT EXISTS experiences_candidature (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidatures(id) ON DELETE CASCADE,
    id_exigence BIGINT NOT NULL REFERENCES exigences_experience(id) ON DELETE RESTRICT,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    duree_jours INTEGER GENERATED ALWAYS AS (date_fin - date_debut) STORED,
    CHECK (date_fin >= date_debut),
    UNIQUE (id_candidature, id_exigence)
);

CREATE INDEX IF NOT EXISTS idx_experiences_candidature_candidature
    ON experiences_candidature (id_candidature);

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'exigences_experience_poste_unique'
    ) THEN
        ALTER TABLE exigences_experience
            ADD CONSTRAINT exigences_experience_poste_unique
            UNIQUE (id_poste_departement, id_poste_experience);
    END IF;
END
$$;

COMMIT;


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

-- Une seule évaluation par candidature, saisie par le jury après l'entretien.
-- Les anciennes évaluations de test sont volontairement réinitialisées.

DROP TABLE IF EXISTS evaluations_candidat CASCADE;

CREATE TABLE evaluations_candidat (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidature BIGINT NOT NULL UNIQUE REFERENCES candidatures(id) ON DELETE CASCADE,
    note NUMERIC(5,2) NOT NULL CHECK (note BETWEEN 0 AND 20),
    remarques TEXT,
    date_evaluation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);


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


INSERT INTO competences (libelle, categorie) VALUES
-- Compétences informatiques
('Java', 'Informatique'),
('Python', 'Informatique'),
('Développement Web', 'Informatique'),
('SQL', 'Informatique'),
('Administration de bases de données', 'Informatique'),
('Réseaux informatiques', 'Informatique'),
('Cybersécurité', 'Informatique'),
('Git et GitHub', 'Informatique'),

-- Compétences en gestion
('Gestion de projet', 'Management'),
('Leadership', 'Management'),
('Gestion du temps', 'Management'),
('Prise de décision', 'Management'),

-- Compétences en communication
('Communication orale', 'Communication'),
('Rédaction professionnelle', 'Communication'),
('Travail en équipe', 'Communication'),
('Négociation', 'Communication'),

-- Compétences administratives et financières
('Comptabilité générale', 'Finance'),
('Gestion budgétaire', 'Finance'),
('Analyse financière', 'Finance'),
('Gestion administrative', 'Administration'),

-- Compétences linguistiques
('Français', 'Langues'),
('Anglais', 'Langues'),
('Malagasy', 'Langues'),

-- Compétences techniques
('Maintenance informatique', 'Technique'),
('Analyse de données', 'Technique'),
('Résolution de problèmes', 'Technique');

INSERT INTO statut_offres(libelle) VALUES('En attente');