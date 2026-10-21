<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// $routes->get('/', 'Home::index');
// $routes->get('/listeEtudiants', 'Etudiant::getAll');
// $routes->get('/etudiant/(:num)', 'Etudiant::getById/$1');
// $routes->get('/retour', 'Etudiant::getAll');
// $routes->get('/retourHome', 'Home::index');

$routes->get('/', 'Formation::index');

$routes->get('/api/formations', 'Formation::getAll');

$routes->post('/api/formations', 'Formation::create');

$routes->get('/form', 'Formation::formationForm');