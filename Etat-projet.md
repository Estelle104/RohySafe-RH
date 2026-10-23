# État du projet RohySafe RH

> Document de passation destiné à un autre développeur ou à une autre IA.
> Dernière mise à jour : 23 octobre 2026.
> Objectif : comprendre rapidement l’avancement, les choix techniques, les fichiers modifiés et les prochaines étapes.

---

## 1. Vue d’ensemble

**RohySafe RH** est une application de gestion du recrutement et des ressources humaines.

### Stack technique

- **Backend** : CodeIgniter 4, PHP 8.2, PostgreSQL.
- **Frontend** : Vue 3, Vite, Vue Router, Pinia.
- **Communication** : API JSON sous `/api`.
- **Développement local** :
  - Backend : `http://localhost:8000`
  - Frontend : Vite avec proxy `/api` vers `http://localhost:8000`
- **Base de données** : `rohysafe_rh`
- **Configuration DB locale** : `backend/app/Config/Database.php` et `backend/.env`.
  - La configuration locale n’est pas la configuration commitée. Ne pas commit les identifiants locaux.

### Organisation principale

```text
backend/
  app/Controllers/Api/    Contrôleurs API
  app/Models/             Modèles CodeIgniter
  app/Config/Routes.php   Routes API
frontend/
  src/views/              Pages Vue
  src/composables/        Logique des pages
  src/services/           Appels API
  src/router/index.js     Routes frontend
sql/                      Scripts SQL et données de test
```

### Domaine métier

Le flux principal est :

```text
Demande de recrutement → Validation finance/DG → Offre → Candidature
→ Présélection → Entretiens → Proposition d’embauche → Employé/Contrat
```

---

## 2. État Git

- Branche courante : **`Pre-selection`**
- HEAD : `3fd0aab` (`Merge branch 'dev' into Pre-selection`)
- Branche distante suivie : `origin/Pre-selection`
- **Aucun commit n’a été effectué pour les travaux décrits dans ce document.**
- Le working tree contient des modifications et de nouveaux fichiers.

### Modifications locales préexistantes

Ces fichiers étaient déjà modifiés avant les travaux récents :

- `.gitignore`
- `backend/app/Config/Database.php`
- `backend/app/Models/DemandeModel.php`
- log non suivi `backend/writable/logs/log-2026-10-23.log`

### Point important sur `DemandeModel.php`

La version commitée de `backend/app/Models/DemandeModel.php` contient une erreur de syntaxe
(accolade manquante). La version du working tree corrige cette erreur et passe `php -l`.
Ne pas écraser cette correction.

### Branche `s_bloc_3`

La branche `s_bloc_3` contient une maquette non fusionnée de détail d’offre et de CV.
Décision prise : **ne pas la merger directement**. Le travail actuel a été repris depuis
`Pre-selection`.

---

## 3. Fonctionnalités terminées

### 3.1 Connexion et inscription candidat

Le login et l’inscription candidat sont maintenant fonctionnels.

#### Backend

Route :

```text
POST /api/candidats/login
```

Contrôleur : `backend/app/Controllers/Api/AuthCandidat.php`
Modèle : `backend/app/Models/CandidatModel.php` (`findByIdentifiant()`)

Payload :

```json
{
  "identifiant": "miora_test",
  "mot_de_passe": "candidat123"
}
```

L’identifiant peut être le `login` ou l’email du candidat. Le mot de passe est vérifié
avec `password_verify()` contre la colonne `candidats.mdp`.

En cas de succès, l’API renvoie le candidat sans le champ `mdp` :

```json
{
  "message": "Connexion réussie.",
  "candidat": {
    "id": "1",
    "nom": "Rakoto",
    "prenom": "Se",
    "email": "se@example.test",
    "login": "miora_test"
  }
}
```

Une erreur d’identifiant ou de mot de passe renvoie `401`.
Un champ vide renvoie `422`.

#### Frontend

- Page : `/candidats/login`
- Vue : `frontend/src/views/LoginCandidatsView.vue`
- Service : `frontend/src/services/candidatAuth.js`

