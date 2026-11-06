<?php

namespace App\Models;

use CodeIgniter\Model;

class ContratModel extends Model
{
    protected $table         = 'contrats';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_employe', 'id_type_contrat', 'date_debut', 'date_fin', 'salaire', 'id_statut_actuel', 'date_signature'];
}
