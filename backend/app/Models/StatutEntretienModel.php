<?php

namespace App\Models;

use CodeIgniter\Model;

class StatutEntretienModel extends Model
{
    protected $table         = 'statut_entretiens';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle'];
}