Après connexion, le candidat est stocké dans `localStorage` sous la clé
`rohysafe_candidat`. La route `/offres/:id/postuler` est protégée par
`router.beforeEach()` : un candidat non connecté est redirigé vers le login avec
`?redirect=...`, puis renvoyé vers l’offre après connexion.

La navbar des offres affiche le prénom du candidat connecté et un bouton
**« Déconnexion »**. Le formulaire CV propose également une déconnexion.
Le login interne propose un lien **« Vous êtes candidat ? Se connecter »**.

#### Inscription

Route :

```text
POST /api/candidats/inscription
```

Page : `/candidats/inscription`
Vue : `frontend/src/views/InscriptionCandidatView.vue`

Payload :

```json
{
  "nom": "Rakoto",
  "prenom": "Miora",
  "email": "miora@example.test",
  "telephone": "0340000001",
  "ville": "Antananarivo",
  "identifiant": "miora_test",
  "mot_de_passe": "motdepasse123",
  "confirmation": "motdepasse123"
}
```

Règles :

- nom, prénom, email et identifiant obligatoires ;
- email valide et unique ;
- identifiant unique, de 3 à 100 caractères, sans espace ;
- mot de passe d’au moins 8 caractères ;
- confirmation identique au mot de passe ;
- mot de passe hashé avec `password_hash()` ;
- le champ `mdp` n’est jamais renvoyé.

Réponses :

- `201` : compte créé + candidat sans `mdp` ;
- `409` : email ou identifiant déjà utilisé ;
- `422` : données invalides.

Après l’inscription, le frontend enregistre automatiquement le candidat dans la session
et redirige vers l’offre demandée (`?redirect=...`) ou vers `/offres`.
La page de login propose le lien **« Créer un compte »**, et la page d’inscription
propose le lien **« Se connecter »**.

#### Comptes de démonstration

```text
miora_test / candidat123
tiana_test / candidat123
```

Le script `sql/22-10-26-010-login-candidat.sql` réinitialise ces deux mots de passe
avec un hash `password_hash()`.

> Limite actuelle : l’authentification est stockée côté navigateur. Les API CV
> reçoivent toujours l’`id_candidat` dans l’URL et ne vérifient pas de jeton serveur.
> Une vraie session/token devra être ajoutée pour empêcher un utilisateur de modifier
> l’identifiant passé aux API.

### 3.2 CV candidat complet

Le CV en ligne candidat est maintenant fonctionnel.

#### Page candidat

- URL : `/offres/:id/postuler` (accès protégé par login candidat)
- Vue : `frontend/src/views/CvCandidatureView.vue`
- Composable : `frontend/src/composables/useCvCandidature.js`
- Service : `frontend/src/services/candidatService.js`

L’identifiant passé aux API provient du candidat connecté
(`getCandidatConnecte().id`) et non plus d’une constante.

La page permet de saisir :

- **Identité** : nom, prénom, email, téléphone, ville.
- **Expériences professionnelles** : poste, date de début, date de fin, description.
- **Formations** : diplôme, établissement, dates, description.
- **Compétences** : compétence et niveau sur 20.
- **Expériences demandées par l’offre** : une période par exigence.

#### Stockage

L’identité est stockée dans la table **`candidats`** :

```text
candidats(id, nom, prenom, email, telephone, ville, date_creation)
```

Les autres sections du CV sont stockées dans :

- `experiences_candidat`
- `formations_candidat`
- `competences_candidat`
- `candidatures`
- `experiences_candidature` pour les périodes répondant aux exigences d’une offre

La vue `vue_cv_candidats` agrège les informations du CV pour l’API.
Elle ne stocke rien.

#### API CV

Routes :

```text
GET  /api/candidats/:idCandidat/cv?offre=:idOffre
GET  /api/candidats/:idCandidat/offres/:idOffre/cv
POST /api/candidats/:idCandidat/offres/:idOffre/cv
```

Contrôleur : `backend/app/Controllers/Api/CvCandidature.php`
Modèle : `backend/app/Models/CandidatModel.php`
Modèle expériences : `backend/app/Models/ExperienceCandidatureModel.php`

Le GET du formulaire retourne :

```json
{
  "offre": {},
  "candidature": {},
  "experiences": [],
  "cv": {},
  "postes": [],
  "competences": []
}
```

