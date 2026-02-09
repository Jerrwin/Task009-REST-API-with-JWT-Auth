<?php

// 1. Error Reporting
// Turn ON for development, Turn OFF for production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Timezone
// Set this to your local timezone to ensure 'created_at' timestamps are correct
date_default_timezone_set('Asia/Kolkata'); // Change to your timezone

// 3. App Constants
// Useful for file uploads or base URLs
define('APP_ROOT', dirname(__DIR__));
define('URL_ROOT', 'http://localhost/Task009');
define('SITE_NAME', 'Hospital Management System');

?>