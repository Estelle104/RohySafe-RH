-- Tables nécessaires aux calculs de valorisation RH.
CREATE TABLE IF NOT EXISTS solde_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    id_type_conge BIGINT NOT NULL REFERENCES type_conges(id),
    periode_debut DATE NOT NULL,
    periode_fin DATE NOT NULL,
    droits_acquis NUMERIC(8,2) NOT NULL DEFAULT 0,
    jours_pris NUMERIC(8,2) NOT NULL DEFAULT 0,
    solde_restant NUMERIC(8,2) NOT NULL DEFAULT 0,
    UNIQUE (id_employe, id_type_conge, periode_debut, periode_fin)
);

CREATE INDEX IF NOT EXISTS idx_solde_conges_employe
    ON solde_conges(id_employe, periode_debut, periode_fin);

CREATE TABLE IF NOT EXISTS remplacement_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_conge BIGINT NOT NULL REFERENCES conges(id),
    id_employee_remplacant BIGINT NOT NULL REFERENCES employes(id),
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    commentaire TEXT,
    CHECK (date_fin >= date_debut)
);

CREATE INDEX IF NOT EXISTS idx_remplacement_conges_periode
    ON remplacement_conges(date_debut, date_fin);
