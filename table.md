# 1. Organisation et utilisateurs

| Table          | Attributs et définition                                                                 |
| -------------- | --------------------------------------------------------------------------------------- |
| `entreprises`  | `id (PK)`, `nom`, `email` — Entreprises gérées par le SIRH.                             |
| `departements` | `id (PK)`, `libelle`, `id_entreprise (FK)` — Départements de chaque entreprise.         |
| `utilisateurs` | `id (PK)`, `login`, `mdp`, `id_role (FK)` — Comptes des utilisateurs du système.        |
| `roles`        | `id (PK)`, `libelle` — Rôles : RH, Finance, Direction, responsable de département, etc. |
| `categories`   | `id (PK)`, `libelle` — Catégories de postes.                                            |
| `postes`       | `id (PK)`, `libelle`, `id_categorie (FK)` — Postes de travail.                          |

# 2. Demandes de recrutement et validations

| Table                 | Attributs et définition                                                                                                                                                               |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `demandes`            | `id (PK)`, `date_creation`, `id_departement (FK)`, `id_poste (FK)`, `nombre_postes`, `id_type_contrat (FK)`, `date_prise_poste`, `id_statut_actuel (FK)` — Demandes de personnel faites par les départements. |
| `statuts_demande`     | `id (PK)`, `libelle` — Liste des états possibles d'une demande.                                                                                                                       |
| `historique_demande`  | `id (PK)`, `id_demande (FK)`, `id_statut (FK)`, `id_utilisateur (FK)`, `date_action`, `motif`, `commentaire` — Historique du parcours de la demande.                                  |
| `validations_demande` | `id (PK)`, `id_demande (FK)`, `id_utilisateur (FK)`, `niveau_validation`, `decision`, `motif`, `date_decision` — Décisions de la RH, de la Finance et de la Direction.                |

# 3. Grilles salariales et budget

| Table                    | Attributs et définition                                                                                                                                                            |
| ------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `grilles_salariales`     | `id (PK)`, `id_poste (FK)`, `id_departement (FK, nullable)`, `montant_min`, `montant_max`, `date_debut`, `date_fin`, `id_type_contrat (FK)` — Fourchettes salariales applicables pendant une période.      |
| `budgets`                | `id (PK)`, `id_departement (FK)`, `annee`, `montant_annuel`, `montant_engage` — Budget annuel du département et montant déjà engagé.                                               |
| `histo_salaire_demandes` | `id (PK)`, `id_demande (FK)`, `montant`, `id_utilisateur (FK)`, `date`, `motif`, `statut_validation` — Historique des montants proposés par la RH pour une demande de recrutement. |

`id_departement` dans `grilles_salariales` est facultatif si le salaire dépend uniquement du poste, indépendamment du département.

# 4. Offres et candidatures

| Table          | Attributs et définition                                                                                                                                        |
| -------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `offres`       | `id (PK)`, `id_demande (FK)`, `titre`, `description`, `date_publication`, `date_limite`, `statut` — Offres d'emploi publiées à partir des demandes approuvées. |
| `candidats`    | `id (PK)`, `nom`, `prenom`, `email`, `telephone`, `ville`, `date_creation` — Informations personnelles du candidat.    
| `statut_candidatures` |`id(PK)`, `libelle` — liste des etats que une candidatures peut avoir
| `historique_candidature_status` | `id_candidatures(PK)`, `id_statut_candidatures (PK)`, `daty`                                         |
| `candidatures` | `id (PK)`, `id_candidat (FK)`, `id_offre (FK)`, `date_candidature` — Candidature d'une personne à une offre précise.                                 |

# 5. CV et compétences des candidats

| Table                  | Attributs et définition                                                                                                                                  |
| ---------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `experiences_candidat` | `id (PK)`, `id_candidat (FK)`, `id_poste (FK)`, `date_debut`, `date_fin`, `description` — Expériences professionnelles d'un candidat. |
| `formations_candidat`  | `id (PK)`, `id_candidat (FK)`, `diplome`, `etablissement`, `date_debut`, `date_fin`, `description` — Diplômes et formations.                             |
| `competences`          | `id (PK)`, `libelle`, `categorie` — Référentiel des compétences techniques ou autres compétences évaluables.                                             |
| `competences_candidat` | `id (PK)`, `id_candidat (FK)`, `id_competence (FK)`, `niveau` — Compétences et niveau de maîtrise du candidat.                                           |


# 6. Critères et présélection automatique

