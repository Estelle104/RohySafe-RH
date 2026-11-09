-- ============================================================
-- HISTORISATION DES POINTAGES, ANOMALIES ET ABSENCES
-- ============================================================

CREATE TABLE IF NOT EXISTS historique_pointages (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_pointage BIGINT NOT NULL REFERENCES pointages(id),
    action VARCHAR(20) NOT NULL CHECK (action IN ('CREATION', 'MODIFICATION', 'SUPPRESSION')),
    ancienne_date_heure TIMESTAMP,
    nouvelle_date_heure TIMESTAMP,
    ancien_type BIGINT REFERENCES type_pointages(id),
    nouveau_type BIGINT REFERENCES type_pointages(id),
    id_utilisateur BIGINT REFERENCES utilisateurs(id),
    motif TEXT,
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS historique_anomalies (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_anomalie BIGINT NOT NULL REFERENCES anomalie_pointages(id),
    ancien_statut BIGINT REFERENCES statut_anomalie_pointages(id),
    nouveau_statut BIGINT REFERENCES statut_anomalie_pointages(id),
    ancien_commentaire TEXT,
    nouveau_commentaire TEXT,
    id_utilisateur BIGINT REFERENCES utilisateurs(id),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS historique_absences (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_absence BIGINT NOT NULL REFERENCES absences(id),
    ancien_statut BIGINT REFERENCES statut_absences(id),
    nouveau_statut BIGINT REFERENCES statut_absences(id),
    ancien_motif TEXT,
    nouveau_motif TEXT,
    id_utilisateur BIGINT REFERENCES utilisateurs(id),
    date_action TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_historique_pointages_pointage
    ON historique_pointages(id_pointage, date_action);
CREATE INDEX IF NOT EXISTS idx_historique_anomalies_anomalie
    ON historique_anomalies(id_anomalie, date_action);
CREATE INDEX IF NOT EXISTS idx_historique_absences_absence
    ON historique_absences(id_absence, date_action);
