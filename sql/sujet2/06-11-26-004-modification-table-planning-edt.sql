-- Supprimer l'association créée à partir de l'ancien fonctionnement
DELETE FROM planning_edt_employes
WHERE id_planning_edt IN (
    SELECT id
    FROM planning_edts
);

-- Supprimer la clé étrangère vers horaire_postes
ALTER TABLE planning_edts
DROP CONSTRAINT planning_edts_id_horaire_poste_fkey;

-- Supprimer la colonne devenue inutile
ALTER TABLE planning_edts
DROP COLUMN id_horaire_poste;