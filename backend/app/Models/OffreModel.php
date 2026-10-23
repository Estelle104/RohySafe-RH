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


    public function getOffresBackoffice(): array
    {
        return $this->db->table('offres o')
            ->select(
                'o.id, o.titre, o.descriptions, o.date_publication, o.date_limite, ' .
                'd.libelle AS departement, tc.libelle AS type_contrat, ' .
                'dm.nombre_demande, so.libelle AS statut, ' .
                '(SELECT COUNT(*) FROM exigences_experience ee ' .
                'WHERE ee.id_poste_departement = pd.id) AS nb_exigences_experience, ' .
                '(SELECT COUNT(*) FROM criteres_poste cp ' .
                'WHERE cp.id_poste = pd.id_poste) AS nb_competences_requises'
            )
            ->join('statut_offres so', 'so.id = o.id_statut', 'left')
            ->join('demandes dm', 'dm.id = o.id_demande')
            ->join('poste_departement pd', 'pd.id = dm.id_poste_departement')
            ->join('departements d', 'd.id = pd.id_departement')
            ->join('type_contrats tc', 'tc.id = dm.id_type_contrat')
            ->orderBy('o.date_publication', 'DESC')
            ->orderBy('o.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getDetail(int $idOffre): ?array
    {
        $offre = $this->db->table('offres o')
            ->select(
                'o.id, o.id_demande, o.titre, o.descriptions, o.date_publication, ' .
                'o.date_limite, d.libelle AS departement, tc.libelle AS type_contrat, ' .
                'dm.nombre_demande, dm.date_prise_poste, dm.salaire_propose, ' .
                'pd.id AS id_poste_departement, pd.id_poste'
            )
            ->join('demandes dm', 'dm.id = o.id_demande')
            ->join('poste_departement pd', 'pd.id = dm.id_poste_departement')
            ->join('departements d', 'd.id = pd.id_departement')
            ->join('type_contrats tc', 'tc.id = dm.id_type_contrat')
            ->where('o.id', $idOffre)
            ->get()
            ->getRowArray();

        if ($offre === null) {
            return null;
        }

        $offre['exigences_experience'] = $this->db->table('exigences_experience ee')
            ->select(
                'ee.id, ee.id_poste_experience, p.libelle AS poste, ' .
                'ee.duree_min, ee.duree_max, ee.obligatoire'
            )
            ->join('postes p', 'p.id = ee.id_poste_experience')
            ->where('ee.id_poste_departement', $offre['id_poste_departement'])
            ->orderBy('ee.obligatoire', 'DESC')
            ->get()
            ->getResultArray();

        $offre['competences_requises'] = $this->db->table('criteres_poste cp')
            ->select(
                'cp.id, cp.id_competence, c.libelle AS competence, c.categorie, ' .
                'cp.note_minimale, cp.obligatoire'
            )
            ->join('competences c', 'c.id = cp.id_competence')
            ->where('cp.id_poste', $offre['id_poste'])
            ->orderBy('cp.obligatoire', 'DESC')
            ->orderBy('c.libelle', 'ASC')
            ->get()
            ->getResultArray();

        foreach (['exigences_experience', 'competences_requises'] as $section) {
            foreach ($offre[$section] as &$ligne) {
                $ligne['obligatoire'] = $this->estVrai($ligne['obligatoire'] ?? false);
            }
            unset($ligne);
        }

        return $offre;
    }

    private function estVrai(mixed $valeur): bool
    {
        return in_array($valeur, [true, 1, '1', 't', 'true'], true);
    }
    
    /** @return list<array<string, mixed>> */
    public function getDemandesAPublier(?int $rhId = null): array
    {
        $builder = $this->db->table('demandes dm')
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
            ->where('role_actuel.libelle', 'RH')
            ->where('sd.libelle', 'valide')
            ->where('o.id IS NULL', null, false);

        if ($rhId !== null) {
            $builder->where('dm.id_utilisateur_actuel', $rhId);
        }

        return $builder->orderBy('dm.date_creation', 'DESC')
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

    public function publierDemande(array $data, ?int $rhId = null): int
    {
        $demandePossible = false;
        foreach ($this->getDemandesAPublier($rhId) as $demande) {
            if ((int) $demande['id'] === (int) $data['id_demande']) { $demandePossible = true; break; }
        }
        if (! $demandePossible) throw new \DomainException('Cette demande ne peut pas être publiée.');
        $statut = $this->db->table('statut_offres')->select('id')->where('libelle', 'publiee')->get()->getRowArray();
        if ($statut === null) throw new \RuntimeException('Statut publiee introuvable.');
        $this->insert(['id_demande' => (int) $data['id_demande'], 'titre' => trim($data['titre']), 'descriptions' => trim($data['descriptions'] ?? ''), 'date_publication' => $data['date_publication'], 'date_limite' => $data['date_limite'], 'id_statut' => $statut['id']]);
        return (int) $this->getInsertID();
    }
}
