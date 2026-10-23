-- Jeu de données complet et cohérent pour RohySafe-RH.
-- Pré-requis :
--   1. table.sql
--   2. sql/22-10-26-002-modification-ajout-table.sql
-- Ce fichier est prévu pour une base vide et s'exécute dans l'ordre
-- des dépendances entre les tables.

BEGIN;

-- 1. Référentiels de l'organisation
INSERT INTO departements (libelle) VALUES
    ('Informatique'),
    ('Ressources humaines'),
    ('Finance')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO roles (libelle) VALUES
    ('DG'),
    ('RH'),
    ('Manager')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO type_contrats (libelle) VALUES
    ('CDI'),
    ('CDD'),
    ('CE')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO statut_demandes (libelle) VALUES
    ('cree'),
    ('valide'),
    ('en_attente'),
    ('refusee'),
    ('annulee')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO statut_offres (libelle) VALUES
    ('brouillon'),
    ('publiee'),
    ('cloturee')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO statut_candidatures (libelle) VALUES
    ('en_attente'),
    ('preselectionne'),
    ('ecarte'),
    ('entretien'),
    ('retenu'),
    ('refuse')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO statut_entretiens (libelle) VALUES
    ('planifie'),
    ('realise'),
    ('annule')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO statut_propositions_embauche (libelle) VALUES
    ('cree'),
    ('en_attente'),
    ('en_negociation'),
    ('validee_finale'),
    ('refusee'),
    ('refusee_candidat'),
    ('annulee')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO statut_employes (libelle) VALUES
    ('actif'),
    ('conge'),
    ('suspendu'),
    ('demissionne'),
    ('licencie'),
    ('retraite')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO statut_contrats (libelle) VALUES
    ('cree'),
    ('signe'),
    ('rompu')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO type_documents (libelle) VALUES
    ('cv'),
    ('diplome'),
    ('cin')
ON CONFLICT (libelle) DO NOTHING;

-- 2. Utilisateurs internes
-- Mot de passe commun de test : Password123!
INSERT INTO utilisateurs (login, mdp, id_role, id_departement)
SELECT v.login,
       '$2y$10$fFZrUXSwkqb9HXDO1ppuh.cuh8miKpaVG.Fg6w/sP4byn2gJVhC5G',
       r.id,
       d.id
FROM (
    VALUES
        ('dg_test', 'DG', NULL),
        ('rh_test', 'RH', 'Ressources humaines'),
        ('manager_info_test', 'Manager', 'Informatique')
) AS v(login, role_libelle, departement_libelle)
JOIN roles r ON r.libelle = v.role_libelle
LEFT JOIN departements d ON d.libelle = v.departement_libelle
ON CONFLICT (login) DO NOTHING;

-- 3. Postes et rattachements aux départements
INSERT INTO categorie_postes (libelle) VALUES
    ('Développement'),
    ('Ressources humaines')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO postes (libelle, id_categorie)
SELECT v.libelle, c.id
FROM (
    VALUES
        ('Développeur Web', 'Développement'),
        ('Responsable RH', 'Ressources humaines')
) AS v(libelle, categorie)
JOIN categorie_postes c ON c.libelle = v.categorie
ON CONFLICT (id_categorie, libelle) DO NOTHING;

INSERT INTO poste_departement (id_poste, id_departement)
SELECT p.id, d.id
FROM (
    VALUES
        ('Développeur Web', 'Informatique'),
        ('Responsable RH', 'Ressources humaines')
) AS v(poste, departement)
JOIN postes p ON p.libelle = v.poste
JOIN departements d ON d.libelle = v.departement
ON CONFLICT (id_poste, id_departement) DO NOTHING;

