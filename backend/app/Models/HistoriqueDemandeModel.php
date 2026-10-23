<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoriqueDemandeModel extends Model
{
    protected $table         = 'historique_demandes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_demande', 'id_statut_demande', 'id_utilisateur', 'salaire_propose', 'remarque'];
}
