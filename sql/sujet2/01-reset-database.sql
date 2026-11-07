-- Vue
DROP VIEW IF EXISTS v_horaire_postes;
 
-- 11. Corrections
DROP TABLE IF EXISTS correction_pointages CASCADE;
 
-- 10. Anomalies
DROP TABLE IF EXISTS anomalie_pointages CASCADE;
DROP TABLE IF EXISTS type_anomalies CASCADE;
DROP TABLE IF EXISTS statut_anomalie_pointages CASCADE;
 
-- 9. Heures supplémentaires
DROP TABLE IF EXISTS histo_demande_heure_sups CASCADE;
DROP TABLE IF EXISTS heure_supplementaires CASCADE;
DROP TABLE IF EXISTS statut_heure_sups CASCADE;
DROP TABLE IF EXISTS conf_heure_sups CASCADE;
 
-- 8. Journées de travail
DROP TABLE IF EXISTS journee_travails CASCADE;
DROP TABLE IF EXISTS statut_journee_travails CASCADE;
 
-- 7 & 6. Pointages
DROP TABLE IF EXISTS pointages CASCADE;
DROP TABLE IF EXISTS type_pointages CASCADE;
-- 5. Planning
DROP TABLE IF EXISTS planning_edt_employes CASCADE;
DROP TABLE IF EXISTS planning_edts CASCADE;
 
-- 4. Régimes et horaires
DROP TABLE IF EXISTS horaire_postes CASCADE;
DROP TABLE IF EXISTS regime_travails CASCADE;
 
-- 3. Congés et absences
DROP TABLE IF EXISTS absences CASCADE;
DROP TABLE IF EXISTS histo_demande_conges CASCADE;
DROP TABLE IF EXISTS statut_conges CASCADE;
DROP TABLE IF EXISTS conges CASCADE;
DROP TABLE IF EXISTS config_conges CASCADE;
DROP TABLE IF EXISTS type_conges CASCADE;
DROP TABLE IF EXISTS remplacement_conges CASCADE;
 
-- 2. Sociétés et provinces
DROP TABLE IF EXISTS histo_employes CASCADE;
DROP TABLE IF EXISTS societe_provinces CASCADE;
DROP TABLE IF EXISTS provinces CASCADE;
DROP TABLE IF EXISTS societes CASCADE;
 
-- 1. Infos personnelles
DROP TABLE IF EXISTS histo_info_employes CASCADE;
DROP TABLE IF EXISTS info_employes CASCADE;
DROP TABLE IF EXISTS statut_professionnelles CASCADE;
DROP TABLE IF EXISTS statut_matrimoniales CASCADE;
DROP TABLE IF EXISTS relation_hierarchiques CASCADE;

COMMIT;
 