-- 4. Grilles salariales et budget
INSERT INTO grilles_salariales (
    id_poste_departement, montant_min, montant_max, id_type_contrat
)
SELECT pd.id, v.montant_min, v.montant_max, tc.id
FROM (
    VALUES
        ('Développeur Web', 'Informatique', 'CDI', 1200000::NUMERIC, 2200000::NUMERIC),
        ('Responsable RH', 'Ressources humaines', 'CDI', 1400000::NUMERIC, 2500000::NUMERIC)
) AS v(poste, departement, contrat, montant_min, montant_max)
JOIN postes p ON p.libelle = v.poste
JOIN departements d ON d.libelle = v.departement
JOIN poste_departement pd ON pd.id_poste = p.id AND pd.id_departement = d.id
JOIN type_contrats tc ON tc.libelle = v.contrat
ON CONFLICT (id_poste_departement, id_type_contrat) DO NOTHING;

INSERT INTO budgets (id_departement, annee, montant_mensuel, montant_engage)
SELECT d.id, 2026, 10000000, 0
FROM departements d
WHERE d.libelle IN ('Informatique', 'Ressources humaines')
ON CONFLICT (id_departement, annee) DO NOTHING;

-- 5. Demandes de recrutement
INSERT INTO demandes (
    date_creation,
    id_poste_departement,
    id_createur,
    id_utilisateur_actuel,
    id_statut_actuel,
    nombre_demande,
    id_type_contrat,
    date_prise_poste,
    salaire_propose
)
SELECT v.date_creation::timestamp,
       pd.id,
       creator.id,
       creator.id,
       sd.id,
       v.nombre,
       tc.id,
       v.date_prise::date,
       v.salaire
FROM (
    VALUES
        ('Développeur Web', 'Informatique', 'manager_info_test', 'valide',
         2, 'CDI', '2026-12-01', 1700000::NUMERIC, '2026-10-10'),
        ('Responsable RH', 'Ressources humaines', 'rh_test', 'en_attente',
         1, 'CDI', '2027-01-01', 1800000::NUMERIC, '2026-10-12')
) AS v(poste, departement, createur, statut, nombre, contrat, date_prise, salaire, date_creation)
JOIN postes p ON p.libelle = v.poste
JOIN departements d ON d.libelle = v.departement
JOIN poste_departement pd ON pd.id_poste = p.id AND pd.id_departement = d.id
JOIN utilisateurs creator ON creator.login = v.createur
JOIN statut_demandes sd ON sd.libelle = v.statut
JOIN type_contrats tc ON tc.libelle = v.contrat
WHERE NOT EXISTS (
    SELECT 1
    FROM demandes existing
    WHERE existing.id_poste_departement = pd.id
      AND existing.date_creation::date = v.date_creation::date
);

INSERT INTO historique_demandes (
    id_demande, id_statut_demande, id_utilisateur, salaire_propose, remarque
)
SELECT dm.id, sd.id, u.id, dm.salaire_propose, 'Demande créée pour test du workflow'
FROM demandes dm
JOIN statut_demandes sd ON sd.id = dm.id_statut_actuel
JOIN utilisateurs u ON u.id = dm.id_createur
WHERE NOT EXISTS (
    SELECT 1 FROM historique_demandes h WHERE h.id_demande = dm.id
);

-- 6. Offres publiées et brouillon
INSERT INTO offres (
    id_demande, titre, descriptions, date_publication, date_limite, id_statut
)
SELECT dm.id,
       v.titre,
       v.description,
       v.date_publication::date,
       v.date_limite::date,
       so.id
FROM (
    VALUES
        ('Développeur Web', 'Développeur Web - RohySafe',
         'Développement et maintenance des applications RH.',
         '2026-10-20', '2026-11-30', 'publiee'),
        ('Responsable RH', 'Responsable des ressources humaines',
         'Gestion administrative et accompagnement des collaborateurs.',
         NULL, NULL, 'brouillon')
) AS v(poste, titre, description, date_publication, date_limite, statut)
JOIN postes p ON p.libelle = v.poste
JOIN demandes dm ON dm.id_poste_departement IN (
    SELECT pd.id
    FROM poste_departement pd
    WHERE pd.id_poste = p.id
)
JOIN statut_offres so ON so.libelle = v.statut
WHERE NOT EXISTS (
    SELECT 1 FROM offres existing WHERE existing.titre = v.titre
);

