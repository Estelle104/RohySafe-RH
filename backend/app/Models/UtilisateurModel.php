<?php

namespace App\Models;

use CodeIgniter\Model;

class UtilisateurModel extends Model
{
    protected $table         = 'utilisateurs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['login', 'mdp', 'id_role', 'id_departement'];
}
