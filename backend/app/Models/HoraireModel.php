<?php

namespace App\Models;

use CodeIgniter\Model;
use InvalidArgumentException;

class HoraireModel extends Model
{
    protected $table         = 'horaire_postes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_poste', 'id_regime', 'libelle', 'heure_debut', 'heure_fin', 'pause_minutes'];

    public function getAll(): array
    {
        return $this->db->table('horaire_postes h')
            ->select([
                'h.id',
                'h.id_poste',
                'h.id_regime',
                'h.libelle AS libelle_horaire',
                'h.heure_debut',
                'h.heure_fin',
                'h.pause_minutes',
                'p.libelle AS poste',
                'rt.libelle AS regime',
                'rt.heures_hebdo',
                'rt.seuil_hs_jour',
                'rt.seuil_hs_semaine',
                'rt.tolerance_retard_max',
            ])
            ->join('postes p', 'p.id = h.id_poste', 'left')
            ->join('regime_travails rt', 'rt.id = h.id_regime', 'left')
            ->orderBy('p.libelle', 'ASC')
            ->orderBy('h.heure_debut', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getById(int $id): ?array
    {
        $horaire = $this->db->table('horaire_postes h')
            ->select([
                'h.id',
                'h.id_poste',
                'h.id_regime',
                'h.libelle AS libelle_horaire',
                'h.heure_debut',
                'h.heure_fin',
                'h.pause_minutes',
                'p.libelle AS poste',
                'rt.libelle AS regime',
                'rt.heures_hebdo',
                'rt.seuil_hs_jour',
                'rt.seuil_hs_semaine',
                'rt.tolerance_retard_max',
            ])
            ->join('postes p', 'p.id = h.id_poste', 'left')
            ->join('regime_travails rt', 'rt.id = h.id_regime', 'left')
            ->where('h.id', $id)
            ->get()
            ->getRowArray();

        return $horaire ?: null;
    }

    public function getParPoste(int $idPoste): array
    {
        return $this->db->table('horaire_postes h')
            ->select([
                'h.id',
                'h.id_poste',
                'h.id_regime',
                'h.libelle AS libelle_horaire',
                'h.heure_debut',
                'h.heure_fin',
                'h.pause_minutes',
                'p.libelle AS poste',
                'rt.libelle AS regime',
                'rt.heures_hebdo',
                'rt.seuil_hs_jour',
                'rt.seuil_hs_semaine',
                'rt.tolerance_retard_max',
            ])
            ->join('postes p', 'p.id = h.id_poste', 'left')
            ->join('regime_travails rt', 'rt.id = h.id_regime', 'left')
            ->where('h.id_poste', $idPoste)
            ->orderBy('h.heure_debut', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getPlanningAvenirParPoste(int $idPoste, ?string $dateDebut = null, ?string $dateFin = null): array
    {
        $dateDebut = $dateDebut ?: date('Y-m-d');
        $dateFin = $dateFin ?: date('Y-m-d', strtotime('+30 days'));

        return $this->db->table('planning_edts pe')
            ->select([
                'pe.id',
                'pe.id_horaire_poste',
                'pe.date_debut',
                'pe.date_fin',
                'pe.jours_semaine',
                'pe.libelle AS planning_libelle',
                'h.id_poste',
                'h.heure_debut',
                'h.heure_fin',
                'h.pause_minutes',
                'p.libelle AS poste',
                'rt.libelle AS regime',
                'rt.heures_hebdo',
            ])
            ->join('horaire_postes h', 'h.id = pe.id_horaire_poste', 'left')
            ->join('postes p', 'p.id = h.id_poste', 'left')
            ->join('regime_travails rt', 'rt.id = h.id_regime', 'left')
            ->where('h.id_poste', $idPoste)
            ->where('pe.date_debut <=', $dateFin)
            ->where('pe.date_fin >=', $dateDebut)
            ->orderBy('pe.date_debut', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function create(array $data): int
    {
        $donnees = $this->normaliserDonnees($data, true);
        $this->insert($donnees);

        return (int) $this->insertID();
    }

    public function modifier(int $id, array $data): bool
    {
        if ($this->find($id) === null) {
            return false;
        }

        $donnees = $this->normaliserDonnees($data, false);

        return $this->update($id, $donnees) !== false;
    }

    public function supprimer(int $id): bool
    {
        return $this->delete($id, true) !== false;
    }

    private function normaliserDonnees(array $data, bool $creation): array
    {
        $donnees = [];

        if (array_key_exists('id_poste', $data)) {
            $donnees['id_poste'] = (int) $data['id_poste'];
        }

        if (array_key_exists('id_regime', $data)) {
            $donnees['id_regime'] = (int) $data['id_regime'];
        }

        if (array_key_exists('libelle', $data)) {
            $donnees['libelle'] = trim((string) $data['libelle']);
        }

        if (array_key_exists('heure_debut', $data)) {
            $donnees['heure_debut'] = trim((string) $data['heure_debut']);
        }

        if (array_key_exists('heure_fin', $data)) {
            $donnees['heure_fin'] = trim((string) $data['heure_fin']);
        }

        if (array_key_exists('pause_minutes', $data)) {
            $donnees['pause_minutes'] = (int) $data['pause_minutes'];
        }

        if ($creation) {
            if (empty($donnees['id_poste'])) {
                throw new InvalidArgumentException('Le poste est obligatoire.');
            }

            if (empty($donnees['id_regime'])) {
                throw new InvalidArgumentException('Le régime est obligatoire.');
            }

            if (empty($donnees['heure_debut'])) {
                throw new InvalidArgumentException('L’heure de début est obligatoire.');
            }

            if (empty($donnees['heure_fin'])) {
                throw new InvalidArgumentException('L’heure de fin est obligatoire.');
            }
        }

        if (! empty($donnees['heure_debut']) && ! empty($donnees['heure_fin']) && $donnees['heure_debut'] === $donnees['heure_fin']) {
            throw new InvalidArgumentException('L’heure de début doit être différente de l’heure de fin.');
        }

        if (isset($donnees['pause_minutes']) && $donnees['pause_minutes'] < 0) {
            throw new InvalidArgumentException('La pause ne peut pas être négative.');
        }

        return $donnees;
    }
}
