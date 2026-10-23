<?php

namespace App\Models;

use CodeIgniter\Model;

class UtilisateurModel extends Model
{
    protected $table         = 'utilisateurs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['login', 'mdp', 'id_role', 'id_departement'];

    private const REGLES_CONNEXION = [
        'login' => 'required|max_length[100]',
        'mdp'   => 'required',
    ];

    private const MESSAGES_CONNEXION = [
        'login' => [
            'required'   => 'Le login est obligatoire.',
            'max_length' => 'Le login est trop long.',
        ],
        'mdp' => [
            'required' => 'Le mot de passe est obligatoire.',
        ],
    ];

    /**
     * Valide les champs et vérifie les identifiants.
     *
     * Succès : ['ok' => true,  'utilisateur' => [...]]
     * Échec  : ['ok' => false, 'code' => 422|401, 'erreurs' => array|string]
     */
    public function authentifier(array $data): array
    {
        $data['login'] = trim((string) ($data['login'] ?? ''));

        $this->setValidationRules(self::REGLES_CONNEXION);
        $this->setValidationMessages(self::MESSAGES_CONNEXION);

        if (! $this->validate($data)) {
            return ['ok' => false, 'code' => 422, 'erreurs' => $this->errors()];
        }

        $utilisateur = $this->trouverParLogin($data['login']);

        // Même message dans les deux cas : on ne révèle pas si le login existe
        if ($utilisateur === null || ! password_verify($data['mdp'], $utilisateur['mdp'])) {
            return ['ok' => false, 'code' => 401, 'erreurs' => 'Login ou mot de passe incorrect.'];
        }

        return ['ok' => true, 'utilisateur' => $this->formater($utilisateur)];
    }

    public function trouverParLogin(string $login): ?array
    {
        return $this->select(
                'utilisateurs.id, utilisateurs.login, utilisateurs.mdp, utilisateurs.id_role, '
                . 'utilisateurs.id_departement, roles.libelle AS role, departements.libelle AS departement'
            )
            ->join('roles', 'roles.id = utilisateurs.id_role')
            ->join('departements', 'departements.id = utilisateurs.id_departement', 'left')
            ->where('utilisateurs.login', $login)
            ->first();
    }

    /** Données à garder en session (sans le mot de passe). */
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