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
    $routes->get('health', 'Health::index');
    $routes->get('departements', 'Departement::index');
    $routes->get('offres', 'Offre::index');

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
});
