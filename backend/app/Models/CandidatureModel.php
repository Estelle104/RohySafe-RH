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
        return $this->db->table('candidatures c')->select('c.id, c.id_offre, ca.nom, ca.prenom, ca.email, o.titre AS offre, sc.libelle AS statut, c.date_candidature')
            ->join('candidats ca', 'ca.id = c.id_candidat')->join('offres o', 'o.id = c.id_offre')->join('statut_candidatures sc', 'sc.id = c.id_statut_actuel')
            ->orderBy('c.date_candidature', 'DESC')->get()->getResultArray();
    }

    public function creerEntretien(int $id, array $data): int
    {
        $candidature = $this->find($id);
        if ($candidature === null) throw new \DomainException('Candidature introuvable.');

        $entretienExistant = $this->db->table('entretiens')->select('id')->where('id_candidature', $id)->get()->getRowArray();
        if ($entretienExistant !== null) throw new \DomainException('Un entretien existe déjà pour cette candidature.');

        $statutActuel = $this->db->table('statut_candidatures')->select('libelle')->where('id', $candidature['id_statut_actuel'])->get()->getRowArray();
        if ($statutActuel === null || ! in_array($statutActuel['libelle'], ['en_attente', 'preselectionne', 'selectionne'], true)) {
            throw new \DomainException('Cette candidature ne peut pas recevoir un entretien.');
        }

        $statutCandidatureEntretien = $this->db->table('statut_candidatures')->select('id')->where('libelle', 'entretien')->get()->getRowArray();
        $statutEntretienPlanifie = $this->db->table('statut_entretiens')->select('id')->where('libelle', 'planifie')->get()->getRowArray();
        if ($statutCandidatureEntretien === null || $statutEntretienPlanifie === null) {
            throw new \RuntimeException('Statuts d’entretien introuvables.');
        }

        $this->db->transStart();
        $this->db->table('historique_candidature_status')->insert([
            'id_candidature' => $id,
            'id_statut_candidature' => $candidature['id_statut_actuel'],
        ]);
        $this->update($id, ['id_statut_actuel' => $statutCandidatureEntretien['id']]);
        $this->db->table('entretiens')->insert([
            'id_candidature' => $id,
            'date_entretien' => $data['date_entretien'],
            'lieu_entretien' => trim((string) ($data['lieu_entretien'] ?? '')),
            'id_statut_actuel' => $statutEntretienPlanifie['id'],
            'commentaire' => trim((string) ($data['commentaire'] ?? '')),
        ]);
        $entretienId = (int) $this->db->insertID();
        $this->db->table('historique_entretiens_statut')->insert([
            'id_entretien' => $entretienId,
            'id_statut_entretien' => $statutEntretienPlanifie['id'],
        ]);
        $this->db->transComplete();

        if (! $this->db->transStatus()) throw new \RuntimeException('Échec de création de l’entretien.');

        return $entretienId;
    }

    public function getDonneesContrat(int $id): ?array
    {
        return $this->db->table('candidatures c')->select('c.id, ca.nom, ca.prenom, ca.ville, o.titre AS titre_offre, p.libelle AS poste, dpt.libelle AS departement, tc.id AS id_type_contrat, tc.libelle AS type_contrat, dm.date_prise_poste, dm.salaire_propose')
            ->join('statut_candidatures sc', 'sc.id = c.id_statut_actuel')->join('candidats ca', 'ca.id = c.id_candidat')->join('offres o', 'o.id = c.id_offre')->join('demandes dm', 'dm.id = o.id_demande')->join('poste_departement pd', 'pd.id = dm.id_poste_departement')->join('postes p', 'p.id = pd.id_poste')->join('departements dpt', 'dpt.id = pd.id_departement')->join('type_contrats tc', 'tc.id = dm.id_type_contrat')->where('c.id', $id)->where('sc.libelle', 'retenu')->get()->getRowArray();
    }

    public function genererContrat(int $id): int
    {
        $donnees = $this->getDonneesContrat($id);
        if ($donnees === null) throw new \DomainException('Cette candidature ne peut pas générer de contrat.');
        $this->db->transStart();
        $employe = $this->db->table('employes')->select('id')->where('id_candidat', $this->find($id)['id_candidat'])->get()->getRowArray();
        if ($employe === null) {
            $matricule = 'EMP-' . date('Y') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
            $this->db->table('employes')->insert(['id_candidat' => $this->find($id)['id_candidat'], 'matricule' => $matricule, 'date_embauche' => $donnees['date_prise_poste'] ?: date('Y-m-d')]);
            $employe = ['id' => $this->db->insertID()];
        }
        $statut = $this->db->table('statut_contrats')->select('id')->where('id', 1)->get()->getRowArray();
        if ($statut === null) throw new \RuntimeException('Le statut de contrat cree (id 1) est introuvable.');
        $this->db->table('contrats')->insert(['id_employe' => $employe['id'], 'id_type_contrat' => $donnees['id_type_contrat'], 'date_debut' => $donnees['date_prise_poste'] ?: date('Y-m-d'), 'date_fin' => null, 'salaire' => $donnees['salaire_propose'], 'id_statut_actuel' => $statut['id'], 'date_signature' => date('Y-m-d')]);
        $contrat = (int) $this->db->insertID(); $this->db->transComplete(); if (! $this->db->transStatus()) throw new \RuntimeException('Échec de génération du contrat.'); return $contrat;
    }

    public function updateStatutCandidatureEngage($id){
        $data = [];
        $data['id_statut_actuel'] = 5;
        $a = $this->update($id,$data);
        return $a;
    }

    public function getByCandidat(int $idCandidat): array
    {
        return $this->db->table('candidatures c')
            ->select('c.id_offre')
            ->where('c.id_candidat', $idCandidat)
            ->get()
            ->getResultArray();
    }
}
