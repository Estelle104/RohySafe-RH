<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CongeModel;
use Throwable;
use InvalidArgumentException;

class CongeController extends BaseController
{
    /** Retourne les soldes de congés des employés. */
    public function soldes()
    {
        try {
            $filtres = [
                'id_employe' => $this->request->getGet('id_employe'),
                'periode_debut' => $this->request->getGet('periode_debut'),
                'periode_fin' => $this->request->getGet('periode_fin'),
            ];

            return $this->response->setJSON([
                'soldes' => (new CongeModel())->getSoldes($filtres),
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de récupérer les soldes de congés.',
            ]);
        }
    }
public function recalculer()
{
    try {
        $model = new \App\Models\CongeModel();

        $nombre = $model->recalculerSoldes();

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Recalcul terminé.',
            'nombre_soldes' => $nombre
        ]);
    } catch (\Throwable $e) {
        log_message('error', $e->getMessage());

        return $this->response
            ->setStatusCode(500)
            ->setJSON(['message' => $e->getMessage()]);
    }
}
    /** Recalcule les soldes de l'année courante. */
    public function recalculerSoldes()
    {
        try {
            $nombre = (new CongeModel())->recalculerSoldes();
            return $this->response->setJSON([
                'message' => 'Les soldes de congés ont été recalculés.',
                'nombre_soldes' => $nombre,
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de recalculer les soldes de congés.',
            ]);
        }
    }

    /** Retourne la valeur des droits et l'indemnité théorique de départ. */
    public function valeurs()
    {
        try {
            return $this->response->setJSON([
                'valeurs' => (new CongeModel())->getValeurs([
                    'id_employe' => $this->request->getGet('id_employe'),
                    'periode_debut' => $this->request->getGet('periode_debut'),
                    'periode_fin' => $this->request->getGet('periode_fin'),
                ]),
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de calculer la valeur des congés.',
            ]);
        }
    }

    public function validerDemande()
    {
        try {
            return $this->response->setJSON([
                'validation' => (new CongeModel())->validerDemande($this->request->getJSON(true) ?? []),
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de valider la demande de congé.',
            ]);
        }
    }

    public function typesDemande()
    {
        return $this->response->setJSON([
            'types' => (new CongeModel())->getTypesPourDemande(),
        ]);
    }

    public function demandes()
    {
        return $this->response->setJSON([
            'demandes' => (new CongeModel())->listerDemandes(),
        ]);
    }

    public function accepter(int $id)
    {
        try {
            $utilisateur = session()->get('utilisateur') ?? [];
            (new CongeModel())->accepterDemande($id, (int) ($utilisateur['id'] ?? 0));
            return $this->response->setJSON(['message' => 'Demande de congé acceptée.']);
        } catch (InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible d’accepter la demande.']);
        }
    }

    public function employesDemande()
    {
        $utilisateur = session()->get('utilisateur') ?? [];
        return $this->response->setJSON([
            'employes' => (new CongeModel())->getEmployesPourDemande(
                isset($utilisateur['id_departement']) ? (int) $utilisateur['id_departement'] : null
            ),
        ]);
    }

    public function creerDemande()
    {
        try {
            $utilisateur = session()->get('utilisateur') ?? [];
            $resultat = (new CongeModel())->creerDemande(
                $this->request->getJSON(true) ?? [],
                $utilisateur
            );
            return $this->response->setStatusCode(201)->setJSON([
                'message' => 'Demande de congé enregistrée.',
                ...$resultat,
            ]);
        } catch (InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible d’enregistrer la demande de congé.',
            ]);
        }
    }

    /** Retourne les règles d'acquisition configurées. */
    public function configurations()
    {
        try {
            return $this->response->setJSON([
                'configurations' => (new CongeModel())->getConfigurations(),
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de récupérer les configurations de congés.',
            ]);
        }
    }

    /** Met à jour le nombre de jours acquis par mois. */
    public function updateConfiguration(int $id)
    {
        $data = $this->request->getJSON(true) ?? [];
        $nombreJour = $data['nombre_jour'] ?? null;

        if (! is_numeric($nombreJour) || (float) $nombreJour < 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Le nombre de jours doit être un nombre positif ou nul.',
            ]);
        }

        try {
            $model = new CongeModel();
            if ($model->find($id) === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Configuration de congé introuvable.',
                ]);
            }

            $model->updateConfiguration($id, (float) $nombreJour);
            return $this->response->setJSON([
                'message' => 'Configuration de congé mise à jour.',
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de mettre à jour la configuration de congé.',
            ]);
        }
    }
}
