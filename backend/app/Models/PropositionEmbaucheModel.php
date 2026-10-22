<?php

namespace App\Models;

use CodeIgniter\Model;

class PropositionEmbaucheModel extends Model
{
    protected $table         = 'propositions_embauche';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_candidature', 'id_statut_actuel'];
}
