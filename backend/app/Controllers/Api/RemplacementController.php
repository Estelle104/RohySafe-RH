<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\RemplacementModel;
use InvalidArgumentException;
use Throwable;

class RemplacementController extends BaseController
{
    public function index()
    {
        try {
            return $this->response->setJSON([
                'remplacements' => (new RemplacementModel())->lister([
                    'id_employe' => $this->request->getGet('id_employe'),
                    'periode_debut' => $this->request->getGet('periode_debut'),
                    'periode_fin' => $this->request->getGet('periode_fin'),
                ]),
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de calculer les remplacements.']);
        }
    }

    public function create()
    {
        try {
            $id = (new RemplacementModel())->creer($this->request->getJSON(true) ?? []);
            return $this->response->setStatusCode(201)->setJSON(['id' => $id, 'message' => 'Remplacement enregistré.']);
        } catch (InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible d’enregistrer le remplacement.']);
        }
    }
}