-- 7. Compétences, soft skills et critères de sélection
INSERT INTO competences (libelle, categorie) VALUES
    ('PHP', 'Technique'),
    ('PostgreSQL', 'Technique'),
    ('Gestion RH', 'Métier')
ON CONFLICT (libelle, categorie) DO NOTHING;

INSERT INTO softskills (libelle) VALUES
    ('Communication'),
    ('Rigueur'),
    ('Esprit d''équipe')
ON CONFLICT (libelle) DO NOTHING;

INSERT INTO criteres_poste (id_poste, id_competence, note_minimale, obligatoire)
SELECT p.id, c.id, v.note, TRUE
FROM (
    VALUES
        ('Développeur Web', 'PHP', 'Technique', 12::NUMERIC),
        ('Développeur Web', 'PostgreSQL', 'Technique', 10::NUMERIC),
        ('Responsable RH', 'Gestion RH', 'Métier', 12::NUMERIC)
) AS v(poste, competence, categorie, note)
JOIN postes p ON p.libelle = v.poste
JOIN competences c ON c.libelle = v.competence AND c.categorie = v.categorie
ON CONFLICT (id_poste, id_competence) DO NOTHING;

INSERT INTO criteres_softskill (id_poste, id_softskill, note_minimale, obligatoire)
SELECT p.id, s.id, 12, TRUE
FROM postes p
JOIN softskills s ON s.libelle IN ('Communication', 'Rigueur')
WHERE p.libelle = 'Développeur Web'
ON CONFLICT (id_poste, id_softskill) DO NOTHING;

INSERT INTO exigences_experience (
    id_poste_departement, id_poste_experience, duree_min, obligatoire
)
SELECT target_pd.id, experience_poste.id, 12, TRUE
FROM poste_departement target_pd
JOIN postes target_poste ON target_poste.id = target_pd.id_poste
JOIN departements d ON d.id = target_pd.id_departement
JOIN postes experience_poste ON experience_poste.libelle = 'Développeur Web'
WHERE target_poste.libelle = 'Développeur Web'
  AND d.libelle = 'Informatique'
  AND NOT EXISTS (
      SELECT 1
      FROM exigences_experience e
      WHERE e.id_poste_departement = target_pd.id
        AND e.id_poste_experience = experience_poste.id
  );

-- 8. Candidats et profils
INSERT INTO candidats (
    nom, prenom, email, telephone, ville, login, mdp
)
VALUES
    ('Rakoto', 'Miora', 'miora.rakoto@example.test', '0340000001',
     'Antananarivo', 'miora_test',
     '$2y$10$fFZrUXSwkqb9HXDO1ppuh.cuh8miKpaVG.Fg6w/sP4byn2gJVhC5G'),
    ('Andria', 'Tiana', 'tiana.andria@example.test', '0340000002',
     'Antsirabe', 'tiana_test',
     '$2y$10$fFZrUXSwkqb9HXDO1ppuh.cuh8miKpaVG.Fg6w/sP4byn2gJVhC5G')
ON CONFLICT (email) DO NOTHING;

INSERT INTO experiences_candidat (
    id_candidat, id_poste, date_debut, date_fin, descriptions
)
SELECT c.id, p.id, '2022-01-01', '2025-12-31',
       'Développement d''applications internes et maintenance corrective.'
FROM candidats c
JOIN postes p ON p.libelle = 'Développeur Web'
WHERE c.email = 'miora.rakoto@example.test'
  AND NOT EXISTS (
      SELECT 1 FROM experiences_candidat e
      WHERE e.id_candidat = c.id AND e.id_poste = p.id
  );

