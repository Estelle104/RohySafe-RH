<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTimeImmutable;
use InvalidArgumentException;

class RemplacementModel extends Model
{
    protected $table = 'remplacement_conges';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'id_conge',
        'id_employee_remplacant',
        'date_debut',
        'date_fin',
        'commentaire',
    ];

    public function lister(array $filtres = []): array
    {
        $builder = $this->db->table('remplacement_conges r')
            ->select("r.*, r.id_employee_remplacant AS id_employe_remplacant,
                CONCAT(rc.prenom, ' ', rc.nom) AS remplacant,
                CONCAT(ec.prenom, ' ', ec.nom) AS employe_absent,
                c.date_debut AS conge_date_debut, c.date_fin AS conge_date_fin,
                COALESCE(ct.salaire, 0) AS salaire_mensuel")
            ->join('conges c', 'c.id = r.id_conge')
            ->join('employes er', 'er.id = r.id_employee_remplacant')
            ->join('candidats rc', 'rc.id = er.id_candidat')
            ->join('employes ea', 'ea.id = c.id_employe')
            ->join('candidats ec', 'ec.id = ea.id_candidat')
            ->join(
                "(SELECT DISTINCT ON (id_employe) id_employe, salaire
                  FROM contrats ORDER BY id_employe, date_debut DESC) ct",
                'ct.id_employe = er.id',
                'left',
                false
            )
            ->orderBy('r.date_debut', 'DESC');

        if (! empty($filtres['periode_debut'])) {
            $builder->where('r.date_fin >=', $filtres['periode_debut']);
        }
        if (! empty($filtres['periode_fin'])) {
            $builder->where('r.date_debut <=', $filtres['periode_fin']);
        }
        if (! empty($filtres['id_employe'])) {
            $builder->where('r.id_employee_remplacant', (int) $filtres['id_employe']);
        }

        $rows = $builder->get()->getResultArray();
        foreach ($rows as &$row) {
            $row['jours'] = $this->joursInclusifs($row['date_debut'], $row['date_fin']);
            $row['heures'] = $this->heuresSupplementairesDansPeriode(
                (int) $row['id_employee_remplacant'],
                (string) $row['date_debut'],
                (string) $row['date_fin']
            );
            $row['salaire_horaire'] = (float) $row['salaire_mensuel'] > 0
                ? (float) $row['salaire_mensuel'] / 173.33
                : 0;
            $row['cout'] = round((float) $row['heures'] * (float) $row['salaire_horaire'], 2);
        }

        unset($row);

        return $rows;
    }

    private function heuresSupplementairesDansPeriode(int $idEmploye, string $debut, string $fin): float
    {
        $latestSubquery = "(SELECT DISTINCT ON (id_heure_supplementaire)
                id_heure_supplementaire, id_statut
            FROM histo_demande_heure_sups
            ORDER BY id_heure_supplementaire, date DESC, id DESC)";
        $dateDebut = $this->db->escape($debut);
        $dateFin = $this->db->escape($fin);

        $row = $this->db->table('heure_supplementaires h')
            ->select("COALESCE(SUM(
                EXTRACT(EPOCH FROM (h.heure_fin::time - h.heure_debut::time)) / 3600
                * (LEAST(h.date_fin, {$dateFin}::date)
                    - GREATEST(h.date_debut, {$dateDebut}::date) + 1)
            ), 0) AS heures", false)
            ->join($latestSubquery . ' latest', 'latest.id_heure_supplementaire = h.id', 'inner', false)
            ->join('statut_heure_sups sh', 'sh.id = latest.id_statut')
            ->where('h.id_employe', $idEmploye)
            ->where('h.date_fin >=', $debut)
            ->where('h.date_debut <=', $fin)
            ->whereIn('LOWER(sh.libelle)', ['validée', 'validee', 'validé', 'valide'], false)
            ->get()
            ->getRowArray();

        return round((float) ($row['heures'] ?? 0), 2);
    }

    public function creer(array $data): int
    {
        $idConge = (int) ($data['id_conge'] ?? 0);
        $idRemplacant = (int) ($data['id_employee_remplacant'] ?? $data['id_employe_remplacant'] ?? 0);
        $debut = trim((string) ($data['date_debut'] ?? ''));
        $fin = trim((string) ($data['date_fin'] ?? ''));

        if ($idConge <= 0 || $idRemplacant <= 0 || $debut === '' || $fin === '') {
            throw new InvalidArgumentException('Le congé, le remplaçant et les dates sont obligatoires.');
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $debut) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin) || $fin < $debut) {
            throw new InvalidArgumentException('La période de remplacement est invalide.');
        }
        $conge = $this->db->table('conges')
            ->select('id, id_employe, date_debut, date_fin')
            ->where('id', $idConge)
            ->get()
            ->getRowArray();

        if ($conge === null) {
            throw new InvalidArgumentException('Le congé est introuvable.');
        }
        if ($this->db->table('employes')->where('id', $idRemplacant)->countAllResults() === 0) {
            throw new InvalidArgumentException('Le remplaçant est introuvable.');
        }
        if ((int) $conge['id_employe'] === $idRemplacant) {
            throw new InvalidArgumentException('Un employé ne peut pas se remplacer lui-même.');
        }
        if ($debut < $conge['date_debut'] || $fin > $conge['date_fin']) {
            throw new InvalidArgumentException('La période de remplacement doit être comprise dans la période du congé.');
        }

        return (int) $this->insert([
            'id_conge' => $idConge,
            'id_employee_remplacant' => $idRemplacant,
            'date_debut' => $debut,
            'date_fin' => $fin,
            'commentaire' => trim((string) ($data['commentaire'] ?? '')) ?: null,
        ], true);
    }

    private function joursInclusifs(string $debut, string $fin): int
    {
        return max(0, (int) ((strtotime($fin) - strtotime($debut)) / 86400) + 1);
    }
}
