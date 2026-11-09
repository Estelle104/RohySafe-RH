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
        $id = (int) $this->getInsertID();
        $type = $this->db->table('type_pointages')->where('id', (int) $data['id_type_pointage'])->get()->getRowArray();
        if (($type['libelle'] ?? null) === 'SORTIE') {
            $this->creerHeureSupplementaire($data);
        }
        return $id;
    }

    public function existeDeja(array $data): bool
    {
        return $this->where('id_employe', (int) $data['id_employe'])
            ->where('date_heure', $data['date_heure'])
            ->where('id_type_pointage', (int) $data['id_type_pointage'])
            ->countAllResults() > 0;
    }

    private function creerHeureSupplementaire(array $data): void
    {
        $dateHeureSortie = (string) $data['date_heure'];
        $dateJour = substr($dateHeureSortie, 0, 10);
        $planning = $this->db->table('planning_edt_employes pee')
            ->select('h.heure_fin')
            ->join('planning_edts pe', 'pe.id = pee.id_planning_edt')
            ->join('employes emp', 'emp.id = pee.id_employe')
            ->join('poste_departement pd', 'pd.id = emp.id_poste_departement')
            ->join('horaire_postes h', 'h.id_poste = pd.id_poste')
            ->where('pee.id_employe', (int) $data['id_employe'])
            ->where('pe.date_debut <=', $dateJour)
            ->where('pe.date_fin >=', $dateJour)
            ->where("EXTRACT(ISODOW FROM DATE '{$dateJour}') = ANY(pe.jours_semaine)", null, false)
            ->orderBy('pe.date_debut', 'DESC')
            ->get()->getRowArray();
        if ($planning === null) return;

        $heurePrevue = strtotime($dateJour . ' ' . $planning['heure_fin']);
        $heureReelle = strtotime($dateHeureSortie);
        if ($heureReelle <= $heurePrevue) return;

        $heureDebut = date('Y-m-d H:i:s', $heurePrevue);
        $heureFin = date('Y-m-d H:i:s', $heureReelle);
        $table = $this->db->table('heure_supplementaires');
        $existe = $table->where([
            'id_employe' => (int) $data['id_employe'],
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
        ])->countAllResults();
        if ($existe > 0) return;

        $table->insert([
            'id_employe' => (int) $data['id_employe'],
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
            'date_debut' => $dateJour,
            'date_fin' => $dateJour,
        ]);
    }
}
