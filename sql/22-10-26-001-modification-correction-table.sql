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
    id_role BIGINT NOT NULL REFERENCES roles(id)
    -- renseigné pour les managers de département, NULL pour DG / RH / Finance
    -- id_departement BIGINT REFERENCES departements(id)
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