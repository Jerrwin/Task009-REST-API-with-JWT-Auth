<?php

require_once __DIR__ . '/../helpers/Response.php';

class JsonMiddleware
{

    public static function handle()
    {
        // 1. Enforce JSON Response Header Globally
        header('Content-Type: application/json; charset=UTF-8');

        $method = $_SERVER['REQUEST_METHOD'];

        // 2. Only validate input for methods that SEND data (POST, PUT, PATCH)
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {

            // A. Check Content-Type Header
            // We use standard 'apache_request_headers' or $_SERVER fallback
            $headers = getallheaders();
            $contentType = isset($headers['Content-Type']) ? $headers['Content-Type'] : '';

            // Handle case-sensitivity issues or extra charset info (e.g., "application/json; charset=utf-8")
            if (strpos($contentType, 'application/json') === false) {
                Response::send(false, "Invalid Content-Type. Please use application/json", [], 415); // 415 Unsupported Media Type
                exit; // Stop execution
            }

            // B. Read Raw Input
            $input = file_get_contents("php://input");

            // C. Check if Body is Empty (Optional but good practice)
            if (empty($input)) {
                Response::send(false, "Request body is empty", [], 400);
                exit;
            }

            // D. Decode JSON
            $data = json_decode($input, true); // true = associative array

            // E. Check for JSON Errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::send(false, "Invalid JSON Format: " . json_last_error_msg(), [], 400);
                exit;
            }

            // F. Attach decoded data to a global variable for Controllers to use
            // This means controllers can just use $_POST (or a custom wrapper) instead of decoding again
            $_POST = $data;
        }
    }
}
?>