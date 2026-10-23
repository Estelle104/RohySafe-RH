<?php

namespace App\Models;

use CodeIgniter\Model;

class EvaluationCandidatModel extends Model
{
    protected $table         = 'evaluations_candidat';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_entretien', 'id_softskill', 'id_evaluateur', 'note', 'commentaire'];
}
