<?php

// 1. Load Configuration (Errors + .env)
require_once __DIR__ . '/../config/config.php';

// 2. Load Core Files
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/middleware/JsonMiddleware.php';

// 3. Run App
JsonMiddleware::handle();
$router = new Router();

// --- Define Routes Here ---
// Authentication Routes
$router->post('/api/register', 'AuthController', 'register');
$router->post('/api/login', 'AuthController', 'login');

// The Refresh Route
$router->post('/api/refresh', 'AuthController', 'refresh');

// Check Login Status
$router->get('/api/user/status', 'AuthController', 'status');

// Patient Routes
$router->get('/api/patients', 'PatientController', 'index');
$router->post('/api/patients', 'PatientController', 'store');
$router->put('/api/patients/{id}', 'PatientController', 'update');
$router->delete('/api/patients/{id}', 'PatientController', 'destroy');

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
?>