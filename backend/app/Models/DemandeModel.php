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
        $id = $this->insert([
            'id_poste_departement' => (int) $data['id_poste_departement'],
            'id_createur' => $utilisateurId,
            'id_utilisateur_actuel' => $utilisateurId,
            'id_statut_actuel' => (int) $statut['id'],
            'nombre_demande' => (int) $data['nombre_demande'],
            'id_type_contrat' => (int) $data['id_type_contrat'],
            'date_prise_poste' => empty($data['date_prise_poste']) ? null : $data['date_prise_poste'],
            'salaire_propose' => $data['salaire_propose'],
        ], true);
        return $id ? ['id' => $id] : ['erreur' => $this->errors() ?: 'Impossible de créer la demande.'];
    }



    function getContratDemande($idDemande)
    {
        return $this->db->table('demandes d')
            ->select('tc.libelle')
            ->join('type_contrats tc', 'tc.id = d.id_type_contrat')
            ->where('d.id', $idDemande)
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
}
