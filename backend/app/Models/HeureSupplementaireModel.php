<?php

namespace App\Models;

use CodeIgniter\Model;
use InvalidArgumentException;
use RuntimeException;

class HeureSupplementaireModel extends Model
{
    protected $table         = 'heure_supplementaires';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_employe', 'heure_debut', 'heure_fin', 'date_debut', 'date_fin'];

    public function getEmployesDisponibles(?int $idDepartement = null): array
    {
        $builder = $this->db->table('employes e')
            ->select('e.id, e.matricule, c.nom, c.prenom, CONCAT(c.prenom, \' \', c.nom) AS nom_complet, pd.id_departement AS id_departement, d.libelle AS departement')
            ->join('candidats c', 'c.id = e.id_candidat')
            ->join('poste_departement pd', 'pd.id = e.id_poste_departement', 'left')
            ->join('departements d', 'd.id = pd.id_departement', 'left');

        if ($idDepartement !== null) {
            $builder->where('pd.id_departement', $idDepartement);
        }

        return $builder
            ->orderBy('c.nom', 'ASC')
            ->orderBy('c.prenom', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function create(array $data): int
    {
        $donnees = $this->normaliserDonnees($data);

        $this->db->transStart();

        $id = (int) $this->insert($donnees, true);

        $idUtilisateur = isset($data['id_utilisateur']) ? (int) $data['id_utilisateur'] : 0;
        $idStatut = $this->trouverIdStatut('En attente');

        $this->db->table('histo_demande_heure_sups')->insert([
            'id_statut' => $idStatut,
            'id_utilisateur' => $idUtilisateur,
            'id_heure_supplementaire' => $id,
            'date' => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            throw new RuntimeException('Impossible d’enregistrer la demande d’heures supplémentaires.');
        }

        return $id;
    }

    private function normaliserDonnees(array $data): array
    {
        $idEmploye = isset($data['id_employe']) ? (int) $data['id_employe'] : 0;
        $idDepartementManager = isset($data['id_departement_manager']) ? (int) $data['id_departement_manager'] : null;
        $dateDebut = trim((string) ($data['date_debut'] ?? ''));
        $dateFin = trim((string) ($data['date_fin'] ?? ''));
        $heureDebut = trim((string) ($data['heure_debut'] ?? ''));
        $heureFin = trim((string) ($data['heure_fin'] ?? ''));

        if ($idEmploye <= 0) {
            throw new InvalidArgumentException('L’employé est obligatoire.');
        }

        $employe = $this->db->table('employes e')
            ->select('e.id, e.id_poste_departement, pd.id_departement AS id_departement')
            ->join('poste_departement pd', 'pd.id = e.id_poste_departement', 'left')
            ->where('e.id', $idEmploye)
            ->get()->getRowArray();

        if ($employe === null) {
            throw new InvalidArgumentException('L’employé sélectionné est introuvable.');
        }

        if ($idDepartementManager !== null && (int) ($employe['id_departement'] ?? 0) !== $idDepartementManager) {
            throw new InvalidArgumentException('Vous ne pouvez déclarer des heures supplémentaires que pour les employés de votre département.');
        }

        foreach (['date_debut' => $dateDebut, 'date_fin' => $dateFin, 'heure_debut' => $heureDebut, 'heure_fin' => $heureFin] as $label => $valeur) {
            if ($valeur === '') {
                throw new InvalidArgumentException("Le champ {$label} est obligatoire.");
            }
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin)) {
            throw new InvalidArgumentException('Les dates doivent être au format YYYY-MM-DD.');
        }

        if (! preg_match('/^\d{2}:\d{2}$/', $heureDebut) || ! preg_match('/^\d{2}:\d{2}$/', $heureFin)) {
            throw new InvalidArgumentException('Les heures doivent être au format HH:MM.');
        }

        if ($dateFin < $dateDebut) {
            throw new InvalidArgumentException('La date de fin doit être postérieure ou égale à la date de début.');
        }

        $dateTimeDebut = $dateDebut . ' ' . $heureDebut . ':00';
        $dateTimeFin = $dateFin . ' ' . $heureFin . ':00';

        if (strtotime($dateTimeFin) <= strtotime($dateTimeDebut)) {
            throw new InvalidArgumentException('L’heure de fin doit être strictement supérieure à l’heure de début.');
        }

        return [
            'id_employe' => $idEmploye,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'heure_debut' => $dateTimeDebut,
            'heure_fin' => $dateTimeFin,
        ];
    }

    private function trouverIdStatut(string $libelle): int
    {
        $statut = $this->db->table('statut_heure_sups')->select('id')->where('libelle', $libelle)->get()->getRowArray();

        if ($statut !== null) {
            return (int) $statut['id'];
        }

        $this->db->table('statut_heure_sups')->insert(['libelle' => $libelle]);

        return (int) $this->db->insertID();
    }

    public function listerDemandes(?string $role = null, ?int $idDepartement = null): array
    {
        $latestSubquery = '(SELECT h1.id_heure_supplementaire, h1.id_statut, h1.id_utilisateur, h1.date
            FROM histo_demande_heure_sups h1
            INNER JOIN (
                SELECT id_heure_supplementaire, MAX(date) AS max_date
                FROM histo_demande_heure_sups
                GROUP BY id_heure_supplementaire
            ) h2
            ON h1.id_heure_supplementaire = h2.id_heure_supplementaire
            AND h1.date = h2.max_date)';

        $builder = $this->db->table('heure_supplementaires hs')
            ->select([
                'hs.id',
                'hs.id_employe',
                'hs.date_debut',
                'hs.date_fin',
                'hs.heure_debut',
                'hs.heure_fin',
                'c.nom',
                'c.prenom',
                "CONCAT(c.prenom, ' ', c.nom) AS nom_complet",
                'pd.id_departement AS id_departement',
                'd.libelle AS departement',
                'statut.libelle AS statut_libelle',
                'latest.date AS date_statut',
                'latest.id_utilisateur AS id_utilisateur',
            ])
            ->join('employes e', 'e.id = hs.id_employe')
            ->join('candidats c', 'c.id = e.id_candidat')
            ->join('poste_departement pd', 'pd.id = e.id_poste_departement', 'left')
            ->join('departements d', 'd.id = pd.id_departement', 'left')
            ->join($latestSubquery . ' latest', 'latest.id_heure_supplementaire = hs.id', 'left')
            ->join('statut_heure_sups statut', 'statut.id = latest.id_statut', 'left');

        if ($role === 'Manager' && $idDepartement !== null) {
            $builder->where('pd.id_departement', $idDepartement);
        }

        return $builder
            ->orderBy('hs.date_debut', 'DESC')
            ->orderBy('hs.date_fin', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function validerDemande(int $idHeureSupplementaire, int $idUtilisateur): void
    {
        $demande = $this->db->table('heure_supplementaires hs')
            ->select('hs.id, statut.libelle AS statut_libelle')
            ->join($this->db->prefixTable('histo_demande_heure_sups') . ' h', 'h.id_heure_supplementaire = hs.id', 'left')
            ->join('statut_heure_sups statut', 'statut.id = h.id_statut', 'left')
            ->where('hs.id', $idHeureSupplementaire)
            ->orderBy('h.date', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if ($demande === null) {
            throw new InvalidArgumentException('La demande d’heures supplémentaires est introuvable.');
        }

        if (($demande['statut_libelle'] ?? '') !== 'En attente') {
            throw new InvalidArgumentException('Seules les demandes en attente peuvent être validées.');
        }

        $idStatut = $this->trouverIdStatut('Validée');

        $this->db->table('histo_demande_heure_sups')->insert([
            'id_statut' => $idStatut,
            'id_utilisateur' => $idUtilisateur,
            'id_heure_supplementaire' => $idHeureSupplementaire,
            'date' => date('Y-m-d H:i:s'),
        ]);
    }
}
