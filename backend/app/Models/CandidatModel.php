<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidatModel extends Model
{
    protected $table         = 'candidats';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['nom', 'prenom', 'email', 'telephone', 'ville'];

    public function getCandidatsIdeauxByPoste(int $idPoste): array
    {
        if ($idPoste <= 0) {
            return [];
        }

        return $this->db->table('vue_classement_candidats_postes')
            ->where('id_poste', $idPoste)
            ->orderBy('score_total', 'DESC')
            ->orderBy('nom', 'ASC')
            ->orderBy('prenom', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getCandidatsIdeauxByDemande(int $idDemande): array
    {
        $poste = $this->db->table('demandes dm')
            ->select('pd.id_poste')
            ->join('poste_departement pd', 'pd.id = dm.id_poste_departement')
            ->where('dm.id', $idDemande)
            ->get()
            ->getRowArray();

        return $poste === null
            ? []
            : $this->getCandidatsIdeauxByPoste((int) $poste['id_poste']);
    }

    public function getCandidatsIdeauxByOffre(int $idOffre): array
    {
        $poste = $this->db->table('offres o')
            ->select('pd.id_poste')
            ->join('demandes dm', 'dm.id = o.id_demande')
            ->join('poste_departement pd', 'pd.id = dm.id_poste_departement')
            ->where('o.id', $idOffre)
            ->get()
            ->getRowArray();

        if ($poste === null) {
            return [];
        }

        $candidats = $this->getCandidatsIdeauxByPoste((int) $poste['id_poste']);
        $candidatures = $this->db->table('candidatures')
            ->select('id, id_candidat')
            ->where('id_offre', $idOffre)
            ->get()
            ->getResultArray();

        $idsParCandidat = [];
        foreach ($candidatures as $candidature) {
            $idsParCandidat[(int) $candidature['id_candidat']] = (int) $candidature['id'];
        }

        foreach ($candidats as &$candidat) {
            $candidat['id_candidature'] = $idsParCandidat[(int) $candidat['id_candidat']] ?? null;
        }
        unset($candidat);

        return $candidats;
    }



    public function getCv(int $idCandidat): ?array
    {
        $cv = $this->db->table('vue_cv_candidats')
            ->where('id_candidat', $idCandidat)
            ->get()
            ->getRowArray();

        if ($cv === null) {
            return null;
        }

        foreach (['experiences', 'formations', 'competences', 'documents'] as $champ) {
            $cv[$champ] = json_decode((string) $cv[$champ], true, 512, JSON_THROW_ON_ERROR);
        }

        return $cv;
    }
}
