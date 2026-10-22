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

    public function detail(int $idOffre)
    {
        try {
            $offre = (new OffreModel())->getDetail($idOffre);

            if ($offre === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Offre introuvable.',
                ]);
            }

            return $this->response->setJSON($offre);
        } catch (Throwable $e) {
            log_message('error', 'Erreur détail offre: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de récupérer le détail de l’offre.',
            ]);
        }
    }
}
