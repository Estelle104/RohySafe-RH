INSERT INTO type_pointages (libelle)
VALUES
    ('ENTREE'),
    ('SORTIE'),
    ('PAUSE_DEBUT'),
    ('PAUSE_FIN')
ON CONFLICT (libelle) DO NOTHING;