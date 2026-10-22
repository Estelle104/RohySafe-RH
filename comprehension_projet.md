# Compréhension du projet : rohysafe_rh

> Document basé uniquement sur `table.sql`. Rien n'est modifié, c'est ma lecture du projet.

## 1. Vue d'ensemble

**rohysafe_rh** est une base de données PostgreSQL pour un **système de gestion des ressources humaines**, centré sur le **cycle de recrutement complet** :

```
Besoin (demande) → Budget/Grille salariale → Offre → Candidatures → Entretiens/Évaluations
→ Proposition d'embauche → Employé → Contrat
```

Principe récurrent : chaque entité à état a
- une table `statut_xxx` (référentiel des statuts),
- une colonne `id_statut_actuel` (état courant),
- une table `historique_xxx` (traçabilité de chaque changement de statut avec `date_action`).

---

## 2. Modules

### 2.1 Organisation & accès
| Table | Rôle |
|---|---|
| `departements` | Départements de l'entreprise |
| `roles` | Rôles applicatifs (ex : DG, RH, chef de département…) |
| `utilisateurs` | Comptes (login, mdp, rôle) |

### 2.2 Postes
| Table | Rôle |
|---|---|
| `categorie_postes` | Catégories de postes |
| `postes` | Postes (libellé + catégorie) |
| `poste_departement` | Association poste ↔ département (un poste ouvert dans un département donné). **Table pivot centrale** : demandes, grilles et exigences s'y rattachent. |

### 2.3 Demande de recrutement (besoin interne)
| Table | Rôle |
|---|---|
| `type_contrats` | CDD, CDI, CE… |
| `statut_demandes` | créée, validée, en attente, refusée, annulée |
| `demandes` | Demande de N personnes pour un `poste_departement` : type de contrat, salaire proposé, date de prise de poste, créateur, utilisateur actuellement en charge, statut actuel |
| `historique_demandes` | Historique des statuts, avec salaire proposé et remarque à chaque étape |

Une demande circule entre utilisateurs (`id_utilisateur_actuel`) : workflow de validation.

### 2.4 Salaires & budget
| Table | Rôle |
|---|---|
| `grilles_salariales` | Fourchette min/max par `poste_departement` et type de contrat |
| `histo_grille_salariales` | Historique des fourchettes avec période de validité (`date_debut`/`date_fin`) |
| `budgets` | Budget par département et par année : `montant_mensuel` et `montant_engage` |

Usage probable : vérifier que le `salaire_propose` d'une demande respecte la grille et que le budget du département le permet.

### 2.5 Offres d'emploi
| Table | Rôle |
|---|---|
| `statut_offres` | Statuts d'une offre |
| `offres` | Offre issue d'une demande : titre, description, dates de publication/limite, statut |

### 2.6 Candidats & profil
| Table | Rôle |
|---|---|
| `candidats` | Identité (nom, prénom, email unique, téléphone, ville) |
| `experiences_candidat` | Expériences passées (liées à `postes`) avec dates |
| `formations_candidat` | Diplômes, établissements, dates |
| `competences` | Référentiel de compétences (libellé + catégorie) |
| `competences_candidat` | Niveau du candidat par compétence (0–20) |
| `softskills` | Référentiel de soft skills |

### 2.7 Candidatures & sélection
| Table | Rôle |
|---|---|
| `statut_candidatures` | Statuts (étape de sélection) |
| `candidatures` | Candidat ↔ offre (unique par couple), statut actuel |
| `historique_candidature_status` | Historique des statuts |

### 2.8 Critères de présélection (calcul automatique)
| Table | Rôle |
|---|---|
| `exigences_experience` | Pour un `poste_departement` : expérience requise dans un poste donné (durée min/max, obligatoire ou non) |
| `criteres_poste` | Pour un poste : compétences requises, note minimale, obligatoire ou non |
| `criteres_softskill` | Soft skills requises, note minimale, obligatoire ou non |

Ces tables servent à **comparer automatiquement** le profil du candidat (expériences, compétences, soft skills) aux exigences du poste, pour la présélection.