Le POST accepte un payload complet :

```json
{
  "candidat": {
    "nom": "Rakoto",
    "prenom": "Miora",
    "email": "miora@example.test",
    "telephone": "0340000001",
    "ville": "Antananarivo"
  },
  "experiences": [
    {
      "id_exigence": 1,
      "date_debut": "2022-01-01",
      "date_fin": "2025-12-31"
    }
  ],
  "experiences_candidat": [
    {
      "id_poste": 9,
      "date_debut": "2022-01-01",
      "date_fin": "2025-12-31",
      "descriptions": "..."
    }
  ],
  "formations": [
    {
      "diplome": "Licence en informatique",
      "etablissement": "Université",
      "date_debut": "2019-10-01",
      "date_fin": "2022-07-31",
      "description": "..."
    }
  ],
  "competences": [
    {
      "id_competence": 1,
      "niveau": 16
    }
  ]
}
```

Le backend valide les données, met à jour le candidat, crée ou met à jour la candidature,
puis remplace les sections du CV présentes dans le payload.

> Les sections `experiences_candidat`, `formations` et `competences` ne sont remplacées
> que si leur clé est présente dans le payload. Cela évite de supprimer une section
> lorsqu’on envoie uniquement les expériences d’offre.

#### Règles métier CV

- `nom` et `prenom` obligatoires.
- `email` obligatoire et unique.
- Une période d’expérience d’offre doit avoir une date de début et une date de fin.
- `date_fin` ne peut pas être dans le futur.
- Une seule période par exigence d’offre.
- `duree_min` / `duree_max` sont en **mois**.
- Convention de calcul utilisée : **1 mois = 30 jours**.
- Une candidature peut être enregistrée avec zéro expérience d’offre.
- `obligatoire` ne bloque pas l’enregistrement ; il sert à la présélection.

#### CV côté recruteur

- Route API : `GET /api/candidats/:idCandidat/cv?offre=:idOffre`
- Contrôleur : `backend/app/Controllers/Api/CandidatIdeal.php`
- Vue : `frontend/src/views/CandidatsIdeauxView.vue`
- Composant : `frontend/src/components/CvCard.vue`

Le CV recruteur affiche le profil du candidat et les expériences liées à l’offre.

### 3.3 Backoffice des exigences d’offre

Une interface backoffice permet de gérer les exigences d’une offre.

#### Liste des offres

- URL : `/backoffice/offres`
- Vue : `frontend/src/views/BackofficeOffresView.vue`
- Composable : `frontend/src/composables/useBackofficeOffres.js`
- Service : `frontend/src/services/exigenceService.js`

La page affiche toutes les offres, y compris :

- `brouillon`
- `publiee`
- `cloturee`

Chaque carte affiche le département, le contrat, le statut, la date limite, le nombre
d’exigences d’expérience et le nombre de compétences requises.

Le bouton **« Ajouter des exigences »** ouvre le formulaire de l’offre.

Un lien **« Backoffice »** a été ajouté dans la navbar de `/offres`.

#### Formulaire des exigences

- URL : `/backoffice/offres/:id/exigences`
- Vue : `frontend/src/views/ExigencesOffreView.vue`
- Composable : `frontend/src/composables/useExigencesOffre.js`

Le formulaire permet :

- d’ajouter, modifier et supprimer une **exigence d’expérience** ;
- d’ajouter, modifier et supprimer une **compétence requise**.

Champs d’une exigence d’expérience :

- poste ;
- durée minimale en mois ;
- durée maximale en mois, optionnelle ;
- obligatoire ou non.

Champs d’une compétence requise :

- compétence ;
- note minimale sur 20, optionnelle ;
- obligatoire ou non.

#### API backoffice des exigences

```text
GET    /api/backoffice/offres
GET    /api/backoffice/offres/:idOffre/exigences
POST   /api/backoffice/offres/:idOffre/exigences/experience
DELETE /api/backoffice/offres/:idOffre/exigences/experience/:idExigence
POST   /api/backoffice/offres/:idOffre/exigences/competence
DELETE /api/backoffice/offres/:idOffre/exigences/competence/:idCritere
```

