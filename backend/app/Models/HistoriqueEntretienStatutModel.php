<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoriqueEntretienStatutModel extends Model
{
    protected $table         = 'historique_entretiens_statut';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_entretien', 'id_statut_entretien'];
}
