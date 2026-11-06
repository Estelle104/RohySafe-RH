<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoriqueCandidatureStatusModel extends Model
{
    protected $table         = 'historique_candidature_status';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_candidature', 'id_statut_candidature'];
}
