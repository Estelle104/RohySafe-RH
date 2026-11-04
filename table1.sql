statut_matrimoniale
    id
    libelle

statut_professionnelle
    id
    libelle 

info_employee
    id
    id_employee
    residence_actuel 
    copie_actuel // chemin vers le pdf de la copie du copie actuel
    date_echeance // date d echeance de la residence actuel
    id_statut_matrimoniale
    id_statut_professionnelle

histo_info_employee
    id
    id_employee
    chemin_residence
    residence
    copie
    id_statut_matrimoniale
    id_statut_professionnelle
    date

societe
    id
    libelle

province
    id
    libelle

societe_province
    id
    id_societe
    id_province

histo_employee
    id
    id_employee
    id_societe
    date

type_conge
    id
    libelle

config_conge
    id
    id_type_conge
    nombre_jour

conge
    id
    id_employee
    date_debut
    date_fin
    id_type_conge


-- 1. Règles
CREATE TABLE regime_travail (
    id                    SERIAL PRIMARY KEY,
    libelle               VARCHAR(100) NOT NULL,      -- ex: 40h/semaine
    heures_hebdo          NUMERIC(4,1) NOT NULL,
    seuil_hs_jour         NUMERIC(4,2),               -- heures/jour avant HS
    seuil_hs_semaine      NUMERIC(4,1),
    tolerance_retard_max  INT NOT NULL DEFAULT 0
);

CREATE TABLE horaire_poste (
    id              SERIAL PRIMARY KEY,
    id_poste        INT NOT NULL REFERENCES poste(id),
    id_regime       INT NOT NULL REFERENCES regime_travail(id),
    libelle         VARCHAR(100),
    heure_debut     TIME NOT NULL,
    heure_fin       TIME NOT NULL,                    -- fin < debut = nuit
    pause_minutes   INT NOT NULL DEFAULT 0
);

-- volume horaire calculé, pas stocké
CREATE VIEW v_horaire_poste AS
SELECT h.*,
  (EXTRACT(EPOCH FROM (
     CASE WHEN heure_fin > heure_debut THEN heure_fin - heure_debut
          ELSE heure_fin - heure_debut + INTERVAL '24 hours' END
  )) / 60 - pause_minutes) / 60 AS volume_horaire_total
FROM horaire_poste h;

-- 2. Planning
CREATE TABLE planning_edt (
    id                SERIAL PRIMARY KEY,
    id_horaire_poste  INT NOT NULL REFERENCES horaire_poste(id),
    date_debut        DATE NOT NULL,
    date_fin          DATE NOT NULL,
    jours_semaine     SMALLINT[] NOT NULL,            -- ISO : 1=lundi ... 7=dimanche
    libelle           VARCHAR(200),
    CHECK (date_fin >= date_debut)
);

CREATE TABLE planning_edt_emp (
    id               SERIAL PRIMARY KEY,
    id_planning_edt  INT NOT NULL REFERENCES planning_edt(id),
    id_employee      INT NOT NULL REFERENCES employee(id),
    UNIQUE (id_planning_edt, id_employee)
);

CREATE TABLE type_pointage (
    id               SERIAL PRIMARY KEY,
    libelle          VARCHAR(20) NOT NULL UNIQUE
                     CHECK (libelle IN ('ENTREE','SORTIE','PAUSE_DEBUT','PAUSE_FIN'))
);

-- 3. Pointages bruts (jamais modifiés)
CREATE TABLE pointage (
    id               BIGSERIAL PRIMARY KEY,
    id_employee      INT NOT NULL REFERENCES employee(id),
    date_heure       TIMESTAMP NOT NULL,
    id_type_pointage INT NOT NULL REFERENCES type_pointage(id),
    cree_par         INT REFERENCES employee(id),     -- renseigné si saisie manuelle
    created_at       TIMESTAMP NOT NULL DEFAULT now()
);
CREATE INDEX idx_pointage_emp_date ON pointage(id_employee, date_heure);

