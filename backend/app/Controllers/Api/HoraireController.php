<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\HoraireModel;
use Throwable;

class HoraireController extends BaseController
{
    public function index()
    {
        try {
            $horaires = (new HoraireModel())->getAll();

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $horaires,
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de récupérer les horaires de travail.']);
        }
    }

    public function byPoste(int $idPoste)
    {
        try {
            $horaires = (new HoraireModel())->getParPoste($idPoste);

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $horaires,
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de récupérer les horaires du poste.']);
        }
    }

    public function planningAvenir(int $idPoste)
    {
        $dateDebut = $this->request->getGet('dateDebut');
        $dateFin = $this->request->getGet('dateFin');

        try {
            $planning = (new HoraireModel())->getPlanningAvenirParPoste($idPoste, $dateDebut, $dateFin);

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $planning,
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de récupérer le planning à venir.']);
        }
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];

        try {
            $id = (new HoraireModel())->create($data);

            return $this->response->setStatusCode(201)->setJSON([
                'success' => true,
                'id' => $id,
                'message' => 'Horaire créé avec succès.',
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(422)
                ->setJSON(['success' => false, 'message' => $e->getMessage() ?: 'Les données de l’horaire sont invalides.']);
        }
    }

    public function update(int $id)
    {
        $data = $this->request->getJSON(true) ?? [];

        try {
            $model = new HoraireModel();
            $ok = $model->modifier($id, $data);

            if (! $ok) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON(['success' => false, 'message' => 'Horaire introuvable.']);
            }

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'id' => $id,
                    'message' => 'Horaire mis à jour avec succès.',
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(422)
                ->setJSON(['success' => false, 'message' => $e->getMessage() ?: 'Les données de l’horaire sont invalides.']);
        }
    }

    public function delete(int $id)
    {
        try {
            $ok = (new HoraireModel())->supprimer($id);

            if (! $ok) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON(['success' => false, 'message' => 'Horaire introuvable.']);
            }

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'message' => 'Horaire supprimé avec succès.',
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de supprimer l’horaire.']);
        }
    }
}
