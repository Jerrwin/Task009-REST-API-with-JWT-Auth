<?php

// 1. LOAD ENVIRONMENT VARIABLES FIRST (Best Practice)
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
            $_SERVER[trim($name)] = trim($value);
        }
    }
} else {
    die("❌ Error: .env file not found at: " . realpath(__DIR__ . '/../'));
}

// 2. Load Core Files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Router.php';
require_once __DIR__ . '/../app/middleware/JsonMiddleware.php';

// 3. Global Middleware
JsonMiddleware::handle();

// 4. Initialize Router
$router = new Router();

// --- DEFINE ROUTES ---
$router->post('/api/register', 'AuthController', 'register');
$router->post('/api/login',    'AuthController', 'login');
$router->get('/api/patients',       'PatientController', 'index');
$router->post('/api/patients',      'PatientController', 'store');
$router->put('/api/patients/{id}',  'PatientController', 'update');
$router->delete('/api/patients/{id}', 'PatientController', 'destroy');

// 5. Run Application
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

?>