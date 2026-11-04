Conception:

Bloc 5: Dossier administratif du salarie:
    Enregistrement des information personnel et proffesionnel du salarie:
        Renouvellement des doc administratif:
            Alerte / notification si date echeance est pour bientot
            Boutton renouveler
                Si residence change -> ajout d'un champs pour entrer le residence actuel 
            Toujours historiser et garder des trace de changement
    Gestion des affectations
    Historisation :
        affectation
        contrat
        cariere
        salaire
    

Bloc 6 Temps de travail et pointage:
    Definition de l'horaire et du planning:
        Emploie du Temps
        Planning:
            CRUD planning
                creer : affecter un planning a un salarié ou a un groupe de salaries.
                modif
                delete
                read
    
        Si planning comporte budget -> attente validation finance sinon: Validation Rh seulement

    Gestion du pointage:
        Entree , sortie , retard et heure supplementaire reel
    
    Calcul du temps de travail reel:
        Temps de trav theorique (dans le planning) + entrer na heure sup ou bien - retard

    Abscence -> impact salaire:
        Si abscence non justifie et non autorise:
            salaire reel =  salaire normale - (Salaire par heure * heure d'abscence)

        Si abscence justofier et autorise:
            pas d'impact 

        Tsy aiko na mbola misy type d'absence hafa 

Bloc 7 : Conges et abcences:
    Demande de conges:
        creer demande :
            Choisir le type de congé.
            Choisir la date de début.
            Choisir la date de fin.
            Ajouter un motif si nécessaire.
            Joindre un justificatif si nécessaire.
            Envoyer sa demande.
                -> verifier si soldes dispo par rapport au demande 
            
        Si demande creer :
            -> Attente de validation dans manager/RH
                -> Verfier chevauchement avec le planning etc
                -> verifier s'il y a quelqu'un pour le remplacer :

            -> Si ok on peut valider et mise a jour du solde et tout ce qui doit etre a jour 


    

    