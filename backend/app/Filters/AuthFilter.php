<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Protège les routes : exige un utilisateur connecté (session).
 * Alias à déclarer dans Config/Filters.php : 'auth' => \App\Filters\AuthFilter::class
 * Usage : ['filter' => 'auth']  ou  ['filter' => 'auth:Manager,RH'] pour limiter par libellé de rôle.
 *
 * Dans un contrôleur, l'utilisateur connecté s'obtient avec : session()->get('utilisateur')
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $utilisateur = session()->get('utilisateur');

        if (empty($utilisateur)) {
            return $this->refuser(401, 'Non connecté.');
        }

        if (! empty($arguments) && ! in_array($utilisateur['role'], $arguments, true)) {
            return $this->refuser(403, 'Accès interdit pour ce rôle.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    private function refuser(int $code, string $message): ResponseInterface
    {
        return service('response')
            ->setStatusCode($code)
            ->setJSON(['status' => $code, 'error' => $code, 'messages' => ['error' => $message]]);
    }
}
