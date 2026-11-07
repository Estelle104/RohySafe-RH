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

CREATE INDEX idx_relation_hierarchiques_subordonne_actif
    ON relation_hierarchiques(id_subordonne, date_fin);

-- Données initiales : rattache le deuxième employé au premier lorsqu'une
-- base contient déjà au moins deux employés et aucune relation.
INSERT INTO relation_hierarchiques (id_superieur, id_subordonne, date_debut)
SELECT MIN(id), MAX(id), CURRENT_DATE
FROM employes
HAVING COUNT(*) >= 2
   AND NOT EXISTS (SELECT 1 FROM relation_hierarchiques);


ALTER TABLE histo_employes
DROP COLUMN id_societe,
ADD COLUMN id_societe_province BIGINT
    REFERENCES societe_provinces(id);