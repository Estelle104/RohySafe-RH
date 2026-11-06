<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CongeModel;
use Throwable;

class CongeController extends BaseController
{
    /** Retourne les règles d'acquisition configurées. */
    public function configurations()
    {
        try {
            return $this->response->setJSON([
                'configurations' => (new CongeModel())->getConfigurations(),
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de récupérer les configurations de congés.',
            ]);
        }
    }

    /** Met à jour le nombre de jours acquis par mois. */
    public function updateConfiguration(int $id)
    {
        $data = $this->request->getJSON(true) ?? [];
        $nombreJour = $data['nombre_jour'] ?? null;

        if (! is_numeric($nombreJour) || (float) $nombreJour < 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Le nombre de jours doit être un nombre positif ou nul.',
            ]);
        }

        try {
            $model = new CongeModel();
            if ($model->find($id) === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Configuration de congé introuvable.',
                ]);
            }

            $model->updateConfiguration($id, (float) $nombreJour);
            return $this->response->setJSON([
                'message' => 'Configuration de congé mise à jour.',
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de mettre à jour la configuration de congé.',
            ]);
        }
    }
}
