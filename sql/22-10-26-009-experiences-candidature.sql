BEGIN;

CREATE TABLE IF NOT EXISTS experiences_candidature (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidatures(id) ON DELETE CASCADE,
    id_exigence BIGINT NOT NULL REFERENCES exigences_experience(id) ON DELETE RESTRICT,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    duree_jours INTEGER GENERATED ALWAYS AS (date_fin - date_debut) STORED,
    CHECK (date_fin >= date_debut),
    UNIQUE (id_candidature, id_exigence)
);

CREATE INDEX IF NOT EXISTS idx_experiences_candidature_candidature
    ON experiences_candidature (id_candidature);

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'exigences_experience_poste_unique'
    ) THEN
        ALTER TABLE exigences_experience
            ADD CONSTRAINT exigences_experience_poste_unique
            UNIQUE (id_poste_departement, id_poste_experience);
    END IF;
END
$$;

COMMIT;
