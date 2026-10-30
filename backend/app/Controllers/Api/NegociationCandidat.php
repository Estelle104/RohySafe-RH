<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Throwable;

class NegociationCandidat extends BaseController
{
    public function show(int $idCandidat, int $idCandidature)
    {
        if ($idCandidat <= 0 || $idCandidature <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Identifiant invalide.']);
        }

        try {
            $db = \Config\Database::connect();
            $candidature = $db->table('candidatures c')
                ->select('c.id, c.id_candidat, c.id_offre, ca.nom, ca.prenom, o.titre AS offre, d.libelle AS departement, p.libelle AS poste, tc.libelle AS type_contrat, dm.salaire_propose, dm.date_prise_poste, dm.nombre_demande')
                ->join('candidats ca', 'ca.id = c.id_candidat')
                ->join('offres o', 'o.id = c.id_offre')
                ->join('demandes dm', 'dm.id = o.id_demande')
                ->join('poste_departement pd', 'pd.id = dm.id_poste_departement')
                ->join('postes p', 'p.id = pd.id_poste')
                ->join('departements d', 'd.id = pd.id_departement')
                ->join('type_contrats tc', 'tc.id = dm.id_type_contrat')
                ->where('c.id', $idCandidature)
                ->where('c.id_candidat', $idCandidat)
                ->get()
                ->getRowArray();

            if ($candidature === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Candidature introuvable.']);
            }

            $proposition = $db->table('propositions_embauche p')
                ->select('p.id, p.date_proposition, sp.libelle AS statut')
                ->join('statut_propositions_embauche sp', 'sp.id = p.id_statut_actuel')
                ->where('p.id_candidature', $idCandidature)
                ->orderBy('p.id', 'DESC')
                ->get()
                ->getRowArray();

            $historique = [];
            if ($proposition !== null) {
                $historique = $db->table('historique_propositions h')
                    ->select('h.*, sp.libelle AS statut')
                    ->join('statut_propositions_embauche sp', 'sp.id = h.id_statut_proposition')
                    ->where('h.id_proposition', $proposition['id'])
                    ->orderBy('h.date_action', 'DESC')
                    ->get()
                    ->getResultArray();
            }

            return $this->response->setJSON([
                'candidature' => $candidature,
                'proposition' => $proposition,
                'historique' => $historique,
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur negociation show: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer la négociation.']);
        }
    }

    public function modifierSalaire(int $idCandidat, int $idCandidature)
    {
        if ($idCandidat <= 0 || $idCandidature <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Identifiant invalide.']);
        }

        $data = $this->request->getJSON(true) ?? [];
        $salaire = $data['salaire_propose'] ?? null;
        if (! is_numeric($salaire) || (float) $salaire < 0) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Le salaire proposé doit être un nombre positif.']);
        }

        try {
            $db = \Config\Database::connect();
            $candidature = $db->table('candidatures')->where('id', $idCandidature)->where('id_candidat', $idCandidat)->get()->getRowArray();
            if ($candidature === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Candidature introuvable.']);
            }

            $proposition = $db->table('propositions_embauche')->where('id_candidature', $idCandidature)->orderBy('id', 'DESC')->get()->getRowArray();
            if ($proposition === null) {
                $statutAttente = $db->table('statut_propositions_embauche')->select('id')->where('libelle', 'en_attente')->get()->getRowArray();
                if ($statutAttente === null) throw new \RuntimeException('Statut introuvable.');
                $idProposition = $db->table('propositions_embauche')->insert([
                    'id_candidature' => $idCandidature,
                    'id_statut_actuel' => (int) $statutAttente['id'],
                    'date_proposition' => date('Y-m-d H:i:s'),
                ], true);
            } else {
                $idProposition = (int) $proposition['id'];
            }

            $statutNegociation = $db->table('statut_propositions_embauche')->select('id')->where('libelle', 'en_negociation')->get()->getRowArray();
            if ($statutNegociation === null) {
                $statutNegociation = $db->table('statut_propositions_embauche')->select('id')->where('libelle', 'en_attente')->get()->getRowArray();
            }

            $db->transStart();
            if ($statutNegociation !== null) {
                $db->table('propositions_embauche')->where('id', $idProposition)->update(['id_statut_actuel' => (int) $statutNegociation['id']]);
            }
            $db->table('historique_propositions')->insert([
                'id_proposition' => $idProposition,
                'salaire_propose' => (float) $salaire,
                'id_type_contrat' => $candidature['id_offre'] ? null : null,
                'id_statut_proposition' => $statutNegociation ? (int) $statutNegociation['id'] : 1,
                'motif' => 'Modification du salaire par le candidat',
                'date_action' => date('Y-m-d H:i:s'),
            ]);

            // Notification RH? We just create notification to RH concept via user flow - RH can see via liste
            $db->transComplete();
            if (! $db->transStatus()) throw new \RuntimeException('Échec de la modification.');

            return $this->response->setJSON(['message' => 'Demande de modification du salaire envoyée au RH.']);
        } catch (Throwable $e) {
            log_message('error', 'Erreur modifier salaire: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de modifier le salaire.']);
        }
    }

