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

    public function index()
    {
        $db = db_connect();

        $statuts = $db->table('statut_demandes')
            ->select('id, libelle')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON($statuts);
    }
    
    public function trouverParLibelle(string $libelle): ?array
    {
        return $this->where('libelle', $libelle)->first();
    }
}
