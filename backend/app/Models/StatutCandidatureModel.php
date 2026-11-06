<?php

namespace App\Models;

use CodeIgniter\Model;

class StatutCandidatureModel extends Model
{
    protected $table         = 'statut_candidatures';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle'];
}
