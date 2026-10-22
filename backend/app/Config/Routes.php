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
    $routes->get('offres/(:num)', 'Offre::detail/$1');
    $routes->get('offres/(:num)/candidats-ideaux', 'CandidatIdeal::byOffre/$1');
    $routes->get('demandes/(:num)/candidats-ideaux', 'CandidatIdeal::byDemande/$1');
    $routes->get('candidats/(:num)/cv', 'CandidatIdeal::cv/$1');
    $routes->post('candidatures/(:num)/proposition', 'CandidatIdeal::createProposition/$1');
});