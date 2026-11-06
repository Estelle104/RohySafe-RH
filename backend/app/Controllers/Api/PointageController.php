<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\PointageModel;
use Throwable;

class PointageController extends BaseController
{
    public function index()
    {
        try {
            return $this->response->setJSON([
                'pointages' => (new PointageModel())->getPointages([
                    'id_employe' => $this->request->getGet('id_employe'),
                    'date_debut' => $this->request->getGet('date_debut'),
                    'date_fin' => $this->request->getGet('date_fin'),
                ]),
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les pointages.']);
        }
    }

    public function parEmploye(int $idEmploye)
    {
        try {
            return $this->response->setJSON([
                'pointages' => (new PointageModel())->getPointages([
                    'id_employe' => $idEmploye,
                    'date_debut' => $this->request->getGet('date_debut'),
                    'date_fin' => $this->request->getGet('date_fin'),
                ]),
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les pointages de cet employé.']);
        }
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        foreach (['id_employe', 'date_heure', 'id_type_pointage'] as $champ) {
            if (! isset($data[$champ]) || $data[$champ] === '') {
                return $this->response->setStatusCode(422)->setJSON(['message' => "Le champ {$champ} est obligatoire."]);
            }
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', (string) $data['date_heure'])) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'La date et l’heure du pointage sont invalides.']);
        }

        try {
            $session = session()->get('utilisateur');
            $data['cree_par'] = $session['id'] ?? null;
            $id = (new PointageModel())->createPointage([
                'id_employe' => (int) $data['id_employe'],
                'date_heure' => $data['date_heure'],
                'id_type_pointage' => (int) $data['id_type_pointage'],
                'cree_par' => $data['cree_par'],
            ]);
            return $this->response->setStatusCode(201)->setJSON(['id' => $id, 'message' => 'Pointage enregistré.']);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible d’enregistrer le pointage.']);
        }
    }
}
