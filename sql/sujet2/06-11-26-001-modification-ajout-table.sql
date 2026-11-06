CREATE TABLE remplacement_conges (
    id SERIAL PRIMARY KEY,

    id_conge INT NOT NULL REFERENCES conges(id),
    id_employee_remplacant INT NOT NULL REFERENCES employes(id),

    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,

    commentaire TEXT,

    CHECK (date_fin >= date_debut)
);

CREATE TABLE relation_hierarchiques (
    id SERIAL PRIMARY KEY,
    id_superieur BIGINT NOT NULL REFERENCES employes(id),
    id_subordonne BIGINT NOT NULL REFERENCES employes(id),
    date_debut DATE NOT NULL,
    date_fin DATE,
    CHECK (id_superieur <> id_subordonne),
    CHECK (date_fin IS NULL OR date_fin >= date_debut)
);


ALTER TABLE histo_employes
DROP COLUMN id_societe,
ADD COLUMN id_societe_province BIGINT
    REFERENCES societe_provinces(id);