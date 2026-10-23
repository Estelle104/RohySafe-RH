<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidatureModel extends Model
{
    protected $table         = 'candidatures';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_candidat', 'id_offre', 'id_statut_actuel'];

    public function getListe(): array
    {
        return $this->db->table('candidatures c')->select('c.id, ca.nom, ca.prenom, ca.email, o.titre AS offre, sc.libelle AS statut, c.date_candidature')
            ->join('candidats ca', 'ca.id = c.id_candidat')->join('offres o', 'o.id = c.id_offre')->join('statut_candidatures sc', 'sc.id = c.id_statut_actuel')
            ->orderBy('c.date_candidature', 'DESC')->get()->getResultArray();
    }

    public function creerEntretien(int $id, array $data): int
    {
        $candidature = $this->find($id);
        $statutActuel = $candidature ? $this->db->table('statut_candidatures')->select('libelle')->where('id', $candidature['id_statut_actuel'])->get()->getRowArray() : null;
        if ($statutActuel === null || $statutActuel['libelle'] !== 'preselectionne') throw new \DomainException('Cette candidature ne peut pas recevoir un entretien.');
        $statutEntretien = $this->db->table('statut_candidatures')->select('id')->where('libelle', 'entretien')->get()->getRowArray();
        if ($statutEntretien === null) throw new \RuntimeException('Statut entretien introuvable.');
        $this->db->transStart();
        $this->db->table('historique_candidature_status')->insert(['id_candidature' => $id, 'id_statut_candidature' => $candidature['id_statut_actuel']]);
        $this->update($id, ['id_statut_actuel' => $statutEntretien['id']]);
        $this->db->table('entretiens')->insert(['id_candidature' => $id, 'date_entretien' => $data['date_entretien'], 'lieu_entretien' => trim($data['lieu_entretien'] ?? ''), 'commentaire' => trim($data['commentaire'] ?? '')]);
        $entretienId = (int) $this->db->insertID(); $this->db->transComplete();
        if (! $this->db->transStatus()) throw new \RuntimeException('Échec de création de l’entretien.');
        return $entretienId;
    }
}
