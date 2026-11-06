<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\RelationHierarchiqueModel;
use DomainException;
use Throwable;

class RelationHierarchiqueController extends BaseController
{
    public function employes()
    {
        try {
            return $this->response->setJSON(['employes' => (new RelationHierarchiqueModel())->getEmployes()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les employés.']);
        }
    }

    public function index()
    {
        try {
            $actives = $this->request->getGet('actives') !== 'false';
            return $this->response->setJSON(['relations' => (new RelationHierarchiqueModel())->getRelations($actives)]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les relations hiérarchiques.']);
        }
    }

    public function organigramme()
    {
        try {
            return $this->response->setJSON(['relations' => (new RelationHierarchiqueModel())->getRelations(true)]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer l’organigramme.']);
        }
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        foreach (['id_superieur', 'id_subordonne', 'date_debut'] as $champ) {
            if (! isset($data[$champ]) || $data[$champ] === '') {
                return $this->response->setStatusCode(422)->setJSON(['message' => "Le champ {$champ} est obligatoire."]);
            }
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data['date_debut'])) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'La date de début est invalide.']);
        }
        if (isset($data['date_fin']) && $data['date_fin'] !== null && (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data['date_fin']) || $data['date_fin'] < $data['date_debut'])) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'La date de fin est invalide.']);
        }

        try {
            $id = (new RelationHierarchiqueModel())->createRelation($data);
            return $this->response->setStatusCode(201)->setJSON(['id' => $id, 'message' => 'Relation hiérarchique enregistrée.']);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible d’enregistrer la relation hiérarchique.']);
        }
    }

    public function close(int $id)
    {
        $data = $this->request->getJSON(true) ?? [];
        $dateFin = $data['date_fin'] ?? date('Y-m-d');
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $dateFin)) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'La date de fin est invalide.']);
        }

        try {
            if (! (new RelationHierarchiqueModel())->closeRelation($id, $dateFin)) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Relation hiérarchique introuvable.']);
            }
            return $this->response->setJSON(['message' => 'Relation hiérarchique clôturée.']);
        } catch (DomainException $e) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de clôturer la relation hiérarchique.']);
        }
    }
}
