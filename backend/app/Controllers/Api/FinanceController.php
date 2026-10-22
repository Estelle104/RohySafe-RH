<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Service\Finance\FinanceService;

class FinanceController extends BaseController
{
    private FinanceService $financeService;

    public function __construct()
    {
        $this->financeService = new FinanceService();
    }

    public function notifications()
    {
        try {
            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'nombre' => $this->financeService->compterNotifications(),
                    'demandes' => $this->financeService->notifications(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function fiche(int $id)
    {
        try {
            $annee = (int) date('Y');

            $calcul = $this->financeService->verifierBudget(
                $id,
                $annee
            );

            return $this->response->setJSON([
                'success' => true,
                'data' => $calcul,
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function valider(int $id)
    {
        try {
            $utilisateurId = (int) session()->get('id_utilisateur');

            $this->financeService->valider(
                $id,
                (int) date('Y'),
                $utilisateurId
            );

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Demande transmise à la Direction générale.',
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function annuler(int $id)
    {
        try {
            $utilisateurId = (int) session()->get('id_utilisateur');
            $payload = $this->request->getJSON(true) ?? [];
            $motif = (string) ($payload['motif'] ?? $this->request->getPost('motif'));

            $this->financeService->annuler(
                $id,
                $utilisateurId,
                $motif
            );

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Demande annulée.',
            ]);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
