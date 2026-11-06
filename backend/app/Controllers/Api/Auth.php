<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UtilisateurModel;
use CodeIgniter\API\ResponseTrait;

class Auth extends BaseController
{
    use ResponseTrait;

    /** POST /api/auth/login  { "login": "...", "mdp": "..." } */
    public function login()
    {
        $resultat = (new UtilisateurModel())->authentifier($this->request->getJSON(true) ?? []);

        if (! $resultat['ok']) {
            return $resultat['code'] === 401
                ? $this->failUnauthorized($resultat['erreurs'])
                : $this->failValidationErrors($resultat['erreurs']);
        }

        // Nouvel identifiant de session à la connexion (protège contre la fixation de session)
        $session = session();
        $session->regenerate(true);
        $session->set('utilisateur', $resultat['utilisateur']);

        return $this->respond(['utilisateur' => $resultat['utilisateur']]);
    }

    /** GET /api/auth/me */
    public function me()
    {
        return $this->respond(['utilisateur' => session()->get('utilisateur')]);
    }

    /** POST /api/auth/logout */
    public function logout()
    {
        session()->destroy();

        return $this->respond(['message' => 'Déconnecté.']);
    }
}