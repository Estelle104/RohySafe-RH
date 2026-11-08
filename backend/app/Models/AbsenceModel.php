<?php

namespace App\Models;

use CodeIgniter\Model;

class AbsenceModel extends Model
{
    protected $table = 'absences';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['id_employe', 'date', 'motif', 'id_statut_absence', 'heure_debut', 'heure_fin'];

    public function getAbsences(): array
    {
        $absences = $this->db->table('absences a')
            ->select("a.id, a.id_employe, a.date, a.motif, e.matricule,
                CONCAT(c.prenom, ' ', c.nom) AS nom_complet, sa.libelle AS statut, a.heure_debut, a.heure_fin,
                COALESCE(ct.salaire, 0) AS salaire_mensuel,
                CASE WHEN sa.libelle = 'NON_JUSTIFIEE' AND a.heure_debut IS NOT NULL AND a.heure_fin IS NOT NULL
                    THEN (COALESCE(ct.salaire, 0) / 173.33) * EXTRACT(EPOCH FROM (a.heure_fin - a.heure_debut)) / 3600
                    ELSE 0 END AS retenue")
            ->join('employes e', 'e.id = a.id_employe')
            ->join('candidats c', 'c.id = e.id_candidat')
            ->join('statut_absences sa', 'sa.id = a.id_statut_absence')
            ->join("(SELECT DISTINCT ON (id_employe) id_employe, salaire FROM contrats WHERE date_debut <= CURRENT_DATE AND (date_fin IS NULL OR date_fin >= CURRENT_DATE) ORDER BY id_employe, date_debut DESC) ct", 'ct.id_employe = a.id_employe', 'left', false)
            ->orderBy('a.date', 'DESC')
            ->get()->getResultArray();

        foreach ($absences as &$absence) {
            if ($absence['statut'] !== 'NON_JUSTIFIEE' || (float) $absence['retenue'] > 0) continue;
            $horaire = $this->db->table('planning_edt_employes pee')
                ->select('h.heure_debut, h.heure_fin, h.pause_minutes')
                ->join('planning_edts pe', 'pe.id = pee.id_planning_edt')
                ->join('employes e', 'e.id = pee.id_employe')
                ->join('poste_departement pd', 'pd.id = e.id_poste_departement')
                ->join('horaire_postes h', 'h.id_poste = pd.id_poste')
                ->where('pee.id_employe', (int) $absence['id_employe'])
                ->where('pe.date_debut <=', $absence['date'])->where('pe.date_fin >=', $absence['date'])
                ->where("EXTRACT(ISODOW FROM DATE '{$absence['date']}') = ANY(pe.jours_semaine)", null, false)
                ->get()->getRowArray();
            if ($horaire !== null) {
                $debut = strtotime($absence['date'] . ' ' . $horaire['heure_debut']);
                $fin = strtotime($absence['date'] . ' ' . $horaire['heure_fin']);
                if ($fin <= $debut) $fin = strtotime('+1 day', $fin);
                $heures = max(0, ($fin - $debut) / 3600 - ((int) $horaire['pause_minutes'] / 60));
                $absence['retenue'] = ((float) $absence['salaire_mensuel'] / 173.33) * $heures;
            }
        }
        unset($absence);
        return $absences;
    }

    public function creerAbsence(array $data): int
    {
        $this->insert($data);
        return (int) $this->getInsertID();
    }

    public function detecterAbsencesAutomatiques(): int
    {
        $statut = $this->db->table('statut_absences')->where('libelle', 'A_TRAITER')->get()->getRowArray();
        if ($statut === null) return 0;
        $plannings = $this->db->table('planning_edt_employes pee')
            ->select('pee.id_employe, pe.date_debut, pe.date_fin, pe.jours_semaine')
            ->join('planning_edts pe', 'pe.id = pee.id_planning_edt')->get()->getResultArray();
        $hier = new \DateTimeImmutable('yesterday'); $total = 0;
        foreach ($plannings as $planning) {
            $debut = new \DateTimeImmutable($planning['date_debut']);
            $fin = new \DateTimeImmutable(min($planning['date_fin'], $hier->format('Y-m-d')));
            $jours = trim((string) $planning['jours_semaine'], '{}');
            $jours = $jours === '' ? [] : array_map('intval', explode(',', $jours));
            for ($date = $debut; $date <= $fin; $date = $date->modify('+1 day')) {
                if (! in_array((int) $date->format('N'), $jours, true)) continue;
                $dateTexte = $date->format('Y-m-d');
                $aPointe = $this->db->table('pointages')->where('id_employe', (int) $planning['id_employe'])->where("DATE(date_heure)", $dateTexte)->countAllResults() > 0;
                $existe = $this->where(['id_employe' => (int) $planning['id_employe'], 'date' => $dateTexte])->countAllResults() > 0;
                if (! $aPointe && ! $existe) {
                    $this->insert(['id_employe' => (int) $planning['id_employe'], 'date' => $dateTexte, 'motif' => 'Absence détectée automatiquement : aucun pointage.', 'id_statut_absence' => $statut['id']]);
                    $total++;
                }
            }
        }
        return $total;
    }

    public function modifierStatut(int $id, int $idStatut, ?string $motif = null): bool
    {
        $data = ['id_statut_absence' => $idStatut];
        if ($motif !== null && trim($motif) !== '') $data['motif'] = trim($motif);
        return $this->update($id, $data);
    }
}
