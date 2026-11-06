<?php

namespace App\Models;

use CodeIgniter\Model;

class StatutOffreModel extends Model
{
    protected $table         = 'statut_offres';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle'];
}