    public function accepter(int $idCandidat, int $idCandidature)
    {
        if ($idCandidat <= 0 || $idCandidature <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Identifiant invalide.']);
        }

        try {
            $db = \Config\Database::connect();
            $candidature = $db->table('candidatures')->where('id', $idCandidature)->where('id_candidat', $idCandidat)->get()->getRowArray();
            if ($candidature === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Candidature introuvable.']);
            }

            $proposition = $db->table('propositions_embauche')->where('id_candidature', $idCandidature)->orderBy('id', 'DESC')->get()->getRowArray();
            if ($proposition === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Aucune proposition.']);
            }

            $statutAcceptee = $db->table('statut_propositions_embauche')->select('id')->where('libelle', 'acceptee')->get()->getRowArray();
            if ($statutAcceptee === null) throw new \RuntimeException('Statut introuvable.');

            $db->table('propositions_embauche')->where('id', (int) $proposition['id'])->update(['id_statut_actuel' => (int) $statutAcceptee['id']]);

            $db->table('notifications_candidats')->insert([
                'id_candidat' => $idCandidat,
                'id_candidature' => $idCandidature,
                'id_offre' => (int) $candidature['id_offre'],
                'type' => 'proposition',
                'message' => "Votre proposition a été acceptée. Le dossier sera finalisé.",
                'lu' => false,
                'date_creation' => date('Y-m-d H:i:s'),
            ]);

            return $this->response->setJSON(['message' => 'Proposition acceptée.']);
        } catch (Throwable $e) {
            log_message('error', 'Erreur accepter: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => "Impossible d'accepter."]);
        }
    }

    public function refuserContratEssai(int $idCandidat, int $idCandidature)
    {
        if ($idCandidat <= 0 || $idCandidature <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['message' => 'Identifiant invalide.']);
        }

        try {
            $db = \Config\Database::connect();
            $candidature = $db->table('candidatures')->where('id', $idCandidature)->where('id_candidat', $idCandidat)->get()->getRowArray();
            if ($candidature === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Candidature introuvable.']);
            }

            $proposition = $db->table('propositions_embauche')->where('id_candidature', $idCandidature)->orderBy('id', 'DESC')->get()->getRowArray();
            if ($proposition === null) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Aucune proposition.']);
            }

            $statutRefusee = $db->table('statut_propositions_embauche')->select('id')->where('libelle', 'refusee')->get()->getRowArray();
            if ($statutRefusee === null) throw new \RuntimeException('Statut introuvable.');

            $db->table('propositions_embauche')->where('id', (int) $proposition['id'])->update(['id_statut_actuel' => (int) $statutRefusee['id']]);

            $db->table('notifications_candidats')->insert([
                'id_candidat' => $idCandidat,
                'id_candidature' => $idCandidature,
                'id_offre' => (int) $candidature['id_offre'],
                'type' => 'proposition',
                'message' => "Vous avez refusé le contrat d'essai.",
                'lu' => false,
                'date_creation' => date('Y-m-d H:i:s'),
            ]);

            return $this->response->setJSON(['message' => "Contrat d'essai refusé."]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur refuser: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => "Impossible de refuser."]);
        }
    }
}
