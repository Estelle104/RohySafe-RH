<?php

namespace App\Models;

use CodeIgniter\Model;

class ExperienceCandidatureModel extends Model
{
    protected $table         = 'experiences_candidature';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'id_candidature',
        'id_exigence',
        'date_debut',
        'date_fin',
    ];

    public function getByCandidature(int $idCandidature): array
    {
        if ($idCandidature <= 0) {
            return [];
        }

        $experiences = $this->db->table('experiences_candidature ec')
            ->select(
                'ec.id, ec.id_candidature, ec.id_exigence, ec.date_debut, ec.date_fin, ' .
                'ec.duree_jours, ee.duree_min, ee.duree_max, ' .
                '(ee.duree_min * 30) AS duree_min_jours, ee.obligatoire, ' .
                'ee.id_poste_experience, p.libelle AS poste'
            )
            ->join('exigences_experience ee', 'ee.id = ec.id_exigence')
            ->join('postes p', 'p.id = ee.id_poste_experience')
            ->where('ec.id_candidature', $idCandidature)
            ->orderBy('ee.obligatoire', 'DESC')
            ->orderBy('p.libelle', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($experiences as &$experience) {
            $experience['obligatoire'] = in_array(
                $experience['obligatoire'],
                [true, 1, '1', 't', 'true'],
                true
            );
        }
        unset($experience);

        return $experiences;
    }
}
