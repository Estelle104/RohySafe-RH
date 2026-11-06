<?php

namespace App\Models;

use CodeIgniter\Model;

class PointageModel extends Model
{
    protected $table         = 'pointages';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_employe', 'date_heure', 'id_type_pointage', 'cree_par'];

    public function getOptions(): array
    {
        return [
            'employes' => $this->db->table('employes e')
                ->select("e.id, e.matricule, CONCAT(c.prenom, ' ', c.nom) AS nom_complet")
                ->join('candidats c', 'c.id = e.id_candidat')
                ->orderBy('c.nom', 'ASC')->orderBy('c.prenom', 'ASC')
                ->get()->getResultArray(),
            'types' => $this->db->table('type_pointages')
                ->select('id, libelle')->orderBy('id', 'ASC')
                ->get()->getResultArray(),
        ];
    }

    public function getPointages(array $filtres = []): array
    {
        $builder = $this->db->table('pointages p')
            ->select("p.id, p.id_employe, p.date_heure, p.id_type_pointage,
                p.cree_par, p.created_at, u.login AS saisi_par, tp.libelle AS type_pointage,
                e.matricule, CONCAT(c.prenom, ' ', c.nom) AS nom_complet")
            ->join('type_pointages tp', 'tp.id = p.id_type_pointage')
            ->join('utilisateurs u', 'u.id = p.cree_par', 'left')
            ->join('employes e', 'e.id = p.id_employe')
            ->join('candidats c', 'c.id = e.id_candidat')
            ->orderBy('p.date_heure', 'DESC');

        if (! empty($filtres['id_employe'])) {
            $builder->where('p.id_employe', (int) $filtres['id_employe']);
        }
        if (! empty($filtres['date_debut'])) {
            $builder->where('p.date_heure >=', $filtres['date_debut'] . ' 00:00:00');
        }
        if (! empty($filtres['date_fin'])) {
            $builder->where('p.date_heure <=', $filtres['date_fin'] . ' 23:59:59');
        }

        return $builder->get()->getResultArray();
    }

    public function createPointage(array $data): int
    {
        $this->insert($data);
        return (int) $this->getInsertID();
    }
}
