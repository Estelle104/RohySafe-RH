<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\RegimeTravailModel;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;

class RegimeTravailController extends BaseController
{
    protected RegimeTravailModel $model;

    public function __construct()
    {
        $this->model = new RegimeTravailModel();
    }

    public function index(): ResponseInterface
    {
        $regimes = $this->model->getAll();

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'success' => true,
                'data' => $regimes,
            ]);
    }

    public function show($id = null): ResponseInterface
    {
        if ($id === null || ! is_numeric($id)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Identifiant invalide.',
            ]);
        }

        $regime = $this->model->getById((int) $id);

        if ($regime === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Régime introuvable.',
            ]);
        }

        return $this->response->setStatusCode(200)->setJSON([
            'success' => true,
            'data' => $regime,
        ]);
    }

    public function create(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);

        if (empty($payload)) {
            $payload = $this->request->getVar(null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }

        try {
            $id = $this->model->create($payload ?? []);

            return $this->response->setStatusCode(201)->setJSON([
                'success' => true,
                'message' => 'Régime ajouté avec succès.',
                'data' => ['id' => $id],
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function update($id = null): ResponseInterface
    {
        if ($id === null || ! is_numeric($id)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Identifiant invalide.',
            ]);
        }

        $payload = $this->request->getJSON(true);

        if (empty($payload)) {
            $payload = $this->request->getVar(null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }

        try {
            $updated = $this->model->modifier((int) $id, $payload ?? []);

            if (! $updated) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Régime introuvable.',
                ]);
            }

            return $this->response->setStatusCode(200)->setJSON([
                'success' => true,
                'message' => 'Régime modifié avec succès.',
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function delete($id = null): ResponseInterface
    {
        if ($id === null || ! is_numeric($id)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Identifiant invalide.',
            ]);
        }

        $deleted = $this->model->supprimer((int) $id);

        if (! $deleted) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Régime introuvable.',
            ]);
        }

        return $this->response->setStatusCode(200)->setJSON([
            'success' => true,
            'message' => 'Régime supprimé avec succès.',
        ]);
    }
}
