
-- ============================================================
-- 1. INFORMATIONS PERSONNELLES DES EMPLOYES
-- ============================================================
 
CREATE TABLE statut_matrimoniales (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);
 
CREATE TABLE statut_professionnelles (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);
 
CREATE TABLE info_employes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL UNIQUE REFERENCES employes(id),
    residence_actuel VARCHAR(255),
    copie_actuel TEXT, -- Chemin vers le PDF
    date_echeance DATE,
    id_statut_matrimoniale BIGINT REFERENCES statut_matrimoniales(id),
    id_statut_professionnelle BIGINT REFERENCES statut_professionnelles(id)
);
 
-- Historique des informations personnelles
CREATE TABLE histo_info_employes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    chemin_residence TEXT,
    residence VARCHAR(255),
    copie TEXT,
    id_statut_matrimoniale BIGINT REFERENCES statut_matrimoniales(id),
    id_statut_professionnelle BIGINT REFERENCES statut_professionnelles(id),
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
 
CREATE INDEX idx_histo_info_employes
ON histo_info_employes(id_employe, date);
 
 
-- ============================================================
-- 2. SOCIETES ET PROVINCES
-- ============================================================
 
CREATE TABLE societes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(150) NOT NULL UNIQUE
);
 
CREATE TABLE provinces (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(150) NOT NULL UNIQUE
);
 
CREATE TABLE societe_provinces (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_societe BIGINT NOT NULL REFERENCES societes(id),
    id_province BIGINT NOT NULL REFERENCES provinces(id),
    UNIQUE (id_societe, id_province)
);
 
-- Historique de l'affectation d'un employé à une société
CREATE TABLE histo_employes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    id_societe BIGINT NOT NULL REFERENCES societes(id),
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
 
CREATE INDEX idx_histo_employes
ON histo_employes(id_employe, date);
 
 
-- ============================================================
-- 3. GESTION DES CONGES
-- ============================================================
 
CREATE TABLE type_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);
 
CREATE TABLE config_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_type_conge BIGINT NOT NULL UNIQUE REFERENCES type_conges(id),
    nombre_jour NUMERIC(6,2) NOT NULL CHECK (nombre_jour >= 0)
);
 
CREATE TABLE conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    id_type_conge BIGINT NOT NULL REFERENCES type_conges(id),
    CHECK (date_fin >= date_debut)
);
 
CREATE INDEX idx_conge_employes
ON conges(id_employe, date_debut);
 
CREATE TABLE statut_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);
 
CREATE TABLE histo_demande_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_statut BIGINT NOT NULL REFERENCES statut_conges(id),
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur BIGINT NOT NULL REFERENCES utilisateurs(id), 
    id_conge BIGINT NOT NULL REFERENCES conges(id)
);
 
CREATE INDEX idx_histo_demande_conge
ON histo_demande_conges(id_conge, date);
 
-- Absences non assimilées à un congé
CREATE TABLE absences (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    date DATE NOT NULL,
    motif TEXT NOT NULL
);
 
CREATE INDEX idx_absence_employes
ON absences(id_employe, date);
 
 
-- ============================================================
-- 4. REGIMES DE TRAVAIL ET HORAIRES
-- ============================================================
 
CREATE TABLE regime_travails (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL,
    heures_hebdo NUMERIC(5,2) NOT NULL CHECK (heures_hebdo > 0),
    seuil_hs_jour NUMERIC(5,2) CHECK (seuil_hs_jour >= 0),
    seuil_hs_semaine NUMERIC(5,2) CHECK (seuil_hs_semaine >= 0),
    tolerance_retard_max INT NOT NULL DEFAULT 0
        CHECK (tolerance_retard_max >= 0)
);
 
CREATE TABLE horaire_postes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_poste BIGINT NOT NULL REFERENCES postes(id),
    id_regime BIGINT NOT NULL REFERENCES regime_travails(id),
    libelle VARCHAR(100),
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    pause_minutes INT NOT NULL DEFAULT 0
        CHECK (pause_minutes >= 0),
    CHECK (heure_debut <> heure_fin)
);
 
