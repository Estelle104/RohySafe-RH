<?php

namespace App\Models;

use CodeIgniter\Model;

class StatutContratModel extends Model
{
    protected $table         = 'statut_contrats';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle'];
}
