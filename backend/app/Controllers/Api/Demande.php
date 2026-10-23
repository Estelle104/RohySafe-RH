<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DemandeModel;
use CodeIgniter\API\ResponseTrait;

class Demande extends BaseController
{
    use ResponseTrait;

    public function options()
    {
        return $this->respond((new DemandeModel())->getOptions());
    }

    public function create()
    {
        $utilisateur = session()->get('utilisateur');
        $data = $this->request->getJSON(true) ?? [];
        $model = new DemandeModel();
        if (! $model->validate($data)) {
            return $this->failValidationErrors($model->errors());
        }
        $resultat = $model->creerPourUtilisateur($data, (int) $utilisateur['id']);
        if (isset($resultat['erreur'])) {
            return $this->failValidationErrors($resultat['erreur']);
        }

        return $this->respondCreated(['id' => $resultat['id'], 'message' => 'Demande enregistrée.']);
    }
}
