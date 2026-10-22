<?php

namespace App\Models;

use CodeIgniter\Model;

class TypeContratModel extends Model
{
    protected $table         = 'type_contrats';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle'];
}
