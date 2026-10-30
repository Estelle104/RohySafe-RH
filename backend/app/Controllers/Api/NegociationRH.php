<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Throwable;

class NegociationRH extends BaseController
{
    public function index()
    {
        try {
            $db = \Config\Database::connect();
            $propositions = $db->table('propositions_embauche p')
                ->select('p.id, p.id_candidature, p.date_proposition, sp.libelle AS statut, c.id_candidat, ca.nom, ca.prenom, ca.email, o.titre AS offre')
                ->join('statut_propositions_embauche sp', 'sp.id = p.id_statut_actuel')
                ->join('candidatures c', 'c.id = p.id_candidature')
                ->join('candidats ca', 'ca.id = c.id_candidat')
                ->join('offres o', 'o.id = c.id_offre')
                ->orderBy('p.date_proposition', 'DESC')
                ->get()
                ->getResultArray();

            $result = [];
            foreach ($propositions as $prop) {
                $dernier = $db->table('historique_propositions h')
                    ->select('h.salaire_propose, h.date_action, h.motif')
                    ->where('h.id_proposition', $prop['id'])
                    ->orderBy('h.date_action', 'DESC')
                    ->get()
                    ->getRowArray();
                $prop['dernier_salaire'] = $dernier['salaire_propose'] ?? null;
                $prop['date_modification'] = $dernier['date_action'] ?? null;
                $prop['motif'] = $dernier['motif'] ?? null;
                $result[] = $prop;
            }

            return $this->response->setJSON($result);
        } catch (Throwable $e) {
            log_message('error', 'Erreur negociations RH: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les négociations.']);
        }
    }

    public function valider(int $idProposition)
    {
        if ($idProposition <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Identifiant invalide.']);
        }

        try {
            $db = \Config\Database::connect();
            $proposition = $db->table('propositions_embauche')->where('id', $idProposition)->get()->getRowArray();
            if ($proposition === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Proposition introuvable.']);
            }

            $candidature = $db->table('candidatures')->where('id', $proposition['id_candidature'])->get()->getRowArray();
            if ($candidature === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Candidature introuvable.']);
            }

            $statutAcceptee = $db->table('statut_propositions_embauche')->select('id')->where('libelle', 'acceptee')->get()->getRowArray();
            if ($statutAcceptee === null) throw new \RuntimeException('Statut introuvable.');

            $db->transStart();
            $db->table('propositions_embauche')->where('id', $idProposition)->update(['id_statut_actuel' => (int) $statutAcceptee['id']]);

            $db->table('notifications_candidats')->insert([
                'id_candidat' => (int) $candidature['id_candidat'],
                'id_candidature' => (int) $proposition['id_candidature'],
                'id_offre' => (int) $candidature['id_offre'],
                'type' => 'proposition',
                'message' => "Votre demande de modification de salaire a été acceptée par le RH. Vous êtes pris(e).",
                'lu' => false,
                'date_creation' => date('Y-m-d H:i:s'),
            ]);

            // Check if all places filled
            $offreId = (int) $candidature['id_offre'];
            $dm = $db->table('offres o')->join('demandes dm', 'dm.id = o.id_demande')->select('dm.nombre_demande')->where('o.id', $offreId)->get()->getRowArray();
            $nombreDemande = (int) ($dm['nombre_demande'] ?? 0);
            if ($nombreDemande > 0) {
                $count = $db->table('candidatures c')
                    ->join('propositions_embauche p', 'p.id_candidature = c.id')
                    ->join('statut_propositions_embauche sp', 'sp.id = p.id_statut_actuel')
                    ->where('c.id_offre', $offreId)
                    ->where('sp.libelle', 'acceptee')
                    ->countAllResults();
                if ($count >= $nombreDemande) {
                    $statutCloturee = $db->table('statut_offres')->select('id')->where('libelle', 'cloturee')->get()->getRowArray();
                    if ($statutCloturee !== null) {
                        $db->table('offres')->where('id', $offreId)->update(['id_statut' => (int) $statutCloturee['id']]);
                    }
                }
            }

            $db->transComplete();
            if (! $db->transStatus()) throw new \RuntimeException('Échec.');

            return $this->response->setJSON(['message' => 'Proposition validée.']);
        } catch (Throwable $e) {
            log_message('error', 'Erreur valider proposition: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de valider la proposition.']);
        }
    }

    public function refuser(int $idProposition)
    {
        if ($idProposition <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Identifiant invalide.']);
        }

        try {
            $db = \Config\Database::connect();
            $proposition = $db->table('propositions_embauche')->where('id', $idProposition)->get()->getRowArray();
            if ($proposition === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Proposition introuvable.']);
            }
            $candidature = $db->table('candidatures')->where('id', $proposition['id_candidature'])->get()->getRowArray();
            if ($candidature === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Candidature introuvable.']);
            }

            $statutRefusee = $db->table('statut_propositions_embauche')->select('id')->where('libelle', 'refusee')->get()->getRowArray();
            if ($statutRefusee === null) throw new \RuntimeException('Statut introuvable.');

            $db->table('propositions_embauche')->where('id', $idProposition)->update(['id_statut_actuel' => (int) $statutRefusee['id']]);
            $db->table('notifications_candidats')->insert([
                'id_candidat' => (int) $candidature['id_candidat'],
                'id_candidature' => (int) $proposition['id_candidature'],
                'id_offre' => (int) $candidature['id_offre'],
                'type' => 'proposition',
                'message' => "Votre demande de modification de salaire a été refusée par le RH.",
                'lu' => false,
                'date_creation' => date('Y-m-d H:i:s'),
            ]);

            return $this->response->setJSON(['message' => 'Proposition refusée.']);
        } catch (Throwable $e) {
            log_message('error', 'Erreur refuser proposition: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de refuser la proposition.']);
        }
    }
}
