<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Throwable;

class NotificationCandidat extends BaseController
{
    public function index(int $idCandidat)
    {
        if ($idCandidat <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Identifiant invalide.']);
        }

        try {
            $db = \Config\Database::connect();
            $notifications = $db->table('notifications_candidats')
                ->select('*')
                ->where('id_candidat', $idCandidat)
                ->orderBy('date_creation', 'DESC')
                ->get()
                ->getResultArray();

            return $this->response->setJSON($notifications);
        } catch (Throwable $e) {
            log_message('error', 'Erreur notifications candidat: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les notifications.']);
        }
    }

    public function marquerLu(int $idNotification)
    {
        if ($idNotification <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Identifiant invalide.']);
        }

        try {
            $db = \Config\Database::connect();
            $db->table('notifications_candidats')->where('id', $idNotification)->update(['lu' => 1]);
            return $this->response->setJSON(['message' => 'Notification marquée comme lue.']);
        } catch (Throwable $e) {
            log_message('error', 'Erreur marquer lu: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de mettre à jour la notification.']);
        }
    }
}
