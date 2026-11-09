-- ============================================================
-- MODULE CONGES - SCRIPT CONSOLIDE
-- Prérequis : 00-Base-Complet.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS type_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO type_conges (libelle) VALUES ('annuel'), ('maladie'), ('exceptionnel')
ON CONFLICT (libelle) DO NOTHING;

CREATE TABLE IF NOT EXISTS config_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_type_conge BIGINT NOT NULL UNIQUE REFERENCES type_conges(id),
    nombre_jour NUMERIC(6,2) NOT NULL CHECK (nombre_jour >= 0)
);

INSERT INTO config_conges (id_type_conge, nombre_jour)
SELECT id, CASE libelle WHEN 'annuel' THEN 2.50 ELSE 0.00 END FROM type_conges
WHERE libelle IN ('annuel', 'maladie', 'exceptionnel')
ON CONFLICT (id_type_conge) DO NOTHING;

CREATE TABLE IF NOT EXISTS solde_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    id_type_conge BIGINT NOT NULL REFERENCES type_conges(id),
    periode_debut DATE NOT NULL,
    periode_fin DATE NOT NULL,
    droits_acquis NUMERIC(8,2) NOT NULL DEFAULT 0 CHECK (droits_acquis >= 0),
    jours_pris NUMERIC(8,2) NOT NULL DEFAULT 0 CHECK (jours_pris >= 0),
    solde_restant NUMERIC(8,2) NOT NULL DEFAULT 0 CHECK (solde_restant = droits_acquis - jours_pris),
    UNIQUE (id_employe, id_type_conge, periode_debut, periode_fin),
    CHECK (periode_fin >= periode_debut)
);
CREATE INDEX IF NOT EXISTS idx_solde_conges_employe ON solde_conges(id_employe, periode_debut, periode_fin);

INSERT INTO solde_conges (id_employe, id_type_conge, periode_debut, periode_fin)
SELECT e.id,
       t.id,
       make_date(EXTRACT(YEAR FROM CURRENT_DATE)::INT, 1, 1),
       make_date(EXTRACT(YEAR FROM CURRENT_DATE)::INT, 12, 31)
FROM employes e CROSS JOIN type_conges t
ON CONFLICT (id_employe, id_type_conge, periode_debut, periode_fin) DO NOTHING;

CREATE TABLE IF NOT EXISTS remplacement_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_conge BIGINT NOT NULL REFERENCES conges(id),
    id_employee_remplacant BIGINT NOT NULL REFERENCES employes(id),
    date_debut DATE NOT NULL, date_fin DATE NOT NULL, commentaire TEXT,
    CHECK (date_fin >= date_debut)
);
CREATE INDEX IF NOT EXISTS idx_remplacement_conges_periode ON remplacement_conges(date_debut, date_fin);

CREATE OR REPLACE FUNCTION creer_soldes_conge_employe() RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    INSERT INTO solde_conges (id_employe, id_type_conge, periode_debut, periode_fin)
    SELECT NEW.id,
           id,
           make_date(EXTRACT(YEAR FROM CURRENT_DATE)::INT, 1, 1),
           make_date(EXTRACT(YEAR FROM CURRENT_DATE)::INT, 12, 31)
    FROM type_conges
    ON CONFLICT (id_employe, id_type_conge, periode_debut, periode_fin)
    DO NOTHING;
    RETURN NEW;
END; $$;
DROP TRIGGER IF EXISTS trg_creer_soldes_conge_employe ON employes;
CREATE TRIGGER trg_creer_soldes_conge_employe
AFTER INSERT ON employes
FOR EACH ROW
EXECUTE FUNCTION creer_soldes_conge_employe();

CREATE OR REPLACE FUNCTION creer_soldes_conge_type() RETURNS TRIGGER LANGUAGE plpgsql AS $$
BEGIN
    INSERT INTO solde_conges (id_employe, id_type_conge, periode_debut, periode_fin)
    SELECT id,
           NEW.id,
           make_date(EXTRACT(YEAR FROM CURRENT_DATE)::INT, 1, 1),
           make_date(EXTRACT(YEAR FROM CURRENT_DATE)::INT, 12, 31)
    FROM employes
    ON CONFLICT (id_employe, id_type_conge, periode_debut, periode_fin)
    DO NOTHING;
    RETURN NEW;
END; $$;
DROP TRIGGER IF EXISTS trg_creer_soldes_conge_type ON type_conges;
CREATE TRIGGER trg_creer_soldes_conge_type
AFTER INSERT ON type_conges
FOR EACH ROW
EXECUTE FUNCTION creer_soldes_conge_type();
