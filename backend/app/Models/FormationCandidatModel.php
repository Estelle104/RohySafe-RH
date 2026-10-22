<?php

namespace App\Models;

use CodeIgniter\Model;

class FormationCandidatModel extends Model
{
    protected $table         = 'formations_candidat';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_candidat', 'diplome', 'etablissement', 'date_debut', 'date_fin', 'description'];
}
