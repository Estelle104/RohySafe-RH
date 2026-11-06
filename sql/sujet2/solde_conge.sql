-- ============================================================
-- SOLDES DE CONGES
-- ============================================================
-- Un enregistrement représente le solde d'un employé pour un type
-- de congé et une période d'acquisition donnée.

CREATE TABLE IF NOT EXISTS solde_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    id_type_conge BIGINT NOT NULL REFERENCES type_conges(id),
    periode_debut DATE NOT NULL,
    periode_fin DATE NOT NULL,
    droits_acquis NUMERIC(6,2) NOT NULL DEFAULT 0
        CHECK (droits_acquis >= 0),
    jours_pris NUMERIC(6,2) NOT NULL DEFAULT 0
        CHECK (jours_pris >= 0),
    solde_restant NUMERIC(6,2) NOT NULL DEFAULT 0
        CHECK (solde_restant >= 0),
    UNIQUE (id_employe, id_type_conge, periode_debut, periode_fin),
    CHECK (periode_fin >= periode_debut),
    CHECK (solde_restant = droits_acquis - jours_pris)
);

CREATE INDEX IF NOT EXISTS idx_solde_conges_employe
ON solde_conges(id_employe, periode_debut, periode_fin);
