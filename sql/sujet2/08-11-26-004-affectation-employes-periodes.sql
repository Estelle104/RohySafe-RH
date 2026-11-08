-- Évolution affectations employés : périodes (date_debut/date_fin) pour clôturer
-- l'affectation précédente lors d'une nouvelle affectation.
-- À appliquer après 06-11-26-001-modification-ajout-table.sql (histo_employes avec id_societe_province).

ALTER TABLE histo_employes
    ADD COLUMN IF NOT EXISTS date_debut DATE,
    ADD COLUMN IF NOT EXISTS date_fin DATE;

-- Migre l'ancienne colonne "date" (timestamp) vers date_debut si présente
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'histo_employes' AND column_name = 'date'
    ) THEN
        UPDATE histo_employes SET date_debut = DATE(date) WHERE date_debut IS NULL;
        ALTER TABLE histo_employes DROP COLUMN date;
    END IF;
END $$;

-- Valeur par défaut pour les lignes existantes sans date_debut
UPDATE histo_employes SET date_debut = CURRENT_DATE WHERE date_debut IS NULL;

ALTER TABLE histo_employes
    ALTER COLUMN date_debut SET NOT NULL;

ALTER TABLE histo_employes
    DROP CONSTRAINT IF EXISTS chk_histo_employes_periode,
    ADD CONSTRAINT chk_histo_employes_periode CHECK (date_fin IS NULL OR date_fin >= date_debut);

-- Index utile pour retrouver l'affectation courante / historique
CREATE INDEX IF NOT EXISTS idx_histo_employes_employe_periode
    ON histo_employes(id_employe, date_fin, date_debut DESC);

-- Reconstitue date_fin pour l'historique existant (chaque affectation se termine
-- la veille du début de la suivante pour le même employé ; la dernière reste ouverte).
WITH ordered AS (
    SELECT id,
           LEAD(date_debut) OVER (PARTITION BY id_employe ORDER BY date_debut, id) AS next_debut
    FROM histo_employes
)
UPDATE histo_employes h
SET date_fin = (ordered.next_debut - INTERVAL '1 day')::date
FROM ordered
WHERE h.id = ordered.id
  AND ordered.next_debut IS NOT NULL
  AND h.date_fin IS NULL;
