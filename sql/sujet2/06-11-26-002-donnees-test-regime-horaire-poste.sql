INSERT INTO regime_travails
    (libelle, heures_hebdo, seuil_hs_jour, seuil_hs_semaine, tolerance_retard_max)
VALUES
    ('Temps plein 40h', 40, 8, 40, 10);

INSERT INTO horaire_postes
    (id_poste, id_regime, libelle, heure_debut, heure_fin, pause_minutes)
VALUES
    ((SELECT id FROM postes ORDER BY id LIMIT 1),
     (SELECT id FROM regime_travails WHERE libelle = 'Temps plein 40h'),
     'Journée standard', '08:00', '17:00', 60);

-- -- Régime supplémentaire
-- INSERT INTO regime_travails
--     (libelle, heures_hebdo, seuil_hs_jour, seuil_hs_semaine, tolerance_retard_max)
-- VALUES
--     ('Temps plein 35h', 35, 7, 35, 10);

-- Horaires des postes
INSERT INTO horaire_postes
    (id_poste, id_regime, libelle, heure_debut, heure_fin, pause_minutes)
VALUES
    -- 2 : Développeur Web PHP
    (2, (SELECT id FROM regime_travails WHERE libelle = 'Temps plein 40h'),
        'Journée standard', '08:00', '17:00', 60),

    -- 3 : Technicien réseau
    (3, (SELECT id FROM regime_travails WHERE libelle = 'Temps plein 40h'),
        'Journée standard', '08:00', '17:00', 60),

    -- 4 : Infirmier(ère) d'entreprise
    (4, (SELECT id FROM regime_travails WHERE libelle = 'Temps plein 40h'),
        'Matin', '07:00', '14:30', 30),

    -- 5 : Comptable
    (5, (SELECT id FROM regime_travails WHERE libelle = 'Temps plein 40h'),
        'Journée standard', '08:00', '17:00', 60),


    -- 6 : Développeur Web
    (6, (SELECT id FROM regime_travails WHERE libelle = 'Temps plein 40h'),
        'Journée décalée', '09:00', '18:00', 60),

    -- 10 : Responsable RH
    (1, (SELECT id FROM regime_travails WHERE libelle = 'Temps plein 40h'),
        'Journée standard', '08:00', '17:00', 60);