-- ============================================================
-- MIGRATION DES SOLDES DE CONGES DES EMPLOYES
-- ============================================================
-- Cette migration :
--   1. initialise le solde de chaque employé existant ;
--   2. crée automatiquement les soldes d'un nouvel employé.
--
-- Les valeurs initiales sont volontairement à zéro. Les droits seront
-- calculés par le service de gestion des soldes.
-- La période initiale correspond à l'année civile en cours.

-- ------------------------------------------------------------
-- 1. Employés existants
-- ------------------------------------------------------------
INSERT INTO solde_conges (
    id_employe,
    id_type_conge,
    periode_debut,
    periode_fin,
    droits_acquis,
    jours_pris,
    solde_restant
)
SELECT
    e.id,
    tc.id,
    make_date(EXTRACT(YEAR FROM CURRENT_DATE)::INT, 1, 1),
    make_date(EXTRACT(YEAR FROM CURRENT_DATE)::INT, 12, 31),
    0.00,
    0.00,
    0.00
FROM employes e
CROSS JOIN type_conges tc
ON CONFLICT (id_employe, id_type_conge, periode_debut, periode_fin)
DO NOTHING;

-- ------------------------------------------------------------
-- 2. Création automatique des soldes d'un nouvel employé
-- ------------------------------------------------------------
CREATE OR REPLACE FUNCTION creer_soldes_conge_employe()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    INSERT INTO solde_conges (
        id_employe,
        id_type_conge,
        periode_debut,
        periode_fin,
        droits_acquis,
        jours_pris,
        solde_restant
    )
    SELECT
        NEW.id,
        tc.id,
        make_date(EXTRACT(YEAR FROM CURRENT_DATE)::INT, 1, 1),
        make_date(EXTRACT(YEAR FROM CURRENT_DATE)::INT, 12, 31),
        0.00,
        0.00,
        0.00
    FROM type_conges tc
    ON CONFLICT (id_employe, id_type_conge, periode_debut, periode_fin)
    DO NOTHING;

    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_creer_soldes_conge_employe ON employes;

CREATE TRIGGER trg_creer_soldes_conge_employe
AFTER INSERT ON employes
FOR EACH ROW
EXECUTE FUNCTION creer_soldes_conge_employe();

-- ------------------------------------------------------------
-- 3. Création automatique des soldes lorsqu'un type de congé est ajouté
-- ------------------------------------------------------------
CREATE OR REPLACE FUNCTION creer_soldes_conge_type()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    INSERT INTO solde_conges (
        id_employe,
        id_type_conge,
        periode_debut,
        periode_fin,
        droits_acquis,
        jours_pris,
        solde_restant
    )
    SELECT
        e.id,
        NEW.id,
        make_date(EXTRACT(YEAR FROM CURRENT_DATE)::INT, 1, 1),
        make_date(EXTRACT(YEAR FROM CURRENT_DATE)::INT, 12, 31),
        0.00,
        0.00,
        0.00
    FROM employes e
    ON CONFLICT (id_employe, id_type_conge, periode_debut, periode_fin)
    DO NOTHING;

    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_creer_soldes_conge_type ON type_conges;

CREATE TRIGGER trg_creer_soldes_conge_type
AFTER INSERT ON type_conges
FOR EACH ROW
EXECUTE FUNCTION creer_soldes_conge_type();
