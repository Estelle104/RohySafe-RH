<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\TempsTravailModel;
use Throwable;

class TempsTravailController extends BaseController
{
    public function recalculer()
    {
        try {
            $filtres = ['id_employe' => $this->request->getGet('id_employe'), 'date_debut' => $this->request->getGet('date_debut'), 'date_fin' => $this->request->getGet('date_fin')];
            return $this->response->setJSON(['message' => 'Temps de travail recalculé.', 'nombre_journees' => (new TempsTravailModel())->recalculer($filtres)]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de calculer le temps de travail.']);
        }
    }

    public function index()
    {
        try {
            return $this->response->setJSON(['temps_travail' => (new TempsTravailModel())->getTempsTravail([
                'id_employe' => $this->request->getGet('id_employe'),
                'date_debut' => $this->request->getGet('date_debut'),
                'date_fin' => $this->request->getGet('date_fin'),
            ])]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer le temps travaillé.']);
        }
    }
}
