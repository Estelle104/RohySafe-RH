<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CandidatModel;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Throwable;

class AuthCandidat extends BaseController
{

    public function inscrire()
    {
        $payload = $this->request->getJSON(true);
        if (!is_array($payload)) {
            $payload = $this->request->getPost();
        }

        $nom = trim((string) ($payload['nom'] ?? ''));
        $prenom = trim((string) ($payload['prenom'] ?? ''));
        $email = mb_strtolower(trim((string) ($payload['email'] ?? '')));
        $telephone = trim((string) ($payload['telephone'] ?? ''));
        $ville = trim((string) ($payload['ville'] ?? ''));
        $identifiant = trim((string) ($payload['identifiant'] ?? $payload['login'] ?? ''));
        $motDePasse = (string) (
            $payload['mot_de_passe']
            ?? $payload['mdp']
            ?? $payload['password']
            ?? ''
        );
        $confirmation = (string) (
            $payload['confirmation']
            ?? $payload['confirmation_mot_de_passe']
            ?? ''
        );

        $erreur = null;

        if ($nom === '' || $prenom === '') {
            $erreur = 'Le nom et le prénom sont obligatoires.';
        } elseif ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $erreur = 'L’adresse email est invalide.';
        } elseif ($identifiant === '' || !preg_match('/^[A-Za-z0-9._-]{3,100}$/', $identifiant)) {
            $erreur = 'L’identifiant doit contenir entre 3 et 100 caractères, sans espace.';
        } elseif (strlen($motDePasse) < 8) {
            $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($motDePasse !== $confirmation) {
            $erreur = 'Les deux mots de passe ne correspondent pas.';
        }

        if ($erreur !== null) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $erreur]);
        }

        try {
            $model = new CandidatModel();

            if ((new CandidatModel())->where('email', $email)->first() !== null) {
                return $this->response->setStatusCode(409)->setJSON([
                    'message' => 'Un compte existe déjà avec cette adresse email.',
                ]);
            }

            if ((new CandidatModel())->where('login', $identifiant)->first() !== null) {
                return $this->response->setStatusCode(409)->setJSON([
                    'message' => 'Cet identifiant est déjà utilisé.',
                ]);
            }

            $idCandidat = (int) $model->insert([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone === '' ? null : $telephone,
                'ville' => $ville === '' ? null : $ville,
                'login' => $identifiant,
                'mdp' => password_hash($motDePasse, PASSWORD_DEFAULT),
            ], true);

            $candidat = $model->find($idCandidat);
            if ($candidat === null) {
                return $this->response->setStatusCode(500)->setJSON([
                    'message' => 'Le compte a été créé mais impossible de le recharger.',
                ]);
            }

            unset($candidat['mdp']);

            return $this->response->setStatusCode(201)->setJSON([
                'message' => 'Compte candidat créé.',
                'candidat' => $candidat,
            ]);
        } catch (DatabaseException $e) {
            log_message('error', 'Erreur inscription candidat: ' . $e->getMessage());

            $message = mb_strtolower($e->getMessage());
            $estDoublon = str_contains($message, 'duplicate')
                || str_contains($message, 'unique')
                || str_contains($message, 'déjà');

            return $this->response->setStatusCode($estDoublon ? 409 : 500)->setJSON([
                'message' => $estDoublon
                    ? 'Cet email ou cet identifiant est déjà utilisé.'
                    : 'Impossible de créer le compte pour le moment.',
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur inscription candidat: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de créer le compte pour le moment.',
            ]);
        }
    }

    public function login()
    {
        $payload = $this->request->getJSON(true);
        if (!is_array($payload)) {
            $payload = $this->request->getPost();
        }

        $identifiant = trim((string) (
            $payload['identifiant']
            ?? $payload['login']
            ?? $payload['email']
            ?? ''
        ));
        $motDePasse = (string) (
            $payload['mot_de_passe']
            ?? $payload['mdp']
            ?? $payload['password']
            ?? ''
        );

        if ($identifiant === '' || $motDePasse === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'L’identifiant et le mot de passe sont obligatoires.',
            ]);
        }

        try {
            $candidat = (new CandidatModel())->findByIdentifiant($identifiant);

            if ($candidat === null || !password_verify($motDePasse, (string) $candidat['mdp'])) {
                return $this->response->setStatusCode(401)->setJSON([
                    'message' => 'Identifiant ou mot de passe incorrect.',
                ]);
            }

            unset($candidat['mdp']);

            return $this->response->setJSON([
                'message' => 'Connexion réussie.',
                'candidat' => $candidat,
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur connexion candidat: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de se connecter pour le moment.',
            ]);
        }
    }
}
