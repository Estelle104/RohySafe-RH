<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DemandeModel;
use Throwable;

class RHController extends BaseController
{
    public function notifications()
    {
        try {
            $utilisateur = session()->get('utilisateur');
            $id = (int) ($utilisateur['id'] ?? 0);
            $id = 2;
            return $this->response->setJSON((new DemandeModel())->getNotificationsByUtilisateurActuel($id));
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['message' => 'Impossible de récupérer les notifications.']);
        }
    }

    public function show(int $id)
    {
        try {
            $demande = (new DemandeModel())->getDetail($id);
            if ($demande === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Demande introuvable.']);
            }

            return $this->response->setJSON($demande);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer la demande.']);
        }
    }

    public function grilleSalariale(int $id)
    {
        try {
            $model = new DemandeModel();
            if ($model->find($id) === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Demande introuvable.']);
            }

            return $this->response->setJSON($model->getGrilleSalariale($id));
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer la grille salariale.']);
        }
    }

    public function proposerSalaire(int $id)
    {
        $data = $this->request->getJSON(true) ?? [];
        $salaire = $data['salaire_propose'] ?? null;
        $remarque = isset($data['remarque']) ? trim((string) $data['remarque']) : null;

        if (! is_numeric($salaire) || (float) $salaire < 0) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Le salaire proposé doit être un nombre positif ou nul.']);
        }

        try {
            $model = new DemandeModel();
            $utilisateur = session()->get('utilisateur');
            if (! $model->validerParRH($id, (int) ($utilisateur['id'] ?? 0), (float) $salaire, $remarque ?: null)) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Demande introuvable.']);
            }

            return $this->response->setJSON(['message' => 'Proposition de salaire enregistrée.']);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible d’enregistrer la proposition.']);
        }
    }

    public function refuser(int $id)
    {
        try {
            if (! (new DemandeModel())->refuser($id)) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Demande introuvable.']);
            }

            return $this->response->setJSON(['message' => 'La demande a été refusée.']);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de refuser la demande.']);
        }
    }
}
