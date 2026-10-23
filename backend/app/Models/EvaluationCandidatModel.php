<?php

namespace App\Models;

use CodeIgniter\Model;

class EvaluationCandidatModel extends Model
{
    protected $table         = 'evaluations_candidat';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_candidature', 'note', 'remarques', 'date_evaluation'];
}
