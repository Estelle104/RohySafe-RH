<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoriePosteModel extends Model
{
    protected $table         = 'categorie_postes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle'];
}
