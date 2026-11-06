<?php

namespace App\Models;

use CodeIgniter\Model;

class ExperienceCandidatModel extends Model
{
    protected $table         = 'experiences_candidat';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_candidat', 'id_poste', 'date_debut', 'date_fin', 'descriptions'];
}