-- 4. Résultat calculé (recalculable)
-- CREATE TABLE journee_travail (
--     id                     BIGSERIAL PRIMARY KEY,
--     id_employee            INT NOT NULL REFERENCES employee(id),
--     date_jour              DATE NOT NULL,
--     id_planning_edt_emp    INT REFERENCES planning_edt_emp(id),  -- NULL = hors planning
--     minutes_prevues        INT NOT NULL DEFAULT 0,
--     minutes_travaillees    INT NOT NULL DEFAULT 0,
--     minutes_retard         INT NOT NULL DEFAULT 0,
--     minutes_depart_anticipe INT NOT NULL DEFAULT 0,
--     minutes_hs             INT NOT NULL DEFAULT 0,
--     statut                 VARCHAR(15) NOT NULL DEFAULT 'BROUILLON'
--                            CHECK (statut IN ('BROUILLON','VALIDE','CLOTURE')),
--     UNIQUE (id_employee, date_jour)
-- );

CREATE TABLE conf_heure_sup(
    id                 SERIAL PRIMARY KEY,
    taux_majoration    NUMERIC(4,2) NOT NULL DEFAULT 1.30 -- taux par rapport aux heures normales
);

CREATE TABLE statut_heure_sup(
    id SERIAL PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL
);

CREATE TABLE histo_demande_heure_sup(
    id                 SERIAL PRIMARY KEY,
    id_statut          INT NOT NULL REFERENCES statut_heure_sup(id),
    date               DATE NOT NULL,
    id_utilisateur     INT NOT NULL REFERENCES utilisateur(id),
    id_heure_supplementaire INT NOT NULL REFERENCES heure_supplementaire(id)
);

CREATE TABLE heure_supplementaire (
    id                 SERIAL PRIMARY KEY,
    id_employer        INT NOT NULL REFERENCES employee(id),
    heure_debut        TIMESTAMP NOT NULL,
    heure_fin          TIMESTAMP NOT NULL,
    date_debut         DATE NOT NULL,
    date_fin           DATE NOT NULL
);

-- 5. Anomalies et traçabilité
CREATE TABLE anomalie_pointage (
    id            SERIAL PRIMARY KEY,
    id_employee   INT NOT NULL REFERENCES employee(id),
    date_jour     DATE NOT NULL,
    id_pointage   BIGINT REFERENCES pointage(id),
    type          VARCHAR(30) NOT NULL
                  CHECK (type IN ('SORTIE_MANQUANTE','ENTREE_MANQUANTE','DOUBLON',
                                  'HORS_PLANNING','ECART_EXCESSIF')),
    statut        VARCHAR(15) NOT NULL DEFAULT 'A_TRAITER'
                  CHECK (statut IN ('A_TRAITER','CORRIGEE','JUSTIFIEE')),
    commentaire   TEXT,
    traite_par    INT REFERENCES employee(id),
    traite_le     TIMESTAMP
);

CREATE TABLE correction_pointage (
    id              SERIAL PRIMARY KEY,
    id_pointage     BIGINT NOT NULL REFERENCES pointage(id),
    ancienne_valeur TIMESTAMP,
    nouvelle_valeur TIMESTAMP,
    motif           TEXT NOT NULL,
    corrige_par     INT NOT NULL REFERENCES employee(id),
    corrige_le      TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE type_conge(
    id      SERIAL PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL
);

CREATE TABLE config_conge(
    id          SERIAL PRIMARY KEY,
    id_type_conge INT NOT NULL REFERENCES type_conge(id),
    nombre_jour INT NOT NULL
);

CREATE TABLE conge(
    id SERIAL PRIMARY KEY,
    id_employee INT NOT NULL REFERENCES employee(id),
    date_debut  DATE NOT NULL,
    date_fin    DATE NOT NULL,
    id_type_conge INT NOT NULL REFERENCES type_conge(id)
);

CREATE TABLE statut_conge(
    id      SERIAL PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL
);

CREATE TABLE histo_demande_conge(
    id                 SERIAL PRIMARY KEY,
    id_statut          INT NOT NULL REFERENCES statut_conge(id),
    date               DATE NOT NULL,
    id_utilisateur     INT NOT NULL REFERENCES utilisateur(id),
    id_conge            INT NOT NULL REFERENCES conge(id)
);

CREATE TABLE absence(
    id SERIAL PRIMARY KEY,
    id_employee INT NOT NULL REFERENCES employee(id),
    date DATE NOT NULL,
    motif       TEXT NOT NULL
);