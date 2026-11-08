-- ============================================================
-- MODULE ABSENCES - SCRIPT CONSOLIDE
-- Prérequis : 00-Base-Complet.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS statut_absences (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(30) NOT NULL UNIQUE
);

INSERT INTO statut_absences (libelle)
VALUES ('A_TRAITER'), ('JUSTIFIEE_AUTORISEE'), ('NON_JUSTIFIEE')
ON CONFLICT (libelle) DO NOTHING;

CREATE TABLE IF NOT EXISTS absences (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    date DATE NOT NULL,
    motif TEXT NOT NULL,
    id_statut_absence BIGINT NOT NULL REFERENCES statut_absences(id),
    heure_debut TIME,
    heure_fin TIME,
    UNIQUE (id_employe, date),
    CHECK (
        heure_fin IS NULL
        OR (heure_debut IS NOT NULL AND heure_fin > heure_debut)
    )
);

CREATE INDEX IF NOT EXISTS idx_absence_employes
    ON absences (id_employe, date);