INSERT INTO formations_candidat (
    id_candidat, diplome, etablissement, date_debut, date_fin, description
)
SELECT c.id, 'Licence en informatique', 'Université de Madagascar',
       '2019-10-01', '2022-07-31', 'Formation en développement logiciel.'
FROM candidats c
WHERE c.email = 'miora.rakoto@example.test'
  AND NOT EXISTS (
      SELECT 1 FROM formations_candidat f
      WHERE f.id_candidat = c.id AND f.diplome = 'Licence en informatique'
  );

INSERT INTO documents_candidat (
    id_candidat, id_type_document, nom_fichier, chemin_fichier
)
SELECT c.id, td.id, 'cv-miora-rakoto.pdf', 'uploads/cv/cv-miora-rakoto.pdf'
FROM candidats c
JOIN type_documents td ON td.libelle = 'cv'
WHERE c.email = 'miora.rakoto@example.test'
  AND NOT EXISTS (
      SELECT 1 FROM documents_candidat dc
      WHERE dc.id_candidat = c.id AND dc.nom_fichier = 'cv-miora-rakoto.pdf'
  );

-- 9. Candidatures et statut historique
INSERT INTO candidatures (id_candidat, id_offre, id_statut_actuel)
SELECT c.id, o.id, sc.id
FROM candidats c
JOIN offres o ON o.titre = 'Développeur Web - RohySafe'
JOIN statut_candidatures sc ON sc.libelle = 'retenu'
WHERE c.email = 'miora.rakoto@example.test'
ON CONFLICT (id_candidat, id_offre) DO NOTHING;

INSERT INTO historique_candidature_status (
    id_candidature, id_statut_candidature
)
SELECT ca.id, sc.id
FROM candidatures ca
JOIN statut_candidatures sc ON sc.id = ca.id_statut_actuel
WHERE NOT EXISTS (
    SELECT 1 FROM historique_candidature_status h
    WHERE h.id_candidature = ca.id
);

DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'competences_candidat'
          AND column_name = 'id_candidature'
    ) THEN
        INSERT INTO competences_candidat (id_candidature, id_competence, niveau)
        SELECT ca.id, comp.id, v.niveau
        FROM (
            VALUES
                ('miora.rakoto@example.test', 'PHP', 'Technique', 16::NUMERIC),
                ('miora.rakoto@example.test', 'PostgreSQL', 'Technique', 14::NUMERIC),
                ('tiana.andria@example.test', 'Gestion RH', 'Métier', 15::NUMERIC)
        ) AS v(email, libelle, categorie, niveau)
        JOIN candidats c ON c.email = v.email
        JOIN candidatures ca ON ca.id_candidat = c.id
        JOIN competences comp ON comp.libelle = v.libelle AND comp.categorie = v.categorie
        ON CONFLICT (id_candidature, id_competence) DO NOTHING;
    ELSE
        INSERT INTO competences_candidat (id_candidat, id_competence, niveau)
        SELECT c.id, comp.id, v.niveau
        FROM (
            VALUES
                ('miora.rakoto@example.test', 'PHP', 'Technique', 16::NUMERIC),
                ('miora.rakoto@example.test', 'PostgreSQL', 'Technique', 14::NUMERIC),
                ('tiana.andria@example.test', 'Gestion RH', 'Métier', 15::NUMERIC)
        ) AS v(email, libelle, categorie, niveau)
        JOIN candidats c ON c.email = v.email
        JOIN competences comp ON comp.libelle = v.libelle AND comp.categorie = v.categorie
        ON CONFLICT (id_candidat, id_competence) DO NOTHING;
    END IF;
END
$$;

-- 10. Entretien et évaluation
INSERT INTO entretiens (
    id_candidature, date_entretien, lieu_entretien, id_statut_actuel, commentaire
)
SELECT ca.id,
       '2026-10-25 09:00:00',
       'Salle RH - Antananarivo',
       se.id,
       'Entretien technique et motivationnel.'
