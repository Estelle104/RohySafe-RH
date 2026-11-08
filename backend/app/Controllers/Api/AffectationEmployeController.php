<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AffectationEmployeModel;
use DomainException;
use Throwable;

class AffectationEmployeController extends BaseController
{
    public function options()
    {
        try {
            $model = new AffectationEmployeModel();
            return $this->response->setJSON([
                'employes' => $model->getEmployes(),
                'societes' => $model->getSocietes(),
                'provinces' => $model->getProvinces(),
                'societe_provinces' => $model->getSocieteProvinces(),
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les options d’affectation.']);
        }
    }

    public function index()
    {
        try {
            return $this->response->setJSON(['affectations' => (new AffectationEmployeModel())->getAffectations()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les affectations.']);
        }
    }

    public function employes()
    {
        try {
            return $this->response->setJSON(['employes' => (new AffectationEmployeModel())->getEmployesModuleAffectation()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les employés.']);
        }
    }

    public function historiqueEmploye(int $idEmploye)
    {
        try {
            $model = new AffectationEmployeModel();
            $employe = $model->getEmploye($idEmploye);
            if ($employe === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Employé introuvable.']);
            }
            return $this->response->setJSON([
                'employe' => $employe,
                'affectations' => $model->getAffectationsByEmploye($idEmploye),
            ]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => "Impossible de récupérer l'historique des affectations."]);
        }
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        foreach (['id_employe', 'id_societe', 'id_province'] as $champ) {
            if (! isset($data[$champ]) || $data[$champ] === '') {
                return $this->response->setStatusCode(422)->setJSON(['message' => "Le champ {$champ} est obligatoire."]);
            }
        }

        try {
            $id = (new AffectationEmployeModel())->createAffectation($data);
            return $this->response->setStatusCode(201)->setJSON(['id' => $id, 'message' => 'Affectation enregistrée (affectation précédente clôturée si nécessaire).']);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible d’enregistrer l’affectation.']);
        }
    }
}
