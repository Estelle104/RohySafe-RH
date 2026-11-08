<?php

namespace App\Models;

use CodeIgniter\Model;

class AnomalieModel extends Model
{
    protected $table = 'anomalie_pointages';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['id_statut', 'commentaire', 'traite_par', 'traite_le'];

    public function getAnomalies(array $filtres = []): array
    {
        $builder = $this->db->table('anomalie_pointages a')
            ->select("a.id, a.id_employe, a.date_jour, a.id_pointage, a.commentaire,
                a.traite_le, ta.libelle AS type_anomalie, sa.libelle AS statut,
                e.matricule, CONCAT(c.prenom, ' ', c.nom) AS nom_complet")
            ->join('type_anomalies ta', 'ta.id = a.id_type_anomalie')
            ->join('statut_anomalie_pointages sa', 'sa.id = a.id_statut')
            ->join('employes e', 'e.id = a.id_employe')
            ->join('candidats c', 'c.id = e.id_candidat')
            ->orderBy('a.date_jour', 'DESC')->orderBy('a.id', 'DESC');
        if (! empty($filtres['id_employe'])) $builder->where('a.id_employe', (int) $filtres['id_employe']);
        if (! empty($filtres['date_debut'])) $builder->where('a.date_jour >=', $filtres['date_debut']);
        if (! empty($filtres['date_fin'])) $builder->where('a.date_jour <=', $filtres['date_fin']);
        return $builder->get()->getResultArray();
    }

    public function traiter(int $id, string $statut, string $commentaire, ?int $utilisateur): bool
    {
        $statutRow = $this->db->table('statut_anomalie_pointages')->where('libelle', $statut)->get()->getRowArray();
        if ($statutRow === null) return false;
        return $this->update($id, ['id_statut' => $statutRow['id'], 'commentaire' => $commentaire, 'traite_par' => $utilisateur, 'traite_le' => date('Y-m-d H:i:s')]);
    }
}