FROM candidatures ca
JOIN statut_entretiens se ON se.libelle = 'realise'
WHERE NOT EXISTS (
    SELECT 1 FROM entretiens e WHERE e.id_candidature = ca.id
);

INSERT INTO historique_entretiens_statut (id_entretien, id_statut_entretien)
SELECT e.id, se.id
FROM entretiens e
JOIN statut_entretiens se ON se.id = e.id_statut_actuel
WHERE NOT EXISTS (
    SELECT 1 FROM historique_entretiens_statut h
    WHERE h.id_entretien = e.id
);

INSERT INTO evaluations_candidat (
    id_entretien, id_softskill, id_evaluateur, note, commentaire
)
SELECT e.id, s.id, u.id, v.note, v.commentaire
FROM entretiens e
JOIN utilisateurs u ON u.login = 'manager_info_test'
JOIN (
    VALUES
        ('Communication', 15::NUMERIC, 'Bonne présentation du projet.'),
        ('Rigueur', 16::NUMERIC, 'Réponses précises et structurées.')
) AS v(softskill, note, commentaire) ON TRUE
JOIN softskills s ON s.libelle = v.softskill
WHERE NOT EXISTS (
    SELECT 1
    FROM evaluations_candidat ec
    WHERE ec.id_entretien = e.id
      AND ec.id_softskill = s.id
      AND ec.id_evaluateur = u.id
);

-- 11. Proposition d'embauche
INSERT INTO propositions_embauche (id_candidature, id_statut_actuel)
SELECT ca.id, sp.id
FROM candidatures ca
JOIN statut_propositions_embauche sp ON sp.libelle = 'en_attente'
WHERE NOT EXISTS (
    SELECT 1 FROM propositions_embauche p
    WHERE p.id_candidature = ca.id
);

INSERT INTO historique_propositions (
    id_proposition, salaire_propose, id_type_contrat,
    id_statut_proposition, motif
)
SELECT p.id, 1700000, tc.id, sp.id,
       'Proposition basée sur la grille salariale du poste.'
FROM propositions_embauche p
JOIN type_contrats tc ON tc.libelle = 'CDI'
JOIN statut_propositions_embauche sp ON sp.id = p.id_statut_actuel
WHERE NOT EXISTS (
    SELECT 1 FROM historique_propositions h
    WHERE h.id_proposition = p.id
);

-- 12. Employé et contrat signé
INSERT INTO employes (
    id_candidat, matricule, date_embauche, id_statut_actuel
)
SELECT c.id, 'EMP-2026-001', '2026-12-01', se.id
FROM candidats c
JOIN statut_employes se ON se.libelle = 'actif'
WHERE c.email = 'miora.rakoto@example.test'
ON CONFLICT (id_candidat) DO NOTHING;

INSERT INTO historique_employes_statut (id_employe, id_statut_employe)
SELECT e.id, se.id
FROM employes e
JOIN statut_employes se ON se.id = e.id_statut_actuel
WHERE NOT EXISTS (
    SELECT 1 FROM historique_employes_statut h
    WHERE h.id_employe = e.id
);

INSERT INTO contrats (
    id_employe, id_type_contrat, date_debut, salaire,
    id_statut_actuel, date_signature
)
SELECT e.id, tc.id, '2026-12-01', 1700000,
       sc.id, '2026-11-20'
FROM employes e
JOIN type_contrats tc ON tc.libelle = 'CDI'
JOIN statut_contrats sc ON sc.libelle = 'signe'
WHERE e.matricule = 'EMP-2026-001'
  AND NOT EXISTS (
      SELECT 1 FROM contrats c WHERE c.id_employe = e.id
  );

INSERT INTO historique_contrats_statut (id_contrat, id_statut_contrat)
SELECT c.id, sc.id
FROM contrats c
JOIN statut_contrats sc ON sc.id = c.id_statut_actuel
WHERE NOT EXISTS (
    SELECT 1 FROM historique_contrats_statut h
    WHERE h.id_contrat = c.id
);

COMMIT;
