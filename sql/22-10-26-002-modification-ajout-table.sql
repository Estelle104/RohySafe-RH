-- Ajouter les colonnes login et mdp à candidats
ALTER TABLE candidats
ADD COLUMN login VARCHAR(100) UNIQUE;

ALTER TABLE candidats
ADD COLUMN mdp TEXT NOT NULL;

-- Créer la table type_documents
CREATE TABLE type_documents (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    libelle VARCHAR(80) NOT NULL UNIQUE
);

-- Créer la table documents_candidat
CREATE TABLE documents_candidat (
    id BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_candidat BIGINT NOT NULL REFERENCES candidats(id),
    id_type_document BIGINT NOT NULL REFERENCES type_documents(id),
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier TEXT NOT NULL,
    date_ajout TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Rendre login et mdp obligatoires après avoir renseigné
-- les valeurs pour les candidats déjà existants
ALTER TABLE candidats
ALTER COLUMN login SET NOT NULL;

ALTER TABLE candidats
ALTER COLUMN mdp SET NOT NULL;