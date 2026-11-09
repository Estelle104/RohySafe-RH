<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use Throwable;
use Config\Database;

class EmployeController extends BaseController
{
    /** Champs modifiables de info_employes */
    private const EDITABLE = [
        'residence_actuel',
        'copie_actuel',
        'date_echeance',
        'id_statut_matrimoniale',
        'id_statut_professionnelle',
    ];

    // api : GET /api/rh/employes : liste des employés (id, nom, prénom, email, téléphone, date embauche)
    public function index()
    {
        try {
            $db = Database::connect();
            $listeEmployes = $db->table('v_info_employes')
                // ->select('id_employe, nom, prenom, matricule,statut_matrimoniale,statut_professionnelle, date_embauche')
                ->select('id_employe, matricule, nom, prenom, email, telephone, statut_actuel, '
                    . 'id_statut_matrimoniale, statut_matrimoniale, '
                    . 'id_statut_professionnelle, statut_professionnelle, '
                    . 'date_echeance, (date_echeance - CURRENT_DATE) AS jours_restants', false)
                ->orderBy('nom', 'ASC')
                ->get()
                ->getResultArray();
            return $this->response->setJSON($listeEmployes);
        } catch (Throwable $e) {
            log_message('error', 'Erreur liste employés: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer la liste des employés.']);
        }
    }

    /** GET /api/employes/(:num) : fiche complète (vue v_info_employes) */
    public function show($id = null)
    {
        try {
            $db = Database::connect();
            $fiche = $db->table('v_info_employes')
                ->where('id_employe', (int) $id)
                ->get()
                ->getRowArray();

            if (! $fiche) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Employé introuvable.']);
            }

            return $this->response->setJSON($fiche);
        } catch (Throwable $e) {
            log_message('error', 'Erreur fiche employé: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer la fiche.']);
        }
    }

    /**
     * POST /api/employes/(:num)/info : met à jour info_employes
     * et archive l'ancien état dans histo_info_employes (multipart : la copie est un fichier).
     */
    public function update($id = null)
    {
        try {
            $id = (int) $id;
            $db = Database::connect();

            if (! $db->table('employes')->where('id', $id)->countAllResults()) {
                return $this->response->setStatusCode(404)->setJSON(['message' => 'Employé introuvable.']);
            }

            $data = $this->request->getPost();

            // Copie du justificatif (optionnelle)
            $file = $this->request->getFile('copie');
            if ($file && $file->isValid() && ! $file->hasMoved()) {
                if (! in_array($file->getMimeType(), ['application/pdf', 'image/jpeg', 'image/png'], true)) {
                    return $this->response->setStatusCode(422)->setJSON(['message' => 'Copie : PDF, JPG ou PNG uniquement.']);
                }
                $nom = $file->getRandomName();
                $file->move(FCPATH . 'uploads/justificatifs', $nom);
                $data['copie_actuel'] = 'uploads/justificatifs/' . $nom;
            }

            $data = array_intersect_key($data, array_flip(self::EDITABLE));
            foreach ($data as $k => $v) {
                if ($v === '') {
                    $data[$k] = null; // chaîne vide -> NULL (dates, ids)
                }
            }
            if (empty($data)) {
                return $this->response->setStatusCode(400)->setJSON(['message' => 'Aucune donnée à mettre à jour.']);
            }

            $db->transStart();

            $ancien = $db->table('info_employes')->where('id_employe', $id)->get()->getRowArray();
            if ($ancien) {
                $db->table('histo_info_employes')->insert([
                    'id_employe'                => $id,
                    'residence'                 => $ancien['residence_actuel'],
                    'copie'                     => $ancien['copie_actuel'],
                    'id_statut_matrimoniale'    => $ancien['id_statut_matrimoniale'],
                    'id_statut_professionnelle' => $ancien['id_statut_professionnelle'],
                    'date'                      => date('Y-m-d H:i:s'),
                ]);
                $db->table('info_employes')->where('id_employe', $id)->update($data);
            } else {
                $data['id_employe'] = $id;
                $db->table('info_employes')->insert($data);
            }

            $db->transComplete();

            if (! $db->transStatus()) {
                return $this->response->setStatusCode(500)->setJSON(['message' => 'Échec de la mise à jour.']);
            }

            $fiche = $db->table('v_info_employes')->where('id_employe', $id)->get()->getRowArray();
            return $this->response->setJSON($fiche);
        } catch (Throwable $e) {
            log_message('error', 'Erreur mise à jour fiche employé: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de mettre à jour la fiche.']);
        }
    }

    /** GET /api/employes/(:num)/historique : du plus récent au plus ancien */
    public function historique($id = null)
    {
        try {
            $db = Database::connect();
            $historique = $db->table('histo_info_employes h')
                ->select('h.id, h.id_employe, h.residence, h.copie, h.date, sm.libelle AS statut_matrimoniale, sp.libelle AS statut_professionnelle')
                ->join('statut_matrimoniales sm', 'sm.id = h.id_statut_matrimoniale', 'left')
                ->join('statut_professionnelles sp', 'sp.id = h.id_statut_professionnelle', 'left')
                ->where('h.id_employe', (int) $id)
                ->orderBy('h.date', 'DESC')
                ->get()
                ->getResultArray();

            return $this->response->setJSON($historique);
        } catch (Throwable $e) {
            log_message('error', 'Erreur historique employé: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => "Impossible de récupérer l'historique."]);
        }
    }

    /** GET /api/referentiels/statuts : listes pour les <select> du formulaire */
    public function statuts()
    {
        try {
            $db = Database::connect();
            return $this->response->setJSON([
                'matrimoniaux'   => $db->table('statut_matrimoniales')->orderBy('id')->get()->getResultArray(),
                'professionnels' => $db->table('statut_professionnelles')->orderBy('id')->get()->getResultArray(),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur statuts: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['message' => 'Impossible de récupérer les statuts.']);
        }
    }
}
