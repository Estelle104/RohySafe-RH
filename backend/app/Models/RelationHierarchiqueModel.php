<?php

namespace App\Models;

use CodeIgniter\Model;
use DomainException;

class RelationHierarchiqueModel extends Model
{
    protected $table         = 'relation_hierarchiques';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_superieur', 'id_subordonne', 'date_debut', 'date_fin'];

    public function getEmployes(): array
    {
        return $this->db->table('employes e')
            ->select("e.id, e.matricule, e.date_embauche, c.nom, c.prenom, CONCAT(c.prenom, ' ', c.nom) AS nom_complet")
            ->join('candidats c', 'c.id = e.id_candidat')
            ->orderBy('c.nom', 'ASC')
            ->orderBy('c.prenom', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getRelations(bool $activesSeulement = false): array
    {
        $builder = $this->db->table('relation_hierarchiques r')
            ->select("r.id, r.id_superieur, r.id_subordonne, r.date_debut, r.date_fin,
                CONCAT(cs.prenom, ' ', cs.nom) AS superieur,
                CONCAT(cb.prenom, ' ', cb.nom) AS subordonne,
                es.matricule AS matricule_superieur, eb.matricule AS matricule_subordonne")
            ->join('employes es', 'es.id = r.id_superieur')
            ->join('candidats cs', 'cs.id = es.id_candidat')
            ->join('employes eb', 'eb.id = r.id_subordonne')
            ->join('candidats cb', 'cb.id = eb.id_candidat')
            ->orderBy('r.date_debut', 'DESC')
            ->orderBy('r.id', 'DESC');

        if ($activesSeulement) {
            $builder->where('r.date_debut <=', date('Y-m-d'))
                ->groupStart()
                ->where('r.date_fin IS NULL', null, false)
                ->orWhere('r.date_fin >=', date('Y-m-d'))
                ->groupEnd();
        }

        return $builder->get()->getResultArray();
    }

    public function createRelation(array $data): int
    {
        $superieur = (int) $data['id_superieur'];
        $subordonne = (int) $data['id_subordonne'];
        $dateDebut = (string) $data['date_debut'];

        if ($superieur === $subordonne) {
            throw new DomainException('Un employé ne peut pas être son propre supérieur.');
        }

        if ($this->db->table('employes')->whereIn('id', [$superieur, $subordonne])->countAllResults() !== 2) {
            throw new DomainException('Les deux employés sélectionnés sont introuvables.');
        }

        $relationActive = $this->db->table($this->table)
            ->where('id_subordonne', $subordonne)
            ->where('date_debut <=', date('Y-m-d'))
            ->groupStart()
            ->where('date_fin IS NULL', null, false)
            ->orWhere('date_fin >=', date('Y-m-d'))
            ->groupEnd()
            ->orderBy('date_debut', 'DESC')
            ->get()
            ->getRowArray();

        $this->db->transStart();
        if ($relationActive !== null) {
            if ($dateDebut <= $relationActive['date_debut']) {
                throw new DomainException('La nouvelle affectation doit commencer après l’affectation actuelle.');
            }

            $dateFin = date('Y-m-d', strtotime($dateDebut . ' -1 day'));
            $this->update((int) $relationActive['id'], ['date_fin' => $dateFin]);
        }

        $this->insert([
            'id_superieur'  => $superieur,
            'id_subordonne' => $subordonne,
            'date_debut'    => $dateDebut,
            'date_fin'      => $data['date_fin'] ?? null,
        ]);
        $id = (int) $this->getInsertID();
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new DomainException('Impossible d’enregistrer la relation hiérarchique.');
        }

        return $id;
    }

    public function closeRelation(int $id, string $dateFin): bool
    {
        $relation = $this->find($id);
        if ($relation === null) {
            return false;
        }
        if ($relation['date_fin'] !== null) {
            throw new DomainException('Cette relation est déjà clôturée.');
        }
        if ($dateFin < $relation['date_debut']) {
            throw new DomainException('La date de fin doit être postérieure ou égale à la date de début.');
        }

        return $this->update($id, ['date_fin' => $dateFin]);
    }
}
