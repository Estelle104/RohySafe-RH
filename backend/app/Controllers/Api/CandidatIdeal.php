<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CandidatModel;
use App\Models\CandidatureModel;
use App\Models\PropositionEmbaucheModel;
use Throwable;

class CandidatIdeal extends BaseController
{
    public function byDemande(int $idDemande)
    {
        return $this->findCandidates(
            fn (): array => (new CandidatModel())->getCandidatsIdeauxByDemande($idDemande),
            $idDemande
        );
    }

    public function byOffre(int $idOffre)
    {
        return $this->findCandidates(
            fn (): array => (new CandidatModel())->getCandidatsIdeauxByOffre($idOffre),
            $idOffre
        );
    }

    public function cv(int $idCandidat)
    {
        if ($idCandidat <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'message' => 'Identifiant de candidat invalide.',
            ]);
        }

        try {
            $idOffre = (int) ($this->request->getGet('offre') ?? 0);
            $cv = (new CandidatModel())->getCv($idCandidat, $idOffre > 0 ? $idOffre : null);

            if ($cv === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Candidat introuvable.',
                ]);
            }

            return $this->response->setJSON(['cv' => $cv]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur CV candidat: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de récupérer le CV du candidat.',
            ]);
        }
    }

    public function createProposition(int $idCandidature)
    {
        if ($idCandidature <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'message' => 'Identifiant de candidature invalide.',
            ]);
        }

        try {
            $model = new PropositionEmbaucheModel();
            $candidature = (new CandidatureModel())->find($idCandidature);

            if ($candidature === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Candidature introuvable.',
                ]);
            }

            $existing = $model->where('id_candidature', $idCandidature)->first();
            if ($existing !== null) {
                return $this->response->setStatusCode(409)->setJSON([
                    'message' => 'Une proposition existe déjà pour cette candidature.',
                    'proposition' => $existing,
                ]);
            }

            $statusId = $model->getStatutId('en_attente');
            if ($statusId === null) {
                return $this->response->setStatusCode(500)->setJSON([
                    'message' => 'Le statut de proposition en_attente est absent.',
                ]);
            }

            $id = $model->insert([
                'id_candidature' => $idCandidature,
                'id_statut_actuel' => $statusId,
            ], true);

            return $this->response
                ->setStatusCode(201)
                ->setJSON(['id' => $id, 'message' => 'Proposition créée.']);
        } catch (Throwable $e) {
            log_message('error', 'Erreur création proposition: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de créer la proposition.',
            ]);
        }
    }
    private function findCandidates(callable $finder, int $id)
    {
        if ($id <= 0) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['message' => 'Identifiant invalide.']);
        }

        try {
            return $this->response->setJSON($finder());
        } catch (Throwable $e) {
            log_message('error', 'Erreur candidats idéaux: ' . $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['message' => 'Impossible de récupérer les candidats idéaux.']);
        }
    }
}
