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


    function getContratDemande($idDemande)
    {
        return $this->db->table('demandes d')
            ->select('tc.libelle')
            ->join('type_contrat tc', 'tc.id = d.id_type_contrat')
            ->where('d.id', $idDemande)
            ->get()
            ->getRowArray();
    }
}
