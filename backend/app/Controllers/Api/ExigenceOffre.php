<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CompetenceModel;
use App\Models\CriterePosteModel;
use App\Models\ExigenceExperienceModel;
use App\Models\OffreModel;
use App\Models\PosteModel;
use InvalidArgumentException;
use Throwable;

class ExigenceOffre extends BaseController
{
    public function offres()
    {
        try {
            return $this->response->setJSON(
                (new OffreModel())->getOffresBackoffice()
            );
        } catch (Throwable $e) {
            log_message('error', 'Erreur liste offres backoffice: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de charger la liste des offres.',
            ]);
        }
    }

    public function formulaire(int $idOffre)
    {
        try {
            $offre = $this->chargerOffre($idOffre);

            if ($offre === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Offre introuvable.',
                ]);
            }

            return $this->response->setJSON([
                'offre' => $offre,
                'postes' => (new PosteModel())->orderBy('libelle', 'ASC')->findAll(),
                'competences' => (new CompetenceModel())->orderBy('libelle', 'ASC')->findAll(),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur chargement exigences offre: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de charger les exigences de l’offre.',
            ]);
        }
    }

    public function enregistrerExperience(int $idOffre)
    {
        try {
            $offre = $this->chargerOffre($idOffre);

            if ($offre === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Offre introuvable.',
                ]);
            }

            $payload = $this->lirePayload();
            $idExigence = $this->entierOptionnel($payload['id'] ?? null, 'l’exigence');
            $idPoste = $this->entierRequis($payload['id_poste_experience'] ?? null, 'le poste');
            $dureeMin = $this->entierOptionnel($payload['duree_min'] ?? null, 'la durée minimale') ?? 0;
            $dureeMax = $this->entierOptionnel($payload['duree_max'] ?? null, 'la durée maximale');

            if ((new PosteModel())->find($idPoste) === null) {
                throw new InvalidArgumentException('Le poste sélectionné est invalide.');
            }

            if ($dureeMin < 0) {
                throw new InvalidArgumentException('La durée minimale ne peut pas être négative.');
            }

            if ($dureeMax !== null && $dureeMax < $dureeMin) {
                throw new InvalidArgumentException(
                    'La durée maximale doit être supérieure ou égale à la durée minimale.'
                );
            }

            $modele = new ExigenceExperienceModel();

            if ($idExigence !== null) {
                $existante = $modele->find($idExigence);

                if (
                    $existante === null ||
                    (int) $existante['id_poste_departement'] !== (int) $offre['id_poste_departement']
                ) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'message' => 'Exigence d’expérience introuvable pour cette offre.',
                    ]);
                }
            }

            $requeteDoublon = (new ExigenceExperienceModel())
                ->where('id_poste_departement', (int) $offre['id_poste_departement'])
                ->where('id_poste_experience', $idPoste);

            if ($idExigence !== null) {
                $requeteDoublon->where('id !=', $idExigence);
            }

            $doublon = $requeteDoublon->first();

            if ($doublon !== null) {
                throw new InvalidArgumentException(
                    'Ce poste possède déjà une exigence d’expérience pour cette offre.'
                );
            }

            $donnees = [
                'id_poste_departement' => (int) $offre['id_poste_departement'],
                'id_poste_experience' => $idPoste,
                'duree_min' => $dureeMin,
                'duree_max' => $dureeMax,
                'obligatoire' => $this->estVrai($payload['obligatoire'] ?? false),
            ];

            if ($idExigence !== null) {
                $modele->update($idExigence, $donnees);
                $message = 'Exigence d’expérience mise à jour.';
                $statut = 200;
            } else {
                $idExigence = (int) $modele->insert($donnees, true);
                $message = 'Exigence d’expérience ajoutée.';
                $statut = 201;
            }

            return $this->response
                ->setStatusCode($statut)
                ->setJSON([
                    'message' => $message,
                    'offre' => $this->chargerOffre($idOffre),
                ]);
        } catch (InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur enregistrement exigence offre: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible d’enregistrer l’exigence d’expérience.',
            ]);
        }
    }

    public function supprimerExperience(int $idOffre, int $idExigence)
    {
        try {
            $offre = $this->chargerOffre($idOffre);

            if ($offre === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Offre introuvable.',
                ]);
            }

            $modele = new ExigenceExperienceModel();
            $exigence = $modele->find($idExigence);

            if (
                $exigence === null ||
                (int) $exigence['id_poste_departement'] !== (int) $offre['id_poste_departement']
            ) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Exigence d’expérience introuvable pour cette offre.',
                ]);
            }

            $utilisations = db_connect()
                ->table('experiences_candidature')
                ->where('id_exigence', $idExigence)
                ->countAllResults();

            if ($utilisations > 0) {
                return $this->response->setStatusCode(422)->setJSON([
                    'message' => 'Cette exigence est déjà utilisée par une candidature.',
                ]);
            }

            $modele->delete($idExigence);

            return $this->response->setJSON([
                'message' => 'Exigence d’expérience supprimée.',
                'offre' => $this->chargerOffre($idOffre),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur suppression exigence offre: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de supprimer l’exigence d’expérience.',
            ]);
        }
    }

    public function enregistrerCompetence(int $idOffre)
    {
        try {
            $offre = $this->chargerOffre($idOffre);

            if ($offre === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Offre introuvable.',
                ]);
            }

            $payload = $this->lirePayload();
            $idCritere = $this->entierOptionnel($payload['id'] ?? null, 'le critère');
            $idCompetence = $this->entierRequis(
                $payload['id_competence'] ?? null,
                'la compétence'
            );
            $noteMinimale = $this->decimalOptionnel(
                $payload['note_minimale'] ?? null,
                'la note minimale'
            );

            if ((new CompetenceModel())->find($idCompetence) === null) {
                throw new InvalidArgumentException('La compétence sélectionnée est invalide.');
            }

            $modele = new CriterePosteModel();

            if ($idCritere !== null) {
                $existant = $modele->find($idCritere);

                if (
                    $existant === null ||
                    (int) $existant['id_poste'] !== (int) $offre['id_poste']
                ) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'message' => 'Compétence requise introuvable pour cette offre.',
                    ]);
                }
            }

            $requeteDoublon = (new CriterePosteModel())
                ->where('id_poste', (int) $offre['id_poste'])
                ->where('id_competence', $idCompetence);

            if ($idCritere !== null) {
                $requeteDoublon->where('id !=', $idCritere);
            }

            $doublon = $requeteDoublon->first();

            if ($doublon !== null) {
                throw new InvalidArgumentException(
                    'Cette compétence est déjà exigée pour cette offre.'
                );
            }

            $donnees = [
                'id_poste' => (int) $offre['id_poste'],
                'id_competence' => $idCompetence,
                'note_minimale' => $noteMinimale,
                'obligatoire' => $this->estVrai($payload['obligatoire'] ?? false),
            ];

            if ($idCritere !== null) {
                $modele->update($idCritere, $donnees);
                $message = 'Compétence requise mise à jour.';
                $statut = 200;
            } else {
                $idCritere = (int) $modele->insert($donnees, true);
                $message = 'Compétence requise ajoutée.';
                $statut = 201;
            }

            return $this->response
                ->setStatusCode($statut)
                ->setJSON([
                    'message' => $message,
                    'offre' => $this->chargerOffre($idOffre),
                ]);
        } catch (InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur enregistrement compétence offre: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible d’enregistrer la compétence requise.',
            ]);
        }
    }

    public function supprimerCompetence(int $idOffre, int $idCritere)
    {
        try {
            $offre = $this->chargerOffre($idOffre);

            if ($offre === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Offre introuvable.',
                ]);
            }

            $modele = new CriterePosteModel();
            $critere = $modele->find($idCritere);

            if (
                $critere === null ||
                (int) $critere['id_poste'] !== (int) $offre['id_poste']
            ) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Compétence requise introuvable pour cette offre.',
                ]);
            }

            $modele->delete($idCritere);

            return $this->response->setJSON([
                'message' => 'Compétence requise supprimée.',
                'offre' => $this->chargerOffre($idOffre),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur suppression compétence offre: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de supprimer la compétence requise.',
            ]);
        }
    }

    private function chargerOffre(int $idOffre): ?array
    {
        return (new OffreModel())->getDetail($idOffre);
    }

    private function lirePayload(): array
    {
        $payload = $this->request->getJSON(true);

        if (is_array($payload)) {
            return $payload;
        }

        $payload = $this->request->getPost();

        return is_array($payload) ? $payload : [];
    }

    private function entierRequis(mixed $valeur, string $libelle): int
    {
        $entier = $this->entierOptionnel($valeur, $libelle);

        if ($entier === null) {
            throw new InvalidArgumentException(
                sprintf('La valeur de %s est obligatoire.', $libelle)
            );
        }

        return $entier;
    }

    private function entierOptionnel(mixed $valeur, string $libelle): ?int
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        if (is_int($valeur)) {
            return $valeur;
        }

        if (is_float($valeur) && floor($valeur) === $valeur) {
            return (int) $valeur;
        }

        if (is_string($valeur) && preg_match('/^-?\d+$/', trim($valeur)) === 1) {
            return (int) trim($valeur);
        }

        throw new InvalidArgumentException(
            sprintf('La valeur de %s doit être un nombre entier.', $libelle)
        );
    }

    private function decimalOptionnel(mixed $valeur, string $libelle): ?float
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        if (!is_numeric($valeur)) {
            throw new InvalidArgumentException(
                sprintf('La valeur de %s est invalide.', $libelle)
            );
        }

        $nombre = (float) $valeur;

        if ($nombre < 0 || $nombre > 20) {
            throw new InvalidArgumentException(
                sprintf('La valeur de %s doit être comprise entre 0 et 20.', $libelle)
            );
        }

        return $nombre;
    }

    private function estVrai(mixed $valeur): bool
    {
        return in_array($valeur, [true, 1, '1', 't', 'true'], true);
    }
}
