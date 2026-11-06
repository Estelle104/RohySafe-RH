<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CandidatModel;
use App\Models\CandidatureModel;
use App\Models\OffreModel;
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

    public function engager(int $id_candidature)
    {
        if ($id_candidature <= 0) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['message' => 'Identifiant invalide.']);
        }

        try {
            $db = \Config\Database::connect();
            $candidatureModel = new CandidatureModel();
            $candidature = $candidatureModel->find($id_candidature);

            if ($candidature === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Candidature introuvable.']);
            }

            $db->transStart();

            $candidatureModel->updateStatutCandidatureEngage($id_candidature);

            $idOffre = (int) $candidature['id_offre'];
            $statutAttente = $db->table('statut_offres')->select('id')->where('libelle', 'En attente')->get()->getRowArray();
            if ($statutAttente !== null) {
                $db->table('offres')->where('id', $idOffre)->update(['id_statut' => (int) $statutAttente['id']]);
            }

            $db->table('notifications_candidats')->insert([
                'id_candidat' => (int) $candidature['id_candidat'],
                'id_candidature' => $id_candidature,
                'id_offre' => $idOffre,
                'type' => 'engage',
                'message' => "Vous êtes engagés(e). Veuillez consulter les détails du contrat pour négocier.",
                'lu' => false,
                'date_creation' => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            if (! $db->transStatus()) {
                throw new \RuntimeException("Échec de l'opération d'engagement.");
            }

            return $this->response->setJSON(['message' => 'Candidat engagé. Offre mise en attente.']);
        } catch (\Throwable $th) {
            log_message('error', 'Erreur engager: ' . $th->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => "Impossible d'engager le candidat."]);
        }
    }

    public function attente(int $id_candidature)
    {
        if ($id_candidature <= 0) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['message' => 'Identifiant invalide.']);
        }

        try {
            $db = \Config\Database::connect();
            $candidatureModel = new CandidatureModel();
            $candidature = $candidatureModel->find($id_candidature);

            if ($candidature === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Candidature introuvable.']);
            }

            $db->transStart();

            $statutAttenteCand = $db->table('statut_candidatures')->select('id')->where('libelle', 'en_attente')->get()->getRowArray();
            if ($statutAttenteCand !== null) {
                $candidatureModel->update($id_candidature, ['id_statut_actuel' => (int) $statutAttenteCand['id']]);
            }

            $idOffre = (int) $candidature['id_offre'];
            $db->table('notifications_candidats')->insert([
                'id_candidat' => (int) $candidature['id_candidat'],
                'id_candidature' => $id_candidature,
                'id_offre' => $idOffre,
                'type' => 'entretien',
                'message' => "Votre candidature est en attente suite à l'entretien. Veuillez vérifier vos notifications.",
                'lu' => false,
                'date_creation' => date('Y-m-d H:i:s'),
            ]);

            $db->transComplete();

            if (! $db->transStatus()) {
                throw new \RuntimeException("Échec de l'opération.");
            }

            return $this->response->setJSON(['message' => 'Candidature mise en attente.']);
        } catch (\Throwable $th) {
            log_message('error', 'Erreur attente: ' . $th->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => "Impossible de mettre en attente le candidat."]);
        }
    }
}
