<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\HeureSupplementaireModel;
use InvalidArgumentException;
use Throwable;

class HeureSupplementaireController extends BaseController
{
    public function employes()
    {
        try {
            $utilisateur = session()->get('utilisateur') ?? [];
            $role = $utilisateur['role'] ?? null;
            $idDepartement = isset($utilisateur['id_departement']) ? (int) $utilisateur['id_departement'] : null;

            if ($role !== 'Manager') {
                return $this->response
                    ->setStatusCode(403)
                    ->setJSON(['success' => false, 'message' => 'Seuls les managers peuvent consulter cette liste.']);
            }

            $employes = (new HeureSupplementaireModel())->getEmployesDisponibles($idDepartement);

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $employes,
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de récupérer la liste des employés.']);
        }
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        $utilisateur = session()->get('utilisateur') ?? [];

        if (($utilisateur['role'] ?? null) !== 'Manager') {
            return $this->response
                ->setStatusCode(403)
                ->setJSON(['success' => false, 'message' => 'Seuls les managers peuvent déclarer des heures supplémentaires.']);
        }

        if (isset($utilisateur['id'])) {
            $data['id_utilisateur'] = (int) $utilisateur['id'];
        }

        if (isset($utilisateur['id_departement'])) {
            $data['id_departement_manager'] = (int) $utilisateur['id_departement'];
        }

        try {
            $id = (new HeureSupplementaireModel())->create($data);

            return $this->response
                ->setStatusCode(201)
                ->setJSON([
                    'success' => true,
                    'id' => $id,
                    'message' => 'Demande d’heures supplémentaires enregistrée.',
                ]);
        } catch (InvalidArgumentException $e) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON(['success' => false, 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible d’enregistrer la demande d’heures supplémentaires.']);
        }
    }

    public function index()
    {
        $utilisateur = session()->get('utilisateur') ?? [];
        $role = $utilisateur['role'] ?? null;
        $idDepartement = isset($utilisateur['id_departement']) ? (int) $utilisateur['id_departement'] : null;

        if ($role !== 'Manager' && $role !== 'RH') {
            return $this->response
                ->setStatusCode(403)
                ->setJSON(['success' => false, 'message' => 'Accès interdit.']);
        }

        try {
            $demandes = (new HeureSupplementaireModel())->listerDemandes($role, $idDepartement);

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'data' => $demandes,
                    'nombre' => count($demandes),
                ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de récupérer les demandes.']);
        }
    }

    public function valider(int $id)
    {
        $utilisateur = session()->get('utilisateur') ?? [];

        if (($utilisateur['role'] ?? null) !== 'RH') {
            return $this->response
                ->setStatusCode(403)
                ->setJSON(['success' => false, 'message' => 'Seuls les RH peuvent valider les demandes.']);
        }

        try {
            $idUtilisateur = isset($utilisateur['id']) ? (int) $utilisateur['id'] : 0;
            (new HeureSupplementaireModel())->validerDemande($id, $idUtilisateur);

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success' => true,
                    'message' => 'Demande validée.',
                ]);
        } catch (InvalidArgumentException $e) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON(['success' => false, 'message' => $e->getMessage()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['success' => false, 'message' => 'Impossible de valider la demande.']);
        }
    }
}
