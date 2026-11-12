<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

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
                e.date_embauche,
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
        $plafondCumul = 90.00;
        $annee = (int) date('Y');
        $debutPeriode = new DateTimeImmutable($annee . '-01-01');
        $finPeriode = new DateTimeImmutable($annee . '-12-31');
        $aujourdHui = new DateTimeImmutable(date('Y-m-d'));
        $finAcquisition = $aujourdHui < $finPeriode ? $aujourdHui : $finPeriode;

        $soldes = $this->db->table('solde_conges sc')
    ->select('sc.id, sc.id_employe, sc.id_type_conge,
        e.date_embauche,
        COALESCE(cc.nombre_jour, 0) AS nombre_jour')
    ->join('employes e', 'e.id = sc.id_employe')
    ->join('config_conges cc', 'cc.id_type_conge = sc.id_type_conge', 'left')
            ->where('sc.periode_debut', $debutPeriode->format('Y-m-d'))
            ->where('sc.periode_fin', $finPeriode->format('Y-m-d'))
            ->get()
            ->getResultArray();

            log_message('debug', 'Date PHP : ' . date('Y-m-d H:i:s'));
log_message('debug', 'Année PHP : ' . date('Y'));

        $this->db->transStart();
        foreach ($soldes as $solde) {
            $dateEmbauche = new DateTimeImmutable($solde['date_embauche']);
            $debutAcquisition = $dateEmbauche > $debutPeriode ? $dateEmbauche : $debutPeriode;
            $moisAcquis = $debutAcquisition <= $finAcquisition
                ? (($finAcquisition->format('Y') - $debutAcquisition->format('Y')) * 12)
                    + ((int) $finAcquisition->format('m') - (int) $debutAcquisition->format('m')) + 1
                : 0;
            // Le cumul des droits ne peut pas dépasser 90 jours.
            $droitsAcquis = min($plafondCumul, max(0, $moisAcquis * (float) $solde['nombre_jour']));

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
                'solde_restant' => min($plafondCumul, max(0, $droitsAcquis - $joursPris)),
            ]);
        }
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new \RuntimeException('Le recalcul des soldes a échoué.');
        }

        return count($soldes);
    }

    /** Calcule la valeur théorique des droits acquis sur chaque solde. */

    public function getValeurs(array $filtres = []): array
{
    $builder = $this->db->table('solde_conges sc')
        ->select("
            sc.id,
            sc.id_employe,
            sc.periode_debut,
            sc.periode_fin,
            sc.droits_acquis,
            sc.jours_pris,
            sc.solde_restant,
            CONCAT(c.prenom, ' ', c.nom) AS nom_complet,
            e.matricule,
            COALESCE(ct.salaire, 0) AS salaire_brut
        ")
        ->join('employes e', 'e.id = sc.id_employe')
        ->join('candidats c', 'c.id = e.id_candidat')
        ->join(
            "(SELECT DISTINCT ON (id_employe)
                id_employe, salaire
              FROM contrats
              ORDER BY id_employe, date_debut DESC) ct",
            'ct.id_employe = e.id',
            'left',
            false
        )
        ->where('sc.id_type_conge', 1)
->where('sc.periode_debut', date('Y') . '-01-01')
->where('sc.periode_fin', date('Y') . '-12-31')
->orderBy('c.nom', 'ASC');

    if (!empty($filtres['id_employe'])) {
        $builder->where('sc.id_employe', (int) $filtres['id_employe']);
    }

    if (!empty($filtres['periode_debut'])) {
        $builder->where('sc.periode_debut >=', $filtres['periode_debut']);
    }

    if (!empty($filtres['periode_fin'])) {
        $builder->where('sc.periode_fin <=', $filtres['periode_fin']);
    }

    $rows = $builder->get()->getResultArray();

    foreach ($rows as &$row) {
        $salaire = (float) $row['salaire_brut'];
        $droits = (float) $row['droits_acquis'];
        $solde = (float) $row['solde_restant'];

        $row['valeur_theorique'] = round(
            $salaire * $droits / 30,
            2
        );

        $row['indemnite_depart'] = round(
            $salaire * $solde / 30,
            2
        );
    }

    unset($row);

    return $rows;
}


    /** Vérifie qu'une demande respecte le solde disponible et la période. */
    public function validerDemande(array $data): array
    {
        $idEmploye = (int) ($data['id_employe'] ?? 0);
        $idTypeConge = (int) ($data['id_type_conge'] ?? 0);
        $debut = (string) ($data['date_debut'] ?? '');
        $fin = (string) ($data['date_fin'] ?? '');
        if ($idEmploye <= 0 || $idTypeConge <= 0 || $debut === '' || $fin === '' || $fin < $debut) {
            return ['valide' => false, 'motif' => 'Les informations du congé sont invalides.'];
        }

        $jours = (int) ((strtotime($fin) - strtotime($debut)) / 86400) + 1;
        $solde = $this->db->table('solde_conges')
            ->where('id_employe', $idEmploye)
            ->where('id_type_conge', $idTypeConge)
            ->where('periode_debut <=', $debut)
            ->where('periode_fin >=', $fin)
            ->orderBy('periode_debut', 'DESC')
            ->get()->getRowArray();

        if ($solde === null) {
            return ['valide' => false, 'motif' => 'Aucun solde disponible pour cette période.'];
        }
        if ((float) $solde['solde_restant'] < $jours) {
            return [
                'valide' => false,
                'motif' => sprintf(
                    'Le solde de congés est insuffisant : %.2f jour(s) disponible(s) pour %d jour(s) demandé(s).',
                    (float) $solde['solde_restant'],
                    $jours
                ),
            ];
        }

        return ['valide' => true, 'jours' => $jours, 'solde_restant' => (float) $solde['solde_restant']];
    }

    public function getTypesPourDemande(): array
    {
        return $this->db->table('type_conges tc')
            ->select('tc.id, tc.libelle')
            ->join('config_conges cc', 'cc.id_type_conge = tc.id', 'left')
            ->orderBy('tc.libelle', 'ASC')
            ->get()->getResultArray();
    }

    public function getEmployesPourDemande(?int $idDepartement = null): array
    {
        $builder = $this->db->table('employes e')
            ->select("e.id, e.matricule, e.id_poste_departement,
                c.nom, c.prenom, CONCAT(c.prenom, ' ', c.nom) AS nom_complet,
                pd.id_departement, d.libelle AS departement")
            ->join('candidats c', 'c.id = e.id_candidat')
            ->join('poste_departement pd', 'pd.id = e.id_poste_departement', 'left')
            ->join('departements d', 'd.id = pd.id_departement', 'left');

        if ($idDepartement !== null) {
            $builder->where('pd.id_departement', $idDepartement);
        }

        return $builder->orderBy('c.nom')->orderBy('c.prenom')->get()->getResultArray();
    }

    public function creerDemande(array $data, array $utilisateur): array
    {
        $idEmploye = (int) ($data['id_employe'] ?? 0);
        $idType = (int) ($data['id_type_conge'] ?? 0);
        $debut = trim((string) ($data['date_debut'] ?? ''));
        $fin = trim((string) ($data['date_fin'] ?? ''));
        $idRemplacant = (int) ($data['id_employe_remplacant'] ?? 0);

        if ($idEmploye <= 0 || $idType <= 0 || $debut === '' || $fin === '') {
            throw new InvalidArgumentException('L’employé, le type et les dates sont obligatoires.');
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $debut) ||
            ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin) || $fin < $debut) {
            throw new InvalidArgumentException('La période du congé est invalide.');
        }

        $validation = $this->validerDemande([
            'id_employe' => $idEmploye,
            'id_type_conge' => $idType,
            'date_debut' => $debut,
            'date_fin' => $fin,
        ]);
        if (! $validation['valide']) {
            throw new InvalidArgumentException($validation['motif']);
        }

        $employe = $this->db->table('employes')
            ->select('id, id_poste_departement')
            ->where('id', $idEmploye)->get()->getRowArray();
        if ($employe === null) {
            throw new InvalidArgumentException('L’employé sélectionné est introuvable.');
        }

        if ($idRemplacant <= 0) {
            $candidat = $this->db->table('employes e')
                ->select('e.id')
                ->where('e.id <>', $idEmploye)
                ->where('e.id_poste_departement', $employe['id_poste_departement'])
                ->orderBy('e.id')
                ->get()->getRowArray();
            $idRemplacant = (int) ($candidat['id'] ?? 0);
        }
        if ($idRemplacant <= 0) {
            throw new InvalidArgumentException('Aucun remplaçant compatible n’a été trouvé.');
        }

        $this->db->transStart();
        $this->db->table('conges')->insert([
            'id_employe' => $idEmploye,
            'date_debut' => $debut,
            'date_fin' => $fin,
            'id_type_conge' => $idType,
        ]);
        $idConge = (int) $this->db->insertID();

        $idStatutConge = $this->getOrCreateStatus('statut_conges', 'En attente');
        $this->db->table('histo_demande_conges')->insert([
            'id_statut' => $idStatutConge,
            'id_utilisateur' => (int) ($utilisateur['id'] ?? 0),
            'id_conge' => $idConge,
        ]);

        $this->db->table('remplacement_conges')->insert([
            'id_conge' => $idConge,
            'id_employee_remplacant' => $idRemplacant,
            'date_debut' => $debut,
            'date_fin' => $fin,
            'commentaire' => 'Remplaçant proposé automatiquement.',
        ]);

        $heureDebut = trim((string) ($data['heure_debut'] ?? '08:00'));
        $heureFin = trim((string) ($data['heure_fin'] ?? '17:00'));
        if (! preg_match('/^\d{2}:\d{2}$/', $heureDebut) ||
            ! preg_match('/^\d{2}:\d{2}$/', $heureFin) ||
            $heureFin <= $heureDebut) {
            throw new InvalidArgumentException('Les horaires du remplacement sont invalides.');
        }
        $dateDebutHeure = $debut . ' ' . $heureDebut . ':00';
        $dateFinHeure = $fin . ' ' . $heureFin . ':00';
        $this->db->table('heure_supplementaires')->insert([
            'id_employe' => $idRemplacant,
            'heure_debut' => $dateDebutHeure,
            'heure_fin' => $dateFinHeure,
            'date_debut' => $debut,
            'date_fin' => $fin,
        ]);
        $idHeure = (int) $this->db->insertID();
        $idStatutHeure = $this->getOrCreateStatus('statut_heure_sups', 'En attente');
        $this->db->table('histo_demande_heure_sups')->insert([
            'id_statut' => $idStatutHeure,
            'id_utilisateur' => (int) ($utilisateur['id'] ?? 0),
            'id_heure_supplementaire' => $idHeure,
        ]);
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new RuntimeException('Impossible d’enregistrer la demande de congé.');
        }

        return [
            'id_conge' => $idConge,
            'id_remplacant' => $idRemplacant,
            'id_heure_supplementaire' => $idHeure,
        ];
    }

    public function listerDemandes(): array
    {
        return $this->db->table('conges c')
            ->select("c.id, c.id_employe, c.date_debut, c.date_fin, tc.libelle AS type_conge,
                CONCAT(ca.prenom, ' ', ca.nom) AS employe, st.libelle AS statut")
            ->join('type_conges tc', 'tc.id = c.id_type_conge')
            ->join('employes e', 'e.id = c.id_employe')
            ->join('candidats ca', 'ca.id = e.id_candidat')
            ->join("(SELECT DISTINCT ON (id_conge) id_conge, id_statut
                FROM histo_demande_conges ORDER BY id_conge, date DESC, id DESC) h", 'h.id_conge = c.id')
            ->join('statut_conges st', 'st.id = h.id_statut')
            ->orderBy('c.date_debut', 'DESC')
            ->get()->getResultArray();
    }

    public function accepterDemande(int $idConge, int $idUtilisateur): void
    {
        $demande = $this->db->table('conges c')
            ->select('c.id, st.libelle AS statut')
            ->join("(SELECT DISTINCT ON (id_conge) id_conge, id_statut
                FROM histo_demande_conges ORDER BY id_conge, date DESC, id DESC) h", 'h.id_conge = c.id')
            ->join('statut_conges st', 'st.id = h.id_statut')
            ->where('c.id', $idConge)->get()->getRowArray();
        if ($demande === null) {
            throw new InvalidArgumentException('La demande de congé est introuvable.');
        }
        if (mb_strtolower($demande['statut']) !== 'en attente') {
            throw new InvalidArgumentException('Seules les demandes en attente peuvent être acceptées.');
        }
        $this->db->table('histo_demande_conges')->insert([
            'id_statut' => $this->getOrCreateStatus('statut_conges', 'Validée'),
            'id_utilisateur' => $idUtilisateur,
            'id_conge' => $idConge,
        ]);
    }

    private function getOrCreateStatus(string $table, string $libelle): int
    {
        $row = $this->db->table($table)->select('id')->where('libelle', $libelle)->get()->getRowArray();
        if ($row !== null) {
            return (int) $row['id'];
        }
        $this->db->table($table)->insert(['libelle' => $libelle]);
        return (int) $this->db->insertID();
    }
}
