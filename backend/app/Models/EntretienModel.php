<?php

namespace App\Models;

use CodeIgniter\Model;

class EntretienModel extends Model
{
    protected $table         = 'entretiens';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_candidature', 'date_entretien', 'lieu_entretien', 'id_statut_actuel', 'commentaire'];
}
