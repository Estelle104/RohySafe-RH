<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PlanningEDTModel;
use Throwable;

class PlanningEDTController extends BaseController
{
    public function index()
    {
        try {
            $plannings = (new PlanningEDTModel())->getAll();

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $this->normaliser($plannings),
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de récupérer les plannings EDT.']);
        }
    }

    public function show(int $id)
    {
        try {
            $planning = (new PlanningEDTModel())->getById($id);

            if (! $planning) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON(['success' => false, 'message' => 'Planning introuvable.']);
            }

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $this->normaliser([$planning])[0] ?? null,
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de récupérer le planning.']);
        }
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? $this->request->getRawInput() ?? [];

        try {
            $model = new PlanningEDTModel();
            $planningId = $model->create($data);

            $idsEmployes = $data['id_employes'] ?? $data['employes'] ?? [];
            if (! empty($idsEmployes)) {
                $model->assignerEmployes($planningId, $idsEmployes);
            }

            return $this->response
                ->setStatusCode(201)
                ->setJSON([
                    'success' => true,
                    'id' => $planningId,
                    'message' => 'Planning créé avec succès.',
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(422)
                ->setJSON(['success' => false, 'message' => $e->getMessage() ?: 'Les données du planning sont invalides.']);
        }
    }

    public function update(int $id)
    {
        $data = $this->request->getJSON(true) ?? $this->request->getRawInput() ?? [];

        try {
            $model = new PlanningEDTModel();
            $ok = $model->updatePlanning($id, $data);

            if (! $ok) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON(['success' => false, 'message' => 'Planning introuvable.']);
            }

            $idsEmployes = $data['id_employes'] ?? $data['employes'] ?? null;
            if (is_array($idsEmployes)) {
                $model->assignerEmployes($id, $idsEmployes);
            }

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'id' => $id,
                    'message' => 'Planning mis à jour avec succès.',
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(422)
                ->setJSON(['success' => false, 'message' => $e->getMessage() ?: 'Les données du planning sont invalides.']);
        }
    }

    public function delete(int $id)
    {
        try {
            $ok = (new PlanningEDTModel())->deletePlanning($id);

            if (! $ok) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON(['success' => false, 'message' => 'Planning introuvable.']);
            }

            return $this->response
                ->setStatusCode(200)
                ->setJSON(['success' => true, 'message' => 'Planning supprimé avec succès.']);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de supprimer le planning.']);
        }
    }

    public function employesDisponibles()
    {
        try {
            $employes = (new PlanningEDTModel())->getEmployesDisponibles();

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $employes,
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de récupérer les employés.']);
        }
    }

    public function employesParPlanning(int $idPlanning)
    {
        try {
            $employes = (new PlanningEDTModel())->getEmployesParPlanning($idPlanning);

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $employes,
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de récupérer les employés du planning.']);
        }
    }

    public function assignerEmployes(int $idPlanning)
    {
        $data = $this->request->getJSON(true) ?? $this->request->getRawInput() ?? [];
        $idsEmployes = $data['id_employes'] ?? $data['employes'] ?? [];

        try {
            $count = (new PlanningEDTModel())->assignerEmployes($idPlanning, $idsEmployes);

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'count' => $count,
                    'message' => 'Employés affectés au planning.',
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(422)
                ->setJSON(['success' => false, 'message' => 'Impossible d’affecter les employés.']);
        }
    }

    public function retirerEmploye(int $idPlanning, int $idEmploye)
    {
        try {
            $db = db_connect();
            $deleted = $db->table('planning_edt_employes')
                ->where('id_planning_edt', $idPlanning)
                ->where('id_employe', $idEmploye)
                ->delete();

            if (! $deleted) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON(['success' => false, 'message' => 'Affectation introuvable.']);
            }

            return $this->response
                ->setStatusCode(200)
                ->setJSON(['success' => true, 'message' => 'Affectation supprimée.']);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de retirer l’affectation.']);
        }
    }

    private function normaliser(array $plannings): array
    {
        $jours = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        return array_map(function (array $planning) use ($jours) {
            $rawJours = $planning['jours_semaine'] ?? [];

            if (is_string($rawJours)) {
                $rawJours = trim($rawJours);
                if ($rawJours === '' || $rawJours === '{}') {
                    $rawJours = [];
                } else {
                    $rawJours = preg_replace('/^\{|\}$/', '', $rawJours);
                    $rawJours = array_values(array_filter(array_map('trim', explode(',', (string) $rawJours))));
                }
            }

            $joursSemaine = is_array($rawJours) ? $rawJours : [];
            $employes = [];
            if (! empty($planning['employes'])) {
                $employes = array_values(array_filter(array_map('trim', explode('|', (string) $planning['employes']))));
            }

            return [
                'id' => (int) ($planning['id'] ?? 0),
                'libelle' => $planning['libelle'] ?? 'Planning',
                'dateDebut' => $planning['date_debut'] ?? null,
                'dateFin' => $planning['date_fin'] ?? null,
                'joursSemaine' => array_values(array_map(fn ($jour) => $jours[(int) $jour] ?? $jour, $joursSemaine)),
                'nombreEmployes' => (int) ($planning['nombre_employes'] ?? count($employes)),
                'employes' => $employes,
                'periodeLabel' => $this->formatPeriode($planning['date_debut'] ?? null, $planning['date_fin'] ?? null),
            ];
        }, $plannings);
    }

    private function formatPeriode(?string $dateDebut, ?string $dateFin): string
    {
        if (! $dateDebut && ! $dateFin) {
            return 'Période non définie';
        }

        $debut = $dateDebut ? date('d/m/Y', strtotime($dateDebut)) : '?';
        $fin = $dateFin ? date('d/m/Y', strtotime($dateFin)) : '?';

        return $debut . ' - ' . $fin;
    }
}
