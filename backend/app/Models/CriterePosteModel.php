<?php

namespace App\Models;

use CodeIgniter\Model;

class CriterePosteModel extends Model
{
    protected $table         = 'criteres_poste';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_poste', 'id_competence', 'note_minimale', 'obligatoire'];
}
