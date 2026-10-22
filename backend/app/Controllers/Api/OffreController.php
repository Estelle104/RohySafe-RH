<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\OffreModel;
use Throwable;

class OffreController extends BaseController
{
    public function index()
    {
        try {
            $offres = (new OffreModel())->getOffresPubliees();

            return $this->response->setJSON($offres);
        } catch (Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON(['message' => 'Impossible de récupérer les offres.']);
        }
    }

    public function demandesAPublier()
    {
        try { return $this->response->setJSON((new OffreModel())->getDemandesAPublier()); }
        catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les demandes.']); }
    }

    public function show(int $id)
    {
        try {
            $offre = (new OffreModel())->getOffreDetail($id);
            return $offre === null ? $this->response->setStatusCode(404)->setJSON(['message' => 'Offre introuvable.']) : $this->response->setJSON($offre);
        } catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer l’offre.']); }
    }

    public function publier()
    {
        $data = $this->request->getJSON(true) ?? [];
        foreach (['id_demande', 'titre', 'date_publication', 'date_limite'] as $champ) {
            if (empty($data[$champ])) return $this->response->setStatusCode(422)->setJSON(['message' => "Le champ {$champ} est obligatoire."]);
        }
        if ($data['date_limite'] < $data['date_publication']) return $this->response->setStatusCode(422)->setJSON(['message' => 'La date limite doit être postérieure à la date de publication.']);
        try {
            $id = (new OffreModel())->publierDemande($data);
            return $this->response->setJSON(['id' => $id, 'message' => 'Offre publiée.']);
        } catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de publier l’offre.']); }
    }
}
