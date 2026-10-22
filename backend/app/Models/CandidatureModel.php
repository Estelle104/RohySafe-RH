<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidatureModel extends Model
{
    protected $table         = 'candidatures';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_candidat', 'id_offre', 'id_statut_actuel'];
}
