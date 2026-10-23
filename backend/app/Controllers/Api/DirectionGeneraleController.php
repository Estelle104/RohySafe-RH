<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Service\DirectionGenerale\DirectionService;
use App\Service\Finance\FinanceService;

class DirectionGeneraleController extends BaseController
{
    private DirectionService $directionService;
    private FinanceService $financeService;

    public function __construct()
    {
        $this->directionService = new DirectionService();
        $this->financeService = new FinanceService();
    }

    public function fiche(int $id)
    {
        try {
            return $this->response->setJSON([
                'success' => true,
                'data' => $this->financeService->verifierBudget($id, (int) date('Y')),
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function notifications()
    {
        try {
            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'nombre' => $this->directionService->compterNotifications(),
                    'demandes' => $this->directionService->notifications(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function valider(int $id)
    {
        try {
            $utilisateurId = (int) session()->get('id_utilisateur');

            $this->directionService->valider(
                $id,
                $utilisateurId
            );

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Demande validée par la Direction générale.',
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function refuser(int $id)
    {
        try {
            $utilisateurId = (int) session()->get('id_utilisateur');
            $annee = (int) date('Y');
            $payload = $this->request->getJSON(true) ?? [];
            $motif = (string) ($payload['motif'] ?? $this->request->getPost('motif'));

            $this->directionService->refuser(
                $id,
                $utilisateurId,
                $annee,
                $motif
            );

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Demande refusée. Le motif a été enregistré.',
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
