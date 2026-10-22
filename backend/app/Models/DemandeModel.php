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
}
