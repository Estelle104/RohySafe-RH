
-- =========================================================
-- BASE DE DONNEES SIRH - POSTGRESQL
-- =========================================================

BEGIN;

-- =========================================================
-- 1. ORGANISATION ET UTILISATEURS
-- =========================================================


CREATE TABLE departements (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL,
    UNIQUE (id_entreprise, libelle)
);

CREATE TABLE roles (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE utilisateurs (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    login VARCHAR(100) NOT NULL UNIQUE,
    mdp TEXT NOT NULL,
    id_role BIGINT NOT NULL REFERENCES roles(id)
);

CREATE TABLE categories (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE postes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(120) NOT NULL,
    id_categorie BIGINT NOT NULL REFERENCES categories(id),
    UNIQUE (id_categorie, libelle)
);

-- =========================================================
-- 2. TYPES DE CONTRAT
-- Créés ici car plusieurs tables en dépendent.
-- =========================================================

CREATE TABLE type_contrats (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL UNIQUE
);

-- =========================================================
-- 3. STATUTS DES DEMANDES
-- =========================================================

CREATE TABLE statut_demandes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

-- =========================================================
-- 4. DEMANDES DE RECRUTEMENT ET VALIDATIONS
-- =========================================================

CREATE TABLE demandes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_departement BIGINT NOT NULL REFERENCES departements(id),
    id_poste BIGINT NOT NULL REFERENCES postes(id),
    nombre_poste INTEGER NOT NULL CHECK (nombre_poste > 0),
    id_type_contrat BIGINT NOT NULL REFERENCES type_contrats(id),
    date_prise_poste DATE,
    id_statut_actuel BIGINT REFERENCES statut_demandes(id)
);

CREATE TABLE historique_demande (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_demande BIGINT NOT NULL REFERENCES demandes(id),
    id_statut_demande BIGINT NOT NULL REFERENCES statut_demandes(id),
    id_utilisateur BIGINT REFERENCES utilisateurs(id),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    remarque TEXT
);

CREATE TABLE validations_demande (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_demande BIGINT NOT NULL REFERENCES demandes(id),
    id_utilisateur BIGINT NOT NULL REFERENCES utilisateurs(id),
    niveau_validation VARCHAR(50) NOT NULL,
    decision VARCHAR(20) NOT NULL
        CHECK (decision IN ('EN_ATTENTE', 'APPROUVEE', 'REFUSEE')),
    motif TEXT,
    date_decision TIMESTAMP
);

-- =========================================================
-- 5. GRILLES SALARIALES ET BUDGET
-- =========================================================

CREATE TABLE grilles_salariales (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES postes(id),
    id_departement BIGINT REFERENCES departements(id),
    montant_min NUMERIC(12,2) NOT NULL CHECK (montant_min >= 0),
    montant_max NUMERIC(12,2) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE,
    id_type_contrat BIGINT NOT NULL REFERENCES type_contrats(id),

    CHECK (montant_max >= montant_min),
    CHECK (date_fin IS NULL OR date_fin >= date_debut)
);

CREATE TABLE budgets (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_departement BIGINT NOT NULL REFERENCES departements(id),
    annee INTEGER NOT NULL CHECK (annee BETWEEN 2000 AND 2200),
    montant_mensuel NUMERIC(14,2) NOT NULL CHECK (montant_mensuel >= 0),
    montant_engage NUMERIC(14,2) NOT NULL DEFAULT 0
        CHECK (montant_engage >= 0),

    UNIQUE (id_departement, annee)
);

CREATE TABLE histo_salaire_demandes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_demande BIGINT NOT NULL REFERENCES demandes(id),
    montant NUMERIC(12,2) NOT NULL CHECK (montant >= 0),
    id_utilisateur BIGINT NOT NULL REFERENCES utilisateurs(id),
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motif TEXT,
    statut_validation VARCHAR(30) NOT NULL DEFAULT 'EN_ATTENTE'
        CHECK (statut_validation IN (
            'EN_ATTENTE', 'APPROUVE', 'REFUSE'
        ))
);

CREATE TABLE negociation_candidats (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    type_contrats BIGINT,
    salaire NUMERIC(12,2),
    id_candidat BIGINT
);

-- =========================================================
-- 6. OFFRES ET CANDIDATS
-- =========================================================