Contrôleur : `backend/app/Controllers/Api/ExigenceOffre.php`

Payload expérience :

```json
{
  "id": null,
  "id_poste_experience": 1,
  "duree_min": 6,
  "duree_max": 10,
  "obligatoire": false
}
```

`id` est `null` pour un ajout, et contient l’identifiant de l’exigence pour une modification.

Payload compétence :

```json
{
  "id": null,
  "id_competence": 1,
  "note_minimale": 12,
  "obligatoire": true
}
```

Règles :

- Un poste ne peut avoir qu’une seule exigence d’expérience par offre.
- Une compétence ne peut être requise qu’une seule fois par offre.
- `duree_max` doit être supérieure ou égale à `duree_min`.
- La suppression d’une exigence d’expérience est refusée si une candidature l’utilise déjà.
- Les doublons renvoient `422`.

### 3.4 Entretiens et évaluation du jury

La planification d’un entretien se fait depuis la page des candidats idéaux d’une offre.
Le bouton « Planifier un entretien » ouvre un dialogue avec date/heure, lieu et commentaire.

Règles appliquées :

- la candidature passe au statut `entretien` ;
- l’entretien est créé avec le statut `planifie` ;
- un historique de statut est enregistré pour la candidature et pour l’entretien ;
- une candidature ne peut avoir qu’un seul entretien ;
- seules les candidatures `en_attente`, `preselectionne` ou `selectionne` peuvent être planifiées.

Après l’entretien, le jury saisit **une seule évaluation par candidature** :

- note sur 20 ;
- remarques libres ;
- enregistrer une nouvelle évaluation remplace la précédente ;
- l’entretien passe alors au statut `realise`.

API :

```text
POST /api/candidatures/:id/entretiens
GET  /api/entretiens/offres
GET  /api/entretiens/offres/:id/candidats
GET  /api/entretiens/candidatures/:id
PUT  /api/entretiens/candidatures/:id/evaluation
```

Les routes de création et de consultation des entretiens sont protégées par
`auth:Manager,RH`.

Pages :

- `/entretiens` : offres ayant des entretiens ;
- `/entretiens/offres/:id` : candidats convoqués pour une offre ;
- `/entretiens/candidatures/:id` : fiche d’évaluation du jury.

Migration : `sql/23-10-26-004-evaluations-candidat-simplifiee.sql`.

### 3.5 Modules déjà présents

Les modules suivants existaient déjà dans le projet :

- Finance : notifications, fiche de demande, validation/annulation.
- Direction générale : notifications, fiche, validation/refus.
- Offres : liste, détail, candidature.
- Candidats idéaux : classement, CV, planification d’entretien.
  L’accès se fait désormais depuis le backoffice (`/backoffice/candidats-ideaux`) :
  la liste publique des offres ne contient plus le bouton « Candidats idéaux ».
- Authentification interne par session (`/api/auth/login`, `/api/auth/me`, `/api/auth/logout`)
  avec contrôle des rôles `DG`, `RH`, `Manager`, `Finance`.

---

## 4. Routes frontend

```text
/                                       → redirection vers /login
/login                                  → Login interne
/candidats/login                        → Login candidat
/candidats/inscription                  → Inscription candidat
/offres                                 → Liste des offres
/offres/:id/postuler                    → CV + candidature (login requis)
/offres/:id/candidats                   → Candidats idéaux pour une offre
/demandes/:id/candidats                 → Candidats idéaux pour une demande
/backoffice/candidats-ideaux            → Liste des offres du backoffice
/backoffice/candidats-ideaux/:id        → Candidats idéaux d’une offre (backoffice)
/backoffice/offres                      → Liste backoffice des offres
/backoffice/offres/:id/exigences        → Formulaire des exigences
/finance                                → Espace finance
/direction-generale                     → Espace direction générale
/entretiens                             → Offres avec entretiens (Manager/RH)
/entretiens/offres/:id                  → Candidats convoqués (Manager/RH)
/entretiens/candidatures/:id            → Évaluation du jury (Manager/RH)
```

Fichier : `frontend/src/router/index.js`

---

## 5. Base de données

### Scripts SQL

