<?php
namespace App\Service\DirectionGenerale;

use App\Models\{BudgetModel, DemandeModel, HistoriqueDemandeModel, StatutDemandeModel};
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

class DirectionService
{
    private BaseConnection $db;
    private DemandeModel $demandes;
    private BudgetModel $budgets;
    private StatutDemandeModel $statuts;
    private HistoriqueDemandeModel $historiques;

    public function __construct()
    {
        $this->db = db_connect();
        $this->demandes = new DemandeModel();
        $this->budgets = new BudgetModel();
        $this->statuts = new StatutDemandeModel();
        $this->historiques = new HistoriqueDemandeModel();
    }

    public function valider(int $id, int $utilisateurId): bool
    {
        $this->db->transBegin();
        try {
            $demande = $this->demande($id);
            $this->exigerDirection($demande);
            $this->changerStatut($id, 'VALIDEE');
            $this->historique($id, $utilisateurId, $this->montant($demande), 'Demande validée par la Direction générale.');
            $this->terminer('Erreur lors de la validation.');
            return true;
        } catch (\Throwable $e) { $this->db->transRollback(); throw $e; }
    }

    public function refuser(int $id, int $utilisateurId, int $annee, string $motif): bool
    {
        if (trim($motif) === '') throw new RuntimeException('Le motif de refus est obligatoire.');
        $this->db->transBegin();
        try {
            $demande = $this->demande($id);
            $this->exigerDirection($demande);
            $budget = $this->budgetVerrouille((int) $demande['id_departement'], $annee);
            $montant = $this->montant($demande);
            if ((float) $budget['montant_engage'] < $montant) throw new RuntimeException('Le budget engagé est incohérent avec cette demande.');
            $this->budgets->update($budget['id'], ['montant_engage' => (float) $budget['montant_engage'] - $montant]);
            $this->changerStatut($id, 'REFUSEE');
            $this->historique($id, $utilisateurId, $montant, $motif);
            $this->terminer('Erreur lors du refus de la demande.');
            return true;
        } catch (\Throwable $e) { $this->db->transRollback(); throw $e; }
    }

    private function demande(int $id): array
    {
        $demande = $this->demandes->trouverAvecDepartement($id);
        if (!$demande) throw new RuntimeException('Demande introuvable.');
        return $demande;
    }

    private function budgetVerrouille(int $departementId, int $annee): array
    {
        $budget = $this->db->query('SELECT * FROM budgets WHERE id_departement = ? AND annee = ? FOR UPDATE', [$departementId, $annee])->getRowArray();
        if (!$budget) throw new RuntimeException('Budget introuvable.');
        return $budget;
    }

    private function montant(array $demande): float { return (float) $demande['salaire_propose'] * (int) $demande['nombre_demande']; }
    private function exigerDirection(array $demande): void { if ($demande['statut_libelle'] !== 'EN_VALIDATION_DIRECTION') throw new RuntimeException('Cette demande n’est pas en attente de la Direction.'); }
    private function changerStatut(int $id, string $libelle): void
    {
        $statut = $this->statuts->trouverParLibelle($libelle);
        if (!$statut) throw new RuntimeException("Statut introuvable : {$libelle}");
        $this->demandes->update($id, ['id_statut_actuel' => $statut['id']]);
    }
    private function historique(int $id, int $utilisateurId, float $montant, string $remarque): void
    {
        $demande = $this->demande($id);
        $this->historiques->insert(['id_demande' => $id, 'id_statut_demande' => $demande['id_statut_actuel'], 'id_utilisateur' => $utilisateurId, 'salaire_propose' => $montant, 'remarque' => $remarque]);
    }
    private function terminer(string $message): void
    {
        if ($this->db->transStatus() === false) throw new RuntimeException($message);
        $this->db->transCommit();
    }
}
