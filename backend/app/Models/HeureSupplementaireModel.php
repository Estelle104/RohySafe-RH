<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

class HeureSupplementaireModel extends Model
{
    protected $table         = 'heure_supplementaires';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_employe', 'heure_debut', 'heure_fin', 'date_debut', 'date_fin', 'id_planning_edt_employes'];

    public function getEmployesDisponibles(?int $idDepartement = null): array
    {
        $builder = $this->db->table('employes e')
            ->select("e.id, e.matricule, c.nom, c.prenom, CONCAT(c.prenom, ' ', c.nom) AS nom_complet, pd.id_departement AS id_departement, d.libelle AS departement")
            ->join('candidats c', 'c.id = e.id_candidat')
            ->join('poste_departement pd', 'pd.id = e.id_poste_departement', 'left')
            ->join('departements d', 'd.id = pd.id_departement', 'left');

        if ($idDepartement !== null) {
            $builder->where('pd.id_departement', $idDepartement);
        }

        return $builder
            ->orderBy('c.nom', 'ASC')
            ->orderBy('c.prenom', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function create(array $data): int
    {
        $donnees = $this->normaliserDonnees($data);

        $this->db->transStart();

        $idPlanningEmploye = $this->traiterPlanningAssocie($data, (int) $donnees['id_employe']);
        if ($idPlanningEmploye !== null) {
            $donnees['id_planning_edt_employes'] = $idPlanningEmploye;
        }

        $this->validerConflitsMetier($donnees);

        $id = (int) $this->insert($donnees, true);

        $idUtilisateur = isset($data['id_utilisateur']) ? (int) $data['id_utilisateur'] : 0;
        $idStatut = $this->trouverIdStatut('En attente');

        $this->db->table('histo_demande_heure_sups')->insert([
            'id_statut' => $idStatut,
            'id_utilisateur' => $idUtilisateur,
            'id_heure_supplementaire' => $id,
            'date' => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new RuntimeException('Impossible d’enregistrer la demande d’heures supplémentaires.');
        }

        return $id;
    }

    private function traiterPlanningAssocie(array $data, int $idEmploye): ?int
    {
        if (! empty($data['id_planning_edt_employes'])) {
            return (int) $data['id_planning_edt_employes'];
        }

        if (! empty($data['id_planning_edt'])) {
            return (int) $data['id_planning_edt'];
        }

        $planning = $data['planning'] ?? $data['planning_edt'] ?? null;
        if ($planning === null) {
            return null;
        }

        if (! is_array($planning)) {
            throw new InvalidArgumentException('Le planning associé est invalide.');
        }

        $dateDebut = trim((string) ($planning['date_debut'] ?? $planning['dateDebut'] ?? ''));
        $dateFin = trim((string) ($planning['date_fin'] ?? $planning['dateFin'] ?? ''));
        $jours = $planning['jours_semaine'] ?? $planning['joursSemaine'] ?? [];
        $libelle = trim((string) ($planning['libelle'] ?? 'Planning HS - employé'));

        if ($dateDebut === '' || $dateFin === '') {
            throw new InvalidArgumentException('La date de début et la date de fin du planning sont obligatoires.');
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin)) {
            throw new InvalidArgumentException('Les dates du planning doivent être au format YYYY-MM-DD.');
        }

        if ($dateFin < $dateDebut) {
            throw new InvalidArgumentException('La date de fin du planning doit être postérieure ou égale à la date de début.');
        }

        $joursNormales = $this->normaliserJoursSemaine($jours);
        if ($joursNormales === []) {
            $joursNormales = $this->infererJoursSemaineDepuisDates($dateDebut, $dateFin);
        }

        if ($joursNormales === []) {
            throw new InvalidArgumentException('Au moins un jour de semaine doit être sélectionné pour le planning associé.');
        }

        $builderPlanning = $this->db->table('planning_edts');
        $builderPlanning
            ->set('date_debut', $dateDebut)
            ->set('date_fin', $dateFin)
            ->set('jours_semaine', $this->toArraySqlPostgres($joursNormales), false)
            ->set('libelle', $libelle !== '' ? $libelle : 'Planning HS - employé');

        $insertPlanning = $builderPlanning->insert();
        if ($insertPlanning === false) {
            throw new RuntimeException('Impossible de créer le planning associé à la demande d’heures supplémentaires.');
        }

        $idPlanning = (int) $this->db->insertID();

        $builderAffectation = $this->db->table('planning_edt_employes');
        $insertAffectation = $builderAffectation
            ->set('id_planning_edt', $idPlanning)
            ->set('id_employe', $idEmploye)
            ->insert();

        if ($insertAffectation === false) {
            throw new RuntimeException('Impossible d’affecter l’employé au planning associé.');
        }

        return (int) $this->db->insertID();
    }

    private function normaliserDonnees(array $data): array
    {
        $idEmploye = isset($data['id_employe']) ? (int) $data['id_employe'] : 0;
        $idDepartementManager = isset($data['id_departement_manager']) ? (int) $data['id_departement_manager'] : null;
        $dateDebut = trim((string) ($data['date_debut'] ?? ''));
        $dateFin = trim((string) ($data['date_fin'] ?? ''));
        $heureDebut = trim((string) ($data['heure_debut'] ?? ''));
        $heureFin = trim((string) ($data['heure_fin'] ?? ''));

        if ($idEmploye <= 0) {
            throw new InvalidArgumentException('L’employé est obligatoire.');
        }

        $employe = $this->db->table('employes e')
            ->select('e.id, e.id_poste_departement, pd.id_departement AS id_departement')
            ->join('poste_departement pd', 'pd.id = e.id_poste_departement', 'left')
            ->where('e.id', $idEmploye)
            ->get()->getRowArray();

        if ($employe === null) {
            throw new InvalidArgumentException('L’employé sélectionné est introuvable.');
        }

        if ($idDepartementManager !== null && (int) ($employe['id_departement'] ?? 0) !== $idDepartementManager) {
            throw new InvalidArgumentException('Vous ne pouvez déclarer des heures supplémentaires que pour les employés de votre département.');
        }

        foreach (['date_debut' => $dateDebut, 'date_fin' => $dateFin, 'heure_debut' => $heureDebut, 'heure_fin' => $heureFin] as $label => $valeur) {
            if ($valeur === '') {
                throw new InvalidArgumentException("Le champ {$label} est obligatoire.");
            }
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin)) {
            throw new InvalidArgumentException('Les dates doivent être au format YYYY-MM-DD.');
        }

        if (! preg_match('/^\d{2}:\d{2}$/', $heureDebut) || ! preg_match('/^\d{2}:\d{2}$/', $heureFin)) {
            throw new InvalidArgumentException('Les heures doivent être au format HH:MM.');
        }

        if ($dateFin < $dateDebut) {
            throw new InvalidArgumentException('La date de fin doit être postérieure ou égale à la date de début.');
        }

        $dateTimeDebut = $dateDebut . ' ' . $heureDebut . ':00';
        $dateTimeFin = $dateFin . ' ' . $heureFin . ':00';

        if (strtotime($dateTimeFin) <= strtotime($dateTimeDebut)) {
            throw new InvalidArgumentException('L’heure de fin doit être strictement supérieure à l’heure de début.');
        }

        return [
            'id_employe' => $idEmploye,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'heure_debut' => $dateTimeDebut,
            'heure_fin' => $dateTimeFin,
        ];
    }

    private function validerConflitsMetier(array $donnees): void
    {
        $idEmploye = (int) ($donnees['id_employe'] ?? 0);
        $dateDebut = (string) ($donnees['date_debut'] ?? '');
        $dateFin = (string) ($donnees['date_fin'] ?? '');
        $heureDebut = (string) ($donnees['heure_debut'] ?? '');
        $heureFin = (string) ($donnees['heure_fin'] ?? '');

        if ($idEmploye <= 0 || $dateDebut === '' || $dateFin === '' || $heureDebut === '' || $heureFin === '') {
            return;
        }

        $dateDebutTs = $this->toDateTimeFromValeur($dateDebut, $heureDebut);
        $dateFinTs = $this->toDateTimeFromValeur($dateFin, $heureFin);

        $this->validerConflitsAvecHeuresSupplementaires($idEmploye, $dateDebutTs, $dateFinTs);
        $this->validerConflitsAvecPlanningEmploye($idEmploye, $dateDebutTs, $dateFinTs, $donnees['id_planning_edt_employes'] ?? null);
        $this->validerConflitsAvecJourneesTravail($idEmploye, $dateDebut, $dateFin);
    }

    private function toDateTimeFromValeur(string $date, string $heure): DateTimeImmutable
    {
        $heureNorm = trim($heure);

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $heureNorm)) {
            return new DateTimeImmutable($heureNorm);
        }

