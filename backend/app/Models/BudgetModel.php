<?php

namespace App\Models;

use CodeIgniter\Model;

class BudgetModel extends Model
{
    protected $table         = 'budgets';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_departement', 'annee', 'montant_mensuel', 'montant_engage'];

    public function trouverPourDepartement(int $departementId, int $annee): ?array
    {
        return $this->where('id_departement', $departementId)
            ->where('annee', $annee)
            ->first();
    }
}
