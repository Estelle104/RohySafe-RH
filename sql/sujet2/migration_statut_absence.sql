-- ============================================================
-- STATUTS DES ABSENCES
-- ============================================================

CREATE TABLE IF NOT EXISTS statut_absences (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(30) NOT NULL UNIQUE
);

INSERT INTO statut_absences (libelle)
VALUES ('A_TRAITER'), ('JUSTIFIEE_AUTORISEE'), ('NON_JUSTIFIEE')
ON CONFLICT (libelle) DO NOTHING;

ALTER TABLE absences ADD COLUMN IF NOT EXISTS id_statut_absence BIGINT;

UPDATE absences a
SET id_statut_absence = sa.id
FROM statut_absences sa
WHERE sa.libelle = COALESCE(a.statut, 'A_TRAITER')
  AND a.id_statut_absence IS NULL;

UPDATE absences
SET id_statut_absence = (SELECT id FROM statut_absences WHERE libelle = 'A_TRAITER')
WHERE id_statut_absence IS NULL;

ALTER TABLE absences ALTER COLUMN id_statut_absence SET NOT NULL;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_absences_statut') THEN
        ALTER TABLE absences ADD CONSTRAINT fk_absences_statut
            FOREIGN KEY (id_statut_absence) REFERENCES statut_absences(id);
    END IF;
END $$;

ALTER TABLE absences DROP CONSTRAINT IF EXISTS ck_absences_statut;
ALTER TABLE absences DROP COLUMN IF EXISTS statut;
