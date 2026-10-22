<?php

namespace App\Models;

use CodeIgniter\Model;

class ExigenceExperienceModel extends Model
{
    protected $table         = 'exigences_experience';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_poste_departement', 'id_poste_experience', 'duree_min', 'duree_max', 'obligatoire'];
}
