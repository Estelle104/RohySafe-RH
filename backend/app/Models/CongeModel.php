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
        $annee = (int) date('Y');
        $debutPeriode = new DateTimeImmutable($annee . '-01-01');
        $finPeriode = new DateTimeImmutable($annee . '-12-31');
        $aujourdHui = new DateTimeImmutable(date('Y-m-d'));
        $finAcquisition = $aujourdHui < $finPeriode ? $aujourdHui : $finPeriode;

        $soldes = $this->db->table('solde_conges sc')
            ->select('sc.id, sc.id_employe, sc.id_type_conge, e.date_embauche,
                COALESCE(cc.nombre_jour, 0) AS nombre_jour')
            ->join('employes e', 'e.id = sc.id_employe')
            ->join('config_conges cc', 'cc.id_type_conge = sc.id_type_conge', 'left')
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
                ->where("LOWER(st.libelle) IN ('validée','validee','valide','valide')", null, false)
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
            throw new RuntimeException('Le recalcul des soldes a échoué.');
        }

        return count($soldes);
    }

    public function getValeurs(array $filtres = []): array
    {
        $builder = $this->db->table('solde_conges sc')
            ->select("
                sc.id, sc.id_employe, sc.periode_debut, sc.periode_fin,
                sc.droits_acquis, sc.jours_pris, sc.solde_restant,
                CONCAT(c.prenom, ' ', c.nom) AS nom_complet,
                e.matricule, COALESCE(ct.salaire, 0) AS salaire_brut
            ")
            ->join('employes e', 'e.id = sc.id_employe')
            ->join('candidats c', 'c.id = e.id_candidat')
            ->join(
                "(SELECT DISTINCT ON (id_employe) id_employe, salaire
                  FROM contrats ORDER BY id_employe, date_debut DESC) ct",
                'ct.id_employe = e.id',
                'left',
                false
            )
            ->where('sc.id_type_conge', 1)
            ->where('sc.periode_debut', date('Y') . '-01-01')
            ->where('sc.periode_fin', date('Y') . '-12-31')
            ->orderBy('c.nom', 'ASC');

        if (! empty($filtres['id_employe'])) {
            $builder->where('sc.id_employe', (int) $filtres['id_employe']);
        }
        if (! empty($filtres['periode_debut'])) {
            $builder->where('sc.periode_debut >=', $filtres['periode_debut']);
        }
        if (! empty($filtres['periode_fin'])) {
            $builder->where('sc.periode_fin <=', $filtres['periode_fin']);
        }

        $rows = $builder->get()->getResultArray();
        foreach ($rows as &$row) {
            $salaire = (float) $row['salaire_brut'];
            $droits = (float) $row['droits_acquis'];
            $solde = (float) $row['solde_restant'];
            $row['valeur_theorique'] = round($salaire * $droits / 30, 2);
            $row['indemnite_depart'] = round($salaire * $solde / 30, 2);
        }
        unset($row);
        return $rows;
    }

    /** Vérifie qu'une demande respecte le solde disponible (tâches 8,9). */
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
            return ['valide' => false, 'motif' => 'Aucun solde disponible pour cette période. Demande bloquée (avance de congé non autorisée).'];
        }
        if ((float) $solde['solde_restant'] < $jours) {
            return [
                'valide' => false,
                'motif' => sprintf(
                    'Solde insuffisant : %.2f jour(s) disponible(s) pour %d jour(s) demandé(s). Demande bloquée (solde négatif non autorisé).',
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
        $fetchBase = function (?int $idDept) {
            $builder = $this->db->table('employes e')
                ->select("e.id, e.matricule, e.id_candidat,
                    c.nom, c.prenom, CONCAT(c.prenom, ' ', c.nom) AS nom_complet,
                    pd.id_departement, d.libelle AS departement")
                ->join('candidats c', 'c.id = e.id_candidat')
                ->join('poste_departement pd', 'pd.id = e.id_poste_departement', 'left')
                ->join('departements d', 'd.id = pd.id_departement', 'left');
            if ($idDept !== null) {
                $builder->where('pd.id_departement', $idDept);
            }
            return $builder->orderBy('c.nom')->orderBy('c.prenom')->get()->getResultArray();
        };

        try {
            $rows = $fetchBase($idDepartement);
            // Si filtre département actif mais aucun poste/département lié, ne pas bloquer la saisie RH
            if ($idDepartement !== null && $rows === []) {
                log_message('warning', 'getEmployesPourDemande: aucun résultat pour id_departement=' . $idDepartement . ', fallback sans filtre.');
                $rows = $fetchBase(null);
            }
            if ($rows !== []) return $rows;
            // Aucun employé trouvé : vérifier existence brute
            return $rows;
        } catch (\Throwable $e) {
            log_message('warning', 'getEmployesPourDemande fallback: ' . $e->getMessage());
            return $this->db->table('employes e')
                ->select("e.id, e.matricule, e.id_candidat, c.nom, c.prenom, CONCAT(c.prenom, ' ', c.nom) AS nom_complet")
                ->join('candidats c', 'c.id = e.id_candidat')
                ->orderBy('c.nom')->orderBy('c.prenom')->get()->getResultArray();
        }
    }

    /** Tâches 3,4,6,7,8,9,10,12,13,15 : création demande (RH) + remplaçant. */
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
            ->select('id, id_poste_departement, id_candidat')
            ->where('id', $idEmploye)->get()->getRowArray();
        if ($employe === null) {
            throw new InvalidArgumentException('L’employé sélectionné est introuvable.');
        }

        if ($idRemplacant <= 0) {
            $suggestions = $this->rechercherRemplacants($idEmploye, $debut, $fin);
            $idRemplacant = (int) ($suggestions[0]['id'] ?? 0);
        }
        if ($idRemplacant <= 0) {
            throw new InvalidArgumentException('Aucun remplaçant disponible n’a été trouvé pour cette période.');
        }
        if ($idRemplacant === $idEmploye) {
            throw new InvalidArgumentException('Un employé ne peut pas se remplacer lui-même.');
        }
        if (! $this->estDisponiblePourRemplacement($idRemplacant, $debut, $fin)) {
            throw new InvalidArgumentException('Le remplaçant sélectionné n’est pas disponible sur cette période.');
        }

        $this->db->transStart();
        $this->db->table('conges')->insert([
            'id_employe' => $idEmploye,
            'date_debut' => $debut,
            'date_fin' => $fin,
            'id_type_conge' => $idType,
        ]);
        $idConge = (int) $this->db->insertID();

        $this->ajouterHistoriqueDemande($idConge, $this->getOrCreateStatus('statut_conges', 'En attente'), (int) ($utilisateur['id'] ?? 0));

        $this->db->table('remplacement_conges')->insert([
            'id_conge' => $idConge,
            'id_employee_remplacant' => $idRemplacant,
            'date_debut' => $debut,
            'date_fin' => $fin,
            'commentaire' => 'Remplacement affecté lors de la demande.',
        ]);

        // Heures supp. remplaçant (comportement existant) - best effort si tables présentes
        try {
            $heureDebut = trim((string) ($data['heure_debut'] ?? '08:00'));
            $heureFin = trim((string) ($data['heure_fin'] ?? '17:00'));
            if (preg_match('/^\d{2}:\d{2}$/', $heureDebut) && preg_match('/^\d{2}:\d{2}$/', $heureFin) && $heureFin > $heureDebut) {
                $this->db->table('heure_supplementaires')->insert([
                    'id_employe' => $idRemplacant,
                    'heure_debut' => $debut . ' ' . $heureDebut . ':00',
                    'heure_fin' => $fin . ' ' . $heureFin . ':00',
                    'date_debut' => $debut,
                    'date_fin' => $fin,
                ]);
                $idHeure = (int) $this->db->insertID();
                $this->db->table('histo_demande_heure_sups')->insert([
                    'id_statut' => $this->getOrCreateStatus('statut_heure_sups', 'En attente'),
                    'id_utilisateur' => (int) ($utilisateur['id'] ?? 0),
                    'id_heure_supplementaire' => $idHeure,
                ]);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Heures supp remplacement non créées: ' . $e->getMessage());
        }
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new RuntimeException('Impossible d’enregistrer la demande de congé.');
        }

        return ['id_conge' => $idConge, 'id_remplacant' => $idRemplacant];
    }

    /** Alias tâche 6. */
    public function createDemandeConge(array $data, array $utilisateur): array
    {
        return $this->creerDemande($data, $utilisateur);
    }

    public function listerDemandes(array $filtres = []): array
    {
        $builder = $this->db->table('conges c')
            ->select("c.id, c.id_employe, c.date_debut, c.date_fin, c.id_type_conge,
                tc.libelle AS type_conge,
                CONCAT(ca.prenom, ' ', ca.nom) AS employe, e.matricule,
                st.libelle AS statut, h.date AS date_statut,
                (SELECT COUNT(*) FROM histo_demande_conges hx WHERE hx.id_conge = c.id) AS nb_historiques,
                rc.id_employee_remplacant, CONCAT(cr.prenom, ' ', cr.nom) AS remplacant")
            ->join('type_conges tc', 'tc.id = c.id_type_conge')
            ->join('employes e', 'e.id = c.id_employe')
            ->join('candidats ca', 'ca.id = e.id_candidat')
            ->join("(SELECT DISTINCT ON (id_conge) id_conge, id_statut, date
                FROM histo_demande_conges ORDER BY id_conge, date DESC, id DESC) h", 'h.id_conge = c.id')
            ->join('statut_conges st', 'st.id = h.id_statut')
            ->join("(SELECT DISTINCT ON (id_conge) id_conge, id_employee_remplacant
                FROM remplacement_conges ORDER BY id_conge, id DESC) rc", 'rc.id_conge = c.id', 'left')
            ->join('employes er', 'er.id = rc.id_employee_remplacant', 'left')
            ->join('candidats cr', 'cr.id = er.id_candidat', 'left')
            ->orderBy('c.date_debut', 'DESC')->orderBy('c.id', 'DESC');

        if (! empty($filtres['id_employe'])) $builder->where('c.id_employe', (int) $filtres['id_employe']);
        if (! empty($filtres['statut'])) $builder->where('LOWER(st.libelle)', mb_strtolower((string) $filtres['statut']));
        if (! empty($filtres['periode_debut'])) $builder->where('c.date_fin >=', $filtres['periode_debut']);
        if (! empty($filtres['periode_fin'])) $builder->where('c.date_debut <=', $filtres['periode_fin']);

        return $builder->get()->getResultArray();
    }

    /** Alias tâche 6. */
    public function getDemandesConge(array $filtres = []): array
    {
        return $this->listerDemandes($filtres);
    }

    public function getDemandeConge(int $idConge): ?array
    {
        $demande = $this->db->table('conges c')
            ->select("c.*, tc.libelle AS type_conge, CONCAT(ca.prenom, ' ', ca.nom) AS employe, e.matricule")
            ->join('type_conges tc', 'tc.id = c.id_type_conge')
            ->join('employes e', 'e.id = c.id_employe')
            ->join('candidats ca', 'ca.id = e.id_candidat')
            ->where('c.id', $idConge)->get()->getRowArray();
        if ($demande === null) return null;
        $demande['statut'] = $this->getDernierStatut($idConge);
        $demande['historique'] = $this->getHistoriqueDemande($idConge);
        $demande['remplacements'] = $this->db->table('remplacement_conges r')
            ->select("r.*, CONCAT(ca.prenom, ' ', ca.nom) AS remplacant, e.matricule")
            ->join('employes e', 'e.id = r.id_employee_remplacant')
            ->join('candidats ca', 'ca.id = e.id_candidat')
            ->where('r.id_conge', $idConge)->orderBy('r.date_debut')->get()->getResultArray();
        return $demande;
    }

    public function getHistoriqueDemande(int $idConge): array
    {
        try {
            return $this->db->table('histo_demande_conges h')
                ->select("h.id, h.date, h.id_utilisateur, st.libelle AS statut,
                    COALESCE(u.login, '') AS utilisateur")
                ->join('statut_conges st', 'st.id = h.id_statut')
                ->join('utilisateurs u', 'u.id = h.id_utilisateur', 'left')
                ->where('h.id_conge', $idConge)
                ->orderBy('h.date', 'DESC')->orderBy('h.id', 'DESC')
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('warning', 'getHistoriqueDemande fallback: ' . $e->getMessage());
            return $this->db->table('histo_demande_conges h')
                ->select("h.id, h.date, h.id_utilisateur, st.libelle AS statut")
                ->join('statut_conges st', 'st.id = h.id_statut')
                ->where('h.id_conge', $idConge)
                ->orderBy('h.date', 'DESC')->orderBy('h.id', 'DESC')
                ->get()->getResultArray();
        }
    }

    private function getDernierStatut(int $idConge): ?string
    {
        $row = $this->db->table('histo_demande_conges h')
            ->select('st.libelle')
            ->join('statut_conges st', 'st.id = h.id_statut')
            ->where('h.id_conge', $idConge)
            ->orderBy('h.date', 'DESC')->orderBy('h.id', 'DESC')
            ->get()->getRowArray();
        return $row['libelle'] ?? null;
    }

    private function ajouterHistoriqueDemande(int $idConge, int $idStatut, int $idUtilisateur): void
    {
        $this->db->table('histo_demande_conges')->insert([
            'id_statut' => $idStatut,
            'id_utilisateur' => $idUtilisateur,
            'id_conge' => $idConge,
        ]);
    }

    /** Tâches 6,7 : validation/acceptation (re-vérifie solde). */
    public function accepterDemande(int $idConge, int $idUtilisateur): void
    {
        $this->changerStatutDemande($idConge, $idUtilisateur, 'Validée', ['en attente']);
    }

    /** Alias tâche 6. */
    public function validerDemandeConge(int $idConge, int $idUtilisateur): void
    {
        $this->accepterDemande($idConge, $idUtilisateur);
    }

    /** Tâches 2,6,7 : refus. */
    public function refuserDemande(int $idConge, int $idUtilisateur, ?string $motif = null): void
    {
        $this->changerStatutDemande($idConge, $idUtilisateur, 'Refusée', ['en attente'], $motif);
    }

    /** Tâche 1/2 : annulation. */
    public function annulerDemande(int $idConge, int $idUtilisateur, ?string $motif = null): void
    {
        $this->changerStatutDemande($idConge, $idUtilisateur, 'Annulée', ['en attente', 'validée', 'validee'], $motif);
    }

    private function changerStatutDemande(int $idConge, int $idUtilisateur, string $nouveau, array $attendus, ?string $motif = null): void
    {
        $demande = $this->db->table('conges')->where('id', $idConge)->get()->getRowArray();
        if ($demande === null) throw new InvalidArgumentException('La demande de congé est introuvable.');
        $statut = $this->getDernierStatut($idConge) ?? '';
        $norm = $this->normaliserStatut($statut);
        $ok = false;
        foreach ($attendus as $a) {
            if ($norm === $this->normaliserStatut($a)) { $ok = true; break; }
        }
        if (! $ok) {
            throw new InvalidArgumentException("Transition impossible : statut actuel « {$statut} ».");
        }
        if ($this->normaliserStatut($nouveau) === 'validee') {
            $validation = $this->validerDemande([
                'id_employe' => $demande['id_employe'],
                'id_type_conge' => $demande['id_type_conge'],
                'date_debut' => $demande['date_debut'],
                'date_fin' => $demande['date_fin'],
            ]);
            if (! $validation['valide']) throw new InvalidArgumentException($validation['motif']);
        }
        $this->ajouterHistoriqueDemande($idConge, $this->getOrCreateStatus('statut_conges', $nouveau), $idUtilisateur);
        // motif : stockable dans commentaire via table dédiée si besoin; non bloquant.
        unset($motif);
    }

    private function normaliserStatut(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace(['é','è','ê'], 'e', $s);
        return $s;
    }

    /** Tâche 11 : congés prévus (validés/en attente à venir ou en cours). */
    public function getCongesPrevus(array $filtres = []): array
    {
        $debut = $filtres['periode_debut'] ?? date('Y-m-d');
        $fin = $filtres['periode_fin'] ?? date('Y-m-d', strtotime('+90 days'));
        return $this->listerDemandes([
            'periode_debut' => $debut,
            'periode_fin' => $fin,
        ]);
    }

    /** Tâches 11,13,16 : employés disponibles pour remplacement sur une période. */
    public function getEmployesDisponiblesRemplacement(string $debut, string $fin, ?int $idEmployeAbsent = null, ?int $idConge = null): array
    {
        $this->assertPeriode($debut, $fin);
        $employes = $this->db->table('employes e')
            ->select("e.id, e.matricule, e.id_poste_departement, e.id_candidat,
                CONCAT(c.prenom, ' ', c.nom) AS nom_complet,
                pd.id_departement, d.libelle AS departement, p.libelle AS poste")
            ->join('candidats c', 'c.id = e.id_candidat')
            ->join('poste_departement pd', 'pd.id = e.id_poste_departement', 'left')
            ->join('departements d', 'd.id = pd.id_departement', 'left')
            ->join('postes p', 'p.id = pd.id_poste', 'left')
            ->orderBy('c.nom')->orderBy('c.prenom')->get()->getResultArray();

        $disponibles = [];
        foreach ($employes as $e) {
            $id = (int) $e['id'];
            if ($idEmployeAbsent !== null && $id === $idEmployeAbsent) continue;
            if ($this->estDisponiblePourRemplacement($id, $debut, $fin, $idConge)) {
                $e['competences'] = $this->getCompetencesEmploye($id);
                $disponibles[] = $e;
            }
        }
        return $disponibles;
    }

    public function estDisponiblePourRemplacement(int $idEmploye, string $debut, string $fin, ?int $idCongeIgnore = null): bool
    {
        // Congé actif (en attente/validée) sur la période
        $conges = $this->db->table('conges cg')
            ->join("(SELECT DISTINCT ON (id_conge) id_conge, id_statut FROM histo_demande_conges ORDER BY id_conge, date DESC, id DESC) h", 'h.id_conge = cg.id')
            ->join('statut_conges st', 'st.id = h.id_statut')
            ->where('cg.id_employe', $idEmploye)
            ->where('cg.date_debut <=', $fin)->where('cg.date_fin >=', $debut)
            ->where("LOWER(st.libelle) IN ('en attente','validee','valide','validée')", null, false);
        if ($idCongeIgnore) $conges->where('cg.id <>', $idCongeIgnore);
        if ($conges->countAllResults() > 0) return false;

        // Déjà remplaçant sur la période
        $remp = $this->db->table('remplacement_conges r')
            ->where('r.id_employee_remplacant', $idEmploye)
            ->where('r.date_debut <=', $fin)->where('r.date_fin >=', $debut);
        if ($idCongeIgnore) $remp->where('r.id_conge <>', $idCongeIgnore);
        if ($remp->countAllResults() > 0) return false;

        // Absence ponctuelle dans la période
        try {
            $abs = $this->db->table('absences')
                ->where('id_employe', $idEmploye)
                ->where('date >=', $debut)->where('date <=', $fin)
                ->countAllResults();
            if ($abs > 0) return false;
        } catch (\Throwable $e) { /* table absences optionnelle */ }

        return true;
    }

    /** Tâche 12,13 : recherche auto remplaçants (poste + compétences + disponibilité). */
    public function rechercherRemplacants(int $idEmployeAbsent, string $debut, string $fin): array
    {
        $this->assertPeriode($debut, $fin);
        $absent = $this->db->table('employes e')
            ->select('e.id, e.id_poste_departement, e.id_candidat')
            ->where('e.id', $idEmployeAbsent)->get()->getRowArray();
        if ($absent === null) throw new InvalidArgumentException('Employé absent introuvable.');

        $competencesAbs = $this->getCompetencesIdsCandidat((int) ($absent['id_candidat'] ?? 0));
        $candidats = $this->getEmployesDisponiblesRemplacement($debut, $fin, $idEmployeAbsent);
        foreach ($candidats as &$c) {
            $score = 0;
            if (! empty($absent['id_poste_departement']) && (int) ($c['id_poste_departement'] ?? 0) === (int) $absent['id_poste_departement']) {
                $score += 100; // même poste
            }
            $compIds = $this->getCompetencesIdsCandidat((int) ($c['id_candidat'] ?? 0));
            $match = count(array_intersect($competencesAbs, $compIds));
            $score += $match * 10;
            $c['score_remplacement'] = $score;
            $c['competences_communes'] = $match;
            $c['meme_poste'] = (! empty($absent['id_poste_departement']) && (int) ($c['id_poste_departement'] ?? 0) === (int) $absent['id_poste_departement']);
        }
        unset($c);
        usort($candidats, static function ($a, $b) {
            $d = ($b['score_remplacement'] ?? 0) <=> ($a['score_remplacement'] ?? 0);
            return $d !== 0 ? $d : strcmp((string) $a['nom_complet'], (string) $b['nom_complet']);
        });
        return $candidats;
    }

    /** Tâche 14,15 : affecter/enregistrer un remplaçant. */
    public function affecterRemplacant(array $data): int
    {
        $idConge = (int) ($data['id_conge'] ?? 0);
        $idRemplacant = (int) ($data['id_employe_remplacant'] ?? $data['id_employee_remplacant'] ?? 0);
        $conge = $this->db->table('conges')->where('id', $idConge)->get()->getRowArray();
        if ($conge === null) throw new InvalidArgumentException('Congé introuvable.');
        $debut = trim((string) ($data['date_debut'] ?? $conge['date_debut']));
        $fin = trim((string) ($data['date_fin'] ?? $conge['date_fin']));
        $this->assertPeriode($debut, $fin);
        if ($debut < $conge['date_debut'] || $fin > $conge['date_fin']) {
            throw new InvalidArgumentException('La période de remplacement doit être incluse dans celle du congé.');
        }
        if ($idRemplacant <= 0) throw new InvalidArgumentException('Remplaçant obligatoire.');
        if ((int) $conge['id_employe'] === $idRemplacant) throw new InvalidArgumentException('Un employé ne peut pas se remplacer lui-même.');
        if (! $this->estDisponiblePourRemplacement($idRemplacant, $debut, $fin, $idConge)) {
            throw new InvalidArgumentException('Le remplaçant n’est pas disponible sur cette période.');
        }
        $this->db->table('remplacement_conges')->insert([
            'id_conge' => $idConge,
            'id_employee_remplacant' => $idRemplacant,
            'date_debut' => $debut,
            'date_fin' => $fin,
            'commentaire' => trim((string) ($data['commentaire'] ?? '')) ?: null,
        ]);
        return (int) $this->db->insertID();
    }

    /** Tâche 16 : employés pouvant partir en congé (solde > 0). */
    public function getEmployesEligiblesConges(?int $idTypeConge = null): array
    {
        $builder = $this->db->table('solde_conges sc')
            ->select("sc.id_employe, e.matricule, CONCAT(c.prenom, ' ', c.nom) AS nom_complet,
                sc.id_type_conge, tc.libelle AS type_conge, sc.solde_restant, sc.periode_debut, sc.periode_fin")
            ->join('employes e', 'e.id = sc.id_employe')
            ->join('candidats c', 'c.id = e.id_candidat')
            ->join('type_conges tc', 'tc.id = sc.id_type_conge')
            ->where('sc.solde_restant >', 0)
            ->where('sc.periode_fin >=', date('Y-m-d'))
            ->orderBy('c.nom')->orderBy('c.prenom');
        if ($idTypeConge) $builder->where('sc.id_type_conge', $idTypeConge);
        return $builder->get()->getResultArray();
    }

    private function getCompetencesIdsCandidat(int $idCandidat): array
    {
        if ($idCandidat <= 0) return [];
        try {
            // Schéma courant : competences_candidat.id_candidature -> candidatures.id_candidat
            $rows = $this->db->table('competences_candidat cc')
                ->select('cc.id_competence')
                ->join('candidatures ca', 'ca.id = cc.id_candidature')
                ->where('ca.id_candidat', $idCandidat)
                ->get()->getResultArray();
            return array_values(array_unique(array_map(static fn($r) => (int) $r['id_competence'], $rows)));
        } catch (\Throwable $e) {
            try {
                // Fallback ancien schéma
                $rows = $this->db->table('competences_candidat')->select('id_competence')
                    ->where('id_candidat', $idCandidat)->get()->getResultArray();
                return array_values(array_unique(array_map(static fn($r) => (int) $r['id_competence'], $rows)));
            } catch (\Throwable $e2) { return []; }
        }
    }

    private function getCompetencesEmploye(int $idEmploye): array
    {
        try {
            return $this->db->table('employes e')
                ->select('co.libelle')
                ->join('candidatures ca', 'ca.id_candidat = e.id_candidat')
                ->join('competences_candidat cc', 'cc.id_candidature = ca.id')
                ->join('competences co', 'co.id = cc.id_competence')
                ->where('e.id', $idEmploye)->get()->getResultArray();
        } catch (\Throwable $e) {
            try {
                return $this->db->table('employes e')
                    ->select('co.libelle')
                    ->join('competences_candidat cc', 'cc.id_candidat = e.id_candidat')
                    ->join('competences co', 'co.id = cc.id_competence')
                    ->where('e.id', $idEmploye)->get()->getResultArray();
            } catch (\Throwable $e2) { return []; }
        }
    }

    private function assertPeriode(string $debut, string $fin): void
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $debut) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin) || $fin < $debut) {
            throw new InvalidArgumentException('La période est invalide (YYYY-MM-DD, fin >= début).');
        }
    }

    private function getOrCreateStatus(string $table, string $libelle): int
    {
        foreach ($this->db->table($table)->select('id, libelle')->get()->getResultArray() as $r) {
            if ($this->normaliserStatut((string) ($r['libelle'] ?? '')) === $this->normaliserStatut($libelle)) {
                return (int) $r['id'];
            }
        }
        $this->db->table($table)->insert(['libelle' => $libelle]);
        return (int) $this->db->insertID();
    }
}
