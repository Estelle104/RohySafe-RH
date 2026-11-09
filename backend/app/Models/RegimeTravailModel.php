<?php

namespace App\Models;

use CodeIgniter\Model;
use InvalidArgumentException;

class RegimeTravailModel extends Model
{
    protected $table         = 'regime_travails';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['libelle', 'heures_hebdo', 'seuil_hs_jour', 'seuil_hs_semaine', 'tolerance_retard_max'];

    public function getAll(): array
    {
        return $this->orderBy('heures_hebdo', 'ASC')->findAll();
    }

    public function getById(int $id): ?array
    {
        return $this->find($id);
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

        if (array_key_exists('libelle', $data)) {
            $donnees['libelle'] = trim((string) $data['libelle']);
        }

        if (array_key_exists('heures_hebdo', $data)) {
            $donnees['heures_hebdo'] = (float) $data['heures_hebdo'];
        }

        if (array_key_exists('seuil_hs_jour', $data)) {
            $donnees['seuil_hs_jour'] = (float) $data['seuil_hs_jour'];
        }

        if (array_key_exists('seuil_hs_semaine', $data)) {
            $donnees['seuil_hs_semaine'] = (float) $data['seuil_hs_semaine'];
        }

        if (array_key_exists('tolerance_retard_max', $data)) {
            $donnees['tolerance_retard_max'] = (int) $data['tolerance_retard_max'];
        }

        if ($creation) {
            if (empty($donnees['libelle'])) {
                throw new InvalidArgumentException('Le libellé du régime est obligatoire.');
            }

            if (! isset($donnees['heures_hebdo'])) {
                throw new InvalidArgumentException('Le nombre d’heures hebdomadaires est obligatoire.');
            }
        }

        if (isset($donnees['heures_hebdo']) && $donnees['heures_hebdo'] <= 0) {
            throw new InvalidArgumentException('Les heures hebdomadaires doivent être strictement positives.');
        }

        if (isset($donnees['seuil_hs_jour']) && $donnees['seuil_hs_jour'] < 0) {
            throw new InvalidArgumentException('Le seuil d’heures supplémentaires par jour ne peut pas être négatif.');
        }

        if (isset($donnees['seuil_hs_semaine']) && $donnees['seuil_hs_semaine'] < 0) {
            throw new InvalidArgumentException('Le seuil d’heures supplémentaires par semaine ne peut pas être négatif.');
        }

        if (isset($donnees['tolerance_retard_max']) && $donnees['tolerance_retard_max'] < 0) {
            throw new InvalidArgumentException('La tolérance de retard ne peut pas être négative.');
        }

        return $donnees;
    }
}
