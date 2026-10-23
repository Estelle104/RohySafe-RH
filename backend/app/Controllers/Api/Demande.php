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
        $data = $this->request->getJSON(true);
        if (! is_array($data)) {
            return $this->failValidationErrors(['body' => 'Le contenu envoyé doit être un JSON valide.']);
        }
        $model = new DemandeModel();
        if (! $model->validate($data)) {
            return $this->failValidationErrors($model->errors());
        }
        try {
            $resultat = $model->creerPourUtilisateur($data, (int) $utilisateur['id']);
        } catch (\Throwable $e) {
            log_message('error', 'Création demande impossible : {message}', ['message' => $e->getMessage()]);
            return $this->failServerError('Une erreur est survenue pendant l’enregistrement de la demande.');
        }
        if (isset($resultat['erreur'])) {
            return $this->failValidationErrors(['demande' => $resultat['erreur']]);
        }

        return $this->respondCreated(['id' => $resultat['id'], 'message' => 'Demande enregistrée.']);
    }
}