Les scripts se trouvent dans `sql/`.

Ordre indicatif d’application :

```text
sql/00-reset-database.sql
sql/22-10-26-001-modification-correction-table.sql
sql/22-10-26-002-modification-ajout-table.sql
sql/22-10-26-003-donees-test-offres.sql
sql/22-10-26-004-donees-test-finance.sql
sql/22-10-26-006-donnees-completes.sql
sql/22-10-26-007.vue.sql
sql/22-10-26-008-vue-cv-candidat.sql
sql/22-10-26-009-experiences-candidature.sql
sql/22-10-26-010-login-candidat.sql
sql/23-10-26-001-donee-test-grille-salariale.sql
sql/23-10-26-002-donee-test-candidatures.sql
sql/23-10-26-003-cdt.sql
sql/23-10-26-004-evaluations-candidat-simplifiee.sql
sql/23-10-26-005-vue-candidats-postes-sans-criteres.sql
```

`00-reset-database.sql` supprime/réinitialise la base : à utiliser avec prudence.

### Scripts ajoutés ou modifiés récemment

- `sql/22-10-26-008-vue-cv-candidat.sql`
  - Vue `vue_cv_candidats`.
  - Expose maintenant `id_poste` dans `experiences` et `id_competence` dans `competences`.
- `sql/22-10-26-009-experiences-candidature.sql`
  - Crée `experiences_candidature`.
  - Ajoute la contrainte unique `(id_poste_departement, id_poste_experience)` sur `exigences_experience`.
- `sql/22-10-26-010-login-candidat.sql`
  - Réinitialise les mots de passe des comptes de démonstration `miora_test` et `tiana_test` avec `candidat123`.
- `sql/23-10-26-004-evaluations-candidat-simplifiee.sql`
  - Supprime les anciennes évaluations de test.
  - Recrée `evaluations_candidat` avec une seule ligne par candidature :
    `id`, `id_candidature` (unique), `note`, `remarques`, `date_evaluation`.
- `sql/23-10-26-005-vue-candidats-postes-sans-criteres.sql`
  - Met à jour `vue_classement_candidats_postes`.
  - Pour un poste sans critère de compétence, tous les candidats sont classés avec
    un score de 0 : ils restent ainsi visibles dans les candidats idéaux et la
    présélection peut utiliser leurs expériences de candidature.

### Table `experiences_candidature`

```sql
CREATE TABLE experiences_candidature (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidature BIGINT NOT NULL REFERENCES candidatures(id) ON DELETE CASCADE,
    id_exigence BIGINT NOT NULL REFERENCES exigences_experience(id) ON DELETE RESTRICT,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    duree_jours INTEGER GENERATED ALWAYS AS (date_fin - date_debut) STORED,
    CHECK (date_fin >= date_debut),
    UNIQUE (id_candidature, id_exigence)
);
```

### Données de test connues

- Candidat `1` : Miora Rakoto.
- Offre `8` : Développeur Web - RohySafe.
- Candidature `1` : candidat `1` + offre `8`.
- Exigence `1` : 12 mois d’expérience.
- Réponse enregistrée : `2022-01-01` → `2025-12-31`, soit `1460` jours.
- La présélection de l’offre `8` retourne Miora avec `experience_ok: true`.
- La liste backoffice contient 8 offres.

### Configuration PostgreSQL

Le projet utilise PostgreSQL. La configuration locale est dans :

- `backend/app/Config/Database.php`
- `backend/.env`

Ne pas publier ni commit les identifiants locaux.

---

## 6. Vérifications déjà effectuées

### Backend

`php -l` a été exécuté avec succès sur :

- `backend/app/Controllers/Api/AuthCandidat.php`
- `backend/app/Controllers/Api/CvCandidature.php`
- `backend/app/Controllers/Api/ExigenceOffre.php`
- `backend/app/Controllers/Api/CandidatIdeal.php`
- `backend/app/Models/CandidatModel.php`
- `backend/app/Models/OffreModel.php`
- `backend/app/Models/ExperienceCandidatureModel.php`
- `backend/app/Models/DemandeModel.php`
- `backend/app/Config/Routes.php`

### Frontend

```bash
cd frontend
npm run build
```