CREATE TABLE offres (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_demande BIGINT NOT NULL REFERENCES demandes(id),
    titre VARCHAR(150) NOT NULL,
    description TEXT,
    date_publication DATE,
    date_limite DATE,
    statut VARCHAR(30) NOT NULL DEFAULT 'BROUILLON'
        CHECK (statut IN (
            'BROUILLON', 'PUBLIEE', 'SUSPENDUE', 'CLOTUREE', 'ANNULEE'
        )),

    CHECK (
        date_limite IS NULL OR date_publication IS NULL
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
    date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE statut_candidatures (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE candidatures (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidat BIGINT NOT NULL REFERENCES candidats(id),
    id_offre BIGINT NOT NULL REFERENCES offres(id),
    date_candidature TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_statut_actuel BIGINT REFERENCES statut_candidatures(id),

    UNIQUE (id_candidat, id_offre)
);

CREATE TABLE historique_candidature_status (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidatures(id),
    id_statut_candidature BIGINT NOT NULL
        REFERENCES statut_candidatures(id),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- 7. CV ET COMPETENCES
-- =========================================================

CREATE TABLE experiences_candidat (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidat BIGINT NOT NULL REFERENCES candidats(id),
    id_poste BIGINT NOT NULL REFERENCES postes(id),
    date_debut DATE NOT NULL,
    date_fin DATE,
    description TEXT,

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
    niveau VARCHAR(50),

    UNIQUE (id_candidat, id_competence)
);

-- =========================================================
-- 8. CRITERES ET PRESELECTION
-- =========================================================

CREATE TABLE softskills (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE poste_departement (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES postes(id),
    id_departement BIGINT NOT NULL REFERENCES departements(id)
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


CREATE TABLE criteres_poste (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES postes(id),
    id_competence BIGINT REFERENCES competences(id),
    -- id_softskill BIGINT REFERENCES softskills(id)
    poids NUMERIC(5,2) NOT NULL DEFAULT 1 CHECK (poids >= 0),
    note_minimale NUMERIC(5,2),
    obligatoire BOOLEAN NOT NULL DEFAULT FALSE,

    CHECK (
        id_competence IS NOT NULL
    )
);

-- CREATE TABLE resultats_preselection (
--     id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
--     id_candidature BIGINT NOT NULL REFERENCES candidatures(id),
--     score NUMERIC(6,2) NOT NULL CHECK (score >= 0),
--     resultat VARCHAR(30) NOT NULL
--         CHECK (resultat IN ('RETENU', 'NON_RETENU', 'A_VERIFIER')),
--     date_calcul TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     details JSONB,

--     UNIQUE (id_candidature, date_calcul)
-- );

-- CREATE TABLE historique_classement (
--     id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
--     id_candidature BIGINT NOT NULL REFERENCES candidatures(id),
--     rang INTEGER NOT NULL CHECK (rang > 0),
--     score NUMERIC(6,2) NOT NULL CHECK (score >= 0),
--     date_classement TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
-- );

-- =========================================================
-- 9. ENTRETIENS ET EVALUATIONS
-- =========================================================

CREATE TABLE statut_entretiens (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE entretiens (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidatures(id),
    date_entretien TIMESTAMP NOT NULL,
    id_statut_actuel BIGINT REFERENCES statut_entretiens(id),
    commentaire TEXT
);

CREATE TABLE historique_entretiens_statut (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_entretien BIGINT NOT NULL REFERENCES entretiens(id),
    id_statut_entretien BIGINT NOT NULL REFERENCES statut_entretiens(id),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE evaluations_candidat (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_entretien BIGINT NOT NULL REFERENCES entretiens(id),
    id_critere BIGINT NOT NULL REFERENCES criteres_poste(id),
    id_evaluateur BIGINT NOT NULL REFERENCES utilisateurs(id),
    note NUMERIC(5,2) NOT NULL CHECK (note BETWEEN 0 AND 20),
    commentaire TEXT,
    date_evaluation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE (id_entretien, id_critere, id_evaluateur)
);

-- =========================================================
-- 10. PROPOSITIONS D'EMBAUCHE ET NEGOCIATIONS
-- =========================================================

CREATE TABLE statut_propositions_embauche (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE propositions_embauche (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidatures(id),
    salaire_propose NUMERIC(12,2) NOT NULL CHECK (salaire_propose >= 0),
    id_type_contrat BIGINT NOT NULL REFERENCES type_contrats(id),
    date_debut_souhaitee DATE,
    date_proposition TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_statut_actuel BIGINT REFERENCES statut_propositions_embauche(id)
);

CREATE TABLE historique_propositions_embauche_statut (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_proposition BIGINT NOT NULL REFERENCES propositions_embauche(id),
    id_statut_proposition BIGINT NOT NULL
        REFERENCES statut_propositions_embauche(id),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE historique_propositions (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_proposition BIGINT NOT NULL REFERENCES propositions_embauche(id),
    salaire_demande NUMERIC(12,2) CHECK (salaire_demande >= 0),
    id_type_contrat_demande BIGINT REFERENCES type_contrats(id),
    reponse_candidat VARCHAR(30) NOT NULL
        CHECK (reponse_candidat IN (
            'ACCEPTE', 'REFUSE', 'CONTRE_PROPOSITION', 'EN_ATTENTE'
        )),
    motif TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur BIGINT REFERENCES utilisateurs(id)
);

-- =========================================================
-- 11. DOCUMENTS CANDIDAT ET CONTRATS
-- =========================================================

CREATE TABLE documents_candidat (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidat BIGINT NOT NULL REFERENCES candidats(id),
    type_document VARCHAR(100) NOT NULL,
    chemin_fichier TEXT NOT NULL,
    date_depot TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_verification TIMESTAMP,
    commentaire TEXT,
    statut_verification VARCHAR(30) NOT NULL DEFAULT 'EN_ATTENTE'
        CHECK (statut_verification IN (
            'EN_ATTENTE', 'ACCEPTE', 'A_CORRIGER', 'REFUSE'
        ))
);

CREATE TABLE statut_contrats (
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

-- La table statut_employes est créée ci-dessous.
-- Pour PostgreSQL, toutes les tables référencées doivent exister
-- au moment de la création de la contrainte FK.



-- =========================================================
-- 11. DOCUMENTS CANDIDAT, EMPLOYES ET CONTRATS
-- Si tu exécutes le script complet, crée statut_employes
-- avant employes.
-- =========================================================

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

CREATE TABLE historique_employes_statut (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    id_statut_employe BIGINT NOT NULL REFERENCES statut_employes(id),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

COMMIT;
