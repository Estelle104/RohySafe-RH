<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoriqueEmployeStatutModel extends Model
{
    protected $table         = 'historique_employes_statut';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_employe', 'id_statut_employe'];
}
