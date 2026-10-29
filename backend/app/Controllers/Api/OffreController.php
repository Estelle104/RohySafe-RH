<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CandidatureModel;
use App\Models\OffreModel;
use Throwable;

class OffreController extends BaseController
{
    public function index()
    {
        try {
            $offres = (new OffreModel())->getOffresPubliees();
            $utilisateur = session()->get('candidat');

            $filtrees = [];
            foreach ($offres as $offre) {
                $libelleStatut = strtolower((string) ($offre['statut'] ?? ''));
                if ($libelleStatut === 'en attente') {
                    continue;
                }
                $filtrees[] = $offre;
            }
            $offres = $filtrees;

            if ($utilisateur && !empty($utilisateur['id'])) {
                $dejaPostulees = [];
                foreach ((new CandidatureModel())->getByCandidat((int) $utilisateur['id']) as $ligne) {
                    if (isset($ligne['id_offre'])) {
                        $dejaPostulees[] = (int) $ligne['id_offre'];
                    }
                }

                foreach ($offres as &$offre) {
                    if (isset($offre['id']) && in_array((int) $offre['id'], $dejaPostulees, true)) {
                        $offre['deja_postule'] = true;
                    } else {
                        $offre['deja_postule'] = false;
                    }
                }
                unset($offre);
            }

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
        try {
            $utilisateur = session()->get('utilisateur');
            $role = $utilisateur['role'] ?? '';

            // La Direction générale consulte toutes les demandes prêtes à publier,
            // tandis qu'un RH ne voit que celles qui lui sont attribuées.
            $estDirection = in_array($role, ['DG', 'Direction'], true);
            $rhId = $estDirection ? null : (int) ($utilisateur['id'] ?? 0);

            return $this->response->setJSON(
                (new OffreModel())->getDemandesAPublier($rhId)
            );
        }
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
            $utilisateur = session()->get('utilisateur');
            $role = $utilisateur['role'] ?? '';
            $estDirection = in_array($role, ['DG', 'Direction'], true);
            $rhId = $estDirection ? null : (int) ($utilisateur['id'] ?? 0);
            $id = (new OffreModel())->publierDemande($data, $rhId);
            return $this->response->setJSON(['id' => $id, 'message' => 'Offre publiée.']);
        } catch (Throwable $e) { log_message('error', $e->getMessage()); return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de publier l’offre.']); }
    }
}
