-- ============================================================
-- PREVENTION DES DOUBLONS DE POINTAGE
-- ============================================================
-- Un même employé ne peut pas avoir deux fois le même type de
-- pointage à la même date et à la même heure.
-- Exécuter après avoir vérifié qu'il n'existe pas déjà de doublons.

CREATE UNIQUE INDEX IF NOT EXISTS uq_pointage_employe_date_type
ON pointages(id_employe, date_heure, id_type_pointage);
