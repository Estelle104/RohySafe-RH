<?php

namespace App\Models;

use CodeIgniter\Model;
use InvalidArgumentException;

class PlanningEDTModel extends Model
{
    protected $table = 'planning_edts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['date_debut', 'date_fin', 'jours_semaine', 'libelle'];

    public function getAll(): array
    {
        return $this->db->table('planning_edts pe')
            ->select([
                'pe.id',
                'pe.date_debut',
                'pe.date_fin',
                'pe.jours_semaine',
                'pe.libelle',
                "COUNT(DISTINCT pee.id_employe) AS nombre_employes",
                "STRING_AGG(DISTINCT CONCAT(c.prenom, ' ', c.nom), ' | ') AS employes",
            ])
            ->join('planning_edt_employes pee', 'pee.id_planning_edt = pe.id', 'left')
            ->join('employes e', 'e.id = pee.id_employe', 'left')
            ->join('candidats c', 'c.id = e.id_candidat', 'left')
            ->groupBy([
                'pe.id',
                'pe.date_debut',
                'pe.date_fin',
                'pe.jours_semaine',
                'pe.libelle',
            ])
            ->orderBy('pe.date_debut', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getById(int $id): ?array
    {
        $row = $this->where('id', $id)->first();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $donnees = $this->normaliserDonnees($data, true);
        $this->validerMetier($donnees, $data);

        $builder = $this->db->table($this->table);
        foreach ($donnees as $champ => $valeur) {
            if ($champ === 'jours_semaine') {
                $builder->set($champ, $this->toArraySqlPostgres($valeur), false);
                continue;
            }

            $builder->set($champ, $valeur);
        }

        $builder->insert();

        return (int) $this->insertID();
    }

    public function updatePlanning(int $id, array $data): bool
    {
        if ($this->find($id) === null) {
            return false;
        }

        $donnees = $this->normaliserDonnees($data, false);
        $this->validerMetier($donnees, $data, $id);
        $builder = $this->db->table($this->table);

        foreach ($donnees as $champ => $valeur) {
            if ($champ === 'jours_semaine') {
                $builder->set($champ, $this->toArraySqlPostgres($valeur), false);
                continue;
            }

            $builder->set($champ, $valeur);
        }

        return $builder->where('id', $id)->update() !== false;
    }

    public function deletePlanning(int $id): bool
    {
        $this->db->table('planning_edt_employes')->where('id_planning_edt', $id)->delete();
        return $this->delete($id) !== false;
    }

    public function getEmployesDisponibles(): array
    {
        return $this->db->table('employes e')
            ->select([
                'e.id',
                "CONCAT(c.prenom, ' ', c.nom) AS nom_complet",
                'c.nom',
                'c.prenom',
                'e.matricule',
            ])
            ->join('candidats c', 'c.id = e.id_candidat', 'left')
            ->orderBy('c.nom', 'ASC')
            ->orderBy('c.prenom', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getEmployesParPlanning(int $idPlanning): array
    {
        return $this->db->table('planning_edt_employes pee')
            ->select([
                'pee.id_planning_edt',
                'e.id AS id_employe',
                "CONCAT(c.prenom, ' ', c.nom) AS nom_complet",
                'c.nom',
                'c.prenom',
            ])
            ->join('employes e', 'e.id = pee.id_employe', 'left')
            ->join('candidats c', 'c.id = e.id_candidat', 'left')
            ->where('pee.id_planning_edt', $idPlanning)
            ->orderBy('c.nom', 'ASC')
            ->orderBy('c.prenom', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function assignerEmployes(int $idPlanning, array $idsEmployes): int
    {
        $idsEmployes = array_values(array_unique(array_filter(array_map('intval', $idsEmployes))));
        if ($idsEmployes === []) {
            return 0;
        }

        $planning = $this->find($idPlanning);
        if ($planning === null) {
            throw new InvalidArgumentException('Le planning est introuvable.');
        }

        $idsExistants = $this->db->table('planning_edt_employes')
            ->select('id_employe')
            ->where('id_planning_edt', $idPlanning)
            ->get()
            ->getResultArray();

        $idsActuels = array_map(static fn ($row) => (int) ($row['id_employe'] ?? 0), $idsExistants);
        $doublons = array_values(array_intersect($idsEmployes, $idsActuels));
        if ($doublons !== []) {
            throw new InvalidArgumentException('Un employé ne peut pas être affecté deux fois dans le même planning.');
        }

        $this->validerConflitsEmployesSurDates($idPlanning, $idsEmployes, $planning);

        $rows = [];
        foreach ($idsEmployes as $idEmploye) {
            $rows[] = [
                'id_planning_edt' => $idPlanning,
                'id_employe' => $idEmploye,
            ];
        }

        $builder = $this->db->table('planning_edt_employes');
        $builder->insertBatch($rows, true);

        return count($rows);
    }

    private function validerConflitsEmployesSurDates(int $idPlanning, array $idsEmployes, array $planning): void
    {
        if ($idsEmployes === []) {
            return;
        }

        $joursActuels = $this->normaliserJoursSemaine($planning['jours_semaine'] ?? []);
        if ($joursActuels === []) {
            return;
        }

        $dateDebut = $planning['date_debut'] ?? null;
        $dateFin = $planning['date_fin'] ?? null;
        if (! $dateDebut || ! $dateFin) {
            return;
        }

        foreach ($idsEmployes as $idEmploye) {
            $conflits = $this->db->table('planning_edt_employes pee')
                ->select([
                    'pe.id',
                    'pe.date_debut',
                    'pe.date_fin',
                    'pe.jours_semaine',
                ])
                ->join('planning_edts pe', 'pe.id = pee.id_planning_edt', 'left')
                ->where('pee.id_employe', $idEmploye)
                ->where('pee.id_planning_edt !=', $idPlanning)
                ->get()
                ->getResultArray();

            foreach ($conflits as $conflit) {
                $autreDateDebut = $conflit['date_debut'] ?? null;
                $autreDateFin = $conflit['date_fin'] ?? null;
                $autresJours = $this->normaliserJoursSemaine($conflit['jours_semaine'] ?? []);

                if (! $autreDateDebut || ! $autreDateFin || $autresJours === []) {
                    continue;
                }

                $datesSeChevauchent = ! (
                    $dateFin < $autreDateDebut ||
                    $autreDateFin < $dateDebut
                );

                $joursCommuns = array_values(array_intersect($joursActuels, $autresJours));
                if ($datesSeChevauchent && $joursCommuns !== []) {
                    throw new InvalidArgumentException('Cet employé est déjà affecté à un autre planning sur une date et un jour identiques.');
                }
            }
        }
    }

    private function normaliserDonnees(array $data, bool $creation): array
    {
        $donnees = [];

        if (array_key_exists('date_debut', $data)) {
            $donnees['date_debut'] = trim((string) $data['date_debut']);
        }

        if (array_key_exists('date_fin', $data)) {
            $donnees['date_fin'] = trim((string) $data['date_fin']);
        }

        if (array_key_exists('libelle', $data)) {
            $donnees['libelle'] = trim((string) $data['libelle']) ?: 'Planning';
        }

        if (array_key_exists('jours_semaine', $data) || array_key_exists('joursSemaine', $data)) {
            $donnees['jours_semaine'] = $this->normaliserJoursSemaine($data['jours_semaine'] ?? $data['joursSemaine'] ?? []);
        }

        if ($creation) {
            if (empty($donnees['date_debut'])) {
                throw new InvalidArgumentException('La date de début est obligatoire.');
            }

            if (empty($donnees['date_fin'])) {
                throw new InvalidArgumentException('La date de fin est obligatoire.');
            }

            if (empty($donnees['jours_semaine'])) {
                throw new InvalidArgumentException('Au moins un jour de semaine doit être sélectionné.');
            }
        }

        if (! empty($donnees['date_debut']) && ! empty($donnees['date_fin']) && $donnees['date_fin'] < $donnees['date_debut']) {
            throw new InvalidArgumentException('La date de fin ne peut pas être avant la date de début.');
        }

        return $donnees;
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

    private function toArraySqlPostgres(array $jours): string
    {
        if ($jours === []) {
            return "'{}'::smallint[]";
        }

        $values = array_map(static fn ($jour) => (int) $jour, $jours);
        return "ARRAY[" . implode(', ', $values) . "]::smallint[]";
    }

    private function validerMetier(array $donnees, array $data = [], ?int $idPlanning = null): void
    {
        if (! empty($donnees['date_debut']) && ! empty($donnees['date_fin'])) {
            $dateDebut = new \DateTimeImmutable($donnees['date_debut']);
            $dateFin = new \DateTimeImmutable($donnees['date_fin']);
            $aujourdhui = new \DateTimeImmutable('today');

            if ($dateFin < $aujourdhui) {
                throw new InvalidArgumentException('Impossible de créer ou modifier un planning dont la date de fin est déjà dépassée.');
            }

            if ($dateDebut < $aujourdhui && $dateFin >= $aujourdhui) {
                // autorisé : le planning est en cours ou vient de commencer mais n'est pas terminé
            }

            if ($dateFin < $dateDebut) {
                throw new InvalidArgumentException('La date de fin ne peut pas être avant la date de début.');
            }

            if ($idPlanning !== null && $dateFin < new \DateTimeImmutable('today')) {
                throw new InvalidArgumentException('Impossible de modifier un planning dont la date de fin est déjà dépassée.');
            }
        }

        if (! empty($donnees['jours_semaine']) && count($donnees['jours_semaine']) > 7) {
            throw new InvalidArgumentException('Un planning ne peut pas contenir plus de 7 jours.');
        }

        $idsEmployes = [];
        if (isset($data['id_employes']) && is_array($data['id_employes'])) {
            $idsEmployes = array_values(array_filter(array_map('intval', $data['id_employes'])));
        }

        if (isset($data['employes']) && is_array($data['employes'])) {
            $idsEmployes = array_merge($idsEmployes, array_values(array_filter(array_map('intval', $data['employes']))));
        }

        if ($idsEmployes !== []) {
            $idsUniques = array_values(array_unique($idsEmployes));
            if (count($idsUniques) !== count($idsEmployes)) {
                throw new InvalidArgumentException('Un employé ne peut pas être affecté deux fois dans le même planning.');
            }
        }
    }
}
