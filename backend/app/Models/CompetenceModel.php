<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetenceModel extends Model
{
    protected $table         = 'competences';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle', 'categorie'];
}
