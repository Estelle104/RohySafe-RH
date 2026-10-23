<?php

namespace App\Models;

use CodeIgniter\Model;

class SoftskillModel extends Model
{
    protected $table         = 'softskills';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle'];
}
