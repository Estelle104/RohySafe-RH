<?php

namespace App\Service\Finance;

use App\Models\BudgetModel;
use App\Models\DemandeModel;
use App\Models\HistoriqueDemandeModel;
use App\Models\StatutDemandeModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

class FinanceService
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

    public function verifierBudget(int $demandeId, int $annee): array
    {
        $demande = $this->demande($demandeId);
        $budget = $this->budget((int) $demande['id_departement'], $annee);
        $montant = (float) $demande['salaire_propose'] * (int) $demande['nombre_demande'];
        $disponible = (float) $budget['montant_mensuel'] - (float) $budget['montant_engage'];

        return compact('demande', 'budget', 'disponible', 'montant') + [
            'est_suffisant' => $montant <= $disponible,
        ];
    }

    public function valider(int $demandeId, int $annee, int $utilisateurId): bool
    {
        $this->db->transBegin();
        try {
            $demande = $this->demande($demandeId);
            $this->exigerStatut($demande, 'EN_VALIDATION_FINANCE');
            $budget = $this->budgetVerrouille((int) $demande['id_departement'], $annee);
            $montant = $this->montantDemande($demande);
            $disponible = (float) $budget['montant_mensuel'] - (float) $budget['montant_engage'];

            if ($montant > $disponible) {
                throw new RuntimeException('Budget insuffisant. Validation impossible.');
            }

            $this->budgets->update($budget['id'], [
                'montant_engage' => (float) $budget['montant_engage'] + $montant,
            ]);
            $this->changerStatut($demandeId, 'EN_VALIDATION_DIRECTION');
            $this->historique($demandeId, $utilisateurId, $montant, 'Validation financière : budget suffisant.');
            $this->terminerTransaction('Erreur lors de la validation financière.');
            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    public function annuler(int $demandeId, int $utilisateurId, string $motif): bool
    {
        if (trim($motif) === '') throw new RuntimeException('Le motif d’annulation est obligatoire.');
        $this->db->transBegin();
        try {
            $demande = $this->demande($demandeId);
            $this->exigerStatut($demande, 'EN_VALIDATION_FINANCE');
            $this->changerStatut($demandeId, 'ANNULEE_FINANCE');
            $this->historique($demandeId, $utilisateurId, $this->montantDemande($demande), $motif);
            $this->terminerTransaction('Erreur lors de l’annulation.');
            return true;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    private function demande(int $id): array
    {
        $demande = $this->demandes->trouverAvecDepartement($id);
        if (!$demande) throw new RuntimeException('Demande introuvable.');
        return $demande;
    }

    private function budget(int $departementId, int $annee): array
    {
        $budget = $this->budgets->trouverPourDepartement($departementId, $annee);
        if (!$budget) throw new RuntimeException('Aucun budget trouvé pour ce département et cette année.');
        return $budget;
    }

    private function budgetVerrouille(int $departementId, int $annee): array
    {
        $budget = $this->db->query('SELECT * FROM budgets WHERE id_departement = ? AND annee = ? FOR UPDATE', [$departementId, $annee])->getRowArray();
        if (!$budget) throw new RuntimeException('Budget introuvable.');
        return $budget;
    }

    private function montantDemande(array $demande): float
    {
        return (float) $demande['salaire_propose'] * (int) $demande['nombre_demande'];
    }

    private function exigerStatut(array $demande, string $statut): void
    {
        if ($demande['statut_libelle'] !== $statut) throw new RuntimeException('La demande n’est pas à l’étape attendue.');
    }

    private function changerStatut(int $demandeId, string $libelle): void
    {
        $statut = $this->statuts->trouverParLibelle($libelle);
        if (!$statut) throw new RuntimeException("Statut introuvable : {$libelle}");
        $this->demandes->update($demandeId, ['id_statut_actuel' => $statut['id']]);
    }

    private function historique(int $demandeId, int $utilisateurId, float $montant, string $remarque): void
    {
        $demande = $this->demande($demandeId);
        $this->historiques->insert([
            'id_demande' => $demandeId,
            'id_statut_demande' => $demande['id_statut_actuel'],
            'id_utilisateur' => $utilisateurId,
            'salaire_propose' => $montant,
            'remarque' => $remarque,
        ]);
    }

    private function terminerTransaction(string $message): void
    {
        if ($this->db->transStatus() === false) throw new RuntimeException($message);
        $this->db->transCommit();
    }
}
