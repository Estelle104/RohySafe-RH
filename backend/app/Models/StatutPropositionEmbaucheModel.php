<?php

namespace App\Models;

use CodeIgniter\Model;

class StatutPropositionEmbaucheModel extends Model
{
    protected $table         = 'statut_propositions_embauche';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle'];
}
