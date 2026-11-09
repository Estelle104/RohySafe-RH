-- ============================================================
-- CONFIGURATION DE L'ACQUISITION DES CONGES
-- ============================================================
-- nombre_jour représente le nombre de jours acquis par mois pour
-- chaque type de congé. La contrainte UNIQUE garantit une seule
-- configuration active par type.

CREATE TABLE IF NOT EXISTS config_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_type_conge BIGINT NOT NULL UNIQUE REFERENCES type_conges(id),
    nombre_jour NUMERIC(6,2) NOT NULL CHECK (nombre_jour >= 0)
);

INSERT INTO config_conges (id_type_conge, nombre_jour)
SELECT tc.id,
       CASE tc.libelle
           WHEN 'annuel' THEN 2.50
           WHEN 'maladie' THEN 0.00
           WHEN 'exceptionnel' THEN 0.00
       END
FROM type_conges tc
WHERE tc.libelle IN ('annuel', 'maladie', 'exceptionnel')
ON CONFLICT (id_type_conge) DO NOTHING;
