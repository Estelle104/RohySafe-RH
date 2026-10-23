<?php

namespace App\Models;

use CodeIgniter\Model;

class PosteModel extends Model
{
    protected $table         = 'postes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle', 'id_categorie'];
}
