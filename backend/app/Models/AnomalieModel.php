<?php

namespace App\Models;

use CodeIgniter\Model;

class AnomalieModel extends Model
{
    protected $table = 'anomalie_pointages';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'id_employe',
        'date_jour',
        'id_pointage',
        'id_type_anomalie',
        'id_statut',
        'commentaire',
        'traite_par',
        'traite_le',
    ];

    public function getAnomalies(array $filtres = []): array
    {
        $builder = $this->db->table('anomalie_pointages a')
            ->select("a.id, a.id_employe, a.date_jour, a.id_pointage, a.commentaire,
                p.date_heure, p.id_type_pointage,
                a.traite_le, ta.libelle AS type_anomalie, sa.libelle AS statut,
                e.matricule, CONCAT(c.prenom, ' ', c.nom) AS nom_complet")
            ->join('pointages p', 'p.id = a.id_pointage', 'left')
            ->join('type_anomalies ta', 'ta.id = a.id_type_anomalie')
            ->join('statut_anomalie_pointages sa', 'sa.id = a.id_statut')
            ->join('employes e', 'e.id = a.id_employe')
            ->join('candidats c', 'c.id = e.id_candidat')
            ->orderBy('a.date_jour', 'DESC')->orderBy('a.id', 'DESC');
        if (! empty($filtres['id_employe'])) $builder->where('a.id_employe', (int) $filtres['id_employe']);
        if (! empty($filtres['date_debut'])) $builder->where('a.date_jour >=', $filtres['date_debut']);
        if (! empty($filtres['date_fin'])) $builder->where('a.date_jour <=', $filtres['date_fin']);
        if (! empty($filtres['statut'])) $builder->where('sa.libelle', $filtres['statut']);
        return $builder->get()->getResultArray();
    }

    /** Retourne les statuts disponibles pour le filtre des anomalies. */
    public function getStatuts(): array
    {
        return $this->db->table('statut_anomalie_pointages')
            ->select('id, libelle')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function traiter(int $id, string $statut, string $commentaire, ?int $utilisateur): bool
    {
        $statutRow = $this->db->table('statut_anomalie_pointages')->where('libelle', $statut)->get()->getRowArray();
        if ($statutRow === null) return false;
        $ancienne = $this->find($id);
        if ($ancienne === null) return false;
        $ok = $this->update($id, ['id_statut' => $statutRow['id'], 'commentaire' => $commentaire, 'traite_par' => $utilisateur, 'traite_le' => date('Y-m-d H:i:s')]);
        if ($ok) $this->db->table('historique_anomalies')->insert(['id_anomalie' => $id, 'ancien_statut' => $ancienne['id_statut'], 'nouveau_statut' => $statutRow['id'], 'ancien_commentaire' => $ancienne['commentaire'], 'nouveau_commentaire' => $commentaire, 'id_utilisateur' => $utilisateur]);
        return $ok;
    }

    public function corriger(int $id, string $dateHeure, int $type, string $motif, ?int $utilisateur): bool
    {
        $anomalie = $this->find($id);
        if ($anomalie === null || empty($anomalie['id_pointage'])) return false;
        $pointage = $this->db->table('pointages')->where('id', $anomalie['id_pointage'])->get()->getRowArray();
        if ($pointage === null) return false;
        $statut = $this->db->table('statut_anomalie_pointages')->where('libelle', 'CORRIGEE')->get()->getRowArray();
        if ($statut === null) return false;
        $this->db->transStart();
        $this->db->table('pointages')->where('id', $pointage['id'])->update(['date_heure' => $dateHeure, 'id_type_pointage' => $type]);
        $this->db->table('correction_pointages')->insert(['id_pointage' => $pointage['id'], 'ancienne_valeur' => $pointage['date_heure'], 'nouvelle_valeur' => $dateHeure, 'motif' => $motif, 'corrige_par' => $utilisateur]);
        $this->db->table('historique_pointages')->insert(['id_pointage' => $pointage['id'], 'action' => 'MODIFICATION', 'ancienne_date_heure' => $pointage['date_heure'], 'nouvelle_date_heure' => $dateHeure, 'ancien_type' => $pointage['id_type_pointage'], 'nouveau_type' => $type, 'id_utilisateur' => $utilisateur, 'motif' => $motif]);
        $this->db->table('anomalie_pointages')->where('id', $id)->update(['id_statut' => $statut['id'], 'commentaire' => $motif, 'traite_par' => $utilisateur, 'traite_le' => date('Y-m-d H:i:s')]);
        $this->db->transComplete();
        return $this->db->transStatus() !== false;
    }

    /** Détecte les entrées sans sortie et les pointages hors planning. */
    public function detecter(array $filtres = []): int
    {
        $points = $this->db->table('pointages p')
            ->select('p.id, p.id_employe, p.date_heure, tp.libelle')
            ->join('type_pointages tp', 'tp.id = p.id_type_pointage')
            ->orderBy('p.id_employe')->orderBy('p.date_heure')
            ->get()->getResultArray();
        $groupes = [];
        foreach ($points as $point) {
            if (! empty($filtres['id_employe']) && (int) $point['id_employe'] !== (int) $filtres['id_employe']) continue;
            $date = substr($point['date_heure'], 0, 10);
            if (! empty($filtres['date_debut']) && $date < $filtres['date_debut']) continue;
            if (! empty($filtres['date_fin']) && $date > $filtres['date_fin']) continue;
            $groupes[$point['id_employe'] . '|' . $date][] = $point;
        }

        $ajouts = 0;
        foreach ($groupes as $cle => $journee) {
            [$idEmploye, $date] = explode('|', $cle);
            $types = array_column($journee, 'libelle');
            if (
                in_array('ENTREE', $types, true)
                && ! in_array('SORTIE', $types, true)
                && $this->heureSortieDepassee((int) $idEmploye, $date)
            ) {
                $ajouts += $this->ajouterSiAbsent((int) $idEmploye, $date, 'SORTIE_MANQUANTE', null);
            }
            foreach ($journee as $point) {
                if (! $this->estDansPlanning((int) $idEmploye, $date, $point['date_heure'])) {
                    $ajouts += $this->ajouterSiAbsent((int) $idEmploye, $date, 'HORS_PLANNING', (int) $point['id']);
                }
            }
        }

        // Un employé affecté à un planning sans aucun pointage est marqué
        // comme entrée manquante pour la journée planifiée.
        $plannings = $this->db->table('planning_edt_employes pee')
            ->select('pee.id_employe, pe.date_debut, pe.date_fin, pe.jours_semaine')
            ->join('planning_edts pe', 'pe.id = pee.id_planning_edt')->get()->getResultArray();
        foreach ($plannings as $planning) {
            $debut = new \DateTimeImmutable($planning['date_debut']);
            $fin = new \DateTimeImmutable(min($planning['date_fin'], date('Y-m-d')));
            for ($date = $debut; $date <= $fin; $date = $date->modify('+1 day')) {
                $dateTexte = $date->format('Y-m-d');
                if (! empty($filtres['date_debut']) && $dateTexte < $filtres['date_debut']) continue;
                if (! empty($filtres['date_fin']) && $dateTexte > $filtres['date_fin']) continue;
                if (! empty($filtres['id_employe']) && (int) $planning['id_employe'] !== (int) $filtres['id_employe']) continue;
                $jourIso = (int) $date->format('N');
                $jours = trim((string) $planning['jours_semaine'], '{}');
                $jours = $jours === '' ? [] : array_map('intval', explode(',', $jours));
                if (in_array($jourIso, $jours, true) && ! isset($groupes[$planning['id_employe'] . '|' . $dateTexte])) {
                    $ajouts += $this->ajouterSiAbsent((int) $planning['id_employe'], $dateTexte, 'ENTREE_MANQUANTE', null);
                }
            }
        }
        return $ajouts;
    }

    private function estDansPlanning(int $idEmploye, string $date, string $dateHeure): bool
    {
        $ligne = $this->db->table('planning_edt_employes pee')
            ->select('h.heure_debut, h.heure_fin')
            ->join('planning_edts pe', 'pe.id = pee.id_planning_edt')
            ->join('employes e', 'e.id = pee.id_employe')
            ->join('poste_departement pd', 'pd.id = e.id_poste_departement')
            ->join('horaire_postes h', 'h.id_poste = pd.id_poste')
            ->where('pee.id_employe', $idEmploye)->where('pe.date_debut <=', $date)->where('pe.date_fin >=', $date)
            ->where("EXTRACT(ISODOW FROM DATE '{$date}') = ANY(pe.jours_semaine)", null, false)
            ->get()->getRowArray();
        if ($ligne === null) return false;
        $heure = strtotime($dateHeure);
        $debut = strtotime($date . ' ' . $ligne['heure_debut']) - 3600;
        $fin = strtotime($date . ' ' . $ligne['heure_fin']) + 3600;
        return $heure >= $debut && $heure <= $fin;
    }

    /**
     * Vérifie si la tolérance d'une heure après la sortie prévue est dépassée.
     * Une sortie manquante n'est donc pas créée trop tôt dans la journée.
     */
    private function heureSortieDepassee(int $idEmploye, string $date): bool
    {
        $horaire = $this->db->table('planning_edt_employes pee')
            ->select('h.heure_fin')
            ->join('planning_edts pe', 'pe.id = pee.id_planning_edt')
            ->join('employes e', 'e.id = pee.id_employe')
            ->join('poste_departement pd', 'pd.id = e.id_poste_departement')
            ->join('horaire_postes h', 'h.id_poste = pd.id_poste')
            ->where('pee.id_employe', $idEmploye)
            ->where('pe.date_debut <=', $date)
            ->where('pe.date_fin >=', $date)
            ->where("EXTRACT(ISODOW FROM DATE '{$date}') = ANY(pe.jours_semaine)", null, false)
            ->get()->getRowArray();

        if ($horaire === null || empty($horaire['heure_fin'])) {
            return false;
        }

        $sortiePrevue = new \DateTimeImmutable($date . ' ' . $horaire['heure_fin']);
        $maintenant = new \DateTimeImmutable('now');

        // Pour un planning qui traverse minuit, la sortie appartient au jour suivant.
        if ($horaire['heure_fin'] < '06:00:00') {
            $sortiePrevue = $sortiePrevue->modify('+1 day');
        }

        return $maintenant >= $sortiePrevue->modify('+1 hour');
    }

    private function ajouterSiAbsent(int $idEmploye, string $date, string $type, ?int $idPointage): int
    {
        $typeRow = $this->db->table('type_anomalies')->where('libelle', $type)->get()->getRowArray();
        $statut = $this->db->table('statut_anomalie_pointages')->where('libelle', 'A_TRAITER')->get()->getRowArray();
        if ($typeRow === null || $statut === null) return 0;
        $query = $this->db->table($this->table)->where(['id_employe' => $idEmploye, 'date_jour' => $date, 'id_type_anomalie' => $typeRow['id']]);
        if ($query->countAllResults() > 0) return 0;
        $this->insert(['id_employe' => $idEmploye, 'date_jour' => $date, 'id_pointage' => $idPointage, 'id_type_anomalie' => $typeRow['id'], 'id_statut' => $statut['id']]);
        return 1;
    }
}
