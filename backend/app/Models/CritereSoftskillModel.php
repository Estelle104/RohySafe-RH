<?php

namespace App\Models;

use CodeIgniter\Model;

class CritereSoftskillModel extends Model
{
    protected $table         = 'criteres_softskill';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_poste', 'id_softskill', 'note_minimale', 'obligatoire'];
}
