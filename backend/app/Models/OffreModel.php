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
}
