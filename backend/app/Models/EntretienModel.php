<?php

namespace App\Models;

use CodeIgniter\Model;

class EntretienModel extends Model
{
    protected $table         = 'entretiens';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_candidature', 'date_entretien', 'lieu_entretien', 'id_statut_actuel', 'commentaire'];

    public function getOffresAvecEntretiens(): array
    {
        return $this->db->table('offres o')
            ->select('o.id, o.titre, COUNT(DISTINCT c.id) AS nb_candidats, MIN(e.date_entretien) AS prochaine_date')
            ->join('candidatures c', 'c.id_offre = o.id')
            ->join('entretiens e', 'e.id_candidature = c.id')
            ->groupBy('o.id, o.titre')
            ->orderBy('prochaine_date', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getOffre(int $idOffre): ?array
    {
        return $this->db->table('offres')->select('id, titre')->where('id', $idOffre)->get()->getRowArray();
    }

    public function getCandidatsParOffre(int $idOffre): array
    {
        $lignes = $this->requeteCandidats()
            ->where('c.id_offre', $idOffre)
            ->orderBy('e.date_entretien', 'DESC')
            ->orderBy('e.id', 'DESC')
            ->get()
            ->getResultArray();

        $candidats = [];
        foreach ($lignes as $ligne) {
            $idCandidature = (int) $ligne['id_candidature'];
            if (! isset($candidats[$idCandidature])) {
                $candidats[$idCandidature] = $ligne;
            }
        }

        return array_values($candidats);
    }

    public function getDetailCandidature(int $idCandidature): ?array
    {
        return $this->requeteCandidats()
            ->where('c.id', $idCandidature)
            ->orderBy('e.date_entretien', 'DESC')
            ->orderBy('e.id', 'DESC')
            ->get()
            ->getRowArray();
    }

    public function enregistrerEvaluation(int $idCandidature, array $data): array
    {
        $note = $data['note'] ?? null;
        if ($note === null || $note === '' || ! is_numeric($note)) {
            throw new \DomainException('La note est obligatoire.');
        }

        $note = round((float) $note, 2);
        if ($note < 0 || $note > 20) {
            throw new \DomainException('La note doit être comprise entre 0 et 20.');
        }

        $candidature = $this->db->table('candidatures')->select('id')->where('id', $idCandidature)->get()->getRowArray();
        if ($candidature === null) {
            throw new \DomainException('Candidature introuvable.');
        }

        $entretien = $this->db->table('entretiens')
            ->select('id, id_statut_actuel')
            ->where('id_candidature', $idCandidature)
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();
        if ($entretien === null) {
            throw new \DomainException('Aucun entretien n’est associé à cette candidature.');
        }

        $statutRealise = $this->db->table('statut_entretiens')->select('id')->where('libelle', 'realise')->get()->getRowArray();
        if ($statutRealise === null) {
            throw new \RuntimeException('Statut d’entretien réalisé introuvable.');
        }

        $remarques = trim((string) ($data['remarques'] ?? ''));
        $maintenant = date('Y-m-d H:i:s');
        $evaluation = $this->db->table('evaluations_candidat')->where('id_candidature', $idCandidature)->get()->getRowArray();

        $this->db->transStart();

        if ($evaluation === null) {
            $this->db->table('evaluations_candidat')->insert([
                'id_candidature' => $idCandidature,
                'note' => $note,
                'remarques' => $remarques,
                'date_evaluation' => $maintenant,
            ]);
        } else {
            $this->db->table('evaluations_candidat')->where('id_candidature', $idCandidature)->update([
                'note' => $note,
                'remarques' => $remarques,
                'date_evaluation' => $maintenant,
            ]);
        }

        if ((int) ($entretien['id_statut_actuel'] ?? 0) !== (int) $statutRealise['id']) {
            $this->db->table('entretiens')->where('id', $entretien['id'])->update([
                'id_statut_actuel' => $statutRealise['id'],
            ]);
            $this->db->table('historique_entretiens_statut')->insert([
                'id_entretien' => $entretien['id'],
                'id_statut_entretien' => $statutRealise['id'],
            ]);
        }

        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Échec de l’enregistrement de l’évaluation.');
        }

        return $this->db->table('evaluations_candidat')->where('id_candidature', $idCandidature)->get()->getRowArray();
    }

    private function requeteCandidats()
    {
        return $this->db->table('entretiens e')
            ->select(
                'c.id AS id_candidature, ca.id AS id_candidat, ca.nom, ca.prenom, ca.email, '
                . 'ca.telephone, ca.ville, o.id AS id_offre, o.titre AS offre, '
                . 'e.id AS id_entretien, e.date_entretien, e.lieu_entretien, '
                . 'e.commentaire AS commentaire_entretien, se.libelle AS statut_entretien, '
                . 'ec.note, ec.remarques, ec.date_evaluation'
            )
            ->join('candidatures c', 'c.id = e.id_candidature')
            ->join('candidats ca', 'ca.id = c.id_candidat')
            ->join('offres o', 'o.id = c.id_offre')
            ->join('statut_entretiens se', 'se.id = e.id_statut_actuel', 'left')
            ->join('evaluations_candidat ec', 'ec.id_candidature = c.id', 'left');
    }
}
