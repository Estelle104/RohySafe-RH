<?php

namespace App\Models;

use CodeIgniter\Model;

class PosteDepartementModel extends Model
{
    protected $table         = 'poste_departement';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_poste', 'id_departement'];
}
