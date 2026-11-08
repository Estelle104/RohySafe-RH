-- Ajouter poste_departement dans l employe

ALTER TABLE employes
ADD COLUMN id_poste_departement BIGINT;

ALTER TABLE employes
ADD CONSTRAINT fk_employes_poste_departement
FOREIGN KEY (id_poste_departement)
REFERENCES poste_departement(id);

CREATE INDEX idx_employes_poste_departement
ON employes(id_poste_departement);