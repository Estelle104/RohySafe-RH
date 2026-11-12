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
        return $this->db->table('candidatures c')->select('c.id, c.id_candidat, ca.nom, ca.prenom, ca.ville, o.titre AS titre_offre, p.libelle AS poste, dpt.libelle AS departement, pd.id AS id_poste_departement, tc.id AS id_type_contrat, tc.libelle AS type_contrat, dm.date_prise_poste, dm.salaire_propose, sc.libelle AS statut_candidature')
            ->join('statut_candidatures sc', 'sc.id = c.id_statut_actuel')
            ->join('candidats ca', 'ca.id = c.id_candidat')
            ->join('offres o', 'o.id = c.id_offre')
            ->join('demandes dm', 'dm.id = o.id_demande')
            ->join('poste_departement pd', 'pd.id = dm.id_poste_departement')
            ->join('postes p', 'p.id = pd.id_poste')
            ->join('departements dpt', 'dpt.id = pd.id_departement')
            ->join('type_contrats tc', 'tc.id = dm.id_type_contrat')
            ->where('c.id', $id)
            ->groupStart()
                ->where('sc.libelle', 'retenu')
                ->orWhere('sc.libelle', 'Embauche')
            ->groupEnd()
            ->get()->getRowArray();
    }

    public function genererContrat(int $id): int
    {
        $donnees = $this->getDonneesContrat($id);
        if ($donnees === null) throw new \DomainException('Cette candidature ne peut pas générer de contrat.');
        $this->db->transStart();
        $employe = $this->db->table('employes')->select('id')->where('id_candidat', $this->find($id)['id_candidat'])->get()->getRowArray();
        if ($employe === null) {
            $matricule = 'EMP-' . date('Y') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
            $this->db->table('employes')->insert([
                'id_candidat' => $this->find($id)['id_candidat'],
                'matricule' => $matricule,
                'date_embauche' => $donnees['date_prise_poste'] ?: date('Y-m-d'),
                'id_statut_actuel' => 1,
                'id_poste_departement' => $donnees['id_poste_departement'],
            ]);
            // Ne pas utiliser insertID() : le trigger des soldes de congés
            // peut insérer une ligne dans une autre table et modifier la séquence.
            $employe = $this->db->table('employes')
                ->select('id, matricule')
                ->where('id_candidat', $this->find($id)['id_candidat'])
                ->get()->getRowArray();
            if ($employe === null) throw new \RuntimeException('Employé créé introuvable.');
        }
        $statut = $this->db->table('statut_contrats')->select('id')->where('id', 1)->get()->getRowArray();
        if ($statut === null) throw new \RuntimeException('Le statut de contrat cree (id 1) est introuvable.');
        $this->db->table('contrats')->insert(['id_employe' => $employe['id'], 'id_type_contrat' => $donnees['id_type_contrat'], 'date_debut' => $donnees['date_prise_poste'] ?: date('Y-m-d'), 'date_fin' => null, 'salaire' => $donnees['salaire_propose'], 'id_statut_actuel' => $statut['id'], 'date_signature' => date('Y-m-d')]);
        $contrat = (int) $this->db->insertID(); $this->db->transComplete(); if (! $this->db->transStatus()) throw new \RuntimeException('Échec de génération du contrat.'); return $contrat;
    }

    public function accepterContratEtEmbaucher(int $idCandidature, ?int $idCandidat = null): array
    {
        $candidature = $this->find($idCandidature);
        if ($candidature === null) {
            throw new \DomainException('Candidature introuvable.');
        }
        if ($idCandidat !== null && (int) $candidature['id_candidat'] !== $idCandidat) {
            throw new \DomainException('Candidature non autorisée.');
        }

        $donnees = $this->getDonneesContrat($idCandidature);
        if ($donnees === null) {
            throw new \DomainException('Cette candidature ne peut pas accepter de contrat.');
        }

        $statutEmbauche = $this->db->table('statut_candidatures')->select('id')->where('libelle', 'Embauche')->get()->getRowArray();
        if ($statutEmbauche === null) {
            throw new \RuntimeException('Statut Embauche introuvable.');
        }

        $this->db->transStart();

        $employe = $this->db->table('employes')->select('id, matricule')->where('id_candidat', $candidature['id_candidat'])->get()->getRowArray();
        $matricule = $employe['matricule'] ?? null;
        if ($employe === null) {
            $matricule = 'EMP-' . date('Y') . '-' . str_pad((string) $idCandidature, 4, '0', STR_PAD_LEFT);
            $this->db->table('employes')->insert([
                'id_candidat' => $candidature['id_candidat'],
                'matricule' => $matricule,
                'date_embauche' => $donnees['date_prise_poste'] ?: date('Y-m-d'),
                'id_statut_actuel' => 1,
                'id_poste_departement' => $donnees['id_poste_departement'],
            ]);
            // Le trigger des soldes de congés peut modifier insertID().
            // Relire l'employé garantit d'utiliser son véritable identifiant.
            $employe = $this->db->table('employes')
                ->select('id, matricule')
                ->where('id_candidat', $candidature['id_candidat'])
                ->get()->getRowArray();
            if ($employe === null) throw new \RuntimeException('Employé créé introuvable.');
        }

        $statutContrat = $this->db->table('statut_contrats')->select('id')->where('id', 1)->get()->getRowArray();
        if ($statutContrat === null) {
            throw new \RuntimeException('Statut contrat introuvable.');
        }

        $contratExistant = $this->db->table('contrats')->where('id_employe', $employe['id'])->orderBy('id', 'DESC')->get()->getRowArray();
        if ($contratExistant === null) {
            $this->db->table('contrats')->insert([
                'id_employe' => $employe['id'],
                'id_type_contrat' => $donnees['id_type_contrat'],
                'date_debut' => $donnees['date_prise_poste'] ?: date('Y-m-d'),
                'date_fin' => null,
                'salaire' => $donnees['salaire_propose'],
                'id_statut_actuel' => $statutContrat['id'],
                'date_signature' => date('Y-m-d'),
            ]);
        }

        $this->update($idCandidature, ['id_statut_actuel' => (int) $statutEmbauche['id']]);

        $this->db->table('notifications_candidats')->insert([
            'id_candidat' => (int) $candidature['id_candidat'],
            'id_candidature' => $idCandidature,
            'id_offre' => (int) $candidature['id_offre'],
            'type' => 'proposition',
            'message' => "Contrat accepté. Vous êtes embauché(e). Matricule : {$matricule}.",
            'lu' => false,
            'date_creation' => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new \RuntimeException("Échec de l'acceptation du contrat.");
        }

        return ['matricule' => $matricule, 'statut' => 'Embauche'];
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
