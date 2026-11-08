<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\AbsenceModel;
use Throwable;

class AbsenceController extends BaseController
{
    public function index()
    {
        try { $model = new AbsenceModel(); $model->detecterAbsencesAutomatiques(); return $this->response->setJSON(['absences' => $model->getAbsences()]); }
        catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les absences.']); }
    }

    public function statut(int $id)
    {
        $data = $this->request->getJSON(true) ?? [];
        if (! in_array($data['statut'] ?? '', ['JUSTIFIEE_AUTORISEE', 'NON_JUSTIFIEE'], true)) return $this->response->setStatusCode(422)->setJSON(['message' => 'Statut invalide.']);
        try {
            $statut = db_connect()->table('statut_absences')->where('libelle', $data['statut'])->get()->getRowArray();
            if ($statut === null || ! (new AbsenceModel())->modifierStatut($id, (int) $statut['id'], $data['motif'] ?? null)) return $this->response->setStatusCode(404)->setJSON(['message' => 'Absence introuvable.']);
            return $this->response->setJSON(['message' => 'Statut de l’absence mis à jour.']);
        } catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de modifier le statut.']); }
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        if (empty($data['id_employe']) || empty($data['date']) || empty(trim((string) ($data['motif'] ?? '')))) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'L’employé, la date et le motif sont obligatoires.']);
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) return $this->response->setStatusCode(422)->setJSON(['message' => 'La date est invalide.']);
        $statut = $data['statut'] ?? 'A_TRAITER';
        if (! in_array($statut, ['A_TRAITER', 'JUSTIFIEE_AUTORISEE', 'NON_JUSTIFIEE'], true)) return $this->response->setStatusCode(422)->setJSON(['message' => 'Le statut de l’absence est invalide.']);
        if (($data['heure_debut'] ?? null) !== null && ($data['heure_fin'] ?? null) !== null && $data['heure_fin'] <= $data['heure_debut']) return $this->response->setStatusCode(422)->setJSON(['message' => 'L’heure de fin doit être postérieure à l’heure de début.']);
        try {
            $statutRow = db_connect()->table('statut_absences')->where('libelle', $statut)->get()->getRowArray();
            if ($statutRow === null) return $this->response->setStatusCode(422)->setJSON(['message' => 'Le statut de l’absence est introuvable.']);
            $id = (new AbsenceModel())->creerAbsence(['id_employe' => (int) $data['id_employe'], 'date' => $data['date'], 'motif' => trim($data['motif']), 'id_statut_absence' => $statutRow['id'], 'heure_debut' => $data['heure_debut'] ?? null, 'heure_fin' => $data['heure_fin'] ?? null]);
            return $this->response->setStatusCode(201)->setJSON(['id' => $id, 'message' => 'Absence enregistrée.']);
        }
        catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible d’enregistrer l’absence.']); }
    }
}
