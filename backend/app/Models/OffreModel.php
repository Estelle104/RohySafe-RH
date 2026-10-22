<?php

namespace App\Models;

use CodeIgniter\Model;

class OffreModel extends Model
{
    protected $table         = 'offres';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'id_demande',
        'titre',
        'descriptions',
        'date_publication',
        'date_limite',
        'id_statut',
    ];

    /**
     * Offres publiées et non expirées, avec les informations de la demande.
     *
     * @return list<array<string, mixed>>
     */
    public function getOffresPubliees(): array
    {
        return $this->db->table('offres o')
            ->select('o.id, d.libelle AS departement, o.titre, o.descriptions, o.date_publication, o.date_limite, tc.libelle AS type_contrat, NULL AS ville, dm.nombre_demande')
            ->join('statut_offres so', 'so.id = o.id_statut')
            ->join('demandes dm', 'dm.id = o.id_demande')
            ->join('poste_departement pd', 'pd.id = dm.id_poste_departement')
            ->join('departements d', 'd.id = pd.id_departement')
            ->join('type_contrats tc', 'tc.id = dm.id_type_contrat')
            ->where('so.libelle', 'publiee')
            ->groupStart()
            ->where('o.date_limite IS NULL', null, false)
            ->orWhere('o.date_limite >=', date('Y-m-d'))
            ->groupEnd()
            ->orderBy('o.date_publication', 'DESC')
            ->get()
            ->getResultArray();
    }

    /** @return list<array<string, mixed>> */
    public function getDemandesAPublier(): array
    {
        return $this->db->table('demandes dm')
            ->select('dm.id, dm.nombre_demande, dm.date_prise_poste, dm.salaire_propose, p.libelle AS poste, d.libelle AS departement, tc.libelle AS type_contrat')
            ->join('poste_departement pd', 'pd.id = dm.id_poste_departement')
            ->join('postes p', 'p.id = pd.id_poste')
            ->join('departements d', 'd.id = pd.id_departement')
            ->join('type_contrats tc', 'tc.id = dm.id_type_contrat')
            ->join('utilisateurs createur', 'createur.id = dm.id_createur')
            ->join('roles role_createur', 'role_createur.id = createur.id_role')
            ->join('utilisateurs actuel', 'actuel.id = dm.id_utilisateur_actuel')
            ->join('roles role_actuel', 'role_actuel.id = actuel.id_role')
            ->join('statut_demandes sd', 'sd.id = dm.id_statut_actuel')
            ->join('offres o', 'o.id_demande = dm.id', 'left')
            ->where('role_createur.libelle', 'DG')
            ->where('role_actuel.libelle', 'RH')
            ->where('sd.libelle', 'valide')
            ->where('o.id IS NULL', null, false)
            ->orderBy('dm.date_creation', 'DESC')
            ->get()->getResultArray();
    }

    /** @return array<string, mixed>|null */
    public function getOffreDetail(int $id): ?array
    {
        return $this->db->table('offres o')
            ->select('o.id, o.titre, o.descriptions, o.date_publication, o.date_limite, d.libelle AS departement, p.libelle AS poste, tc.libelle AS type_contrat, dm.nombre_demande')
            ->join('demandes dm', 'dm.id = o.id_demande')->join('poste_departement pd', 'pd.id = dm.id_poste_departement')
            ->join('postes p', 'p.id = pd.id_poste')->join('departements d', 'd.id = pd.id_departement')
            ->join('type_contrats tc', 'tc.id = dm.id_type_contrat')->where('o.id', $id)->get()->getRowArray();
    }

    public function publierDemande(array $data): int
    {
        $demandePossible = false;
        foreach ($this->getDemandesAPublier() as $demande) {
            if ((int) $demande['id'] === (int) $data['id_demande']) { $demandePossible = true; break; }
        }
        if (! $demandePossible) throw new \DomainException('Cette demande ne peut pas être publiée.');
        $statut = $this->db->table('statut_offres')->select('id')->where('libelle', 'publiee')->get()->getRowArray();
        if ($statut === null) throw new \RuntimeException('Statut publiee introuvable.');
        $this->insert(['id_demande' => (int) $data['id_demande'], 'titre' => trim($data['titre']), 'descriptions' => trim($data['descriptions'] ?? ''), 'date_publication' => $data['date_publication'], 'date_limite' => $data['date_limite'], 'id_statut' => $statut['id']]);
        return (int) $this->getInsertID();
    }
}
