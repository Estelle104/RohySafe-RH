-- Réinitialisation de la base de données RohySafe RH
-- Suppression de toutes les tables dans l'ordre inverse des dépendances

DROP TABLE IF EXISTS documents_candidat CASCADE;
DROP TABLE IF EXISTS type_documents CASCADE;

DROP TABLE IF EXISTS historique_contrats_statut CASCADE;
DROP TABLE IF EXISTS contrats CASCADE;
DROP TABLE IF EXISTS statut_contrats CASCADE;

DROP TABLE IF EXISTS historique_employes_statut CASCADE;
DROP TABLE IF EXISTS employes CASCADE;
DROP TABLE IF EXISTS statut_employes CASCADE;

DROP TABLE IF EXISTS historique_propositions CASCADE;
DROP TABLE IF EXISTS propositions_embauche CASCADE;
DROP TABLE IF EXISTS statut_propositions_embauche CASCADE;

DROP TABLE IF EXISTS evaluations_candidat CASCADE;
DROP TABLE IF EXISTS historique_entretiens_statut CASCADE;
DROP TABLE IF EXISTS entretiens CASCADE;
DROP TABLE IF EXISTS statut_entretiens CASCADE;

DROP TABLE IF EXISTS criteres_softskill CASCADE;
DROP TABLE IF EXISTS criteres_poste CASCADE;
DROP TABLE IF EXISTS exigences_experience CASCADE;
DROP TABLE IF EXISTS softskills CASCADE;
DROP TABLE IF EXISTS competences_candidat CASCADE;
DROP TABLE IF EXISTS competences CASCADE;
DROP TABLE IF EXISTS formations_candidat CASCADE;
DROP TABLE IF EXISTS experiences_candidat CASCADE;
DROP TABLE IF EXISTS historique_candidature_status CASCADE;
DROP TABLE IF EXISTS candidatures CASCADE;
DROP TABLE IF EXISTS statut_candidatures CASCADE;
DROP TABLE IF EXISTS offres CASCADE;
DROP TABLE IF EXISTS statut_offres CASCADE;
DROP TABLE IF EXISTS budgets CASCADE;
DROP TABLE IF EXISTS histo_grille_salariales CASCADE;
DROP TABLE IF EXISTS grilles_salariales CASCADE;
DROP TABLE IF EXISTS historique_demandes CASCADE;
DROP TABLE IF EXISTS demandes CASCADE;
DROP TABLE IF EXISTS statut_demandes CASCADE;
DROP TABLE IF EXISTS type_contrats CASCADE;
DROP TABLE IF EXISTS poste_departement CASCADE;
DROP TABLE IF EXISTS postes CASCADE;
DROP TABLE IF EXISTS categorie_postes CASCADE;
DROP TABLE IF EXISTS candidatures CASCADE;
DROP TABLE IF EXISTS candidats CASCADE;
DROP TABLE IF EXISTS utilisateurs CASCADE;
DROP TABLE IF EXISTS roles CASCADE;
DROP TABLE IF EXISTS departements CASCADE;

-- Note : la table candidats apparaît en double dans le schéma initial ;
-- la version finale du modèle contient une seule définition.
