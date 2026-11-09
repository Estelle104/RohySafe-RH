<?php

namespace App\Models;

use CodeIgniter\Model;

class TempsTravailModel extends Model
{
    public function getTempsTravail(array $filtres = []): array
    {
        $builder = $this->db->table('journee_travails jt')
            ->select("jt.id, jt.id_employe, jt.date_jour, jt.minutes_prevues,
                jt.minutes_travaillees, jt.minutes_retard, jt.minutes_depart_anticipe,
                jt.minutes_hs, e.matricule, CONCAT(c.prenom, ' ', c.nom) AS nom_complet")
            ->join('employes e', 'e.id = jt.id_employe')
            ->join('candidats c', 'c.id = e.id_candidat')
            ->orderBy('jt.date_jour', 'DESC');
        if (! empty($filtres['id_employe'])) $builder->where('jt.id_employe', (int) $filtres['id_employe']);
        if (! empty($filtres['date_debut'])) $builder->where('jt.date_jour >=', $filtres['date_debut']);
        if (! empty($filtres['date_fin'])) $builder->where('jt.date_jour <=', $filtres['date_fin']);
        return $builder->get()->getResultArray();
    }

    /** Calcule les journées à partir des pointages d'entrée et de sortie. */
    public function recalculer(array $filtres = []): int
    {
        $builder = $this->db->table('pointages p')
            ->select('p.id_employe, DATE(p.date_heure) AS date_jour, p.date_heure, tp.libelle')
            ->join('type_pointages tp', 'tp.id = p.id_type_pointage')
            ->orderBy('p.id_employe')->orderBy('p.date_heure');
        if (! empty($filtres['id_employe'])) $builder->where('p.id_employe', (int) $filtres['id_employe']);
        if (! empty($filtres['date_debut'])) $builder->where('p.date_heure >=', $filtres['date_debut'] . ' 00:00:00');
        if (! empty($filtres['date_fin'])) $builder->where('p.date_heure <=', $filtres['date_fin'] . ' 23:59:59');

        $groupes = [];
        foreach ($builder->get()->getResultArray() as $pointage) {
            $cle = $pointage['id_employe'] . '|' . $pointage['date_jour'];
            $groupes[$cle][] = $pointage;
        }

        $statut = $this->db->table('statut_journee_travails')->where('libelle', 'CREE')->get()->getRowArray();
        if ($statut === null) {
            $this->db->table('statut_journee_travails')->insert(['libelle' => 'CREE']);
            $statut = $this->db->table('statut_journee_travails')->where('libelle', 'CREE')->get()->getRowArray();
        }
        if ($statut === null) throw new \RuntimeException('Le statut CREE est absent.');

        $comptees = 0;
        $this->db->transStart();
        foreach ($groupes as $points) {
            $entree = null; $sortie = null; $pauseDebut = null; $pauseMinutes = 0;
            foreach ($points as $point) {
                if ($point['libelle'] === 'ENTREE' && $entree === null) $entree = $point['date_heure'];
                if ($point['libelle'] === 'SORTIE') $sortie = $point['date_heure'];
                if ($point['libelle'] === 'PAUSE_DEBUT') $pauseDebut = $point['date_heure'];
                if ($point['libelle'] === 'PAUSE_FIN' && $pauseDebut !== null) {
                    $pauseMinutes += max(0, (strtotime($point['date_heure']) - strtotime($pauseDebut)) / 60);
                    $pauseDebut = null;
                }
            }
            if ($entree === null || $sortie === null) continue;
            $travaillees = max(0, (int) round((strtotime($sortie) - strtotime($entree)) / 60 - $pauseMinutes));
            $idEmploye = (int) $points[0]['id_employe'];
            $dateJour = $points[0]['date_jour'];
            $planning = $this->db->table('planning_edt_employes pee')
                ->select('pee.id AS id_planning_edt_employes, h.heure_debut, h.heure_fin, h.pause_minutes, rt.tolerance_retard_max')
                ->join('planning_edts pe', 'pe.id = pee.id_planning_edt')
                ->join('employes emp', 'emp.id = pee.id_employe')
                ->join('poste_departement pd', 'pd.id = emp.id_poste_departement')
                ->join('horaire_postes h', 'h.id_poste = pd.id_poste')
                ->join('regime_travails rt', 'rt.id = h.id_regime', 'left')
                ->where('pee.id_employe', $idEmploye)
                ->where('pe.date_debut <=', $dateJour)
                ->where('pe.date_fin >=', $dateJour)
                ->where("EXTRACT(ISODOW FROM DATE '{$dateJour}') = ANY(pe.jours_semaine)", null, false)
                ->orderBy('pe.date_debut', 'DESC')
                ->get()->getRowArray();

            $minutesPrevues = $travaillees;
            $minutesRetard = 0;
            $minutesDepartAnticipe = 0;
            $minutesHs = 0;
            if ($planning !== null) {
                $debutPrevu = strtotime($dateJour . ' ' . $planning['heure_debut']);
                $finPrevue = strtotime($dateJour . ' ' . $planning['heure_fin']);
                if ($finPrevue <= $debutPrevu) $finPrevue = strtotime('+1 day', $finPrevu);
                $minutesPrevues = max(0, (int) round(($finPrevue - $debutPrevu) / 60 - (int) $planning['pause_minutes']));
                $minutesRetard = max(0, (int) round((strtotime($entree) - $debutPrevu) / 60 - (int) ($planning['tolerance_retard_max'] ?? 0)));
                $minutesDepartAnticipe = max(0, (int) round(($finPrevue - strtotime($sortie)) / 60));
                $minutesHs = max(0, $travaillees - $minutesPrevues);
            }
            $data = ['id_employe' => $idEmploye, 'date_jour' => $dateJour, 'minutes_prevues' => $minutesPrevues, 'minutes_travaillees' => $travaillees, 'minutes_retard' => $minutesRetard, 'minutes_depart_anticipe' => $minutesDepartAnticipe, 'minutes_hs' => $minutesHs, 'id_planning_edt_employes' => $planning['id_planning_edt_employes'] ?? null, 'id_statut' => (int) $statut['id']];
            $existant = $this->db->table('journee_travails')->where(['id_employe' => $data['id_employe'], 'date_jour' => $data['date_jour']])->get()->getRowArray();
            if ($existant) $this->db->table('journee_travails')->where('id', $existant['id'])->update($data);
            else $this->db->table('journee_travails')->insert($data);
            $comptees++;
        }
        $this->db->transComplete();
        if ($this->db->transStatus() === false) throw new \RuntimeException('Le recalcul du temps de travail a échoué.');
        return $comptees;
    }
}
