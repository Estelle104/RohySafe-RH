
INSERT INTO grilles_salariales (
    id_poste_departement,
    montant_min,
    montant_max,
    id_type_contrat
)
SELECT
    pd.id,
    g.montant_min,
    g.montant_max,
    tc.id
FROM poste_departement pd
JOIN postes p
    ON p.id = pd.id_poste
CROSS JOIN (
    VALUES
        ('CDI', 'Développeur Mobile', 1200000, 2500000),
        ('CDD', 'Développeur Mobile', 1100000, 2300000),
        ('CE',  'Développeur Mobile', 900000, 1800000),

        ('CDI', 'Développeur Web PHP', 1000000, 2200000),
        ('CDD', 'Développeur Web PHP', 900000, 2000000),
        ('CE',  'Développeur Web PHP', 800000, 1600000),

        ('CDI', 'Technicien réseau', 800000, 1600000),
        ('CDD', 'Technicien réseau', 750000, 1450000),
        ('CE',  'Technicien réseau', 600000, 1200000),

        ('CDI', 'Infirmier(ère) d''entreprise', 900000, 1800000),
        ('CDD', 'Infirmier(ère) d''entreprise', 850000, 1650000),
        ('CE',  'Infirmier(ère) d''entreprise', 700000, 1300000),

        ('CDI', 'Comptable', 900000, 1900000),
        ('CDD', 'Comptable', 800000, 1700000),
        ('CE',  'Comptable', 650000, 1300000)
) AS g(type_contrat, poste, montant_min, montant_max)
JOIN type_contrats tc
    ON tc.libelle = g.type_contrat
WHERE p.libelle = g.poste;