Build Vite validé.

### Tests API manuels

Login candidat :

- `POST /api/candidats/login` avec `miora_test / candidat123` : `200`.
- `POST /api/candidats/login` avec l’email de `tiana_test` : `200`.
- Mauvais mot de passe : `401`.
- Identifiant ou mot de passe vide : `422`.
- Le champ `mdp` n’est pas retourné dans la réponse.
- `GET /api/candidats/2/offres/8/cv` avec l’identifiant du candidat connecté : `200`.

Inscription candidat :

- Inscription valide : `201`, candidat retourné sans `mdp`.
- Connexion immédiate avec le compte créé : `200`.
- Email déjà utilisé : `409`.
- Identifiant déjà utilisé : `409`.
- Mot de passe trop court : `422`.
- Confirmation différente : `422`.
- Compte de test supprimé après vérification.

CV candidat :

- `GET /api/candidats/1/offres/8/cv` : `200`
- `POST /api/candidats/1/offres/8/cv` avec CV complet : `200`
- Identité, expériences, formations et compétences correctement persistées.
- Dates futures : `422`.
- Exigence inconnue : `422`.

Exigences d’offre :

- `GET /api/backoffice/offres` : `200`, 8 offres.
- `GET /api/backoffice/offres/8/exigences` : `200`.
- Ajout d’une exigence d’expérience : `201`.
- Modification : `200`.
- Ajout en doublon : `422`.
- Suppression : `200`.
- Suppression d’une exigence utilisée par une candidature : `422`.
- Ajout d’une compétence requise : `201`.
- Modification : `200`.
- Doublon : `422`.
- Suppression : `200`.

### Artefacts de build

Le build frontend écrit dans `backend/public/`. Après un build, restaurer les fichiers générés
pour ne pas polluer le dépôt :

```bash
git restore backend/public/index.html
rm -rf backend/public/assets
```

---

## 7. Décisions et conventions importantes

- Le candidat courant provient de la session frontend (`localStorage`, clé `rohysafe_candidat`).
- Le login candidat vérifie le mot de passe avec `password_verify()`.
- L’inscription candidat hash le mot de passe avec `password_hash()` et exige un email et un identifiant uniques.
- L’`id_candidat` passé aux API CV est celui du candidat connecté.
- L’authentification candidat est encore côté client : aucune session/token n’est vérifié par l’API.
- L’accès backoffice n’est pas protégé par un rôle : les routes sont accessibles directement.
- Les durées d’expérience sont exprimées en **mois**.
- `1 mois = 30 jours` pour le calcul de présélection.
- Une période entre deux dates est calculée en jours via `date_fin - date_debut`.
- Les expériences de profil restent dans `experiences_candidat`.
- Les réponses aux exigences d’une offre restent dans `experiences_candidature`.
- `OffreModel::getDetail()` normalise les booléens PostgreSQL en vrais booléens PHP.
- Le frontend compare parfois les booléens avec `=== true`.

---

## 8. Fichiers importants

### Backend

- `backend/app/Config/Routes.php`
  - Routes API, y compris login candidat, CV et backoffice des exigences.
- `backend/app/Controllers/Api/AuthCandidat.php`
  - Connexion candidat par login ou email et inscription.
- `backend/app/Controllers/Api/CvCandidature.php`
  - GET/POST du CV et de la candidature.
- `backend/app/Controllers/Api/ExigenceOffre.php`
  - Liste backoffice des offres et CRUD des exigences.
- `backend/app/Controllers/Api/CandidatIdeal.php`
  - Candidats idéaux, CV recruteur, proposition d’embauche.
- `backend/app/Controllers/Api/EntretienController.php`
  - Liste des offres avec entretiens, candidats convoqués, détail et évaluation.
- `backend/app/Models/CandidatModel.php`
  - `findByIdentifiant()`, `getCv()`, présélection par expériences, état des entretiens.
- `backend/app/Models/EntretienModel.php`
  - Requêtes du suivi des entretiens et enregistrement de l’évaluation unique.
- `backend/app/Models/OffreModel.php`
  - Offres publiées, liste backoffice, détail avec exigences.
