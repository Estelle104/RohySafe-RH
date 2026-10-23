<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Formation::index');

$routes->get('/api/formations', 'Formation::getAll');

$routes->post('/api/formations', 'Formation::create');

$routes->get('/form', 'Formation::formationForm');


$routes->group('api', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    $routes->post('auth/login', 'Auth::login');
    $routes->get('auth/me', 'Auth::me');
    $routes->post('auth/logout', 'Auth::logout');
    $routes->get('health', 'Health::index');
    $routes->get('departements', 'Departement::index');
    $routes->get('offres', 'Offre::index');
    $routes->group('demandes', ['filter' => 'auth'], static function ($routes) {
        $routes->get('options', 'Demande::options');
        $routes->post('', 'Demande::create');
    });
    $routes->get('offres/(:num)', 'Offre::detail/$1');
    $routes->get('offres/(:num)/candidats-ideaux', 'CandidatIdeal::byOffre/$1');
    $routes->get('demandes/(:num)/candidats-ideaux', 'CandidatIdeal::byDemande/$1');
    $routes->post('candidats/login', 'AuthCandidat::login');
    $routes->post('candidats/inscription', 'AuthCandidat::inscrire');
    $routes->get('candidats/(:num)/cv', 'CandidatIdeal::cv/$1');
    $routes->get('candidats/(:num)/offres/(:num)/cv', 'CvCandidature::formulaire/$1/$2');
    $routes->post('candidats/(:num)/offres/(:num)/cv', 'CvCandidature::enregistrer/$1/$2');
    $routes->post('candidatures/(:num)/proposition', 'CandidatIdeal::createProposition/$1');

    // Backoffice des exigences d'offre
    $routes->get('backoffice/offres', 'ExigenceOffre::offres');
    $routes->get('backoffice/offres/(:num)/exigences', 'ExigenceOffre::formulaire/$1');
    $routes->post('backoffice/offres/(:num)/exigences/experience', 'ExigenceOffre::enregistrerExperience/$1');
    $routes->delete('backoffice/offres/(:num)/exigences/experience/(:num)', 'ExigenceOffre::supprimerExperience/$1/$2');
    $routes->post('backoffice/offres/(:num)/exigences/competence', 'ExigenceOffre::enregistrerCompetence/$1');
    $routes->delete('backoffice/offres/(:num)/exigences/competence/(:num)', 'ExigenceOffre::supprimerCompetence/$1/$2');

    $routes->get('backoffice/candidats' , 'ExigenceOffre::formulaire/$1');
    // Finance
    $routes->get('finance/notifications', 'FinanceController::notifications');
    $routes->get('finance/demandes/(:num)', 'FinanceController::fiche/$1');
    $routes->post('finance/demandes/(:num)/validation', 'FinanceController::valider/$1');
    $routes->post('finance/demandes/(:num)/annulation', 'FinanceController::annuler/$1');

    // Direction generale
    $routes->get('direction-generale/notifications', 'DirectionGeneraleController::notifications');
    $routes->get('direction-generale/demandes/(:num)', 'DirectionGeneraleController::fiche/$1');
    $routes->post('direction-generale/demandes/(:num)/validation', 'DirectionGeneraleController::valider/$1');
    $routes->post('direction-generale/demandes/(:num)/refus', 'DirectionGeneraleController::refuser/$1');
    
        $routes->get('offres', 'OffreController::index');
        $routes->get('offres/a-publier', 'OffreController::demandesAPublier');
        $routes->get('offres/(:num)', 'OffreController::show/$1');
        $routes->post('offres', 'OffreController::publier');
        $routes->get('demandes/notifications', 'RHController::notifications');
        $routes->get('demandes/(:num)', 'RHController::show/$1');
        $routes->get('demandes/(:num)/grille-salariale', 'RHController::grilleSalariale/$1');
        $routes->post('demandes/(:num)/proposition-salaire', 'RHController::proposerSalaire/$1');
        $routes->post('demandes/(:num)/refuser', 'RHController::refuser/$1');
        $routes->get('candidatures', 'CandidatureController::index');
        $routes->post('candidatures/(:num)/entretiens', 'CandidatureController::creerEntretien/$1', ['filter' => 'auth:Manager,RH']);
        $routes->get('candidatures/(:num)/contrat', 'CandidatureController::ficheContrat/$1');
        $routes->post('candidatures/(:num)/contrat', 'CandidatureController::genererContrat/$1');

        $routes->group('entretiens', ['filter' => 'auth:Manager,RH'], static function ($routes) {
            $routes->get('offres', 'EntretienController::offres');
            $routes->get('offres/(:num)/candidats', 'EntretienController::candidats/$1');
            $routes->get('candidatures/(:num)', 'EntretienController::detail/$1');
            $routes->put('candidatures/(:num)/evaluation', 'EntretienController::enregistrerEvaluation/$1');
        });
});
