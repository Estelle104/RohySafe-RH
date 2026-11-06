<?php

namespace App\Models;

use CodeIgniter\Model;

class StatutDemandeModel extends Model
{
    protected $table         = 'statut_demandes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle'];

    public function trouverParLibelle(string $libelle): ?array
    {
        return $this->where('libelle', $libelle)->first();
    }
}
