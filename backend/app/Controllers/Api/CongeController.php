<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CongeModel;
use Throwable;
use InvalidArgumentException;

class CongeController extends BaseController
{
    public function soldes()
    {
        try {
            $filtres = [
                'id_employe' => $this->request->getGet('id_employe'),
                'periode_debut' => $this->request->getGet('periode_debut'),
                'periode_fin' => $this->request->getGet('periode_fin'),
            ];
            return $this->response->setJSON(['soldes' => (new CongeModel())->getSoldes($filtres)]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les soldes de congés.']);
        }
    }

    public function recalculer()
    {
        try {
            $nombre = (new CongeModel())->recalculerSoldes();
            return $this->response->setJSON(['success' => true, 'message' => 'Recalcul terminé.', 'nombre_soldes' => $nombre]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => $e->getMessage()]);
        }
    }

    public function recalculerSoldes()
    {
        try {
            $nombre = (new CongeModel())->recalculerSoldes();
            return $this->response->setJSON(['message' => 'Les soldes de congés ont été recalculés.', 'nombre_soldes' => $nombre]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de recalculer les soldes de congés.']);
        }
    }

    public function valeurs()
    {
        try {
            return $this->response->setJSON(['valeurs' => (new CongeModel())->getValeurs([
                'id_employe' => $this->request->getGet('id_employe'),
                'periode_debut' => $this->request->getGet('periode_debut'),
                'periode_fin' => $this->request->getGet('periode_fin'),
            ])]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de calculer la valeur des congés.']);
        }
    }

    public function validerDemande()
    {
        try {
            return $this->response->setJSON(['validation' => (new CongeModel())->validerDemande($this->request->getJSON(true) ?? [])]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de valider la demande de congé.']);
        }
    }

    public function typesDemande()
    {
        return $this->response->setJSON(['types' => (new CongeModel())->getTypesPourDemande()]);
    }

    /** Tâche 6,7 : consultation. */
    public function demandes()
    {
        try {
            $filtres = [
                'id_employe' => $this->request->getGet('id_employe'),
                'statut' => $this->request->getGet('statut'),
                'periode_debut' => $this->request->getGet('periode_debut'),
                'periode_fin' => $this->request->getGet('periode_fin'),
            ];
            return $this->response->setJSON(['demandes' => (new CongeModel())->getDemandesConge($filtres)]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les demandes de congés.']);
        }
    }

    public function demande(int $id)
    {
        try {
            $demande = (new CongeModel())->getDemandeConge($id);
            if ($demande === null) return $this->response->setStatusCode(404)->setJSON(['message' => 'Demande introuvable.']);
            return $this->response->setJSON(['demande' => $demande]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer la demande.']);
        }
    }

    public function accepter(int $id) { return $this->actionStatut($id, 'accepter'); }
    public function refuser(int $id)  { return $this->actionStatut($id, 'refuser'); }
    public function annuler(int $id)  { return $this->actionStatut($id, 'annuler'); }

    private function actionStatut(int $id, string $action)
    {
        try {
            $utilisateur = session()->get('utilisateur') ?? [];
            $idU = (int) ($utilisateur['id'] ?? 0);
            $body = $this->request->getJSON(true) ?? [];
            $motif = $body['motif'] ?? null;
            $model = new CongeModel();
            if ($action === 'accepter') {
                $model->accepterDemande($id, $idU);
                try { $model->recalculerSoldes(); } catch (\Throwable $re) { log_message('warning', 'Recalcul soldes après validation: ' . $re->getMessage()); }
                $msg = 'Demande de congé validée. Les soldes ont été recalculés.';
            }
            elseif ($action === 'refuser') { $model->refuserDemande($id, $idU, $motif); $msg = 'Demande de congé refusée.'; }
            else { $model->annulerDemande($id, $idU, $motif); $msg = 'Demande de congé annulée.'; }
            return $this->response->setJSON(['message' => $msg]);
        } catch (InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de traiter la demande.']);
        }
    }

    public function employesDemande()
    {
        $utilisateur = session()->get('utilisateur') ?? [];
        return $this->response->setJSON(['employes' => (new CongeModel())->getEmployesPourDemande(
            isset($utilisateur['id_departement']) ? (int) $utilisateur['id_departement'] : null
        )]);
    }

    public function creerDemande()
    {
        try {
            $utilisateur = session()->get('utilisateur') ?? [];
            $resultat = (new CongeModel())->createDemandeConge($this->request->getJSON(true) ?? [], $utilisateur);
            return $this->response->setStatusCode(201)->setJSON(['message' => 'Demande de congé enregistrée.', ...$resultat]);
        } catch (InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible d’enregistrer la demande de congé.']);
        }
    }

    public function configurations()
    {
        try { return $this->response->setJSON(['configurations' => (new CongeModel())->getConfigurations()]); }
        catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les configurations de congés.']); }
    }

    public function updateConfiguration(int $id)
    {
        $data = $this->request->getJSON(true) ?? [];
        $nombreJour = $data['nombre_jour'] ?? null;
        if (! is_numeric($nombreJour) || (float) $nombreJour < 0) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Le nombre de jours doit être un nombre positif ou nul.']);
        }
        try {
            $model = new CongeModel();
            if ($model->find($id) === null) return $this->response->setStatusCode(404)->setJSON(['message' => 'Configuration de congé introuvable.']);
            $model->updateConfiguration($id, (float) $nombreJour);
            return $this->response->setJSON(['message' => 'Configuration de congé mise à jour.']);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de mettre à jour la configuration de congé.']);
        }
    }

    /** Tâches 11,16 : congés prévus. */
    public function congesPrevus()
    {
        try {
            return $this->response->setJSON(['conges' => (new CongeModel())->getCongesPrevus([
                'periode_debut' => $this->request->getGet('periode_debut'),
                'periode_fin' => $this->request->getGet('periode_fin'),
            ])]);
        } catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les congés prévus.']); }
    }

    /** Tâches 11,13,16 : disponibles pour remplacement. */
    public function disponiblesRemplacement()
    {
        try {
            $debut = (string) ($this->request->getGet('date_debut') ?? $this->request->getGet('periode_debut') ?? '');
            $fin = (string) ($this->request->getGet('date_fin') ?? $this->request->getGet('periode_fin') ?? '');
            $idAbsent = $this->request->getGet('id_employe_absent');
            $idConge = $this->request->getGet('id_conge');
            return $this->response->setJSON(['employes' => (new CongeModel())->getEmployesDisponiblesRemplacement(
                $debut, $fin, $idAbsent ? (int) $idAbsent : null, $idConge ? (int) $idConge : null
            )]);
        } catch (InvalidArgumentException $e) { return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]); }
        catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les employés disponibles.']); }
    }

    /** Tâches 12,13 : suggestion auto remplaçants. */
    public function suggererRemplacants()
    {
        try {
            $idAbsent = (int) ($this->request->getGet('id_employe_absent') ?? $this->request->getGet('id_employe') ?? 0);
            $debut = (string) ($this->request->getGet('date_debut') ?? '');
            $fin = (string) ($this->request->getGet('date_fin') ?? '');
            return $this->response->setJSON(['remplacants' => (new CongeModel())->rechercherRemplacants($idAbsent, $debut, $fin)]);
        } catch (InvalidArgumentException $e) { return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]); }
        catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de rechercher des remplaçants.']); }
    }

    /** Tâches 14,15 : affecter remplaçant. */
    public function affecterRemplacant()
    {
        try {
            $id = (new CongeModel())->affecterRemplacant($this->request->getJSON(true) ?? []);
            return $this->response->setStatusCode(201)->setJSON(['id' => $id, 'message' => 'Remplaçant affecté.']);
        } catch (InvalidArgumentException $e) { return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]); }
        catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible d’affecter le remplaçant.']); }
    }

    /** Tâche 16 : employés pouvant partir en congé. */
    public function employesEligibles()
    {
        try {
            $idType = $this->request->getGet('id_type_conge');
            return $this->response->setJSON(['employes' => (new CongeModel())->getEmployesEligiblesConges($idType ? (int) $idType : null)]);
        } catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les employés éligibles.']); }
    }
}
