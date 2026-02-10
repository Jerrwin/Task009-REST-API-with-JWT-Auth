<?php

require_once __DIR__ . '/../helpers/Response.php';

class JsonMiddleware
{

    public static function handle()
    {
        // 1. Force all responses to be JSON
        header('Content-Type: application/json; charset=UTF-8');

        $method = $_SERVER['REQUEST_METHOD'];

        // 2. Only validate input for methods that send data (POST, PUT, PATCH)
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {

            // Get Headers safely
            $headers = getallheaders();
            $contentType = isset($headers['Content-Type']) ? $headers['Content-Type'] : '';

            // 3. VALIDATION: Check if Content-Type is 'application/json'
            if (stripos($contentType, 'application/json') === false) {
                Response::send(false, "Error: Content-Type must be application/json", [], 415);
                exit;
            }

            // 4. Read the raw JSON input
            $input = file_get_contents("php://input");

            // 5. VALIDATION: Check if body is empty
            if (empty($input)) {
                Response::send(false, "Error: Request body is empty", [], 400);
                exit;
            }

            // 6. Decode JSON into an associative array
            $data = json_decode($input, true);

            // 7. VALIDATION: Check for JSON syntax errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::send(false, "Error: Invalid JSON Format - " . json_last_error_msg(), [], 400);
                exit;
            }

            // 8. Success: Attach data to global $_POST for Controllers to use
            $_POST = $data;
            $_REQUEST = array_merge($_REQUEST, $data);
        }
    }
}
?>