<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\CandidatModel;
use App\Models\CandidatureModel;
use App\Models\CompetenceModel;
use App\Models\ExperienceCandidatureModel;
use App\Models\OffreModel;
use App\Models\PosteModel;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class CvCandidature extends BaseController
{
    public function formulaire(int $idCandidat, int $idOffre)
    {
        if ($idCandidat <= 0 || $idOffre <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'message' => 'Identifiant de candidat ou d’offre invalide.',
            ]);
        }

        try {
            $offre = (new OffreModel())->getDetail($idOffre);

            if ($offre === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Offre introuvable.',
                ]);
            }

            $cv = (new CandidatModel())->getCv($idCandidat, $idOffre);
            if ($cv === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Candidat introuvable.',
                ]);
            }

            $candidature = (new CandidatureModel())
                ->where('id_candidat', $idCandidat)
                ->where('id_offre', $idOffre)
                ->first();

            $experiences = $candidature === null
                ? []
                : (new ExperienceCandidatureModel())->getByCandidature((int) $candidature['id']);

            return $this->response->setJSON([
                'offre' => $offre,
                'candidature' => $candidature,
                'experiences' => $experiences,
                'cv' => $cv,
                'postes' => (new PosteModel())->orderBy('libelle', 'ASC')->findAll(),
                'competences' => (new CompetenceModel())->orderBy('libelle', 'ASC')->findAll(),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur formulaire CV candidature: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible de charger le formulaire de candidature.',
            ]);
        }
    }

    public function enregistrer(int $idCandidat, int $idOffre)
    {
        if ($idCandidat <= 0 || $idOffre <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'message' => 'Identifiant de candidat ou d’offre invalide.',
            ]);
        }

        $payload = $this->request->getJSON(true);
        if (!is_array($payload)) {
            $payload = $this->request->getPost();
        }

        foreach (['experiences', 'experiences_candidat', 'formations', 'competences'] as $champ) {
            if (isset($payload[$champ]) && !is_array($payload[$champ])) {
                return $this->response->setStatusCode(422)->setJSON([
                    'message' => sprintf('Le format du champ %s est invalide.', $champ),
                ]);
            }
        }

        if (isset($payload['candidat']) && !is_array($payload['candidat'])) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => 'Le format des informations du candidat est invalide.',
            ]);
        }

        try {
            $offre = (new OffreModel())->getDetail($idOffre);

            if ($offre === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Offre introuvable.',
                ]);
            }

            if ((new CandidatModel())->find($idCandidat) === null) {
                return $this->response->setStatusCode(404)->setJSON([
                    'message' => 'Candidat introuvable.',
                ]);
            }

            $exigences = [];
            foreach ($offre['exigences_experience'] as $exigence) {
                $exigences[(int) $exigence['id']] = $exigence;
            }

            $donneesCandidat = isset($payload['candidat'])
                ? $this->validerCandidat($payload['candidat'], $idCandidat)
                : null;

            $lignesOffre = $this->validerExperiencesOffre(
                $payload['experiences'] ?? [],
                $exigences
            );
            $postesValides = $this->indexerParId((new PosteModel())->findAll());
            $competencesValides = $this->indexerParId((new CompetenceModel())->findAll());

            $lignesProfil = isset($payload['experiences_candidat'])
                ? $this->validerExperiencesProfil(
                    $payload['experiences_candidat'],
                    $postesValides,
                    $idCandidat
                )
                : [];
            $lignesFormations = isset($payload['formations'])
                ? $this->validerFormations($payload['formations'], $idCandidat)
                : [];
            $lignesCompetences = isset($payload['competences'])
                ? $this->validerCompetences(
                    $payload['competences'],
                    $competencesValides
                )
                : [];

            $db = db_connect();
            $db->transBegin();

            try {
                if ($donneesCandidat !== null) {
                    (new CandidatModel())->update($idCandidat, $donneesCandidat);
                }

                $candidatureModel = new CandidatureModel();
                $candidature = $candidatureModel
                    ->where('id_candidat', $idCandidat)
                    ->where('id_offre', $idOffre)
                    ->first();

                $creee = $candidature === null;

                if ($creee) {
                    $statut = $db->table('statut_candidatures')
                        ->select('id')
                        ->where('libelle', 'en_attente')
                        ->get()
                        ->getRowArray();

                    if ($statut === null) {
                        throw new RuntimeException('Statut de candidature en_attente introuvable.');
                    }

                    $idCandidature = (int) $candidatureModel->insert([
                        'id_candidat' => $idCandidat,
                        'id_offre' => $idOffre,
                        'id_statut_actuel' => (int) $statut['id'],
                    ], true);
                } else {
                    $idCandidature = (int) $candidature['id'];
                }

                $db->table('experiences_candidature')
                    ->where('id_candidature', $idCandidature)
                    ->delete();

                if ($lignesOffre !== []) {
                    foreach ($lignesOffre as &$ligne) {
                        $ligne['id_candidature'] = $idCandidature;
                    }
                    unset($ligne);

                    $db->table('experiences_candidature')->insertBatch($lignesOffre);
                }

                $this->remplacerSection(
                    $db,
                    'experiences_candidat',
                    $idCandidat,
                    $payload,
                    'experiences_candidat',
                    $lignesProfil
                );

                $this->remplacerSection(
                    $db,
                    'formations_candidat',
                    $idCandidat,
                    $payload,
                    'formations',
                    $lignesFormations
                );

                if (array_key_exists('competences', $payload)) {
                    $db->table('competences_candidat')
                        ->where('id_candidature', $idCandidature)
                        ->delete();

                    if ($lignesCompetences !== []) {
                        foreach ($lignesCompetences as &$ligne) {
                            $ligne['id_candidature'] = $idCandidature;
                        }
                        unset($ligne);

                        $db->table('competences_candidat')->insertBatch($lignesCompetences);
                    }
                }

                $db->transCommit();
            } catch (Throwable $e) {
                $db->transRollback();
                throw $e;
            }

            $candidature = $candidatureModel->find($idCandidature);
            $experiencesEnregistrees = (new ExperienceCandidatureModel())
                ->getByCandidature($idCandidature);

            return $this->response
                ->setStatusCode($creee ? 201 : 200)
                ->setJSON([
                    'message' => 'CV et candidature enregistrés.',
                    'candidature' => $candidature,
                    'cv' => (new CandidatModel())->getCv($idCandidat, $idOffre),
                    'experiences' => $experiencesEnregistrees,
                ]);
        } catch (InvalidArgumentException $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'message' => $e->getMessage(),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Erreur enregistrement CV candidature: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'message' => 'Impossible d’enregistrer la candidature.',
            ]);
        }
    }

    private function validerCandidat(array $candidat, int $idCandidat): array
    {
        $nom = trim((string) ($candidat['nom'] ?? ''));
        $prenom = trim((string) ($candidat['prenom'] ?? ''));
        $email = trim((string) ($candidat['email'] ?? ''));
        $telephone = trim((string) ($candidat['telephone'] ?? ''));
        $ville = trim((string) ($candidat['ville'] ?? ''));

        if ($nom === '' || $prenom === '') {
            throw new InvalidArgumentException('Le nom et le prénom sont obligatoires.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('L’adresse email est invalide.');
        }

        $emailExistant = (new CandidatModel())
            ->where('email', $email)
            ->where('id !=', $idCandidat)
            ->first();

        if ($emailExistant !== null) {
            throw new InvalidArgumentException('Cette adresse email est déjà utilisée.');
        }

        return [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'telephone' => $telephone !== '' ? $telephone : null,
            'ville' => $ville !== '' ? $ville : null,
        ];
    }

    private function validerExperiencesOffre(array $experiences, array $exigences): array
    {
        $lignes = [];
        $dejaVus = [];
        $aujourdHui = new DateTimeImmutable('today');

        foreach ($experiences as $experience) {
            if (!is_array($experience)) {
                throw new InvalidArgumentException('Une expérience est mal formatée.');
            }

            $idExigence = (int) ($experience['id_exigence'] ?? 0);

            if (!isset($exigences[$idExigence])) {
                throw new InvalidArgumentException(
                    'Une expérience ne correspond pas à une exigence de cette offre.'
                );
            }

            if (isset($dejaVus[$idExigence])) {
                throw new InvalidArgumentException(
                    'Une même exigence ne peut avoir qu’une seule période.'
                );
            }

            $dejaVus[$idExigence] = true;

            $dateDebut = $this->validerDate($experience['date_debut'] ?? null, 'début');
            $dateFin = $this->validerDate($experience['date_fin'] ?? null, 'fin');

            if ($dateFin < $dateDebut) {
                throw new InvalidArgumentException(
                    'La date de fin doit être postérieure ou égale à la date de début.'
                );
            }

            if ($dateFin > $aujourdHui) {
                throw new InvalidArgumentException(
                    'La date de fin ne peut pas être dans le futur.'
                );
            }

            $lignes[] = [
                'id_exigence' => $idExigence,
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin->format('Y-m-d'),
            ];
        }

        return $lignes;
    }

    private function validerExperiencesProfil(
        array $experiences,
        array $postesValides,
        int $idCandidat
    ): array {
        $lignes = [];

        foreach ($experiences as $experience) {
            if (!is_array($experience)) {
                throw new InvalidArgumentException('Une expérience du CV est mal formatée.');
            }

            $idPoste = (int) ($experience['id_poste'] ?? 0);

            if (!isset($postesValides[$idPoste])) {
                throw new InvalidArgumentException('Le poste d’une expérience du CV est invalide.');
            }

            $dateDebut = $this->validerDate($experience['date_debut'] ?? null, 'début');
            $dateFin = $this->validerDateOptionnelle($experience['date_fin'] ?? null, 'fin');

            if ($dateFin !== null && $dateFin < $dateDebut) {
                throw new InvalidArgumentException(
                    'La date de fin d’une expérience du CV doit suivre la date de début.'
                );
            }

            $lignes[] = [
                'id_candidat' => $idCandidat,
                'id_poste' => $idPoste,
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin?->format('Y-m-d'),
                'descriptions' => trim((string) (
                    $experience['descriptions'] ?? $experience['description'] ?? ''
                )) ?: null,
            ];
        }

        return $lignes;
    }

    private function validerFormations(array $formations, int $idCandidat): array
    {
        $lignes = [];

        foreach ($formations as $formation) {
            if (!is_array($formation)) {
                throw new InvalidArgumentException('Une formation est mal formatée.');
            }

            $diplome = trim((string) ($formation['diplome'] ?? ''));
            if ($diplome === '') {
                throw new InvalidArgumentException('Le diplôme d’une formation est obligatoire.');
            }

            $dateDebut = $this->validerDateOptionnelle($formation['date_debut'] ?? null, 'début');
            $dateFin = $this->validerDateOptionnelle($formation['date_fin'] ?? null, 'fin');

            if ($dateDebut !== null && $dateFin !== null && $dateFin < $dateDebut) {
                throw new InvalidArgumentException(
                    'La date de fin d’une formation doit suivre la date de début.'
                );
            }

            $lignes[] = [
                'id_candidat' => $idCandidat,
                'diplome' => $diplome,
                'etablissement' => trim((string) ($formation['etablissement'] ?? '')) ?: null,
                'date_debut' => $dateDebut?->format('Y-m-d'),
                'date_fin' => $dateFin?->format('Y-m-d'),
                'description' => trim((string) ($formation['description'] ?? '')) ?: null,
            ];
        }

        return $lignes;
    }

    private function validerCompetences(
        array $competences,
        array $competencesValides
    ): array {
        $lignes = [];
        $dejaVus = [];

        foreach ($competences as $competence) {
            if (!is_array($competence)) {
                throw new InvalidArgumentException('Une compétence est mal formatée.');
            }

            $idCompetence = (int) ($competence['id_competence'] ?? 0);

            if (!isset($competencesValides[$idCompetence])) {
                throw new InvalidArgumentException('Une compétence sélectionnée est invalide.');
            }

            if (isset($dejaVus[$idCompetence])) {
                throw new InvalidArgumentException('Une même compétence est présente deux fois.');
            }

            $dejaVus[$idCompetence] = true;
            $niveauBrut = $competence['niveau'] ?? null;

            if ($niveauBrut !== null && $niveauBrut !== '' && !is_numeric($niveauBrut)) {
                throw new InvalidArgumentException('Le niveau d’une compétence est invalide.');
            }

            $niveau = ($niveauBrut === null || $niveauBrut === '')
                ? null
                : (float) $niveauBrut;

            if ($niveau !== null && ($niveau < 0 || $niveau > 20)) {
                throw new InvalidArgumentException('Le niveau d’une compétence doit être entre 0 et 20.');
            }

            $lignes[] = [
                'id_competence' => $idCompetence,
                'niveau' => $niveau,
            ];
        }

        return $lignes;
    }

    private function remplacerSection(
        $db,
        string $table,
        int $idCandidat,
        array $payload,
        string $champPayload,
        array $lignes
    ): void {
        if (!array_key_exists($champPayload, $payload)) {
            return;
        }

        $db->table($table)->where('id_candidat', $idCandidat)->delete();

        if ($lignes !== []) {
            $db->table($table)->insertBatch($lignes);
        }
    }

    private function indexerParId(array $lignes): array
    {
        $index = [];

        foreach ($lignes as $ligne) {
            $index[(int) $ligne['id']] = $ligne;
        }

        return $index;
    }

    private function validerDate(mixed $valeur, string $libelle): DateTimeImmutable
    {
        $date = $this->validerDateOptionnelle($valeur, $libelle);

        if ($date === null) {
            throw new InvalidArgumentException(
                sprintf('La date de %s est obligatoire.', $libelle)
            );
        }

        return $date;
    }

    private function validerDateOptionnelle(mixed $valeur, string $libelle): ?DateTimeImmutable
    {
        if ($valeur === null || $valeur === '') {
            return null;
        }

        if (!is_string($valeur)) {
            throw new InvalidArgumentException(
                sprintf('La date de %s est invalide.', $libelle)
            );
        }

        $valeur = trim($valeur);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $valeur);

        if ($date === false || $date->format('Y-m-d') !== $valeur) {
            throw new InvalidArgumentException(
                sprintf('La date de %s est invalide.', $libelle)
            );
        }

        return $date;
    }
}