| Table                    | Attributs et définition                                                                                                                                                                               |
| ------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `exigences_experience`   | `id (PK)`, `id_poste (FK)`, `duree_min`, `duree_max`, `obligatoire` — Expérience requise pour un poste.                                                                         |
| `softskills`             | `id (PK)`, `libelle` — Référentiel des compétences comportementales pertinentes pour le poste.                                                                                                        |
| `criteres_poste`         | `id (PK)`, `id_poste (FK)`, `id_competence (FK, nullable)`, `id_softskill (FK, nullable)`, `poids`, `note_minimale`, `obligatoire` — Critères utilisés pour évaluer ou présélectionner les candidats. |
| `resultats_preselection` | `id (PK)`, `id_candidature (FK)`, `score`, `resultat`, `date_calcul`, `details` — Résultat calculé de la présélection d'une candidature.                                                              |
| `historique_classement`  | `id (PK)`, `id_candidature (FK)`, `rang`, `score`, `date_classement` — Historique du classement des candidatures pour une offre.                                                                      |

Dans `criteres_poste`, au moins l'un des champs `id_competence` ou `id_softskill` doit être renseigné. Les critères d'expérience peuvent rester dans `exigences_experience`, car ils nécessitent des informations spécifiques comme la durée.

# 7. Entretiens et évaluations

| Table                  | Attributs et définition                                                                                                                                                                          |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `statut_entretiens` | `id(PK)`, `libelle` — Statut d un entretiens
| `entretiens`           | `id (PK)`, `id_candidature (FK)`, `date_entretien`, `type_entretien`, `commentaire` — Entretiens réalisés pour une candidature.                                                        |
| `historique_entretiens_statut` | `id_entretien(PK)`, `id_statut_entretiens(PK)`, `daty`
| `evaluations_candidat` | `id (PK)`, `id_entretien (FK)`, `id_critere (FK)`, `id_evaluateur (FK vers utilisateurs)`, `note`, `commentaire`, `date_evaluation` — Notes attribuées par les évaluateurs aux critères définis. |

# 8. Propositions d'embauche et négociations

| Table                     | Attributs et définition                                                                                                                                                                                            |
| ------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `statut_propositions_embauche` | `id(PK)`, `libelle` |
| `propositions_embauche`   | `id (PK)`, `id_candidature (FK)`, `salaire_propose`, `id_type_contrat (FK)`, `date_debut_souhaitee`, `date_proposition` — Proposition d'embauche envoyée au candidat.                                    |
| `historique_propositions_embauche_statut` | `id_propositions_embauche`, `id_statut_propositions_embauche`, `daty` |
| `historique_propositions` | `id (PK)`, `id_proposition (FK)`, `salaire_demande`, `id_type_contrat_demande (FK)`, `reponse_candidat`, `motif`, `date_action`, `id_utilisateur (FK, nullable)` — Historique des réponses et contre-propositions. |

# 9. Documents et contrats

| Table                | Attributs et définition                                                                                                                                                                                                                               |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `documents_candidat` | `id (PK)`, `id_candidat (FK)`, `type_document`, `chemin_fichier`, `date_depot`, `date_verification`, `commentaire` — Pièces justificatives et contrôle de leur conformité. |
| `types_contrat`      | `id (PK)`, `libelle` — Types de contrat : CDD, CDI, etc.                                                                                                                                                                                              |
| `statut_contrats` | `id (PK)`, `libelle` - Statut contrats|
| `contrats`           | `id (PK)`, `id_employe (FK)`, `id_type_contrat (FK)`, `date_debut`, `date_fin`, `salaire`, `date_signature` — Contrats de travail et leur état.
| `historique_contrats_statut` | `id_contrats (PK)`, `id_statut_contrats`, `daty`                                                                                             |
<!-- | `documents_contrat`  | `id (PK)`, `id_contrat (FK)`, `type_document`, `chemin_fichier`, `version`, `date_creation`, `date_signature`, `statut` — Versions des contrats et documents associés.                                                                                | -->

# 10. Employés et intégration

| Table               | Attributs et définition                                                                                                                                                                               |
| ------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `statut_employes` | `id(PK)`, `libelle` - statut employes |
| `employes`          | `id (PK)`, `id_candidat (FK, unique)`, `matricule`, `date_embauche` — Informations administratives de l'employé.                                                                            |
| `historique_employes_statut` | `id_employes(PK)`, `id_statut_employes`, `daty` |


est ce que tu as compris: voici comment ca marche , dans la page finance il y a une notification (cloche avec nombre de notification)quand on clique sur une notification => la fiche du demande avec les calcul automatique s'affiche et si la demande est valider alors le boutton valider peut etre cliquer sinon elle est griser et seul le boutton annuler peut etre cliquer ,  dans cette partie le system fait tout mais juste la fincance valide ou non 
