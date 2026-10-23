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

    protected $validationRules = [
        'id_poste_departement' => 'required|is_natural_no_zero',
        'nombre_demande' => 'required|is_natural_no_zero',
        'id_type_contrat' => 'required|is_natural_no_zero',
        'date_prise_poste' => 'permit_empty',
        'salaire_propose' => 'required|decimal|greater_than_equal_to[0]',
    ];

    public function getOptions(): array
    {
        $postes = $this->db->table('poste_departement pd')
            ->select('pd.id, p.libelle AS poste, d.libelle AS departement')
            ->join('postes p', 'p.id = pd.id_poste')
            ->join('departements d', 'd.id = pd.id_departement')
            ->orderBy('d.libelle')->orderBy('p.libelle')->get()->getResultArray();
        $types = $this->db->table('type_contrats')->orderBy('libelle')->get()->getResultArray();

        return ['postes' => $postes, 'types_contrat' => $types];
    }

    public function creerPourUtilisateur(array $data, int $utilisateurId): array
    {
        $statut = $this->db->table('statut_demandes')->where('libelle', 'cree')->get()->getRowArray();
        if (! $statut) return ['erreur' => "Le statut 'cree' doit exister dans statut_demandes."];
        $rh = $this->db->table('utilisateurs u')
            ->select('u.id')
            ->join('roles r', 'r.id = u.id_role')
            ->where('r.libelle', 'RH')
            ->orderBy('u.id', 'ASC')
            ->get()->getRowArray();
        if (! $rh) return ['erreur' => 'Aucun utilisateur RH trouvé.'];
        $this->db->transStart();
        $id = $this->insert([
            'id_poste_departement' => (int) $data['id_poste_departement'],
            'id_createur' => $utilisateurId,
            'id_utilisateur_actuel' => $rh['id'],
            'id_statut_actuel' => (int) $statut['id'],
            'nombre_demande' => (int) $data['nombre_demande'],
            'id_type_contrat' => (int) $data['id_type_contrat'],
            'date_prise_poste' => empty($data['date_prise_poste']) ? null : $data['date_prise_poste'],
            'salaire_propose' => $data['salaire_propose'],
        ], true);
        if ($id) {
            $this->db->table('historique_demandes')->insert([
                'id_demande' => $id,
                'id_statut_demande' => $statut['id'],
                'id_utilisateur' => $utilisateurId,
                'salaire_propose' => $data['salaire_propose'],
                'remarque' => 'Demande créée par le Manager et transmise au RH.',
            ]);
        }
        $this->db->transComplete();

        return $id && $this->db->transStatus()
            ? ['id' => $id]
            : ['erreur' => $this->errors() ?: 'Impossible de créer la demande.'];
    }



    function getContratDemande($idDemande)
    {
        return $this->db->table('demandes d')
            ->select('tc.libelle')
            ->join('type_contrats tc', 'tc.id = d.id_type_contrat')
            ->where('d.id', $idDemande);
    }

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

    public function trouverAvecDepartement(int $id): ?array
    {
        return $this->select('demandes.*, pd.id_departement, sd.libelle AS statut_libelle')
            ->join('poste_departement pd', 'pd.id = demandes.id_poste_departement')
            ->join('statut_demandes sd', 'sd.id = demandes.id_statut_actuel')
            ->where('demandes.id', $id)
            ->first();
    }

    public function demandesPourFinance(): array
    {
        return $this->select('demandes.id, demandes.date_creation, demandes.salaire_propose, demandes.nombre_demande, sd.libelle AS statut_libelle, p.libelle AS poste, dep.libelle AS departement')
            ->join('statut_demandes sd', 'sd.id = demandes.id_statut_actuel')
            ->join('poste_departement pd', 'pd.id = demandes.id_poste_departement')
            ->join('postes p', 'p.id = pd.id_poste')
            ->join('departements dep', 'dep.id = pd.id_departement')
            ->where('sd.libelle', 'EN_VALIDATION_FINANCE')
            ->orderBy('demandes.date_creation', 'DESC')
            ->findAll();
    }

    public function compterPourFinance(): int
    {
        return $this->join('statut_demandes sd', 'sd.id = demandes.id_statut_actuel')
            ->where('sd.libelle', 'EN_VALIDATION_FINANCE')
            ->countAllResults();
    }

    public function demandesPourDirection(): array
    {
        return $this->select('demandes.id, demandes.date_creation, demandes.salaire_propose, demandes.nombre_demande, sd.libelle AS statut_libelle, p.libelle AS poste, dep.libelle AS departement')
            ->join('statut_demandes sd', 'sd.id = demandes.id_statut_actuel')
            ->join('poste_departement pd', 'pd.id = demandes.id_poste_departement')
            ->join('postes p', 'p.id = pd.id_poste')
            ->join('departements dep', 'dep.id = pd.id_departement')
            ->where('sd.libelle', 'EN_VALIDATION_DIRECTION')
            ->orderBy('demandes.date_creation', 'DESC')
            ->findAll();
    }

    public function compterPourDirection(): int
    {
        return $this->join('statut_demandes sd', 'sd.id = demandes.id_statut_actuel')
            ->where('sd.libelle', 'EN_VALIDATION_DIRECTION')
            ->countAllResults();
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

    public function enregistrerPropositionSalaire(int $id, float $salaire, ?string $remarque, int $utilisateurId = 0): bool
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

        $financeUtilisateur = $this->db->table('utilisateurs')
            ->select('id')
            ->where('id_role', $finance['id'])
            ->get()
            ->getRowArray();

        if ($financeUtilisateur === null) {
            throw new \RuntimeException('Aucun utilisateur Finance trouvé.');
        }

        $statutFinance = $this->db->table('statut_demandes')
            ->where('libelle', 'EN_VALIDATION_FINANCE')
            ->get()->getRowArray();
        if ($statutFinance === null) {
            throw new \RuntimeException("Le statut 'EN_VALIDATION_FINANCE' est introuvable.");
        }

        $statutsRH = array_map('intval', array_column(
            $this->db->table('statut_demandes')->whereIn('libelle', ['cree', 'en_attente'])->get()->getResultArray(),
            'id'
        ));
        if (! in_array((int) $demande['id_statut_actuel'], $statutsRH, true)) {
            throw new \RuntimeException('Cette demande n’est pas en attente de validation RH.');
        }

        $this->db->transStart();
        $this->update($id, [
            'salaire_propose'       => $salaire,
            'id_utilisateur_actuel' => $financeUtilisateur['id'],
            'id_statut_actuel'      => $statutFinance['id'],
        ]);
        $this->db->table('historique_demandes')->insert([
            'id_demande'        => $id,
            'id_statut_demande' => $statutFinance['id'],
            'id_utilisateur'    => $utilisateurId ?: $demande['id_utilisateur_actuel'],
            'salaire_propose'   => $salaire,
            'remarque'          => $remarque ?: 'Demande validée par le RH et transmise à la Finance.',
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

    public function validerParRH(int $id, int $utilisateurId, float $salaire, ?string $remarque): bool
    {
        return $this->enregistrerPropositionSalaire($id, $salaire, $remarque, $utilisateurId);
    }

}
