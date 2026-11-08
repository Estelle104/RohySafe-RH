<?php

namespace App\Models;

use CodeIgniter\Model;
use DomainException;

class AffectationEmployeModel extends Model
{
    protected $table         = 'histo_employes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_employe', 'id_societe_province', 'date_debut', 'date_fin'];

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

    public function getEmployesModuleAffectation(): array
    {
        // Liste des employés pour le module affectation, avec affectation courante (si existante)
        $employes = $this->db->table('employes e')
            ->select("e.id, e.matricule, e.date_embauche, c.nom, c.prenom, CONCAT(c.prenom, ' ', c.nom) AS nom_complet")
            ->join('candidats c', 'c.id = e.id_candidat')
            ->orderBy('c.nom', 'ASC')
            ->orderBy('c.prenom', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($employes as &$emp) {
            $courante = $this->db->table('histo_employes h')
                ->select('h.id, h.date_debut, h.date_fin, s.libelle AS societe, p.libelle AS province')
                ->join('societe_provinces sp', 'sp.id = h.id_societe_province')
                ->join('societes s', 's.id = sp.id_societe')
                ->join('provinces p', 'p.id = sp.id_province')
                ->where('h.id_employe', $emp['id'])
                ->groupStart()
                    ->where('h.date_fin IS NULL', null, false)
                    ->orWhere('h.date_fin >=', date('Y-m-d'))
                ->groupEnd()
                ->orderBy('h.date_debut', 'DESC')
                ->orderBy('h.id', 'DESC')
                ->get()
                ->getRowArray();
            $emp['affectation_courante'] = $courante;
        }

        return $employes;
    }

    public function getEmploye(int $idEmploye): ?array
    {
        return $this->db->table('employes e')
            ->select("e.id, e.matricule, e.date_embauche, c.nom, c.prenom, CONCAT(c.prenom, ' ', c.nom) AS nom_complet")
            ->join('candidats c', 'c.id = e.id_candidat')
            ->where('e.id', $idEmploye)
            ->get()
            ->getRowArray();
    }

    public function getSocietes(): array
    {
        return $this->db->table('societes')->select('id, libelle')->orderBy('libelle', 'ASC')->get()->getResultArray();
    }

    public function getProvinces(): array
    {
        return $this->db->table('provinces')->select('id, libelle')->orderBy('libelle', 'ASC')->get()->getResultArray();
    }

    public function getSocieteProvinces(): array
    {
        return $this->db->table('societe_provinces sp')
            ->select('sp.id, sp.id_societe, sp.id_province, s.libelle AS societe, p.libelle AS province')
            ->join('societes s', 's.id = sp.id_societe')
            ->join('provinces p', 'p.id = sp.id_province')
            ->orderBy('s.libelle', 'ASC')
            ->orderBy('p.libelle', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getAffectations(): array
    {
        return $this->db->table('histo_employes h')
            ->select("h.id, h.id_employe, h.id_societe_province, h.date_debut, h.date_fin, e.matricule, CONCAT(c.prenom, ' ', c.nom) AS employe, s.libelle AS societe, p.libelle AS province")
            ->join('employes e', 'e.id = h.id_employe')
            ->join('candidats c', 'c.id = e.id_candidat')
            ->join('societe_provinces sp', 'sp.id = h.id_societe_province')
            ->join('societes s', 's.id = sp.id_societe')
            ->join('provinces p', 'p.id = sp.id_province')
            ->orderBy('h.date_debut', 'DESC')
            ->orderBy('h.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getAffectationsByEmploye(int $idEmploye): array
    {
        return $this->db->table('histo_employes h')
            ->select("h.id, h.id_employe, h.id_societe_province, h.date_debut, h.date_fin, s.libelle AS societe, p.libelle AS province")
            ->join('societe_provinces sp', 'sp.id = h.id_societe_province')
            ->join('societes s', 's.id = sp.id_societe')
            ->join('provinces p', 'p.id = sp.id_province')
            ->where('h.id_employe', $idEmploye)
            ->orderBy('h.date_debut', 'DESC')
            ->orderBy('h.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function createAffectation(array $data): int
    {
        $idEmploye = (int) ($data['id_employe'] ?? 0);
        $idSociete = (int) ($data['id_societe'] ?? 0);
        $idProvince = (int) ($data['id_province'] ?? 0);
        $dateDebut = (string) ($data['date_debut'] ?? date('Y-m-d'));

        if ($idEmploye <= 0 || $idSociete <= 0 || $idProvince <= 0) {
            throw new DomainException("L'employé, la société et la province sont obligatoires.");
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut)) {
            throw new DomainException('La date de début est invalide.');
        }

        if ($this->db->table('employes')->where('id', $idEmploye)->countAllResults() === 0) {
            throw new DomainException("L'employé sélectionné est introuvable.");
        }

        $sp = $this->db->table('societe_provinces')
            ->select('id')
            ->where('id_societe', $idSociete)
            ->where('id_province', $idProvince)
            ->get()
            ->getRowArray();

        if ($sp === null) {
            $this->db->table('societe_provinces')->insert([
                'id_societe' => $idSociete,
                'id_province' => $idProvince,
            ]);
            $idSocieteProvince = (int) $this->db->insertID();
        } else {
            $idSocieteProvince = (int) $sp['id'];
        }

        $this->db->transStart();

        // Clôture de l'affectation courante (ouverte) de l'employé
        $courante = $this->db->table($this->table)
            ->where('id_employe', $idEmploye)
            ->groupStart()
                ->where('date_fin IS NULL', null, false)
                ->orWhere('date_fin >=', $dateDebut)
            ->groupEnd()
            ->orderBy('date_debut', 'DESC')
            ->get()
            ->getRowArray();

        if ($courante !== null) {
            $dateFinAncienne = date('Y-m-d', strtotime($dateDebut . ' -1 day'));
            if ($dateFinAncienne < $courante['date_debut']) {
                throw new DomainException("La nouvelle affectation doit commencer après le début de l'affectation actuelle.");
            }
            $this->db->table($this->table)->where('id', (int) $courante['id'])->update(['date_fin' => $dateFinAncienne]);
        }

        $this->insert([
            'id_employe' => $idEmploye,
            'id_societe_province' => $idSocieteProvince,
            'date_debut' => $dateDebut,
            'date_fin' => null,
        ]);
        $id = (int) $this->getInsertID();

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new DomainException("Impossible d'enregistrer l'affectation.");
        }

        return $id;
    }
}