-- Volume horaire calculé automatiquement (gère les horaires de nuit)
CREATE VIEW v_horaire_postes AS
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
FROM horaire_postes h;
 
 
-- ============================================================
-- 5. PLANNING DES EMPLOYES
-- ============================================================
 
CREATE TABLE planning_edts (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_horaire_poste BIGINT NOT NULL REFERENCES horaire_postes(id),
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
 
CREATE TABLE planning_edt_employes (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_planning_edt BIGINT NOT NULL REFERENCES planning_edts(id),
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    UNIQUE (id_planning_edt, id_employe)
);
 
CREATE INDEX idx_planning_edt_employes_employe
ON planning_edt_employes(id_employe);
 
 
-- ============================================================
-- 6. TYPES DE POINTAGE
-- ============================================================
 
CREATE TABLE type_pointages (
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
 
CREATE TABLE pointages (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    date_heure TIMESTAMP NOT NULL,
    id_type_pointage BIGINT NOT NULL REFERENCES type_pointages(id),
    cree_par BIGINT REFERENCES utilisateurs(id), 
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
 
CREATE INDEX idx_pointage_emp_date
ON pointages(id_employe, date_heure);
 
 
-- ============================================================
-- 8. RESULTATS JOURNALIERS CALCULES
-- ============================================================
 
CREATE TABLE statut_journee_travails (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(15) NOT NULL UNIQUE
        CHECK (libelle IN ('CREE', 'VALIDE', 'CLOTURE'))
);
 
CREATE TABLE journee_travails (
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
 
    CHECK (minutes_prevues >= 0),
    CHECK (minutes_travaillees >= 0),
    CHECK (minutes_retard >= 0),
    CHECK (minutes_depart_anticipe >= 0),
    CHECK (minutes_hs >= 0),
 
    UNIQUE (id_employe, date_jour)
);
 
CREATE INDEX idx_journee_travails_date
ON journee_travails(date_jour);
 
 
-- ============================================================
-- 9. GESTION DES HEURES SUPPLEMENTAIRES
-- ============================================================
 
CREATE TABLE conf_heure_sups (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    taux_majoration NUMERIC(5,2) NOT NULL DEFAULT 1.30
        CHECK (taux_majoration >= 1)
);
 
CREATE TABLE statut_heure_sups (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);
 
CREATE TABLE heure_supplementaires (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    heure_debut TIMESTAMP NOT NULL,
    heure_fin TIMESTAMP NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
 
    CHECK (heure_fin > heure_debut),
    CHECK (date_fin >= date_debut)
);
 
CREATE INDEX idx_heure_sup_employes
ON heure_supplementaires(id_employe, date_debut);
 
CREATE TABLE histo_demande_heure_sups (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_statut BIGINT NOT NULL REFERENCES statut_heure_sups(id),
    date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur BIGINT NOT NULL REFERENCES utilisateurs(id),
    id_heure_supplementaire BIGINT NOT NULL
        REFERENCES heure_supplementaires(id)
);
 
CREATE INDEX idx_histo_heure_sups
ON histo_demande_heure_sups(id_heure_supplementaire, date);
 
 
-- ============================================================
-- 10. ANOMALIES DE POINTAGE
-- ============================================================
 
CREATE TABLE type_anomalies (
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

CREATE TABLE statut_anomalie_pointages (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(15) NOT NULL UNIQUE
        CHECK (libelle IN ('A_TRAITER', 'CORRIGEE', 'JUSTIFIEE'))
);
 
CREATE TABLE anomalie_pointages (
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
 
CREATE INDEX idx_anomalie_employee
ON anomalie_pointages(id_employe, date_jour);
 
 
-- ============================================================
-- 11. CORRECTIONS DE POINTAGE
-- ============================================================
 
CREATE TABLE correction_pointages (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_pointage BIGINT NOT NULL REFERENCES pointages(id),
    ancienne_valeur TIMESTAMP,
    nouvelle_valeur TIMESTAMP,
    motif TEXT NOT NULL,
    corrige_par BIGINT NOT NULL REFERENCES utilisateurs(id),
    corrige_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
 
CREATE INDEX idx_correction_pointage
ON correction_pointages(id_pointage, corrige_le);
 
 