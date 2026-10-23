<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidatModel extends Model
{
    protected $table         = 'candidats';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['nom', 'prenom', 'email', 'telephone', 'ville', 'login', 'mdp'];

    public function findByIdentifiant(string $identifiant): ?array
    {
        return $this->groupStart()
            ->where('login', $identifiant)
            ->orWhere('email', $identifiant)
            ->groupEnd()
            ->first();
    }

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
            ->select('pd.id AS id_poste_departement, pd.id_poste')
            ->join('demandes dm', 'dm.id = o.id_demande')
            ->join('poste_departement pd', 'pd.id = dm.id_poste_departement')
            ->where('o.id', $idOffre)
            ->get()
            ->getRowArray();

        if ($poste === null) {
            return [];
        }

        $candidatures = $this->db->table('candidatures')
            ->select('id')
            ->where('id_offre', $idOffre)
            ->get()
            ->getResultArray();

        if ($candidatures === []) {
            return [];
        }

        $idsCandidatures = [];
        foreach ($candidatures as $candidature) {
            $idsCandidatures[(int) $candidature['id']] = true;
        }

        $candidats = array_values(array_filter(
            $this->getCandidatsIdeauxByPoste((int) $poste['id_poste']),
            static fn (array $candidat): bool => isset(
                $idsCandidatures[(int) ($candidat['id_candidature'] ?? 0)]
            )
        ));

        if ($candidats === []) {
            return [];
        }

        $entretiensParCandidature = [];
        $entretiens = $this->db->table('entretiens e')
            ->select('e.id AS id_entretien, e.id_candidature, e.date_entretien, e.lieu_entretien, se.libelle AS statut_entretien, ec.note AS note_evaluation, ec.remarques AS remarques_evaluation')
            ->join('statut_entretiens se', 'se.id = e.id_statut_actuel', 'left')
            ->join('evaluations_candidat ec', 'ec.id_candidature = e.id_candidature', 'left')
            ->whereIn('e.id_candidature', array_keys($idsCandidatures))
            ->orderBy('e.date_entretien', 'DESC')
            ->orderBy('e.id', 'DESC')
            ->get()
            ->getResultArray();

        foreach ($entretiens as $entretien) {
            $idCandidature = (int) $entretien['id_candidature'];
            if (! isset($entretiensParCandidature[$idCandidature])) {
                $entretiensParCandidature[$idCandidature] = $entretien;
            }
        }

        foreach ($candidats as &$candidat) {
            $idCandidature = (int) ($candidat['id_candidature'] ?? 0);
            $entretien = $entretiensParCandidature[$idCandidature] ?? null;

            $candidat['id_candidature'] = $idCandidature;
            $candidat['entretien_cree'] = $entretien !== null;
            $candidat['id_entretien'] = $entretien['id_entretien'] ?? null;
            $candidat['date_entretien'] = $entretien['date_entretien'] ?? null;
            $candidat['lieu_entretien'] = $entretien['lieu_entretien'] ?? null;
            $candidat['statut_entretien'] = $entretien['statut_entretien'] ?? null;
            $candidat['note_evaluation'] = $entretien['note_evaluation'] ?? null;
            $candidat['remarques_evaluation'] = $entretien['remarques_evaluation'] ?? null;
        }
        unset($candidat);

        return $this->filtrerParExperiences(
            $candidats,
            (int) $poste['id_poste_departement']
        );
    }

    private function filtrerParExperiences(array $candidats, int $idPosteDepartement): array
    {
        $exigences = $this->db->table('exigences_experience ee')
            ->select(
                'ee.id, ee.duree_min, ee.duree_max, ee.obligatoire, ' .
                'p.libelle AS poste, (ee.duree_min * 30) AS duree_min_jours'
            )
            ->join('postes p', 'p.id = ee.id_poste_experience')
            ->where('ee.id_poste_departement', $idPosteDepartement)
            ->orderBy('ee.obligatoire', 'DESC')
            ->orderBy('p.libelle', 'ASC')
            ->get()
            ->getResultArray();

        $obligatoires = array_values(array_filter(
            $exigences,
            static fn (array $exigence): bool => in_array(
                $exigence['obligatoire'],
                [true, 1, '1', 't', 'true'],
                true
            )
        ));

        if ($obligatoires === []) {
            foreach ($candidats as &$candidat) {
                $candidat['experience_ok'] = true;
                $candidat['experiences_manquantes'] = [];
            }
            unset($candidat);

            return $candidats;
        }

        $modele = new ExperienceCandidatureModel();
        $resultat = [];

        foreach ($candidats as $candidat) {
            $idCandidature = (int) ($candidat['id_candidature'] ?? 0);
            $reponses = $idCandidature > 0
                ? $modele->getByCandidature($idCandidature)
                : [];

            $durees = [];
            foreach ($reponses as $reponse) {
                $durees[(int) $reponse['id_exigence']] = (int) $reponse['duree_jours'];
            }

            $manquantes = [];
            foreach ($obligatoires as $exigence) {
                $idExigence = (int) $exigence['id'];
                $dureeMin = (int) $exigence['duree_min_jours'];

                if ($dureeMin > 0 && (!isset($durees[$idExigence]) || $durees[$idExigence] < $dureeMin)) {
                    $manquantes[] = $exigence['poste'];
                }
            }

            $candidat['experience_ok'] = $manquantes === [];
            $candidat['experiences_manquantes'] = $manquantes;

            if ($manquantes === []) {
                $resultat[] = $candidat;
            }
        }

        return $resultat;
    }



    public function getCv(int $idCandidat, ?int $idOffre = null): ?array
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

        if ($idOffre !== null && $idOffre > 0) {
            $candidature = $this->db->table('candidatures')
                ->select('id')
                ->where('id_candidat', $idCandidat)
                ->where('id_offre', $idOffre)
                ->get()
                ->getRowArray();

            $idCandidature = $candidature === null ? null : (int) $candidature['id'];
            $cv['id_candidature'] = $idCandidature;
            $cv['experiences_exigences'] = $idCandidature === null
                ? []
                : (new ExperienceCandidatureModel())->getByCandidature($idCandidature);
            $cv['competences'] = $idCandidature === null
                ? []
                : $this->getCompetencesByCandidature($idCandidature);
        }

        return $cv;
    }

    private function getCompetencesByCandidature(int $idCandidature): array
    {
        return $this->db->table('competences_candidat cc')
            ->select('cc.id, cc.id_competence, comp.libelle AS competence, comp.categorie, cc.niveau')
            ->join('competences comp', 'comp.id = cc.id_competence')
            ->where('cc.id_candidature', $idCandidature)
            ->orderBy('comp.libelle', 'ASC')
            ->get()
            ->getResultArray();
    }
}