        if (preg_match('/^\d{2}:\d{2}$/', $heureNorm)) {
            return new DateTimeImmutable($date . ' ' . $heureNorm . ':00');
        }

        return new DateTimeImmutable($date . ' ' . substr($heureNorm, 0, 5) . ':00');
    }

    private function toArraySqlPostgres(array $jours): string
    {
        if ($jours === []) {
            return "'{}'::smallint[]";
        }

        $values = array_map(static fn ($jour) => (int) $jour, $jours);

        return 'ARRAY[' . implode(', ', $values) . ']::smallint[]';
    }

    private function validerConflitsAvecHeuresSupplementaires(int $idEmploye, DateTimeImmutable $dateDebut, DateTimeImmutable $dateFin): void
    {
        $rows = $this->db->table('heure_supplementaires hs')
            ->select('hs.id, hs.date_debut, hs.date_fin, hs.heure_debut, hs.heure_fin')
            ->where('hs.id_employe', $idEmploye)
            ->where('hs.heure_fin >', $dateDebut->format('Y-m-d H:i:s'))
            ->where('hs.heure_debut <', $dateFin->format('Y-m-d H:i:s'))
            ->get()
            ->getResultArray();

        if ($rows !== []) {
            throw new InvalidArgumentException('Un chevauchement existe déjà sur une demande d’heures supplémentaires pour cet employé.');
        }
    }

    private function validerConflitsAvecPlanningEmploye(int $idEmploye, DateTimeImmutable $dateDebut, DateTimeImmutable $dateFin, ?int $idPlanningEmploye = null): void
    {
        $rows = $this->db->table('planning_edt_employes pee')
            ->select(['pee.id', 'pe.date_debut AS planning_date_debut', 'pe.date_fin AS planning_date_fin', 'pe.jours_semaine'])
            ->join('planning_edts pe', 'pe.id = pee.id_planning_edt', 'left')
            ->where('pee.id_employe', $idEmploye)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            if ($idPlanningEmploye !== null && (int) ($row['id'] ?? 0) === $idPlanningEmploye) {
                continue;
            }

            $planningDateDebut = $row['planning_date_debut'] ?? null;
            $planningDateFin = $row['planning_date_fin'] ?? null;
            $jours = $this->normaliserJoursSemaine($row['jours_semaine'] ?? []);

            if ($planningDateDebut === null || $planningDateFin === null || $jours === []) {
                continue;
            }

            $dateDebutPlanning = new DateTimeImmutable($planningDateDebut);
            $dateFinPlanning = new DateTimeImmutable($planningDateFin);

            if ($dateFin < $dateDebutPlanning || $dateDebut > $dateFinPlanning) {
                continue;
            }

            $jourDebut = (int) $dateDebut->format('N');
            $jourFin = (int) $dateFin->format('N');

            $joursDemande = [];
            for ($jour = $jourDebut; $jour <= $jourFin; $jour++) {
                $joursDemande[] = $jour;
            }

            $joursCommuns = array_values(array_intersect($jours, $joursDemande));
            if ($joursCommuns !== []) {
                throw new InvalidArgumentException('L’employé est déjà prévu sur ces dates et jours de semaine.');
            }
        }
    }

    private function validerConflitsAvecJourneesTravail(int $idEmploye, string $dateDebut, string $dateFin): void
    {
        $rows = $this->db->table('journee_travails jt')
            ->select('jt.date_jour, jt.minutes_prevues')
            ->where('jt.id_employe', $idEmploye)
            ->where('jt.date_jour >=', $dateDebut)
            ->where('jt.date_jour <=', $dateFin)
            ->where('jt.minutes_prevues >', 0)
            ->get()
            ->getResultArray();

        if ($rows !== []) {
            throw new InvalidArgumentException('L’employé est déjà prévu pour travailler pendant cette période.');
        }
    }

    private function normaliserJoursSemaine($valeur): array
    {
        $brut = is_array($valeur) ? $valeur : (is_string($valeur) ? explode(',', $valeur) : []);
        $jours = array_map('trim', array_map('strval', $brut));
        $jours = array_values(array_filter($jours, fn ($jour) => $jour !== ''));

        if ($jours === []) {
            return [];
        }

        $numeriques = [];
        foreach ($jours as $jour) {
            $nombre = (int) $jour;
            if ($nombre >= 1 && $nombre <= 7) {
                $numeriques[] = $nombre;
            }
        }

        return array_values(array_unique($numeriques));
    }

    private function infererJoursSemaineDepuisDates(string $dateDebut, string $dateFin): array
    {
        $debut = new DateTimeImmutable($dateDebut);
        $fin = new DateTimeImmutable($dateFin);
        $jours = [];

        for ($date = $debut; $date <= $fin; $date = $date->modify('+1 day')) {
            $jours[] = (int) $date->format('N');
        }

        return array_values(array_unique($jours));
    }

    private function trouverIdStatut(string $libelle): int
    {
        $statut = $this->db->table('statut_heure_sups')->select('id')->where('libelle', $libelle)->get()->getRowArray();

        if ($statut !== null) {
            return (int) $statut['id'];
        }

        $this->db->table('statut_heure_sups')->insert(['libelle' => $libelle]);

        return (int) $this->db->insertID();
    }

    public function listerDemandes(?string $role = null, ?int $idDepartement = null): array
    {
        $latestSubquery = '(SELECT h1.id_heure_supplementaire, h1.id_statut, h1.id_utilisateur, h1.date
            FROM histo_demande_heure_sups h1
            INNER JOIN (
                SELECT id_heure_supplementaire, MAX(date) AS max_date
                FROM histo_demande_heure_sups
                GROUP BY id_heure_supplementaire
            ) h2
            ON h1.id_heure_supplementaire = h2.id_heure_supplementaire
            AND h1.date = h2.max_date)';

        $builder = $this->db->table('heure_supplementaires hs')
            ->select([
                'hs.id',
                'hs.id_employe',
                'hs.date_debut',
                'hs.date_fin',
                'hs.heure_debut',
                'hs.heure_fin',
                'c.nom',
                'c.prenom',
                "CONCAT(c.prenom, ' ', c.nom) AS nom_complet",
                'pd.id_departement AS id_departement',
                'd.libelle AS departement',
                'statut.libelle AS statut_libelle',
                'latest.date AS date_statut',
                'latest.id_utilisateur AS id_utilisateur',
            ])
            ->join('employes e', 'e.id = hs.id_employe')
            ->join('candidats c', 'c.id = e.id_candidat')
            ->join('poste_departement pd', 'pd.id = e.id_poste_departement', 'left')
            ->join('departements d', 'd.id = pd.id_departement', 'left')
            ->join($latestSubquery . ' latest', 'latest.id_heure_supplementaire = hs.id', 'left')
            ->join('statut_heure_sups statut', 'statut.id = latest.id_statut', 'left');

        if ($role === 'Manager' && $idDepartement !== null) {
            $builder->where('pd.id_departement', $idDepartement);
        }

        return $builder
            ->orderBy('hs.date_debut', 'DESC')
            ->orderBy('hs.date_fin', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function validerDemande(int $idHeureSupplementaire, int $idUtilisateur): void
    {
        $demande = $this->db->table('heure_supplementaires hs')
            ->select('hs.id, statut.libelle AS statut_libelle')
            ->join($this->db->prefixTable('histo_demande_heure_sups') . ' h', 'h.id_heure_supplementaire = hs.id', 'left')
            ->join('statut_heure_sups statut', 'statut.id = h.id_statut', 'left')
            ->where('hs.id', $idHeureSupplementaire)
            ->orderBy('h.date', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if ($demande === null) {
            throw new InvalidArgumentException('La demande d’heures supplémentaires est introuvable.');
        }

        if (($demande['statut_libelle'] ?? '') !== 'En attente') {
            throw new InvalidArgumentException('Seules les demandes en attente peuvent être validées.');
        }

        $idStatut = $this->trouverIdStatut('Validée');

        $this->db->table('histo_demande_heure_sups')->insert([
            'id_statut' => $idStatut,
            'id_utilisateur' => $idUtilisateur,
            'id_heure_supplementaire' => $idHeureSupplementaire,
            'date' => date('Y-m-d H:i:s'),
        ]);

        $this->creerPlanningApresValidation($idHeureSupplementaire);
    }

    private function creerPlanningApresValidation(int $idHeureSupplementaire): void
    {
        $demande = $this->db->table('heure_supplementaires')
            ->select('id, id_employe, date_debut, date_fin, heure_debut, heure_fin, id_planning_edt_employes')
            ->where('id', $idHeureSupplementaire)
            ->get()
            ->getRowArray();

        if ($demande === null) {
            throw new RuntimeException('La demande validée est introuvable.');
        }
        if (! empty($demande['id_planning_edt_employes'])) {
            return;
        }

        $jours = [];
        $debut = new DateTimeImmutable((string) $demande['date_debut']);
        $fin = new DateTimeImmutable((string) $demande['date_fin']);
        for ($date = $debut; $date <= $fin; $date = $date->modify('+1 day')) {
            $jours[] = (int) $date->format('N');
        }
        $jours = array_values(array_unique($jours));

        $planning = $this->db->table('planning_edts');
        $planning
            ->set('date_debut', $demande['date_debut'])
            ->set('date_fin', $demande['date_fin'])
            ->set('jours_semaine', 'ARRAY[' . implode(',', $jours) . ']::smallint[]', false)
            ->set(
                'libelle',
                'HS remplacement ' . substr((string) $demande['heure_debut'], 11, 5)
                . '-' . substr((string) $demande['heure_fin'], 11, 5)
            )
            ->insert();

        $idPlanning = (int) $this->db->insertID();
        if ($idPlanning <= 0) {
            throw new RuntimeException('Impossible de créer l’EDT du remplacement.');
        }

        $this->db->table('planning_edt_employes')->insert([
            'id_planning_edt' => $idPlanning,
            'id_employe' => (int) $demande['id_employe'],
        ]);
        $idPlanningEmploye = (int) $this->db->insertID();

        if ($idPlanningEmploye <= 0) {
            throw new RuntimeException('Impossible d’affecter l’EDT au remplaçant.');
        }

        $this->db->table('heure_supplementaires')
            ->where('id', $idHeureSupplementaire)
            ->update(['id_planning_edt_employes' => $idPlanningEmploye]);
    }
}
