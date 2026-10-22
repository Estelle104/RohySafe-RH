<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidatModel extends Model
{
    protected $table         = 'candidats';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['nom', 'prenom', 'email', 'telephone', 'ville'];
}
