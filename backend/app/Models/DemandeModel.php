<?php

namespace App\Models;

use CodeIgniter\Model;

class DemandeModel extends Model
{
    protected $table         = 'demandes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_poste_departement', 'id_createur', 'id_utilisateur_actuel', 'id_statut_actuel', 'nombre_demande', 'id_type_contrat', 'date_prise_poste', 'salaire_propose'];

    public function trouverAvecDepartement(int $id): ?array
    {
        return $this->select('demandes.*, pd.id_departement, sd.libelle AS statut_libelle')
            ->join('poste_departement pd', 'pd.id = demandes.id_poste_departement')
            ->join('statut_demandes sd', 'sd.id = demandes.id_statut_actuel')
            ->where('demandes.id', $id)
            ->first();
    }

    public function demandesPourFinance(): array
    {
        return $this->select('demandes.id, demandes.date_creation, demandes.salaire_propose, demandes.nombre_demande, sd.libelle AS statut_libelle, p.libelle AS poste, dep.libelle AS departement')
            ->join('statut_demandes sd', 'sd.id = demandes.id_statut_actuel')
            ->join('poste_departement pd', 'pd.id = demandes.id_poste_departement')
            ->join('postes p', 'p.id = pd.id_poste')
            ->join('departements dep', 'dep.id = pd.id_departement')
            ->where('sd.libelle', 'EN_VALIDATION_FINANCE')
            ->orderBy('demandes.date_creation', 'DESC')
            ->findAll();
    }

    public function compterPourFinance(): int
    {
        return $this->join('statut_demandes sd', 'sd.id = demandes.id_statut_actuel')
            ->where('sd.libelle', 'EN_VALIDATION_FINANCE')
            ->countAllResults();
    }

    public function demandesPourDirection(): array
    {
        return $this->select('demandes.id, demandes.date_creation, demandes.salaire_propose, demandes.nombre_demande, sd.libelle AS statut_libelle, p.libelle AS poste, dep.libelle AS departement')
            ->join('statut_demandes sd', 'sd.id = demandes.id_statut_actuel')
            ->join('poste_departement pd', 'pd.id = demandes.id_poste_departement')
            ->join('postes p', 'p.id = pd.id_poste')
            ->join('departements dep', 'dep.id = pd.id_departement')
            ->where('sd.libelle', 'EN_VALIDATION_DIRECTION')
            ->orderBy('demandes.date_creation', 'DESC')
            ->findAll();
    }

    public function compterPourDirection(): int
    {
        return $this->join('statut_demandes sd', 'sd.id = demandes.id_statut_actuel')
            ->where('sd.libelle', 'EN_VALIDATION_DIRECTION')
            ->countAllResults();
    }
}
