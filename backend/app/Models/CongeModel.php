<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTimeImmutable;

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

    /** Récupère les soldes avec les informations utiles de l'employé et du type. */
    public function getSoldes(array $filtres = []): array
    {
        $builder = $this->db->table('solde_conges sc')
            ->select("sc.id, sc.id_employe, sc.id_type_conge, sc.periode_debut,
                sc.periode_fin, sc.droits_acquis, sc.jours_pris, sc.solde_restant,
                e.matricule, CONCAT(c.prenom, ' ', c.nom) AS nom_complet,
                tc.libelle")
            ->join('employes e', 'e.id = sc.id_employe')
            ->join('candidats c', 'c.id = e.id_candidat')
            ->join('type_conges tc', 'tc.id = sc.id_type_conge')
            ->orderBy('c.nom', 'ASC')
            ->orderBy('c.prenom', 'ASC')
            ->orderBy('sc.periode_debut', 'DESC');

        if (! empty($filtres['id_employe'])) {
            $builder->where('sc.id_employe', (int) $filtres['id_employe']);
        }
        if (! empty($filtres['periode_debut'])) {
            $builder->where('sc.periode_debut >=', $filtres['periode_debut']);
        }
        if (! empty($filtres['periode_fin'])) {
            $builder->where('sc.periode_fin <=', $filtres['periode_fin']);
        }

        return $builder->get()->getResultArray();
    }

    /** Recalcule les droits, les jours pris et le solde de la période courante. */
    public function recalculerSoldes(): int
    {
        $annee = (int) date('Y');
        $debutPeriode = new DateTimeImmutable($annee . '-01-01');
        $finPeriode = new DateTimeImmutable($annee . '-12-31');
        $aujourdHui = new DateTimeImmutable(date('Y-m-d'));
        $finAcquisition = $aujourdHui < $finPeriode ? $aujourdHui : $finPeriode;

        $soldes = $this->db->table('solde_conges sc')
            ->select('sc.id, sc.id_employe, sc.id_type_conge, e.date_embauche, cc.nombre_jour')
            ->join('employes e', 'e.id = sc.id_employe')
            ->join('config_conges cc', 'cc.id_type_conge = sc.id_type_conge')
            ->where('sc.periode_debut', $debutPeriode->format('Y-m-d'))
            ->where('sc.periode_fin', $finPeriode->format('Y-m-d'))
            ->get()
            ->getResultArray();

        $this->db->transStart();
        foreach ($soldes as $solde) {
            $dateEmbauche = new DateTimeImmutable($solde['date_embauche']);
            $debutAcquisition = $dateEmbauche > $debutPeriode ? $dateEmbauche : $debutPeriode;
            $moisAcquis = $debutAcquisition <= $finAcquisition
                ? (($finAcquisition->format('Y') - $debutAcquisition->format('Y')) * 12)
                    + ((int) $finAcquisition->format('m') - (int) $debutAcquisition->format('m')) + 1
                : 0;
            $droitsAcquis = max(0, $moisAcquis * (float) $solde['nombre_jour']);

            $joursPris = $this->db->table('conges cg')
                ->select("COALESCE(SUM((LEAST(cg.date_fin, '{$finPeriode->format('Y-m-d')}')
                    - GREATEST(cg.date_debut, '{$debutPeriode->format('Y-m-d')}')) + 1), 0) AS jours_pris", false)
                ->join('histo_demande_conges hdc', 'hdc.id_conge = cg.id')
                ->join('statut_conges st', 'st.id = hdc.id_statut')
                ->where('cg.id_employe', (int) $solde['id_employe'])
                ->where('cg.id_type_conge', (int) $solde['id_type_conge'])
                ->where('cg.date_debut <=', $finPeriode->format('Y-m-d'))
                ->where('cg.date_fin >=', $debutPeriode->format('Y-m-d'))
                ->where('st.libelle', 'VALIDE')
                ->where("hdc.date = (SELECT MAX(h2.date) FROM histo_demande_conges h2 WHERE h2.id_conge = cg.id)", null, false)
                ->get()
                ->getRow('jours_pris');
            $joursPris = (float) ($joursPris ?? 0);

            $this->db->table('solde_conges')->where('id', (int) $solde['id'])->update([
                'droits_acquis' => $droitsAcquis,
                'jours_pris' => $joursPris,
                'solde_restant' => max(0, $droitsAcquis - $joursPris),
            ]);
        }
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException('Le recalcul des soldes a échoué.');
        }

        return count($soldes);
    }
}
