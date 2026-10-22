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
    $routes->get('demandes/notifications', 'RHController::notifications');
    $routes->get('demandes/(:num)', 'RHController::show/$1');
    $routes->get('demandes/(:num)/grille-salariale', 'RHController::grilleSalariale/$1');
    $routes->post('demandes/(:num)/proposition-salaire', 'RHController::proposerSalaire/$1');
    $routes->post('demandes/(:num)/refuser', 'RHController::refuser/$1');
});
