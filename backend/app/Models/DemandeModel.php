<?php

namespace App\Models;

use CodeIgniter\Model;

class DemandeModel extends Model
{
    protected $table         = 'demandes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_poste_departement', 'id_createur', 'id_utilisateur_actuel', 'id_statut_actuel', 'nombre_demande', 'id_type_contrat', 'date_prise_poste', 'salaire_propose'];

    public function getNotifications(): array {
        $builder = $this->db->table('demandes dm')
            ->select("
                dm.id,
                dm.date_creation,
                dm.nombre_demande AS nombre_poste,
                d.libelle AS departement,
                p.libelle AS poste,
                tc.libelle AS type_contrat,
                dm.date_prise_poste,
                dm.salaire_propose,
                uc.login AS utilisateur,
                ua.login AS utilisateur_actuel,
                dm.id_createur,
                dm.id_utilisateur_actuel,
                CASE sd.libelle
                    WHEN 'cree' THEN 'Créée'
                    WHEN 'en_attente' THEN 'En attente'
                    WHEN 'valide' THEN 'Validée'
                    WHEN 'refusee' THEN 'Refusée'
                    WHEN 'annulee' THEN 'Annulée'
                    ELSE INITCAP(REPLACE(sd.libelle, '_', ' '))
                END AS statut
            ", false)
            ->join(
                'poste_departement pd',
                'pd.id = dm.id_poste_departement'
            )
            ->join('postes p', 'p.id = pd.id_poste')
            ->join('departements d', 'd.id = pd.id_departement')
            ->join('type_contrats tc', 'tc.id = dm.id_type_contrat')
            ->join('statut_demandes sd', 'sd.id = dm.id_statut_actuel')
            ->join('utilisateurs uc', 'uc.id = dm.id_createur', 'left')
            ->join('utilisateurs ua', 'ua.id = dm.id_utilisateur_actuel', 'left')
            ->orderBy('dm.date_creation', 'DESC');

        log_message(
            'debug',
            "Requête notifications :\n" .
            $builder->getCompiledSelect(false)
        );

        return $builder->get()->getResultArray();
    }

    public function getNotificationsByUtilisateurActuel(int $idUtilisateur): array {
        $builder = $this->db->table('demandes dm')
            ->select("
                dm.id,
                dm.date_creation,
                dm.nombre_demande AS nombre_poste,
                d.libelle AS departement,
                p.libelle AS poste,
                tc.libelle AS type_contrat,
                dm.date_prise_poste,
                dm.salaire_propose,
                uc.login AS utilisateur,
                ua.login AS utilisateur_actuel,
                dm.id_createur,
                dm.id_utilisateur_actuel,
                CASE sd.libelle
                    WHEN 'cree' THEN 'Créée'
                    WHEN 'en_attente' THEN 'En attente'
                    WHEN 'valide' THEN 'Validée'
                    WHEN 'refusee' THEN 'Refusée'
                    WHEN 'annulee' THEN 'Annulée'
                    ELSE INITCAP(REPLACE(sd.libelle, '_', ' '))
                END AS statut
            ", false)
            ->join(
                'poste_departement pd',
                'pd.id = dm.id_poste_departement'
            )
            ->join('postes p', 'p.id = pd.id_poste')
            ->join('departements d', 'd.id = pd.id_departement')
            ->join('type_contrats tc', 'tc.id = dm.id_type_contrat')
            ->join('statut_demandes sd', 'sd.id = dm.id_statut_actuel')
            ->join('utilisateurs uc', 'uc.id = dm.id_createur', 'left')
            ->join('utilisateurs ua', 'ua.id = dm.id_utilisateur_actuel', 'left')
            ->where('dm.id_utilisateur_actuel', $idUtilisateur)
            ->orderBy('dm.date_creation', 'DESC');

        log_message(
            'debug',
            "Requête notifications utilisateur {$idUtilisateur} :\n" .
            $builder->getCompiledSelect(false)
        );

        return $builder->get()->getResultArray();
    }

}
