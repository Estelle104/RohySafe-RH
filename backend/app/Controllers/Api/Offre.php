<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\OffreModel;
use Throwable;

class Offre extends BaseController
{
    public function index()
    {
        try {
            $offres = (new OffreModel())->getOffresPubliees();

            return $this->response->setJSON($offres);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['message' => 'Impossible de récupérer les offres.']);
        }
    }
}
