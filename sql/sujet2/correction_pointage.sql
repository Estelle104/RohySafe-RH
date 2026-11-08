-- ============================================================
-- CORRECTIONS DE POINTAGE
-- ============================================================
-- La structure existe déjà dans 01-Base-Complet.sql.
-- Ce script autonome peut être exécuté plusieurs fois sans doublon.

CREATE TABLE IF NOT EXISTS correction_pointages (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_pointage BIGINT NOT NULL REFERENCES pointages(id),
    ancienne_valeur TIMESTAMP,
    nouvelle_valeur TIMESTAMP,
    motif TEXT NOT NULL,
    corrige_par BIGINT NOT NULL REFERENCES utilisateurs(id),
    corrige_le TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_correction_pointage
ON correction_pointages(id_pointage, corrige_le);
