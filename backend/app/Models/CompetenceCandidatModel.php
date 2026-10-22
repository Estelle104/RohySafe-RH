<?php

namespace App\Models;

use CodeIgniter\Model;

class CompetenceCandidatModel extends Model
{
    protected $table         = 'competences_candidat';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_candidat', 'id_competence', 'niveau'];
}
