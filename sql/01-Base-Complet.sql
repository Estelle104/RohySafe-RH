
-- ============================================================
-- 1. INFORMATIONS PERSONNELLES DES EMPLOYES
-- ============================================================

CREATE TABLE statut_matrimoniale (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE statut_professionnelle (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE info_employee (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employee BIGINT NOT NULL UNIQUE REFERENCES employee(id),
    residence_actuel VARCHAR(255),
    copie_actuel TEXT, -- Chemin vers le PDF
    date_echeance DATE,
    id_statut_matrimoniale BIGINT REFERENCES statut_matrimoniale(id),
    id_statut_professionnelle BIGINT REFERENCES statut_professionnelle(id)
);

-- Historique des informations personnelles
CREATE TABLE histo_info_employee (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employee BIGINT NOT NULL REFERENCES employee(id),
    chemin_residence TEXT,
    residence VARCHAR(255),
    copie TEXT,
    id_statut_matrimoniale BIGINT REFERENCES statut_matrimoniale(id),
    id_statut_professionnelle BIGINT REFERENCES statut_professionnelle(id),
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_histo_info_employee
ON histo_info_employee(id_employee, date);


-- ============================================================
-- 2. SOCIETES ET PROVINCES
-- ============================================================

CREATE TABLE societe (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE province (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE societe_province (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_societe BIGINT NOT NULL REFERENCES societe(id),
    id_province BIGINT NOT NULL REFERENCES province(id),
    UNIQUE (id_societe, id_province)
);

-- Historique de l'affectation d'un employé à une société
CREATE TABLE histo_employee (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employee BIGINT NOT NULL REFERENCES employee(id),
    id_societe BIGINT NOT NULL REFERENCES societe(id),
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_histo_employee
ON histo_employee(id_employee, date);


-- ============================================================
-- 3. GESTION DES CONGES
-- ============================================================

CREATE TABLE type_conge (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE config_conge (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_type_conge BIGINT NOT NULL REFERENCES type_conge(id),
    nombre_jour NUMERIC(6,2) NOT NULL CHECK (nombre_jour >= 0),
    UNIQUE (id_type_conge)
);

CREATE TABLE conge (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employee BIGINT NOT NULL REFERENCES employee(id),
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    id_type_conge BIGINT NOT NULL REFERENCES type_conge(id),
    CHECK (date_fin >= date_debut)
);

CREATE INDEX idx_conge_employee
ON conge(id_employee, date_debut);

CREATE TABLE statut_conge (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE histo_demande_conge (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_statut BIGINT NOT NULL REFERENCES statut_conge(id),
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur BIGINT NOT NULL REFERENCES utilisateur(id),
    id_conge BIGINT NOT NULL REFERENCES conge(id)
);

CREATE INDEX idx_histo_demande_conge
ON histo_demande_conge(id_conge, date);

-- Absences non assimilées à un congé
CREATE TABLE absence (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employee BIGINT NOT NULL REFERENCES employee(id),
    date DATE NOT NULL,
    motif TEXT NOT NULL
);

CREATE INDEX idx_absence_employee
ON absence(id_employee, date);




-- ============================================================
-- 4. REGIMES DE TRAVAIL ET HORAIRES
-- ============================================================

CREATE TABLE regime_travail (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL,
    heures_hebdo NUMERIC(5,2) NOT NULL CHECK (heures_hebdo > 0),
    seuil_hs_jour NUMERIC(5,2) CHECK (seuil_hs_jour >= 0),
    seuil_hs_semaine NUMERIC(5,2) CHECK (seuil_hs_semaine >= 0),
    tolerance_retard_max INT NOT NULL DEFAULT 0
        CHECK (tolerance_retard_max >= 0)
);

CREATE TABLE horaire_poste (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES poste(id),
    id_regime BIGINT NOT NULL REFERENCES regime_travail(id),
    libelle VARCHAR(100),
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    pause_minutes INT NOT NULL DEFAULT 0
        CHECK (pause_minutes >= 0)
);

-- Volume horaire calculé automatiquement
CREATE VIEW v_horaire_poste AS
SELECT
    h.*,
    (
        EXTRACT(EPOCH FROM (
            CASE
                WHEN h.heure_fin > h.heure_debut
                    THEN h.heure_fin - h.heure_debut
                ELSE h.heure_fin - h.heure_debut + INTERVAL '24 hours'
            END
        )) / 60 - h.pause_minutes
    ) / 60 AS volume_horaire_total
FROM horaire_poste h;


-- ============================================================
-- 5. PLANNING DES EMPLOYES
-- ============================================================

CREATE TABLE planning_edt (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_horaire_poste BIGINT NOT NULL REFERENCES horaire_poste(id),
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    jours_semaine SMALLINT[] NOT NULL,
    libelle VARCHAR(200),

    CHECK (date_fin >= date_debut),

    CHECK (
        cardinality(jours_semaine) > 0
        AND jours_semaine <@ ARRAY[1,2,3,4,5,6,7]::SMALLINT[]
    )
);

CREATE TABLE planning_edt_emp (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_planning_edt BIGINT NOT NULL REFERENCES planning_edt(id),
    id_employee BIGINT NOT NULL REFERENCES employee(id),
    UNIQUE (id_planning_edt, id_employee)
);


-- ============================================================
-- 6. TYPES DE POINTAGE
-- ============================================================

CREATE TABLE type_pointage (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(20) NOT NULL UNIQUE
        CHECK (
            libelle IN (
                'ENTREE',
                'SORTIE',
                'PAUSE_DEBUT',
                'PAUSE_FIN'
            )
        )
);


-- ============================================================
-- 7. POINTAGES BRUTS (DONNEES NON MODIFIEES)
-- ============================================================

CREATE TABLE pointage (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employee BIGINT NOT NULL REFERENCES employee(id),
    date_heure TIMESTAMP NOT NULL,
    id_type_pointage BIGINT NOT NULL REFERENCES type_pointage(id),
    cree_par BIGINT REFERENCES employee(id),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_pointage_emp_date
ON pointage(id_employee, date_heure);


-- ============================================================
-- 8. RESULTATS JOURNALIERS CALCULES
-- ============================================================

CREATE TABLE statut_journee_travail (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(15) NOT NULL UNIQUE
        CHECK (libelle IN ('CREE', 'VALIDE', 'CLOTURE'))
);

CREATE TABLE journee_travail (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employee BIGINT NOT NULL REFERENCES employee(id),
    date_jour DATE NOT NULL,
    id_planning_edt_emp BIGINT REFERENCES planning_edt_emp(id),

    minutes_prevues INT NOT NULL DEFAULT 0,
    minutes_travaillees INT NOT NULL DEFAULT 0,
    minutes_retard INT NOT NULL DEFAULT 0,
    minutes_depart_anticipe INT NOT NULL DEFAULT 0,
    minutes_hs INT NOT NULL DEFAULT 0,

    statut INT NOT NULL REFERENCES statut_journee_travail(id),

    CHECK (minutes_prevues >= 0),
    CHECK (minutes_travaillees >= 0),
    CHECK (minutes_retard >= 0),
    CHECK (minutes_depart_anticipe >= 0),
    CHECK (minutes_hs >= 0),

    UNIQUE (id_employee, date_jour)
);


-- ============================================================
-- 9. GESTION DES HEURES SUPPLEMENTAIRES
-- ============================================================

CREATE TABLE conf_heure_sup (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    taux_majoration NUMERIC(5,2) NOT NULL DEFAULT 1.30
        CHECK (taux_majoration >= 1)
);

CREATE TABLE statut_heure_sup (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE heure_supplementaire (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employer BIGINT NOT NULL REFERENCES employee(id),
    heure_debut TIMESTAMP NOT NULL,
    heure_fin TIMESTAMP NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,

    CHECK (heure_fin > heure_debut),
    CHECK (date_fin >= date_debut)
);

CREATE TABLE histo_demande_heure_sup (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_statut BIGINT NOT NULL REFERENCES statut_heure_sup(id),
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur BIGINT NOT NULL REFERENCES utilisateur(id),
    id_heure_supplementaire BIGINT NOT NULL
        REFERENCES heure_supplementaire(id)
);

CREATE INDEX idx_histo_heure_sup
ON histo_demande_heure_sup(id_heure_supplementaire, date);


-- ============================================================
-- 10. ANOMALIES DE POINTAGE
-- ============================================================
CREATE TABLE type_anomalie (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(30) NOT NULL UNIQUE
        CHECK (
            libelle IN (
                'SORTIE_MANQUANTE',
                'ENTREE_MANQUANTE',
                'DOUBLON',
                'HORS_PLANNING',
                'ECART_EXCESSIF'
            )
        )
);



CREATE TABLE anomalie_pointage (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employee BIGINT NOT NULL REFERENCES employee(id),
    date_jour DATE NOT NULL,
    id_pointage BIGINT REFERENCES pointage(id),

    type_anomalie BIGINT NOT NULL REFERENCES type_anomalie(id),

    statut VARCHAR(15) NOT NULL DEFAULT 'A_TRAITER'
        CHECK (statut IN ('A_TRAITER', 'CORRIGEE', 'JUSTIFIEE')),

    commentaire TEXT,
    traite_par BIGINT REFERENCES employee(id),
    traite_le TIMESTAMP
);

CREATE INDEX idx_anomalie_employee
ON anomalie_pointage(id_employee, date_jour);


-- ============================================================
-- 11. CORRECTIONS DE POINTAGE
-- ============================================================

CREATE TABLE correction_pointage (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_pointage BIGINT NOT NULL REFERENCES pointage(id),
    ancienne_valeur TIMESTAMP,
    nouvelle_valeur TIMESTAMP,
    motif TEXT NOT NULL,
    corrige_par BIGINT NOT NULL REFERENCES employee(id),
    corrige_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_correction_pointage
ON correction_pointage(id_pointage, corrige_le);
