    -- ============================================================
    -- Données de test : provinces, societes, societe_provinces
    -- (tables déjà créées dans le script des tables)
    -- ============================================================

    -- Provinces de Madagascar
    INSERT INTO provinces (libelle) VALUES
        ('Antananarivo'),
        ('Antsiranana'),
        ('Fianarantsoa'),
        ('Mahajanga'),
        ('Toamasina'),
        ('Toliara')
    ON CONFLICT (libelle) DO NOTHING;

    -- Sociétés fictives
    INSERT INTO societes (libelle) VALUES
        ('Rohysafe Madagascar SA'),
        ('Vanilla Export SARL'),
        ('Mada Agro Industries'),
        ('Tsiry Logistique'),
        ('Ocean Pêche Madagascar'),
        ('Soa Construction')
    ON CONFLICT (libelle) DO NOTHING;

    -- Provinces où chaque société est implantée
    INSERT INTO societe_provinces (id_societe, id_province)
    SELECT s.id, p.id
    FROM (VALUES
        ('Rohysafe Madagascar SA', 'Antananarivo'),
        ('Rohysafe Madagascar SA', 'Toamasina'),
        ('Rohysafe Madagascar SA', 'Mahajanga'),
        ('Vanilla Export SARL',    'Antsiranana'),
        ('Vanilla Export SARL',    'Toamasina'),
        ('Mada Agro Industries',   'Antananarivo'),
        ('Mada Agro Industries',   'Fianarantsoa'),
        ('Mada Agro Industries',   'Toliara'),
        ('Tsiry Logistique',       'Antananarivo'),
        ('Tsiry Logistique',       'Toamasina'),
        ('Ocean Pêche Madagascar', 'Mahajanga'),
        ('Ocean Pêche Madagascar', 'Toliara'),
        ('Soa Construction',       'Antananarivo'),
        ('Soa Construction',       'Fianarantsoa')
    ) AS v(societe, province)
    JOIN societes  s ON s.libelle = v.societe
    JOIN provinces p ON p.libelle = v.province
    ON CONFLICT (id_societe, id_province) DO NOTHING;