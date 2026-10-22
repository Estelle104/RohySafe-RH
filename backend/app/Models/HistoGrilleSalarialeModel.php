<?php

namespace App\Models;

use CodeIgniter\Model;

class HistoGrilleSalarialeModel extends Model
{
    protected $table         = 'histo_grille_salariales';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_grille', 'montant_min', 'montant_max', 'date_debut', 'date_fin'];
}
