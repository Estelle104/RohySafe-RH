-- Une seule évaluation par candidature, saisie par le jury après l'entretien.
-- Les anciennes évaluations de test sont volontairement réinitialisées.

DROP TABLE IF EXISTS evaluations_candidat CASCADE;

CREATE TABLE evaluations_candidat (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidature BIGINT NOT NULL UNIQUE REFERENCES candidatures(id) ON DELETE CASCADE,
    note NUMERIC(5,2) NOT NULL CHECK (note BETWEEN 0 AND 20),
    remarques TEXT,
    date_evaluation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
