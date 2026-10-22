<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Service\DirectionGenerale\DirectionService;

class DirectionGeneraleController extends BaseController
{
    private DirectionService $directionService;

    public function __construct()
    {
        $this->directionService = new DirectionService();
    }

    /**
     * Valider définitivement une demande.
     */
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

    /**
     * Refuser une demande avec un motif obligatoire.
     */
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
