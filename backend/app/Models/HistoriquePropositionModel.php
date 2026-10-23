<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoriquePropositionModel extends Model
{
    protected $table         = 'historique_propositions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_proposition', 'salaire_propose', 'id_type_contrat', 'id_statut_proposition', 'motif'];
}
