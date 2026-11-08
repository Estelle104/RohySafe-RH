<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AnomalieModel;
use Throwable;

class AnomalieController extends BaseController
{
    public function detecter()
    {
        try {
            $nombre = (new AnomalieModel())->detecter(['id_employe' => $this->request->getGet('id_employe'), 'date_debut' => $this->request->getGet('date_debut'), 'date_fin' => $this->request->getGet('date_fin')]);
            return $this->response->setJSON(['nombre_anomalies' => $nombre]);
        } catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de détecter les anomalies.']); }
    }

    public function index()
    {
        try {
            return $this->response->setJSON(['anomalies' => (new AnomalieModel())->getAnomalies([
                'id_employe' => $this->request->getGet('id_employe'), 'date_debut' => $this->request->getGet('date_debut'), 'date_fin' => $this->request->getGet('date_fin'),
            ])]);
        } catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les anomalies.']); }
    }

    public function traiter(int $id)
    {
        $data = $this->request->getJSON(true) ?? [];
        if (! in_array($data['id_statut'] ?? '', ['JUSTIFIEE', 'CORRIGEE'], true)) return $this->response->setStatusCode(422)->setJSON(['message' => 'Le statut de traitement est invalide.']);
        try {
            $session = session()->get('utilisateur');
            if (! (new AnomalieModel())->traiter($id, $data['id_statut'], (string) ($data['commentaire'] ?? ''), $session['id'] ?? null)) return $this->response->setStatusCode(404)->setJSON(['message' => 'Anomalie introuvable.']);
            return $this->response->setJSON(['message' => 'Anomalie traitée.']);
        } catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de traiter l’anomalie.']); }
    }
}