### 2.9 Entretiens & évaluation
| Table | Rôle |
|---|---|
| `statut_entretiens` | Statuts d'entretien |
| `entretiens` | Entretien lié à une candidature : date, lieu, statut, commentaire |
| `historique_entretiens_statut` | Historique des statuts |
| `evaluations_candidat` | Note (0–20) par soft skill, par évaluateur et par entretien |

### 2.10 Proposition d'embauche (après présélection)
| Table | Rôle |
|---|---|
| `statut_propositions_embauche` | validée_finale, refusée, annulée, en attente, en négociation, refusée par le candidat, créée (acceptée par le DG mais pas encore ok côté candidat) |
| `propositions_embauche` | Proposition envoyée au candidat retenu |
| `historique_propositions` | Historique : salaire proposé, type de contrat, statut, motif (permet la **négociation**) |

### 2.11 Employés & contrats
| Table | Rôle |
|---|---|
| `statut_employes` | actif, congé, suspendu, démissionné, licencié, retraité |
| `employes` | Un candidat devenu employé (matricule unique, date d'embauche) |
| `historique_employes_statut` | Historique des statuts |
| `statut_contrats` | signé, rompu, créé |
| `contrats` | Contrat d'un employé : type, dates, salaire, statut, date de signature |
| `historique_contrats_statut` | Historique des statuts |

---

## 3. Flux métier compris

1. Un utilisateur crée une **demande** (poste + département, nombre, contrat, salaire). Elle passe par un workflow de validation, tracé dans l'historique.
2. Le salaire est confronté à la **grille salariale** et au **budget** du département.
3. Une demande validée donne lieu à une **offre** publiée.
4. Les **candidats** postulent (**candidatures**).
5. Présélection automatique via `exigences_experience`, `criteres_poste`, `criteres_softskill`.
6. **Entretiens** et **évaluations** (notes par soft skill et par évaluateur).
7. **Proposition d'embauche** au candidat retenu, avec négociation possible du salaire et du contrat.
8. Si acceptée : création de l'**employé** et de son **contrat**.

---

## 4. Points d'attention repérés dans le script (non corrigés)

| # | Table | Problème |
|---|---|---|
| 1 | `\ c rohysafe_rh;` | Doit s'écrire `\c rohysafe_rh;` (commande psql) |
| 2 | `departements` | `UNIQUE (id_entreprise, libelle)` : la colonne `id_entreprise` n'existe pas |
| 3 | `demandes` | `UNIQUE (id_statut_actuel)` : n'autoriserait qu'une seule demande par statut |
| 4 | `offres` | `UNIQUE (id_statut)` : même problème |
| 5 | `candidatures` | `NOT DEFAULT CURRENT_TIMESTAMP` : doit être `NOT NULL DEFAULT CURRENT_TIMESTAMP` |
| 6 | `propositions_embauche` | Virgule en trop après la dernière colonne |
| 7 | `criteres_softskill` | Aucun lien vers un poste (`id_poste` / `id_poste_departement`), donc impossible de savoir à quel poste s'applique le critère |
| 8 | `competences_candidat` | `NUMERIC(5,2)CHECK` : espace manquant (fonctionne, mais à nettoyer) |
| 9 | `statut_offres`, `entretiens`, `employes`, `contrats` | `id_statut_actuel` nullable alors que c'est NOT NULL ailleurs (incohérence possible) |
| 10 | `budgets` | `montant_mensuel` / `montant_engage` : à préciser (engagé = cumul des salaires mensuels des demandes validées ?) |

## 5. Questions ouvertes

- Le budget est-il bloquant (refus automatique si dépassé) ou seulement informatif ?
- Le salaire proposé hors grille est-il refusé automatiquement ou soumis à validation ?
- Les `criteres_poste` sont-ils par poste seul ou par `poste_departement` (comme `exigences_experience`) ?
- Qui valide à chaque étape du workflow de demande (rôles) ?
- Comment est calculé le score de présélection (pondération, critères obligatoires éliminatoires) ?
