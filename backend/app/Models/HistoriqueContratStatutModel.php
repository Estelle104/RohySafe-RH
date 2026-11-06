<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoriqueContratStatutModel extends Model
{
    protected $table         = 'historique_contrats_statut';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_contrat', 'id_statut_contrat'];
}
