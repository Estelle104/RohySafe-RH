-- ============================================================
-- MODULE POINTAGE, PLANNING ET ANOMALIES - SCRIPT CONSOLIDE
-- Prérequis : 00-Base-Complet.sql
-- ============================================================

ALTER TABLE employes
    ADD COLUMN IF NOT EXISTS id_poste_departement BIGINT;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'fk_employes_poste_departement'
    ) THEN
        ALTER TABLE employes
            ADD CONSTRAINT fk_employes_poste_departement
            FOREIGN KEY (id_poste_departement)
            REFERENCES poste_departement(id);
    END IF;
END $$;

CREATE TABLE IF NOT EXISTS regime_travails (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL,
    heures_hebdo NUMERIC(5,2) NOT NULL CHECK (heures_hebdo > 0),
    seuil_hs_jour NUMERIC(5,2),
    seuil_hs_semaine NUMERIC(5,2),
    tolerance_retard_max INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS horaire_postes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES postes(id),
    id_regime BIGINT NOT NULL REFERENCES regime_travails(id),
    libelle VARCHAR(100),
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    pause_minutes INT NOT NULL DEFAULT 0 CHECK (pause_minutes >= 0)
);

CREATE TABLE IF NOT EXISTS planning_edts (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    jours_semaine SMALLINT[] NOT NULL,
    libelle VARCHAR(200),
    CHECK (date_fin >= date_debut)
);

ALTER TABLE planning_edts
    DROP COLUMN IF EXISTS id_horaire_poste;

CREATE TABLE IF NOT EXISTS planning_edt_employes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_planning_edt BIGINT NOT NULL REFERENCES planning_edts(id),
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    UNIQUE (id_planning_edt, id_employe)
);

CREATE TABLE IF NOT EXISTS type_pointages (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(20) NOT NULL UNIQUE
        CHECK (libelle IN ('ENTREE', 'SORTIE', 'PAUSE_DEBUT', 'PAUSE_FIN'))
);

INSERT INTO type_pointages (libelle)
VALUES ('ENTREE'), ('SORTIE'), ('PAUSE_DEBUT'), ('PAUSE_FIN')
ON CONFLICT (libelle) DO NOTHING;

CREATE TABLE IF NOT EXISTS pointages (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    date_heure TIMESTAMP NOT NULL,
    id_type_pointage BIGINT NOT NULL REFERENCES type_pointages(id),
    cree_par BIGINT REFERENCES utilisateurs(id),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_pointage_emp_date
    ON pointages (id_employe, date_heure);

CREATE UNIQUE INDEX IF NOT EXISTS uq_pointage_employe_date_type
    ON pointages (id_employe, date_heure, id_type_pointage);

CREATE TABLE IF NOT EXISTS statut_journee_travails (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(15) NOT NULL UNIQUE
        CHECK (libelle IN ('CREE', 'VALIDE', 'CLOTURE'))
);

INSERT INTO statut_journee_travails (libelle)
VALUES ('CREE'), ('VALIDE'), ('CLOTURE')
ON CONFLICT (libelle) DO NOTHING;

CREATE TABLE IF NOT EXISTS journee_travails (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    date_jour DATE NOT NULL,
    id_planning_edt_employes BIGINT REFERENCES planning_edt_employes(id),
    minutes_prevues INT NOT NULL DEFAULT 0,
    minutes_travaillees INT NOT NULL DEFAULT 0,
    minutes_retard INT NOT NULL DEFAULT 0,
    minutes_depart_anticipe INT NOT NULL DEFAULT 0,
    minutes_hs INT NOT NULL DEFAULT 0,
    id_statut BIGINT NOT NULL REFERENCES statut_journee_travails(id),
    UNIQUE (id_employe, date_jour)
);

CREATE TABLE IF NOT EXISTS heure_supplementaires (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    heure_debut TIMESTAMP NOT NULL,
    heure_fin TIMESTAMP NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    id_planning_edt_employes BIGINT REFERENCES planning_edt_employes(id),
    CHECK (heure_fin > heure_debut)
);

CREATE TABLE IF NOT EXISTS type_anomalies (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(30) NOT NULL UNIQUE
);

INSERT INTO type_anomalies (libelle)
VALUES
    ('SORTIE_MANQUANTE'),
    ('ENTREE_MANQUANTE'),
    ('DOUBLON'),
    ('HORS_PLANNING'),
    ('ECART_EXCESSIF')
ON CONFLICT (libelle) DO NOTHING;

CREATE TABLE IF NOT EXISTS statut_anomalie_pointages (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(15) NOT NULL UNIQUE
);

INSERT INTO statut_anomalie_pointages (libelle)
VALUES ('A_TRAITER'), ('CORRIGEE'), ('JUSTIFIEE')
ON CONFLICT (libelle) DO NOTHING;

CREATE TABLE IF NOT EXISTS anomalie_pointages (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    date_jour DATE NOT NULL,
    id_pointage BIGINT REFERENCES pointages(id),
    id_type_anomalie BIGINT NOT NULL REFERENCES type_anomalies(id),
    id_statut BIGINT NOT NULL REFERENCES statut_anomalie_pointages(id),
    commentaire TEXT,
    traite_par BIGINT REFERENCES utilisateurs(id),
    traite_le TIMESTAMP
);

CREATE TABLE IF NOT EXISTS correction_pointages (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_pointage BIGINT NOT NULL REFERENCES pointages(id),
    ancienne_valeur TIMESTAMP,
    nouvelle_valeur TIMESTAMP,
    motif TEXT NOT NULL,
    corrige_par BIGINT NOT NULL REFERENCES utilisateurs(id),
    corrige_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
