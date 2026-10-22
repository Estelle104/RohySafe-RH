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

    /** @return array<string, mixed>|null */
    public function getDetail(int $id): ?array
    {
        return $this->db->table('demandes dm')
            ->select("dm.id, dm.date_creation, dm.nombre_demande AS nombre_poste, dm.date_prise_poste, dm.salaire_propose, d.libelle AS departement, p.libelle AS poste, tc.libelle AS type_contrat, uc.login AS createur, ua.login AS utilisateur_actuel, CASE sd.libelle WHEN 'cree' THEN 'Créée' WHEN 'en_attente' THEN 'En attente' WHEN 'valide' THEN 'Validée' WHEN 'refusee' THEN 'Refusée' WHEN 'annulee' THEN 'Annulée' ELSE INITCAP(REPLACE(sd.libelle, '_', ' ')) END AS statut", false)
            ->join('poste_departement pd', 'pd.id = dm.id_poste_departement')
            ->join('postes p', 'p.id = pd.id_poste')
            ->join('departements d', 'd.id = pd.id_departement')
            ->join('type_contrats tc', 'tc.id = dm.id_type_contrat')
            ->join('statut_demandes sd', 'sd.id = dm.id_statut_actuel')
            ->join('utilisateurs uc', 'uc.id = dm.id_createur', 'left')
            ->join('utilisateurs ua', 'ua.id = dm.id_utilisateur_actuel', 'left')
            ->where('dm.id', $id)
            ->get()
            ->getRowArray();
    }

    /** @return list<array<string, mixed>> */
    public function getGrilleSalariale(int $id): array
    {
        return $this->db->table('grilles_salariales gs')
            ->select('gs.id, tc.libelle AS type_contrat, gs.montant_min, gs.montant_max')
            ->join('demandes dm', 'dm.id_poste_departement = gs.id_poste_departement')
            ->join('type_contrats tc', 'tc.id = gs.id_type_contrat')
            ->where('dm.id', $id)
            ->orderBy('gs.montant_min', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function enregistrerPropositionSalaire(int $id, float $salaire, ?string $remarque): bool
    {
        $demande = $this->find($id);
        if ($demande === null) {
            return false;
        }

        $finance = $this->db->table('roles')
            ->select('id')
            ->where('libelle', 'Finance')
            ->get()
            ->getRowArray();

        if ($finance === null) {
            throw new \RuntimeException('Le Finance est introuvable.');
        }

        $finance_utilisateur = $this->db->table('utilisateurs')
            ->select('id')
            ->where('id_role', $finance['id'])
            ->get()
            ->getRowArray();

        if ($finance_utilisateur === null) {
            throw new \RuntimeException('Aucun utilisateur Finance trouvé.');
        }

        $this->db->transStart();
        $this->update($id, [
            'salaire_propose'       => $salaire,
            'id_utilisateur_actuel' => $finance_utilisateur['id'],
        ]);
        $this->db->table('historique_demandes')->insert([
            'id_demande'        => $id,
            'id_statut_demande' => $demande['id_statut_actuel'],
            'id_utilisateur'    => $demande['id_utilisateur_actuel'],
            'salaire_propose'   => $salaire,
            'remarque'          => $remarque,
        ]);
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    public function refuser(int $id): bool
    {
        $demande = $this->find($id);
        if ($demande === null) {
            return false;
        }

        $statutRefuse = $this->db->table('statut_demandes')
            ->select('id')
            ->where('libelle', 'refusee')
            ->get()
            ->getRowArray();

        if ($statutRefuse === null) {
            throw new \RuntimeException('Le statut refusee est introuvable.');
        }

        $this->db->transStart();
        $this->update($id, ['id_statut_actuel' => $statutRefuse['id']]);
        $this->db->table('historique_demandes')->insert([
            'id_demande'        => $id,
            'id_statut_demande' => $statutRefuse['id'],
            'id_utilisateur'    => $demande['id_utilisateur_actuel'],
            'salaire_propose'   => $demande['salaire_propose'],
            'remarque'          => 'Demande refusée.',
        ]);
        $this->db->transComplete();

        return $this->db->transStatus();
    }

}
