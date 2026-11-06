-- ============================================================
-- TYPES DE CONGES
-- ============================================================
-- Les types sont insérés de manière idempotente afin de pouvoir
-- rejouer le script sans créer de doublons.

CREATE TABLE IF NOT EXISTS type_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO type_conges (libelle)
VALUES
    ('annuel'),
    ('maladie'),
    ('exceptionnel')
ON CONFLICT (libelle) DO NOTHING;
