<?php

namespace App\Models;

use CodeIgniter\Model;

class GrilleSalarialeModel extends Model
{
    protected $table         = 'grilles_salariales';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_poste_departement', 'montant_min', 'montant_max', 'id_type_contrat'];
}
