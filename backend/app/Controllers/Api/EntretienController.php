<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\EntretienModel;
use Throwable;

class EntretienController extends BaseController
{
    public function offres()
    {
        try {
            return $this->response->setJSON((new EntretienModel())->getOffresAvecEntretiens());
        } catch (Throwable $e) {
            log_message('error', 'Erreur liste des entretiens: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de récupérer les offres avec des entretiens.',
            ]);
        }
    }

    public function candidats(int $idOffre)
    {
        if ($idOffre <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Identifiant d’offre invalide.']);
        }

        try {
            $model = new EntretienModel();
            $offre = $model->getOffre($idOffre);
            if ($offre === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Offre introuvable.']);
            }

            return $this->response->setJSON([
                'offre' => $offre,
                'candidats' => $model->getCandidatsParOffre($idOffre),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur candidats entretiens: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de récupérer les candidats de cette offre.',
            ]);
        }
    }

    public function detail(int $idCandidature)
    {
        if ($idCandidature <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Identifiant de candidature invalide.']);
        }

        try {
            $detail = (new EntretienModel())->getDetailCandidature($idCandidature);
            if ($detail === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Candidature ou entretien introuvable.',
                ]);
            }

            return $this->response->setJSON($detail);
        } catch (Throwable $e) {
            log_message('error', 'Erreur détail entretien: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de récupérer le détail de l’entretien.',
            ]);
        }
    }

    public function enregistrerEvaluation(int $idCandidature)
    {
        if ($idCandidature <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Identifiant de candidature invalide.']);
        }

        try {
            $data = $this->request->getJSON(true) ?? [];
            $evaluation = (new EntretienModel())->enregistrerEvaluation($idCandidature, $data);

            return $this->response->setJSON([
                'message' => 'Évaluation enregistrée.',
                'evaluation' => $evaluation,
            ]);
        } catch (\DomainException $e) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur enregistrement évaluation: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible d’enregistrer l’évaluation.',
            ]);
        }
    }
}
