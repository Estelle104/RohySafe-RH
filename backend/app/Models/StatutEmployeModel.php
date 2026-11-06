<?php

namespace App\Models;

use CodeIgniter\Model;

class StatutEmployeModel extends Model
{
    protected $table         = 'statut_employes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle'];
}
