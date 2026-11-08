-- Tâches 1-16 (Congés) : socle statuts/historique, remplacements/disponibilité.
-- Idempotent : peut être rejoué sans casser les données existantes.

-- 1) Statuts des demandes de congés
INSERT INTO statut_conges (libelle)
SELECT v.libelle
FROM (VALUES ('En attente'), ('Validée'), ('Refusée'), ('Annulée')) AS v(libelle)
WHERE NOT EXISTS (SELECT 1 FROM statut_conges sc WHERE lower(sc.libelle) = lower(v.libelle));

-- Normalisation douce des libellés courants (évite les doublons fonctionnels)
-- (on ne supprime pas les lignes pour ne pas casser les FK existantes)

-- 10/15) Table remplaçant <-> employé absent / période (consolidation)
CREATE TABLE IF NOT EXISTS remplacement_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_conge BIGINT NOT NULL REFERENCES conges(id) ON DELETE CASCADE,
    id_employee_remplacant BIGINT NOT NULL REFERENCES employes(id),
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    commentaire TEXT,
    CHECK (date_fin >= date_debut)
);

-- Si la table existe avec des types différents, on ajoute seulement les index utiles.
CREATE INDEX IF NOT EXISTS idx_remplacement_conges_periode
    ON remplacement_conges(date_debut, date_fin);
CREATE INDEX IF NOT EXISTS idx_remplacement_conges_conge
    ON remplacement_conges(id_conge);
CREATE INDEX IF NOT EXISTS idx_remplacement_conges_remplacant
    ON remplacement_conges(id_employee_remplacant, date_debut, date_fin);

-- 2) Historique : index pour consultation rapide
CREATE INDEX IF NOT EXISTS idx_histo_demande_conges_conge_date
    ON histo_demande_conges(id_conge, date DESC, id DESC);

-- 8/9/16) Soldes : table attendue par l'application (déjà présente dans certains scripts)
CREATE TABLE IF NOT EXISTS solde_conges (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_employe BIGINT NOT NULL REFERENCES employes(id),
    id_type_conge BIGINT NOT NULL REFERENCES type_conges(id),
    periode_debut DATE NOT NULL,
    periode_fin DATE NOT NULL,
    droits_acquis NUMERIC(8,2) NOT NULL DEFAULT 0,
    jours_pris NUMERIC(8,2) NOT NULL DEFAULT 0,
    solde_restant NUMERIC(8,2) NOT NULL DEFAULT 0,
    UNIQUE (id_employe, id_type_conge, periode_debut, periode_fin),
    CHECK (periode_fin >= periode_debut)
);

CREATE INDEX IF NOT EXISTS idx_solde_conges_employe_periode
    ON solde_conges(id_employe, periode_debut, periode_fin);