- `backend/app/Models/ExperienceCandidatureModel.php`
  - Lecture des expériences liées à une candidature.
- `backend/app/Models/DemandeModel.php`
  - Correction locale à conserver.

### Frontend

- `frontend/src/router/index.js`
  - Routes des pages + garde de la route de candidature.
- `frontend/src/services/candidatAuth.js`
  - Session candidat frontend, login et déconnexion.
- `frontend/src/views/LoginCandidatsView.vue`
  - Formulaire de connexion candidat.
- `frontend/src/views/InscriptionCandidatView.vue`
  - Formulaire d’inscription du candidat.
- `frontend/src/views/LoginView.vue`
  - Login interne + lien vers l’espace candidat.
- `frontend/src/views/OffresView.vue`
  - Liste des offres, lien Backoffice, état connecté et déconnexion.
- `frontend/src/views/CvCandidatureView.vue`
  - Formulaire CV complet.
- `frontend/src/composables/useCvCandidature.js`
  - État et validation du CV.
- `frontend/src/views/BackofficeOffresView.vue`
  - Liste backoffice des offres.
- `frontend/src/composables/useBackofficeOffres.js`
  - Chargement et recherche des offres backoffice.
- `frontend/src/views/ExigencesOffreView.vue`
  - Formulaire des exigences d’une offre.
- `frontend/src/composables/useExigencesOffre.js`
  - CRUD des exigences côté frontend.
- `frontend/src/services/exigenceService.js`
  - Appels API backoffice.
- `frontend/src/services/candidatService.js`
  - Appels API candidat et CV.
- `frontend/src/components/CvCard.vue`
  - Affichage du CV recruteur.
- `frontend/src/views/CandidatsIdeauxView.vue`
  - Candidats idéaux, accès aux exigences et dialogue de planification d’entretien.
- `frontend/src/components/EntretienDialog.vue`
  - Formulaire date/heure, lieu et commentaire pour un entretien.
- `frontend/src/services/entretienService.js`
  - Appels API de création et de suivi des entretiens.
- `frontend/src/services/authInterne.js`
  - Lecture de la session interne et contrôle des rôles.
- `frontend/src/views/EntretiensOffresView.vue`
  - Liste des offres ayant des entretiens.
- `frontend/src/views/EntretiensCandidatsView.vue`
  - Candidats convoqués pour une offre.
- `frontend/src/views/EntretienEvaluationView.vue`
  - Fiche d’évaluation du jury.

### SQL

- `sql/22-10-26-008-vue-cv-candidat.sql`
- `sql/22-10-26-009-experiences-candidature.sql`
- `sql/22-10-26-010-login-candidat.sql`
- `sql/23-10-26-004-evaluations-candidat-simplifiee.sql`
- `sql/23-10-26-005-vue-candidats-postes-sans-criteres.sql`

---

## 9. Limites et points de vigilance

1. **Authentification candidat côté client uniquement.**
   Le candidat est stocké dans `localStorage` et l’`id_candidat` est passé dans l’URL
   des API CV. Un utilisateur peut modifier cet identifiant : il faudra ajouter un
   token/session vérifié côté backend.

2. **Le backoffice n’applique pas encore le contrôle de rôle.**
   L’authentification interne existe, mais les routes `/backoffice/*` et
   `/api/backoffice/*` ne sont pas toutes protégées. Les routes d’entretiens,
   elles, exigent le rôle `Manager` ou `RH`.

3. **La route de candidature est protégée côté frontend seulement.**
   Les API CV restent appelables directement si l’on connaît l’identifiant du candidat.

4. **La présélection par demande n’applique pas les nouvelles exigences d’expérience.**
   `getCandidatsIdeauxByOffre()` filtre les exigences obligatoires.
   `getCandidatsIdeauxByDemande()` garde l’ancien comportement.

5. **Le backoffice gère uniquement les exigences d’expérience et les compétences.**
   Les `criteres_softskill` ne sont pas encore intégrés au formulaire.

6. **Pas de pagination** sur la liste backoffice des offres.
   La recherche est faite côté client.

7. **Le CV candidat ne gère pas encore les documents/photo.**
   La table `documents_candidat` existe mais n’est pas éditée dans le formulaire.

