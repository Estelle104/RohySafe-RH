# Gestion des Ressources Humaines
- Manifestation de `Besoin` personnel depuis un département spécifique - Mila olona
- Gestion de `Recrutement`
- `Embauche` - contrat de recrutement
- `Vie du salarié`
- `Évolution`
- `Rupture` de contrat

## Objectifs
> Comment est le processus de RH?

> Processus métier en règles de gestion, données, workflow et interfaces

> Concevoir un SIRH - toute cycle de vie du salarié

> Non limitée à des simples CRUD

> Faire évoluer un cas pratique jusqu' qu qrchitecture et MVP

### Bloc 1- Expression du besoin personnel
- Crétion de nouveau poste ou remplacement d'un poste
- Informations: poste, département (section, groupe,..), effectif (combien no ilaina), motif, budget , date souhaitée
> Avy any @ RH no miainga ny grille an'ny salaire, après mandalo validation betsaka
- workflow: Manager -> RH -> Finance -> Direction -> ouverture de recrutement
> 1 emp -> 1 poste

> 1 poste -> plus. emp

### Bloc 2 - Recrutement
- Transformer une demande validée en offre d' emploi
- Publier l'offre et recevoir plus. candidatures
- Présélectionner, organiser les entretiens et les tests (technique)
  - Séléction de dossier
  - Test (il y a plusieurs niveaux, on va prendre 3)
    - 0: écriture
  - Entretien
- Le plus important pour les applications: `Tracabilité`
- Conserver les évaluations et la déision finale
- Objets: Offre

### Bloc 3 - Séléction d'embauche
- Le candidat retenu recoit une poposition d'embauche
- Après acceptation: création du dossier salarié et du marticule
- Créer le contrat, le poste et l'affectation
- Point de conception: CANDIDAT != EMPLOYÉ - rmq
- Une personne peut avoir plusieurs candidateurs avant de devenir salarié

> Asiana contrat de travail (internet) rehefa hi- contacter anle olona hoe mety aminy ve 
> Rehefa accepté daholo -> Generer contrat en pdf
> Raha misy tsy accepté -> tsy voaray
> Rha misy autre option -> mandalo validation aloha d raha accepte-n ny any @ direction vao mety

### Bloc 4 - COntrat et onboarding
- 1 salarié peut avoir plusieurs contrats successifs: CDD (Contrat à Durrée Détérminer), renouvellement, CDI (Durrée Indéterminer), CE (Essaie)
- Par defaut, rehefa hanao contrat @ volouh d: CE
- Etat: creer, signer, valider
- COntrat signé-> mahazo badge na num matricule -> compte (facultatif) (ny RHno matetika manao azy) -> matériel -> formation, inscription paie
- Utiliser une checkliist et workflow d integration
- Objets: COntrat, Affectation, Onboarding, OnboardingTache, Document