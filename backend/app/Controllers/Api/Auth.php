<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UtilisateurModel;
use CodeIgniter\API\ResponseTrait;

class Auth extends BaseController
{
    use ResponseTrait;

    /**
     * POST /api/auth/login
     * Corps JSON : { "login": "...", "mdp": "..." }
     */
    public function login()
    {
        $data = $this->request->getJSON(true) ?? [];

        $regles = [
            'login' => 'required|max_length[100]',
            'mdp'   => 'required',
        ];

        if (! $this->validateData($data, $regles)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $utilisateur = $this->chercher(trim($data['login']));

        // Même message dans les deux cas : on ne révèle pas si le login existe
        if ($utilisateur === null || ! password_verify($data['mdp'], $utilisateur['mdp'])) {
            return $this->failUnauthorized('Login ou mot de passe incorrect.');
        }

        $info = $this->formater($utilisateur);

        // Nouvel identifiant de session à la connexion (protège contre la fixation de session)
        $session = session();
        $session->regenerate(true);
        $session->set('utilisateur', $info);

        return $this->respond(['utilisateur' => $info]);
    }

    /**
     * GET /api/auth/me
     * Renvoie l'utilisateur de la session, ou null s'il n'est pas connecté.
     * Sert au front pour savoir, au chargement de la page, si la session est encore valide.
     */
    public function me()
    {
        return $this->respond(['utilisateur' => session()->get('utilisateur')]);
    }

    /**
     * POST /api/auth/logout
     */
    public function logout()
    {
        session()->destroy();

        return $this->respond(['message' => 'Déconnecté.']);
    }

    /**
     * Utilise le UtilisateurModel existant avec des jointures (aucun SQL brut).
     */
    private function chercher(string $login): ?array
    {
        return (new UtilisateurModel())
            ->select(
                'utilisateurs.id, utilisateurs.login, utilisateurs.mdp, utilisateurs.id_role, '
                . 'utilisateurs.id_departement, roles.libelle AS role, departements.libelle AS departement'
            )
            ->join('roles', 'roles.id = utilisateurs.id_role')
            ->join('departements', 'departements.id = utilisateurs.id_departement', 'left')
            ->where('utilisateurs.login', $login)
            ->first();
    }

    private function formater(array $u): array
    {
        return [
            'id'             => (int) $u['id'],
            'login'          => $u['login'],
            'id_role'        => (int) $u['id_role'],
            'role'           => $u['role'],
            'id_departement' => $u['id_departement'] !== null ? (int) $u['id_departement'] : null,
            'departement'    => $u['departement'],
        ];
    }
}