8. **La table `candidats` ne contient que** :
   `nom`, `prenom`, `email`, `telephone`, `ville`.
   Ajouter date de naissance, adresse, photo, etc. nécessiterait une migration.

9. **Le fichier `DemandeModel.php` commité est invalide.**
   Le working tree contient la correction. Ne pas revenir à la version commitée sans corriger.

10. **Le build frontend écrit dans le backend.**
    Toujours restaurer `backend/public/index.html` et supprimer `backend/public/assets`
    après un build si ces fichiers ne doivent pas être commités.

11. **Aucun test automatisé métier n’a été ajouté.**
    Les vérifications ont été faites manuellement via `curl`, `php -l` et `npm run build`.

---

## 10. Prochaines étapes recommandées

### Priorité haute

1. Ajouter un token/session serveur pour sécuriser les API CV et candidat.
2. Ajouter une vraie authentification et protéger les routes backoffice.
3. Ajouter des tests automatisés sur :
   - l’enregistrement d’un CV complet ;
   - les règles de dates ;
   - la présélection ;
   - l’ajout/suppression des exigences ;
   - les doublons et les suppressions bloquées ;
   - le login candidat et la redirection après connexion.
4. Commit les changements actuels sur la branche `Pre-selection` après revue.

### Priorité moyenne

5. Gérer les softskills dans le backoffice des exigences.
6. Appliquer le filtrage par expériences à la présélection par demande.
7. Ajouter pagination et filtres serveur sur la liste des offres backoffice.
8. Afficher un récapitulatif du CV avant validation finale.
9. Ajouter la gestion des documents candidat.

### Améliorations UX

10. Afficher un message de succès proche du formulaire concerné.
11. Ajouter un lien direct vers le backoffice depuis l’espace recruteur.
12. Ajouter des confirmations et messages d’erreur plus détaillés.
13. Ajouter un état de chargement par action.

---

## 11. Commandes utiles

### Backend

```bash
cd backend
php -l app/Controllers/Api/AuthCandidat.php
php -l app/Controllers/Api/CvCandidature.php
php -l app/Controllers/Api/ExigenceOffre.php
php spark serve --port 8000
```

### Frontend

```bash
cd frontend
npm install
npm run dev
npm run build
```

### Vérification API

```bash
curl http://127.0.0.1:8000/api/backoffice/offres
curl http://127.0.0.1:8000/api/backoffice/offres/8/exigences
curl -X POST http://127.0.0.1:8000/api/candidats/login \
  -H 'Content-Type: application/json' \
  -d '{"identifiant":"miora_test","mot_de_passe":"candidat123"}'
curl http://127.0.0.1:8000/api/candidats/1/offres/8/cv

# Entretiens (session interne Manager ou RH)
curl -c cookies.txt -H 'Content-Type: application/json' \
  -d '{"login":"manager_info_test","mdp":"Password123!"}' \
  http://127.0.0.1:8000/api/auth/login
curl -b cookies.txt http://127.0.0.1:8000/api/entretiens/offres
curl -b cookies.txt http://127.0.0.1:8000/api/entretiens/offres/8/candidats
```

### Nettoyage après build

```bash
git restore backend/public/index.html
rm -rf backend/public/assets
```

---

## 12. Résumé pour la prochaine IA

Le projet est une application SIRH/recrutement CodeIgniter 4 + Vue 3.
Les fonctionnalités récemment ajoutées sont :

1. **Le login et l’inscription candidat** par identifiant/email et mot de passe,
   avec session frontend, auto-connexion après inscription et protection de la route
   de candidature.
2. **Le CV candidat complet** avec identité, expériences, formations, compétences
   et périodes d’expérience liées à une offre. L’`id_candidat` utilisé est celui du
   candidat connecté.
3. **Le backoffice des exigences des offres** avec liste des offres, formulaire
   d’ajout/modification/suppression des exigences d’expérience et des compétences requises.

Les API et le build frontend fonctionnent. Les travaux ne sont pas commités.
Les principaux points à traiter ensuite sont la sécurisation serveur des sessions,
l’authentification backoffice, les tests automatisés, les softskills et le filtrage
par expériences pour les demandes.
