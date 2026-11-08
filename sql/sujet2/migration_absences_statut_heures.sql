-- Statut et durée des absences pour calculer les retenues salariales.
ALTER TABLE absences
    ADD COLUMN IF NOT EXISTS statut VARCHAR(30) NOT NULL DEFAULT 'A_TRAITER',
    ADD COLUMN IF NOT EXISTS heure_debut TIME,
    ADD COLUMN IF NOT EXISTS heure_fin TIME;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_absences_statut') THEN
        ALTER TABLE absences ADD CONSTRAINT ck_absences_statut CHECK (statut IN ('A_TRAITER', 'JUSTIFIEE_AUTORISEE', 'NON_JUSTIFIEE'));
    END IF;
END $$;

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_absences_heures') THEN
        ALTER TABLE absences ADD CONSTRAINT ck_absences_heures CHECK (heure_fin IS NULL OR (heure_debut IS NOT NULL AND heure_fin > heure_debut));
    END IF;
END $$;
