-- ============================================================
-- ANOMALIES DE POINTAGE
-- ============================================================
-- Les structures sont déjà présentes dans 01-Base-Complet.sql.
-- Ce script autonome est rejouable sans créer de doublon.

CREATE TABLE IF NOT EXISTS type_anomalies (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(30) NOT NULL UNIQUE
        CHECK (libelle IN (
            'SORTIE_MANQUANTE',
            'ENTREE_MANQUANTE',
            'DOUBLON',
            'HORS_PLANNING',
            'ECART_EXCESSIF'
        ))
);

CREATE TABLE IF NOT EXISTS statut_anomalie_pointages (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(15) NOT NULL UNIQUE
        CHECK (libelle IN ('A_TRAITER', 'CORRIGEE', 'JUSTIFIEE'))
);

INSERT INTO type_anomalies (libelle)
VALUES
    ('SORTIE_MANQUANTE'),
    ('ENTREE_MANQUANTE'),
    ('DOUBLON'),
    ('HORS_PLANNING'),
    ('ECART_EXCESSIF')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO statut_anomalie_pointages (libelle)
VALUES
    ('A_TRAITER'),
    ('CORRIGEE'),
    ('JUSTIFIEE')
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

CREATE INDEX IF NOT EXISTS idx_anomalie_employee
ON anomalie_pointages(id_employe, date_jour);
