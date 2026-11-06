<?php

namespace App\Models;

use CodeIgniter\Model;

class CongeModel extends Model
{
    protected $table         = 'config_conges';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_type_conge', 'nombre_jour'];

    /** Récupère chaque règle avec le libellé de son type de congé. */
    public function getConfigurations(): array
    {
        return $this->db->table('config_conges cc')
            ->select('cc.id, cc.id_type_conge, tc.libelle, cc.nombre_jour')
            ->join('type_conges tc', 'tc.id = cc.id_type_conge')
            ->orderBy('tc.libelle', 'ASC')
            ->get()
            ->getResultArray();
    }

    /** Met à jour le nombre de jours acquis par mois pour un type. */
    public function updateConfiguration(int $id, float $nombreJour): bool
    {
        return $this->update($id, ['nombre_jour' => $nombreJour]);
    }
}
