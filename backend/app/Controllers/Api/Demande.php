<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DemandeModel;
use Throwable;

class Demande extends BaseController
{
    public function notifications()
    {
        try {
            return $this->response->setJSON((new DemandeModel())->getNotificationsByUtilisateurActuel(3));
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['message' => 'Impossible de récupérer les notifications.']);
        }
    }
}
